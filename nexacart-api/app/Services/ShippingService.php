<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\SellerPickupAddress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ShippingService
{
    public function __construct(
        private readonly GhnService $ghnService,
        private readonly ShippingPackageBuilder $packageBuilder,
    ) {}

    public function getOptions(
        User $customer,
        int $sellerId,
        int $addressId,
        array $cartItemIds,
    ): array {
        $context = $this->resolveShippingContext(
            customer: $customer,
            sellerId: $sellerId,
            addressId: $addressId,
            cartItemIds: $cartItemIds,
        );

        $pickupAddress = $context['pickup_address'];
        $deliveryAddress = $context['delivery_address'];

        $services = $this->ghnService
            ->getAvailableServices(
                shopId: (int) $pickupAddress->ghn_shop_id,
                fromDistrictId: (int) $pickupAddress->district_id,
                toDistrictId: (int) $deliveryAddress->district_id,
            );

        if (empty($services)) {
            throw ValidationException::withMessages([
                'shipping' => [
                    'GHN hiện không có phương thức vận chuyển phù hợp cho tuyến này.',
                ],
            ]);
        }

        $options = [];

        foreach ($services as $service) {
            $serviceId =
                (int) (
                    $service['service_id'] ?? 0
                );

            $serviceTypeId =
                (int) (
                    $service['service_type_id'] ?? 0
                );

            if ($serviceTypeId !== 2) {
                continue;
            }

            if ($serviceId <= 0) {
                continue;
            }

            $fee =
                $this->calculateFee(
                    pickupAddress: $pickupAddress,
                    deliveryAddress: $deliveryAddress,
                    package: $context['package'],
                    serviceId: $serviceId,
                );

            $options[] = [
                'provider' =>'ghn',
                'service_id' => $serviceId,
                'service_type_id' => $serviceTypeId,
                'service_name' => $service['short_name']
                 ?? 'GHN',
                'shipping_fee' => $this
                    ->extractShippingFee(
                        $fee,
                    ),
            ];
        }

        if (empty($options)) {
            throw ValidationException::withMessages([
                'shipping' => [
                    'Không thể tính phí vận chuyển cho đơn hàng này.',
                ],
            ]);
        }

        return $options;
    }

    // xác minh thông tin gh - gọi ghn - tính phí 
    public function quoteSelectedService(
        User $customer,
        int $sellerId,
        int $addressId,
        array $cartItemIds,
        int $serviceId,
    ): array {
        $context = $this->resolveShippingContext(
            customer: $customer,
            sellerId: $sellerId,
            addressId: $addressId,
            cartItemIds: $cartItemIds,
        );

        $pickupAddress = $context['pickup_address'];
        $deliveryAddress = $context['delivery_address'];
        $package = $context['package'];

        $services = $this->ghnService
            ->getAvailableServices(
                shopId: (int) $pickupAddress->ghn_shop_id,
                fromDistrictId: (int) $pickupAddress->district_id,
                toDistrictId: (int) $deliveryAddress->district_id,
            );

        $selectedService =
            collect($services)
            ->first(
                function (array $service) use ($serviceId)
                 {
                    return (
                        (int) (
                            $service['service_id'] ?? 0
                        ) === $serviceId &&  (int) (
                            $service['service_type_id'] ?? 0
                        ) === 2
                    );
                },
            );

        if (!$selectedService) {
            throw ValidationException::withMessages([
                'shipping_service_id' => [
                    'Phương thức vận chuyển không còn khả dụng.',
                ],
            ]);
        }

        $fee = $this->calculateFee(
            pickupAddress: $pickupAddress,
            deliveryAddress: $deliveryAddress,
            package: $package,
            serviceId: $serviceId,
        );

        return [
            'provider' => 'ghn',
            'service_id' => $serviceId,

            'service_type_id' =>
            isset($selectedService['service_type_id'])
                ? (int) $selectedService['service_type_id']
                : null,

            'service_name' =>
            $selectedService['short_name'] ?? 'GHN',

            'shipping_fee' =>
            $this->extractShippingFee($fee),

            /*
             * Snapshot dùng để CheckoutService kiểm tra lại
             * sau khi lock DB.
             */
            'package' => [
                'weight' =>
                (int) $package['weight'],
                'length' =>
                (int) $package['length'],
                'width' =>
                (int) $package['width'],
                'height' =>
                (int) $package['height'],
            ],

            'delivery' => [
                'address_id' =>
                (int) $deliveryAddress->id,
                'district_id' =>
                (int) $deliveryAddress->district_id,
                'ward_code' =>
                (string) $deliveryAddress->ward_code,
            ],

            'pickup' => [
                'address_id' =>
                (int) $pickupAddress->id,
                'shop_id' =>
                (int) $pickupAddress->ghn_shop_id,
                'district_id' =>
                (int) $pickupAddress->district_id,
                'ward_code' =>
                (string) $pickupAddress->ward_code,
            ],
        ];
    }

    private function resolveShippingContext(
        User $customer,
        int $sellerId,
        int $addressId,
        array $cartItemIds,
    ): array {
        
        $deliveryAddress = $customer
            ->addresses()
            ->whereKey($addressId)
            ->first();

        if (!$deliveryAddress) {
            throw ValidationException::withMessages([
                'address_id' => [
                    'Địa chỉ nhận hàng không hợp lệ.',
                ],
            ]);
        }

        if (
            !$deliveryAddress->district_id
            || !$deliveryAddress->ward_code
        ) {
            throw ValidationException::withMessages([
                'address_id' => [
                    'Địa chỉ nhận hàng chưa có đầy đủ mã địa chỉ GHN.',
                ],
            ]);
        }

        $pickupAddress = SellerPickupAddress::query()
            ->where('seller_id', $sellerId)
            ->where('is_default', true)
            ->first();

        if (!$pickupAddress) {
            throw ValidationException::withMessages([
                'seller_id' => [
                    'Người bán chưa thiết lập địa chỉ lấy hàng.',
                ],
            ]);
        }

        if (!$pickupAddress->ghn_shop_id) {
            throw ValidationException::withMessages([
                'seller_id' => [
                    'Địa chỉ lấy hàng của người bán chưa được đồng bộ GHN.',
                ],
            ]);
        }

        if (
            !$pickupAddress->district_id
            || !$pickupAddress->ward_code
        ) {
            throw ValidationException::withMessages([
                'seller_id' => [
                    'Địa chỉ lấy hàng chưa có đầy đủ mã địa chỉ GHN.',
                ],
            ]);
        }

        $cart = Cart::query()
            ->where('user_id', $customer->id)
            ->first();

        if (!$cart) {
            throw ValidationException::withMessages([
                'cart_item_ids' => [
                    'Giỏ hàng không tồn tại.',
                ],
            ]);
        }

        $requestedIds = $this->normalizeCartItemIds(
            $cartItemIds,
        );

        if ($requestedIds->isEmpty()) {
            throw ValidationException::withMessages([
                'cart_item_ids' => [
                    'Không có sản phẩm để tính phí vận chuyển.',
                ],
            ]);
        }

    
        $cartItems = $cart->items()
            ->with('product')
            ->whereIn('id', $requestedIds)
            ->whereHas(
                'product',
                function ($query) use ($sellerId) {
                    $query->where(
                        'seller_id',
                        $sellerId,
                    );
                },
            )
            ->get();

        if (
            $cartItems->count()
            !== $requestedIds->count()
        ) {
            throw ValidationException::withMessages([
                'cart_item_ids' => [
                    'Một hoặc nhiều sản phẩm không hợp lệ, '
                        . 'không thuộc giỏ hàng hoặc không thuộc người bán đã chọn.',
                ],
            ]);
        }

        $package = $this->packageBuilder->build(
            $cartItems,
        );

        return [
            'delivery_address' => $deliveryAddress,
            'pickup_address' => $pickupAddress,
            'cart_items' => $cartItems,
            'package' => $package,
        ];
    }

    private function calculateFee(
        SellerPickupAddress $pickupAddress,
        $deliveryAddress,
        array $package,
        int $serviceId,
    ): array {
        return $this->ghnService->calculateFee(
            shopId: (int) $pickupAddress->ghn_shop_id,
            fromDistrictId: (int) $pickupAddress->district_id,
            fromWardCode: (string) $pickupAddress->ward_code,
            toDistrictId: (int) $deliveryAddress->district_id,
            toWardCode: (string) $deliveryAddress->ward_code,
            serviceId: $serviceId,
            weight: (int) $package['weight'],
            length: (int) $package['length'],
            width: (int) $package['width'],
            height: (int) $package['height'],
        );
    }

    private function extractShippingFee(array $fee): int
    {
        if (
            !array_key_exists('total', $fee)
            || !is_numeric($fee['total'])
        ) {
            throw ValidationException::withMessages([
                'shipping' => [
                    'GHN không trả về phí vận chuyển hợp lệ.',
                ],
            ]);
        }

        $shippingFee = (int) $fee['total'];

        if ($shippingFee < 0) {
            throw ValidationException::withMessages([
                'shipping' => [
                    'Phí vận chuyển GHN trả về không hợp lệ.',
                ],
            ]);
        }

        return $shippingFee;
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
}
