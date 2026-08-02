<?php

namespace Tests\Feature\Security\Orders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Không cho phép chuyển trực tiếp:
     * pending -> completed
     */
    public function test_seller_cannot_change_pending_order_directly_to_completed(): void
    {
        // Arrange
        $seller = User::factory()->create([
            'role' => UserRole::Seller,
            'status' => UserStatus::Active,
        ]);

        $order = Order::factory()->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::Pending->value,
            'confirmed_at' => null,
            'shipping_at' => null,
            'completed_at' => null,
        ]);

        Sanctum::actingAs($seller);

        // Act
        $response = $this->patchJson(
            "/api/v1/seller/orders/{$order->id}/status",
            [
                'status' => OrderStatus::Completed->value,
            ]
        );

        // Assert
        $response->assertConflict();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'seller_id' => $seller->id,
            'status' => OrderStatus::Pending->value,
            'completed_at' => null,
        ]);
    }

    /**
     * Không cho phép chuyển:
     * cancelled -> shipping
     */
    public function test_seller_cannot_change_cancelled_order_to_shipping(): void
    {
        // Arrange
        $seller = User::factory()->create([
            'role' => UserRole::Seller,
            'status' => UserStatus::Active,
        ]);

        $order = Order::factory()->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::Cancelled->value,
            'cancelled_at' => now(),
            'shipping_at' => null,
        ]);

        Sanctum::actingAs($seller);

        // Act
        $response = $this->patchJson(
            "/api/v1/seller/orders/{$order->id}/status",
            [
                'status' => OrderStatus::Shipping->value,
            ]
        );

        // Assert
        $response->assertConflict();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
            'shipping_at' => null,
        ]);
    }

    /**
     * Luồng hợp lệ:
     * pending -> confirmed
     */
    public function test_seller_can_change_their_pending_order_to_confirmed(): void
    {
        // Arrange
        $seller = User::factory()->create([
            'role' => UserRole::Seller,
            'status' => UserStatus::Active,
        ]);

        $order = Order::factory()->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::Pending->value,
            'confirmed_at' => null,
        ]);

        Sanctum::actingAs($seller);

        // Act
        $response = $this->patchJson(
            "/api/v1/seller/orders/{$order->id}/status",
            [
                'status' => OrderStatus::Confirmed->value,
            ]
        );

        // Assert
        $response->assertOk();

        $order->refresh();

        $this->assertSame(
            OrderStatus::Confirmed,
            $order->status
        );

        $this->assertNotNull(
            $order->confirmed_at
        );
    }

    /**
     * Customer không được gọi API cập nhật trạng thái
     * dành cho seller.
     */
    public function test_customer_cannot_change_order_status_through_seller_api(): void
    {
        // Arrange
        $customer = User::factory()->create([
            'role' => UserRole::Customer,
            'status' => UserStatus::Active,
        ]);

        $seller = User::factory()->create([
            'role' => UserRole::Seller,
            'status' => UserStatus::Active,
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'seller_id' => $seller->id,
            'status' => OrderStatus::Pending->value,
        ]);

        Sanctum::actingAs($customer);

        // Act
        $response = $this->patchJson(
            "/api/v1/seller/orders/{$order->id}/status",
            [
                'status' => OrderStatus::Confirmed->value,
            ]
        );

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Pending->value,
            'confirmed_at' => null,
        ]);
    }

    /**
     * Seller A không được thay đổi trạng thái order
     * thuộc Seller B.
     */
    public function test_seller_cannot_change_another_sellers_order_status(): void
    {
        // Arrange
        $sellerA = User::factory()->create([
            'role' => UserRole::Seller,
            'status' => UserStatus::Active,
        ]);

        $sellerB = User::factory()->create([
            'role' => UserRole::Seller,
            'status' => UserStatus::Active,
        ]);

        $order = Order::factory()->create([
            'seller_id' => $sellerA->id,
            'status' => OrderStatus::Pending->value,
        ]);

        Sanctum::actingAs($sellerB);

        // Act
        $response = $this->patchJson(
            "/api/v1/seller/orders/{$order->id}/status",
            [
                'status' => OrderStatus::Confirmed->value,
            ]
        );

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'seller_id' => $sellerA->id,
            'status' => OrderStatus::Pending->value,
            'confirmed_at' => null,
        ]);
    }
}
