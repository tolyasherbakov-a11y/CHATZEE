<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class BroadcastAuthTest extends TestCase
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

    public function test_channel_auth_allows_participant_and_denies_other(): void
    {
        $a = $this->register('Alice', 'alice@example.com');
        $b = $this->register('Bob',   'bob@example.com');
        $c = $this->register('Eve',   'eve@example.com');

        // Стартуем личку A<->B
        $conv = $this->withToken($a['token'])->postJson('/api/v1/conversations/start', [
            'user_id' => $b['id'],
        ])->assertCreated();
        $convId = (string) $conv->json('id');

        // Laravel channel auth — POST /broadcasting/auth
        $channel = "private-conversation.$convId"; // Laravel автоматически префиксует private-

        // Участник А — 200 OK
        $this->withToken($a['token'])->post('/broadcasting/auth', [
            'channel_name' => $channel,
        ])->assertOk();

        // Участник B — 200 OK
        $this->withToken($b['token'])->post('/broadcasting/auth', [
            'channel_name' => $channel,
        ])->assertOk();

        // Посторонний C — 403
        $this->withToken($c['token'])->post('/broadcasting/auth', [
            'channel_name' => $channel,
        ])->assertStatus(403);
    }
}
