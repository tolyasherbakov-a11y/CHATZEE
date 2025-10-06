<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // чтобы не словить 429 при регистрации
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function register(string $name, string $email, string $password = "password123"): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $resp = $this->postJson("/api/v1/auth/register", [
            "name" => $name,
            "email" => $email,
            "password" => $password,
            "password_confirmation" => $password,
        ])->assertCreated();

        $token = $resp->json("token");
        $me = $this->withToken($token)->getJson("/api/v1/auth/me")->assertOk();
        return ["token" => $token, "id" => $me->json("id")];
    }

    public function test_direct_conversation_and_messages(): void
    {
        $u1 = $this->register("Alice", "alice@example.com");
        $u2 = $this->register("Bob", "bob@example.com");

        // старт direct-разговора Alice -> Bob
        $conv = $this->withToken($u1["token"])
            ->postJson("/api/v1/conversations/start", ["user_id" => $u2["id"]])
            ->assertCreated();

        $convId = $conv->json("id");
        $this->assertIsInt($convId);

        // Alice пишет сообщение
        $this->withToken($u1["token"])
            ->postJson("/api/v1/conversations/{$convId}/messages", ["body" => "Hello Bob"])
            ->assertCreated();

        // Bob отвечает
        $this->withToken($u2["token"])
            ->postJson("/api/v1/conversations/{$convId}/messages", ["body" => "Hi Alice"])
            ->assertCreated();

        // Alice читает ленту
        $list = $this->withToken($u1["token"])
            ->getJson("/api/v1/conversations/{$convId}/messages")
            ->assertOk();

        $this->assertGreaterThanOrEqual(2, $list->json("total"));
        $bodies = array_column($list->json("data"), "body");
        $this->assertTrue(in_array("Hello Bob", $bodies, true));
        $this->assertTrue(in_array("Hi Alice", $bodies, true));
    }
}