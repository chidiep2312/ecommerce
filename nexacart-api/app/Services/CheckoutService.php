<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidVoucherException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\UserRole;
use App\Enums\UserStatus;

class CheckoutService
{
    public function __construct(
        private readonly VoucherService $voucherService
    ) {}

    public function checkout(
        User $user,
        array $data
    ): Order {
        return DB::transaction(function () use ($user, $data) {
            /*
             * Khóa giỏ hàng để tránh hai request checkout
             * cùng một giỏ hàng tại cùng một thời điểm.
             */
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($cart === null) {
                throw new EmptyCartException(
                    'Giỏ hàng đang trống.'
                );
            }

            /*
             * Chỉ lấy CartItem thuộc Seller mà Customer
             * đang muốn checkout.
             *
             * Một lần checkout tạo một Order cho một Seller.
             */
            $cartItems = $cart->items()
                ->whereHas(
                    'product',
                    function ($query) use ($data) {
                        $query->where(
                            'seller_id',
                            $data['seller_id']
                        );
                    }
                )
                ->get();

            if ($cartItems->isEmpty()) {
                throw new EmptyCartException(
                    'Giỏ hàng không có sản phẩm của người bán này.'
                );
            }

            /*
             * Sắp xếp ID trước khi khóa để giảm nguy cơ deadlock.
             */
            $productIds = $cartItems
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values();

            /*
             * Khóa tất cả Product sẽ được checkout.
             */
            $lockedProducts = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $resolvedSellerIds = $lockedProducts
                ->pluck('seller_id')
                ->map(fn($sellerId) => (int) $sellerId)
                ->unique()
                ->values();

            if ($resolvedSellerIds->count() !== 1) {
                throw new ProductUnavailableException(
                    'Một đơn hàng chỉ được chứa sản phẩm của một người bán.'
                );
            }

            $resolvedSellerId = (int) $resolvedSellerIds->first();

            if ($resolvedSellerId !== (int) $data['seller_id']) {
                throw new ProductUnavailableException(
                    'Người bán không khớp với sản phẩm trong giỏ hàng.'
                );
            }

            $seller = User::query()
                ->whereKey($resolvedSellerId)
                ->lockForUpdate()
                ->first();

            if (
                $seller === null ||
                $seller->role !== UserRole::Seller ||
                $seller->status !== UserStatus::Active
            ) {
                throw new ProductUnavailableException(
                    'Người bán hiện không còn hoạt động.'
                );
            }
            /*
             * Kiểm tra lại Product và tồn kho.
             *
             * Không được tin kết quả đã kiểm tra khi thêm Cart,
             * vì trạng thái và stock có thể đã thay đổi.
             */
            foreach ($cartItems as $cartItem) {
                $product = $lockedProducts->get(
                    $cartItem->product_id
                );

                if ($product === null) {
                    throw new ProductUnavailableException(
                        'Một sản phẩm trong giỏ hàng không còn tồn tại.'
                    );
                }

                /*
                 * Kiểm tra Product thực sự thuộc Seller được chọn.
                 * Đây là lớp bảo vệ bổ sung.
                 */
                if (
                    $product->seller_id
                    !== (int) $data['seller_id']
                ) {
                    throw new ProductUnavailableException(
                        'Sản phẩm không thuộc người bán đã chọn.'
                    );
                }

                if (
                    $product->status
                    !== ProductStatus::Active
                ) {
                    throw new ProductUnavailableException(
                        "Sản phẩm {$product->name} hiện không được phép bán."
                    );
                }

                if ($cartItem->quantity > $product->stock) {
                    throw new InsufficientStockException(
                        "Sản phẩm {$product->name} chỉ còn "
                            . "{$product->stock} sản phẩm trong kho."
                    );
                }
            }

            /*
             * Tính tổng tiền từ giá hiện tại trong database.
             *
             * Không nhận price hoặc subtotal từ frontend.
             */
            $subtotal = $cartItems->sum(
                function ($cartItem) use ($lockedProducts) {
                    $product = $lockedProducts->get(
                        $cartItem->product_id
                    );

                    return (float) $product->effective_price
                        * $cartItem->quantity;
                }
            );

            $voucher = null;
            $discountAmount = 0;

            /*
             * Kiểm tra và khóa Voucher nếu Customer sử dụng.
             */
            if (! empty($data['voucher_code'])) {
                $voucherCode = strtoupper(
                    trim($data['voucher_code'])
                );

                $voucher = Voucher::query()
                    ->where('code', $voucherCode)
                    ->lockForUpdate()
                    ->first();

                if ($voucher === null) {
                    throw new InvalidVoucherException(
                        'Mã voucher không tồn tại.'
                    );
                }

                $this->voucherService
                    ->ensureVoucherIsUsable(
                        $voucher,
                        $user,
                        $subtotal
                    );

                $discountAmount = $this
                    ->voucherService
                    ->calculateDiscount(
                        $voucher,
                        $subtotal
                    );
            }

            $shippingFee = 0;

            $merchandiseAmount = max(
                0,
                $subtotal - $discountAmount
            );

            $total = $merchandiseAmount
                + $shippingFee;

            /*
             * Một Order thuộc đúng một Seller.
             */
            $order = Order::query()->create([
                'user_id' => $user->id,
                'seller_id' => $resolvedSellerId,
                'voucher_id' => $voucher?->id,
                'order_code' => $this->generateOrderCode(),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'status' => OrderStatus::Pending,
                'shipping_name' => $data['shipping_name'],
                'shipping_phone' => $data['shipping_phone'],
                'shipping_address' => $data['shipping_address'],
                'customer_note' => $data['customer_note'] ?? null,
            ]);

            /*
             * Tạo snapshot OrderItem và trừ tồn kho.
             */
            foreach ($cartItems as $cartItem) {
                $product = $lockedProducts->get(
                    $cartItem->product_id
                );

                $unitPrice = (float) $product->effective_price;

                $lineTotal = $unitPrice
                    * $cartItem->quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $product->decrement(
                    'stock',
                    $cartItem->quantity
                );
            }

            /*
             * Voucher chỉ được ghi nhận sau khi Order
             * đã được tạo thành công.
             */
            if ($voucher !== null) {
                $voucher->increment('used_count');

                $voucher->usages()->create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                ]);
            }

            /*
             * Chỉ xóa những CartItem vừa checkout.
             *
             * Không xóa sản phẩm thuộc các Seller khác.
             */
            $cart->items()
                ->whereIn(
                    'id',
                    $cartItems->pluck('id')
                )
                ->delete();

            return $order->load([
                'items',
                'voucher',
                'customer:id,name,email',
                'seller:id,name,email',
            ]);
        }, 3);
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'NC'
                . now()->format('YmdHis')
                . strtoupper(Str::random(5));
        } while (
            Order::query()
            ->where('order_code', $code)
            ->exists()
        );

        return $code;
    }
}
