<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Routing\Middleware\ThrottleRequests;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // отключаем троттлинг только для этого тестового класса
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function authToken(): string
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $resp = $this->postJson("/api/v1/auth/register", [
            "name" => "Bob",
            "email" => "bob@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
        ])->assertCreated();

        return $resp->json("token");
    }

    public function test_update_profile(): void
    {
        $token = $this->authToken();

        $updated = $this->withToken($token)->patchJson("/api/v1/profile", [
            "name" => "Bobby",
            "bio" => "Hello!",
            "settings" => ["lang" => "ru", "theme" => "dark"],
        ])->assertOk();

        $this->assertSame("Bobby", $updated->json("name"));
        $this->assertSame("Hello!", $updated->json("bio"));
        $this->assertSame("ru", $updated->json("settings.lang"));
    }

    public function test_attach_avatar_path(): void
    {
        $token = $this->authToken();

        $me = $this->withToken($token)->getJson("/api/v1/auth/me")->assertOk();
        $userId = $me->json("id");

        $key = "avatars/{$userId}/example.jpg";

        $resp = $this->withToken($token)->postJson("/api/v1/profile/avatar/attach", [
            "key" => $key,
        ])->assertOk();

        $this->assertSame($key, $resp->json("avatar_path"));
        $this->assertStringContainsString($key, $resp->json("avatar_url"));
    }
}