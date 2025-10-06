<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class ConversationsSelfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function register(string $name, string $email): array
    {
        $r = $this->postJson('/api/v1/auth/register', [
            'name' => $name,
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        return ['id' => (string) $r->json('id'), 'token' => (string) $r->json('token')];
    }

    public function test_cannot_start_with_self(): void
    {
        $a = $this->register('Alice', 'alice@example.com');

        $this->withToken($a['token'])->postJson('/api/v1/conversations/start', [
            'user_id' => $a['id'],
        ])->assertStatus(422);
    }
}
