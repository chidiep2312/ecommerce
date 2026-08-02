<?php

namespace Tests\Feature\Security\Orders;

use Illuminate\Foundation\Testing\WithFaker;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Enums\UserStatus;
class OrderAuthorizationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
  
    public function test_customer_cannot_view_another_customers_order(): void
    {
        // Arrange: khách hàng đang đăng nhập
        $customerA = User::factory()->create([
            'role' => UserRole::Customer,
            'status' => UserStatus::Active,
        ]);

        // Arrange: chủ sở hữu đơn hàng
        $customerB = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        // Arrange: người bán của đơn hàng
        $seller = User::factory()->create([
            'role' => UserRole::Seller,
            'status' => UserStatus::Active,
        ]);

        $order = Order::factory()->create([
            'user_id' => $customerB->id,
           
        ]);

        Sanctum::actingAs($customerA);

        // Act
        $response = $this->getJson(
            "/api/v1/customer/orders/{$order->id}"
        );

        // Assert
        $response->assertForbidden();
    }

    public function test_customer_can_view_their_own_order(): void
{
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
        'status' => UserStatus::Active,
    ]);

    $order = Order::factory()->create([
        'user_id' => $customer->id,
    ]);

    Sanctum::actingAs($customer);

    $response = $this->getJson(
        "/api/v1/customer/orders/{$order->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $order->id);
}
public function test_seller_can_view_other_seller_order(): void
{
    $sellerA = User::factory()->create([
        'role' => UserRole::Seller,
        'status' => UserStatus::Active,
    ]);

    $order = Order::factory()->create([
        'user_id' =>  $sellerA->id,
    ]);


    $sellerB = User::factory()->create([
        'role' => UserRole::Seller,
        'status' => UserStatus::Active,
    ]);

    Sanctum::actingAs( $sellerB);
    $response = $this->getJson(
        "/api/v1/seller/orders/{$order->id}"
    );

    $response->assertForbidden();
}


}
