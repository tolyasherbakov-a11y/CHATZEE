<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;
use App\Models\Conversation;

class BootstrapAndTypingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function register(string $name, string $email): array
    {
        $resp = $this->postJson("/api/v1/auth/register", [
            "name" => $name,
            "email" => $email,
            "password" => "password123",
            "password_confirmation" => "password123",
        ])->assertCreated();

        $token = $resp->json("token");
        $me = $this->withToken($token)->getJson("/api/v1/auth/me")->assertOk();
        return ["token" => $token, "id" => $me->json("id")];
    }

    public function test_bootstrap_and_typing_endpoints(): void
    {
        $a = $this->register("Alice", "alice@example.com");
        $b = $this->register("Bob", "bob@example.com");

        // создать или найти direct диалог
        $conv = $this->withToken($a["token"])
            ->postJson("/api/v1/conversations/start", ["user_id" => $b["id"]])
            ->assertCreated();
        $convId = $conv->json("id");

        // bootstrap
        $boot = $this->withToken($a["token"])->getJson("/api/v1/bootstrap")->assertOk();
        $boot->assertJsonStructure([
            "app" => ["name","env"],
            "user" => ["id","name","email","roles","permissions"],
            "reverb" => ["key","host","port","scheme","path"],
            "s3" => ["disk","bucket","region","cdn"],
            "features" => ["chat","typing","presence"],
        ]);

        // typing
        $this->withToken($a["token"])->postJson("/api/v1/conversations/{$convId}/typing/start")->assertNoContent();
        $this->withToken($a["token"])->postJson("/api/v1/conversations/{$convId}/typing/stop")->assertNoContent();
    }
}