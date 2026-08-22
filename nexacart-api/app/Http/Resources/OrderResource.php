<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /*
             * ==============================
             * ORDER
             * ==============================
             */
            'id' =>
                $this->id,

            'order_code' =>
                $this->order_code,

            'status' =>
                $this->status?->value,

            'payment_method' =>
                $this->payment_method?->value
                    ?? $this->payment_method,

            /*
             * ==============================
             * MONEY
             * ==============================
             */
            'subtotal' =>
                (float) $this->subtotal,

            'discount_amount' =>
                (float) $this->discount_amount,

            'shipping_fee' =>
                (float) $this->shipping_fee,

            'total' =>
                (float) $this->total,

            /*
             * ==============================
             * SHIPPING
             * ==============================
             */
            'shipping' => [
                /*
                 * Đơn vị vận chuyển.
                 */
                'provider' =>
                    $this->shipping_provider,

                /*
                 * Service GHN Customer đã chọn.
                 */
                'service' => [
                    'id' =>
                        $this->shipping_service_id,

                    'type_id' =>
                        $this->shipping_service_type_id,

                    'name' =>
                        $this->shipping_service_name,
                ],

                /*
                 * Người nhận hàng.
                 *
                 * Đây là snapshot được lấy từ
                 * Customer Address khi checkout.
                 */
                'recipient' => [
                    'name' =>
                        $this->shipping_name,

                    'phone' =>
                        $this->shipping_phone,

                    'address' =>
                        $this->shipping_address,

                    /*
                     * Mã GHN dùng khi tạo vận đơn.
                     */
                    'district_id' =>
                        $this->shipping_district_id,

                    'ward_code' =>
                        $this->shipping_ward_code,
                ],

                /*
                 * Địa chỉ Seller giao hàng cho GHN lấy.
                 */
                'pickup' => [
                    'address_id' =>
                        $this->pickup_address_id,

                    'ghn_shop_id' =>
                        $this->pickup_ghn_shop_id,

                    'name' =>
                        $this->pickup_name,

                    'phone' =>
                        $this->pickup_phone,

                    'address' =>
                        $this->pickup_address,

                    'district_id' =>
                        $this->pickup_district_id,

                    'ward_code' =>
                        $this->pickup_ward_code,
                ],

                /*
                 * Snapshot kiện hàng.
                 *
                 * weight: gram
                 * length/width/height: cm
                 */
                'package' => [
                    'weight' =>
                        $this->package_weight,

                    'length' =>
                        $this->package_length,

                    'width' =>
                        $this->package_width,

                    'height' =>
                        $this->package_height,
                ],
            ],

            /*
             * ==============================
             * NOTE
             * ==============================
             */
            'customer_note' =>
                $this->customer_note,

            /*
             * ==============================
             * RELATIONSHIPS
             * ==============================
             */
            'customer' =>
                new UserResource(
                    $this->whenLoaded(
                        'customer',
                    ),
                ),

            'seller' =>
                new UserResource(
                    $this->whenLoaded(
                        'seller',
                    ),
                ),

            'voucher' =>
                new VoucherResource(
                    $this->whenLoaded(
                        'voucher',
                    ),
                ),

            'items' =>
                OrderItemResource::collection(
                    $this->whenLoaded(
                        'items',
                    ),
                ),

            'items_count' =>
                $this->whenCounted(
                    'items',
                ),

            /*
             * ==============================
             * ORDER TIMELINE
             * ==============================
             */
            'confirmed_at' =>
                $this->confirmed_at
                    ?->toISOString(),

            'shipping_at' =>
                $this->shipping_at
                    ?->toISOString(),

            'completed_at' =>
                $this->completed_at
                    ?->toISOString(),

            'cancelled_at' =>
                $this->cancelled_at
                    ?->toISOString(),

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}