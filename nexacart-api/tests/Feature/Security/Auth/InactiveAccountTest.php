<?php

namespace Tests\Feature\Security\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use Laravel\Sanctum\Sanctum;
use App\Enums\UserStatus;
class InactiveAccountTest extends TestCase
{
    /**
     * A basic feature test example.
     */
   public function test_inactive_user_cannot_access_profile(): void
{
    $user = User::factory()->create([
        'status' => UserStatus::Locked,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')
        ->assertForbidden();
}

}
