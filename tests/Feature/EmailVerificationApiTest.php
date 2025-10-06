<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class EmailVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_and_verify_email(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        // Register user
        $resp = $this->postJson('/api/v1/auth/register', [
            'name' => 'Veronica',
            'email' => 'vero@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $token = $resp->json('token');

        // Fake notifications and request sending
        Notification::fake();
        $this->withToken($token)->postJson('/api/v1/email/verification-notification')
            ->assertCreated();

        Notification::assertSentTo(
            auth()->user() ?? \App\Models\User::where('email','vero@example.com')->first(),
            VerifyEmail::class
        );

        // Build signed URL and hit verify endpoint
        $user = \App\Models\User::where('email','vero@example.com')->first();
        $url = URL::temporarySignedRoute(
            'verification.verify.api', Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->getJson($url)->assertOk();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
