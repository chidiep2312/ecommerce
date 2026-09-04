<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShipmentService
{
    public function __construct(
        private readonly GhnService $ghnService,
    ) {}

    public function createForConfirmedOrder(Order $order): Shipment
    {
        $shipment = DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::Confirmed) {
                throw new \RuntimeException(
                    'Chỉ có thể tạo vận đơn cho Order đã được xác nhận.'
                );
            }

            $existingShipment = Shipment::query()
                ->where('client_order_code', $lockedOrder->order_code)
                ->first();

            if ($existingShipment) {
                return $existingShipment;
            }

            $codAmount = $lockedOrder->payment_method === 'cod'
                ? (string) $lockedOrder->total
                : '0.00';

            return $lockedOrder->shipments()->create([
                'provider' => 'ghn',
                'client_order_code' => $lockedOrder->order_code,
                'shop_id' => $lockedOrder->pickup_ghn_shop_id,
                'service_id' => $lockedOrder->shipping_service_id,
                'status' => 'pending_creation',
                'cod_amount' => $codAmount,
            ]);
        });

        return $this->syncToGhn($shipment);
    }

    public function syncToGhn(Shipment $shipment): Shipment
    {
        if ($shipment->provider_order_code) {
            return $shipment;
        }

        $shipment->load([
            'order.items',
        ]);

        $order = $shipment->order;

        try {
            $payload = $this->buildGhnPayload($order, $shipment);

            $result = $this->ghnService->createShippingOrder(
                shopId: (int) $shipment->shop_id,
                payload: $payload,
            );

            $shipment->update([
                'provider_order_code' => $result['order_code'],
                'status' => 'ready_to_pick',
                'provider_total_fee' => isset($result['total_fee'])
                    ? (string) $result['total_fee']
                    : null,
                'expected_delivery_at' => $result['expected_delivery_time'] ?? null,
                'failure_message' => null,
                'create_response' => $result,
                'synced_at' => now(),
            ]);
        } catch (Throwable $exception) {
            /*
             * Không rollback Order Confirmed.
             *
             * GHN lỗi không có nghĩa Order
             * NexaCart phải biến mất.
             */
            $shipment->update([
                'status' => 'create_failed',
                'failure_message' => $exception->getMessage(),
            ]);

            Log::error('GHN create shipping order failed.', [
                'shipment_id' => $shipment->id,
                'order_id' => $shipment->order_id,
                'client_order_code' => $shipment->client_order_code,
                'message' => $exception->getMessage(),
            ]);
        }

        return $shipment->fresh();
    }

    private function buildGhnPayload(Order $order, Shipment $shipment): array
    {

        $paymentTypeId = 1;

        $codAmount = $order->payment_method === 'cod'
            ? (int) $order->total
            : 0;

        $items = $order->items
            ->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'code' => $item->product_sku,
                    'quantity' => (int) $item->quantity,
                    'price' => (int) $item->unit_price,
                ];
            })
            ->values()
            ->all();

        $content = $order->items
            ->pluck('product_name')
            ->take(10)
            ->implode(', ');

        return [

            'client_order_code' => $shipment->client_order_code,

            'to_name' => $order->shipping_name,
            'to_phone' => $order->shipping_phone,
            'to_address' => $order->shipping_address,
            'to_district_id' => (int) $order->shipping_district_id,
            'to_ward_code' => (string) $order->shipping_ward_code,

            'from_name' => $order->pickup_name,
            'from_phone' => $order->pickup_phone,
            'from_address' => $order->pickup_address,

            'payment_type_id' => $paymentTypeId,
            'cod_amount' => $codAmount,

            'weight' => (int) $order->package_weight,
            'length' => (int) $order->package_length,
            'width' => (int) $order->package_width,
            'height' => (int) $order->package_height,

            'service_id' => (int) $order->shipping_service_id,

            'required_note' => 'KHONGCHOXEMHANG',
            'note' => $order->customer_note ?? '',
            'content' => $content,

            'insurance_value' => 0,

            'items' => $items,
        ];
    }
}
