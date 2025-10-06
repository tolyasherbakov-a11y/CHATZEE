<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_auth_flow(): void
    {
        // ensure roles/permissions exist in the fresh testing DB
        $this->seed(RolesAndPermissionsSeeder::class);

        // Register
        $resp = $this->postJson("/api/v1/auth/register", [
            "name" => "Alice",
            "email" => "alice@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
        ])->assertCreated();

        $token = $resp->json("token");
        $this->assertIsString($token);

        // Login
        $resp2 = $this->postJson("/api/v1/auth/login", [
            "email" => "alice@example.com",
            "password" => "password123",
        ])->assertOk();

        $token2 = $resp2->json("token");
        $this->assertIsString($token2);

        // Me
        $me = $this->withHeader("Authorization", "Bearer ".$token2)
            ->getJson("/api/v1/auth/me")
            ->assertOk();

        $this->assertSame("alice@example.com", $me->json("email"));

        // Logout
        $this->withHeader("Authorization", "Bearer ".$token2)
            ->postJson("/api/v1/auth/logout")
            ->assertNoContent();
    }
}