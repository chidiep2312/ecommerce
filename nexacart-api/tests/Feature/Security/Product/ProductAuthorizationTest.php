<?php

namespace Tests\Feature\Security\Product;

use Illuminate\Foundation\Testing\WithFaker;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Enums\UserStatus;
class ProductAuthorizationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
  
public function test_seller_can_update_other_seller_product(): void
{
    $sellerA = User::factory()->create([
        'role' => UserRole::Seller,
        'status' => UserStatus::Active,
    ]);

    $product = Product::factory()->create([
        'seller_id' =>  $sellerA->id,
    ]);


    $sellerB = User::factory()->create([
        'role' => UserRole::Seller,
        'status' => UserStatus::Active,
    ]);

    Sanctum::actingAs( $sellerB);
    $response = $this->patchJson(
        "/api/v1/seller/products/{$product->id}"
    );

    $response->assertForbidden();
}


public function test_seller_can_view_other_seller_product(): void
{
    $sellerA = User::factory()->create([
        'role' => UserRole::Seller,
        'status' => UserStatus::Active,
    ]);

    $product = Product::factory()->create([
        'seller_id' =>  $sellerA->id,
    ]);


    $sellerB = User::factory()->create([
        'role' => UserRole::Seller,
        'status' => UserStatus::Active,
    ]);

    Sanctum::actingAs( $sellerB);
    $response = $this->getJson(
        "/api/v1/seller/products/{$product->id}"
    );

    $response->assertForbidden();
}
}