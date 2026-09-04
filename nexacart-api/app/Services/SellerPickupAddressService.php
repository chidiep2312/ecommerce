<?php

namespace App\Services;


use App\Models\SellerPickupAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SellerPickupAddressService
{
    public function __construct(
        private GhnService $ghnService,
    ) {}
    public function create(
        User $seller,
        array $data,
    ): SellerPickupAddress {
        return DB::transaction(
            function () use (
                $seller,
                $data,
            ) {
                $hasAddress =
                    $seller
                    ->pickupAddresses()
                    ->exists();

                if (!$hasAddress) {
                    $data['is_default'] =
                        true;
                }

                if (
                    $data['is_default']
                    ?? false
                ) {
                    $seller
                        ->pickupAddresses()
                        ->update([
                            'is_default' =>
                            false,
                        ]);
                }

           
                $address =
                    $seller
                    ->pickupAddresses()
                    ->create($data);

            
                $shopId =
                    $this
                    ->ghnService
                    ->createStore(
                        name: $seller->name,

                        phone: $address->phone,

                        address: $address
                            ->address_line,

                        districtId: $address
                            ->district_id,

                        wardCode: $address
                            ->ward_code,
                    );

                /*
             * Bước 3:
             * mapping GHN Shop.
             */
                $address->update([
                    'ghn_shop_id' =>
                    $shopId,

                    'ghn_synced_at' =>
                    now(),
                ]);

                return $address->fresh();
            },
        );
    }

    public function syncWithGhn(
        SellerPickupAddress $address,
    ): SellerPickupAddress {
        /*
         * Đã sync rồi thì không tạo
         * GHN Store lần nữa.
         */
        if ($address->ghn_shop_id) {
            return $address;
        }

        $address->update([
            'ghn_sync_status' =>
            'pending',
        ]);

        try {
            $shopId =
                $this
                ->ghnService
                ->createStore(
                    name: $address
                        ->contact_name,

                    phone: $address
                        ->phone,

                    address: $address
                        ->full_address,

                    districtId: $address
                        ->district_id,

                    wardCode: $address
                        ->ward_code,
                );

            $address->update([
                'ghn_shop_id' =>
                $shopId,

                'ghn_sync_status' =>
                'synced',

                'ghn_synced_at' =>
                now(),
            ]);
        } catch (Throwable $exception) {
            /*
             * Address NexaCart vẫn tồn tại.
             * Chỉ GHN sync thất bại.
             */
            $address->update([
                'ghn_sync_status' =>
                'failed',
            ]);

            Log::error(
                'GHN create store failed.',
                [
                    'pickup_address_id' =>
                    $address->id,

                    'seller_id' =>
                    $address
                        ->seller_id,

                    'message' =>
                    $exception
                        ->getMessage(),
                ],
            );
        }

        return $address->fresh();
    }
    public function setDefault(
        User $seller,
        SellerPickupAddress $address,
    ): SellerPickupAddress {
        /*
         * Ownership check.
         */
        abort_unless(
            $address->seller_id ===
                $seller->id,
            403,
            'Bạn không có quyền sửa địa chỉ này.',
        );

        DB::transaction(
            function () use (
                $seller,
                $address,
            ) {
                $seller
                    ->pickupAddresses()
                    ->update([
                        'is_default' =>
                        false,
                    ]);

                $address->update([
                    'is_default' =>
                    true,
                ]);
            },
        );

        return $address->fresh();
    }
}