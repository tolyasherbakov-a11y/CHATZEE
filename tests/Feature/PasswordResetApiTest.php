<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_and_reset_password_flow(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        // Register user
        $resp = $this->postJson('/api/v1/auth/register', [
            'name' => 'Paul',
            'email' => 'paul@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        // Request reset link
        Notification::fake();
        $this->postJson('/api/v1/password/forgot', ['email' => 'paul@example.com'])
            ->assertOk();
        // Extract token from notification
        $token = null;
        Notification::assertSentTo(
            \App\Models\User::where('email','paul@example.com')->first(),
            ResetPassword::class,
            function ($n) use (&$token) { $token = $n->token; return true; }
        );
        $this->assertNotEmpty($token);

        // Reset password
        $this->postJson('/api/v1/password/reset', [
            'email' => 'paul@example.com',
            'token' => $token,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk();

        // Login with new password should succeed
        $this->postJson('/api/v1/auth/login', [
            'email' => 'paul@example.com',
            'password' => 'newpass123',
        ])->assertOk();
    }
}
