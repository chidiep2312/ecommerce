<?php

namespace Tests\Feature\Security\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_login_is_rate_limited(): void
{
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/api/v1/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->postJson('/api/v1/login', [
        'email' => 'unknown@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
}
}
