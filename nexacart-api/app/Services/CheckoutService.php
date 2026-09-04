<?php

namespace App\Services;
//use App\Services\ShippingService;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidVoucherException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerPickupAddress;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly VoucherService $voucherService,
        private readonly ShippingService $shippingService,
        private readonly ShippingPackageBuilder $packageBuilder,
    ) {}

    public function checkout(User $user, array $data): Order
    {
        $existingOrder = $this->findExistingOrder(
            $user,
            $data['idempotency_key'],
        );

        if ($existingOrder) {
            return $this->loadOrder($existingOrder);
        }

        $shippingQuote = $this->shippingService->quoteSelectedService(
            customer: $user,
            sellerId: (int) $data['seller_id'],
            addressId: (int) $data['address_id'],
            cartItemIds: $data['cart_item_ids'],
            serviceId: (int) $data['shipping_service_id'],
        );

        if (
            ($data['shipping_provider'] ?? null)
            !== $shippingQuote['provider']
        ) {
            throw ValidationException::withMessages([
                'shipping_provider' => [
                    'Đơn vị vận chuyển không hợp lệ.',
                ],
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $data,
            $shippingQuote,
        ) {

            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();


            $existingOrder = $this->findExistingOrder(
                $user,
                $data['idempotency_key'],
            );

            if ($existingOrder) {
                return $this->loadOrder($existingOrder);
            }

            if (!$cart) {
                throw new EmptyCartException(
                    'Giỏ hàng đang trống.',
                );
            }

            $deliveryAddress = $user
                ->addresses()
                ->whereKey($data['address_id'])
                ->lockForUpdate()
                ->first();

            if (!$deliveryAddress) {
                throw new ProductUnavailableException(
                    'Địa chỉ nhận hàng không hợp lệ.',
                );
            }

            $this->assertDeliveryAddressUnchanged(
                $deliveryAddress,
                $shippingQuote,
            );

            $requestedCartItemIds = $this->normalizeCartItemIds(
                $data['cart_item_ids'],
            );

            if ($requestedCartItemIds->isEmpty()) {
                throw new EmptyCartException(
                    'Không có sản phẩm để thanh toán.',
                );
            }


            $cartItems = $cart->items()
                ->whereIn('id', $requestedCartItemIds)
                ->whereHas(
                    'product',
                    function ($query) use ($data) {
                        $query->where(
                            'seller_id',
                            (int) $data['seller_id'],
                        );
                    },
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if (
                $cartItems->count()
                !== $requestedCartItemIds->count()
            ) {
                throw new ProductUnavailableException(
                    'Một hoặc nhiều sản phẩm được chọn không hợp lệ.',
                );
            }

            if ($cartItems->isEmpty()) {
                throw new EmptyCartException(
                    'Giỏ hàng không có sản phẩm của người bán này.',
                );
            }

            $productIds = $cartItems
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values();


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
                    'Một đơn hàng chỉ được chứa sản phẩm của một người bán.',
                );
            }

            $resolvedSellerId = (int) $resolvedSellerIds->first();

            if ($resolvedSellerId !== (int) $data['seller_id']) {
                throw new ProductUnavailableException(
                    'Người bán không khớp với sản phẩm trong giỏ hàng.',
                );
            }

            $seller = User::query()
                ->whereKey($resolvedSellerId)
                ->lockForUpdate()
                ->first();

            if (
                !$seller
                || $seller->role !== UserRole::Seller
                || $seller->status !== UserStatus::Active
            ) {
                throw new ProductUnavailableException(
                    'Người bán hiện không còn hoạt động.',
                );
            }

            $pickupAddress = SellerPickupAddress::query()
                ->whereKey(
                    $shippingQuote['pickup']['address_id'],
                )
                ->where('seller_id', $resolvedSellerId)
                ->where('is_default', true)
                ->lockForUpdate()
                ->first();

            if (!$pickupAddress) {
                throw new ProductUnavailableException(
                    'Địa chỉ lấy hàng của người bán đã thay đổi. '
                        . 'Vui lòng tính lại phí vận chuyển.',
                );
            }

            $this->assertPickupAddressUnchanged(
                $pickupAddress,
                $shippingQuote,
            );


            foreach ($cartItems as $cartItem) {
                $product = $lockedProducts->get(
                    $cartItem->product_id,
                );

                if (!$product) {
                    throw new ProductUnavailableException(
                        'Một sản phẩm trong giỏ hàng không còn tồn tại.',
                    );
                }

                if (
                    (int) $product->seller_id
                    !== $resolvedSellerId
                ) {
                    throw new ProductUnavailableException(
                        'Sản phẩm không thuộc người bán đã chọn.',
                    );
                }

                if ($product->status !== ProductStatus::Active) {
                    throw new ProductUnavailableException(
                        "Sản phẩm {$product->name} hiện không được phép bán.",
                    );
                }

                if (
                    (int) $cartItem->quantity
                    > (int) $product->stock
                ) {
                    throw new InsufficientStockException(
                        "Sản phẩm {$product->name} chỉ còn "
                            . "{$product->stock} sản phẩm trong kho.",
                    );
                }

                $cartItem->setRelation('product', $product);
            }


            $currentPackage = $this->packageBuilder->build(
                $cartItems,
            );

            $this->assertPackageUnchanged(
                $currentPackage,
                $shippingQuote,
            );


            $subtotal = $cartItems->reduce(
                function (
                    string $subtotal,
                    $cartItem,
                ) use ($lockedProducts): string {
                    $product = $lockedProducts->get(
                        $cartItem->product_id,
                    );

                    $lineTotal = Money::multiply(
                        (string) $product->effective_price,
                        (int) $cartItem->quantity,
                    );

                    return Money::add(
                        $subtotal,
                        $lineTotal,
                    );
                },
                Money::zero(),
            );

            $voucher = null;
            $discountAmount = Money::zero();

            if (!empty($data['voucher_code'])) {
                $voucherCode = strtoupper(
                    trim($data['voucher_code']),
                );

                $voucher = Voucher::query()
                    ->where('code', $voucherCode)
                    ->lockForUpdate()
                    ->first();

                if (!$voucher) {
                    throw new InvalidVoucherException(
                        'Mã voucher không tồn tại.',
                    );
                }

                $this->voucherService->ensureVoucherIsUsable(
                    $voucher,
                    $user,
                    $subtotal,
                );

                $discountAmount = $this
                    ->voucherService
                    ->calculateDiscount(
                        $voucher,
                        $subtotal,
                    );
            }


            $shippingFee = Money::add(
                Money::zero(),
                (string) $shippingQuote['shipping_fee'],
            );

            $merchandiseAmount = Money::subtractFloorZero(
                $subtotal,
                $discountAmount,
            );

            $total = Money::add(
                $merchandiseAmount,
                $shippingFee,
            );


            $order = Order::query()->create([
                'user_id' => $user->id,
                'seller_id' => $resolvedSellerId,
                'voucher_id' => $voucher?->id,
                'order_code' => $this->generateOrderCode(),
                'idempotency_key' => $data['idempotency_key'],

                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'status' => OrderStatus::Pending,

                'payment_method' => $data['payment_method'],

                'shipping_provider' =>
                $shippingQuote['provider'],

                'shipping_service_id' =>
                $shippingQuote['service_id'],

                'shipping_service_type_id' =>
                $shippingQuote['service_type_id'],

                'shipping_service_name' =>
                $shippingQuote['service_name'],

                'shipping_name' =>
                $deliveryAddress->recipient_name,

                'shipping_phone' =>
                $deliveryAddress->phone,

                'shipping_address' =>
                $this->buildShippingAddress(
                    $deliveryAddress,
                ),

                'shipping_district_id' =>
                (int) $deliveryAddress->district_id,

                'shipping_ward_code' =>
                (string) $deliveryAddress->ward_code,

                'pickup_address_id' =>
                $pickupAddress->id,

                'pickup_ghn_shop_id' =>
                (int) $pickupAddress->ghn_shop_id,

                'pickup_name' =>
                $pickupAddress->contact_name,

                'pickup_phone' =>
                $pickupAddress->phone,

                'pickup_address' =>
                $pickupAddress->full_address,

                'pickup_district_id' =>
                (int) $pickupAddress->district_id,

                'pickup_ward_code' =>
                (string) $pickupAddress->ward_code,

                'package_weight' =>
                (int) $currentPackage['weight'],

                'package_length' =>
                (int) $currentPackage['length'],

                'package_width' =>
                (int) $currentPackage['width'],

                'package_height' =>
                (int) $currentPackage['height'],

                'customer_note' =>
                $data['customer_note'] ?? null,
            ]);

            foreach ($cartItems as $cartItem) {
                $product = $lockedProducts->get(
                    $cartItem->product_id,
                );

                $unitPrice = (string) $product->effective_price;

                $lineTotal = Money::multiply(
                    $unitPrice,
                    (int) $cartItem->quantity,
                );

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
                    (int) $cartItem->quantity,
                );
            }

            if ($voucher) {
                $voucher->increment('used_count');
                $voucher->usages()->create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                ]);
            }

            $cart->items()->whereIn('id', $cartItems->pluck('id'),)->delete();

            return $this->loadOrder($order);
        }, 3);
    }

    private function assertDeliveryAddressUnchanged(
        $deliveryAddress,
        array $shippingQuote,
    ): void {
        $snapshot = $shippingQuote['delivery'];

        if (
            (int) $deliveryAddress->id
            !== (int) ($snapshot['address_id'] ?? 0)
            ||
            (int) $deliveryAddress->district_id
            !== (int) ($snapshot['district_id'] ?? 0)
            ||
            (string) $deliveryAddress->ward_code
            !== (string) ($snapshot['ward_code'] ?? '')
        ) {
            throw new ProductUnavailableException(
                'Địa chỉ nhận hàng đã thay đổi. '
                    . 'Vui lòng tính lại phí vận chuyển.',
            );
        }
    }

    private function assertPickupAddressUnchanged(
        SellerPickupAddress $pickupAddress,
        array $shippingQuote,
    ): void {
        $snapshot = $shippingQuote['pickup'];

        if (
            (int) $pickupAddress->id
            !== (int) ($snapshot['address_id'] ?? 0)
            ||
            (int) $pickupAddress->ghn_shop_id
            !== (int) ($snapshot['shop_id'] ?? 0)
            ||
            (int) $pickupAddress->district_id
            !== (int) ($snapshot['district_id'] ?? 0)
            ||
            (string) $pickupAddress->ward_code
            !== (string) ($snapshot['ward_code'] ?? '')
        ) {
            throw new ProductUnavailableException(
                'Địa chỉ lấy hàng của người bán đã thay đổi. '
                    . 'Vui lòng tính lại phí vận chuyển.',
            );
        }
    }

    private function assertPackageUnchanged(
        array $currentPackage,
        array $shippingQuote,
    ): void {
        $quotedPackage = $shippingQuote['package'];

        foreach (
            [
                'weight',
                'length',
                'width',
                'height',
            ] as $field
        ) {
            if (
                (int) ($currentPackage[$field] ?? 0)
                !== (int) ($quotedPackage[$field] ?? 0)
            ) {
                throw new ProductUnavailableException(
                    'Thông tin kiện hàng đã thay đổi. '
                        . 'Vui lòng tính lại phí vận chuyển.',
                );
            }
        }
    }

    private function normalizeCartItemIds(
        array $cartItemIds,
    ): Collection {
        return collect($cartItemIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->sort()
            ->values();
    }

    private function buildShippingAddress(
        $deliveryAddress,
    ): string {
        return implode(
            ', ',
            array_filter([
                $deliveryAddress->address_line,
                $deliveryAddress->ward,
                $deliveryAddress->district,
                $deliveryAddress->province,
            ]),
        );
    }

    private function findExistingOrder(
        User $user,
        string $idempotencyKey,
    ): ?Order {
        return Order::query()
            ->where('user_id', $user->id)
            ->where(
                'idempotency_key',
                $idempotencyKey,
            )
            ->first();
    }

    private function loadOrder(Order $order): Order
    {
        return $order->load([
            'items',
            'voucher',
            'customer:id,name,email',
            'seller:id,name,email',
        ]);
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