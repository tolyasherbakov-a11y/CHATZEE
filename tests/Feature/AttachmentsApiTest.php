<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;
class AttachmentsApiTest extends TestCase
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
    public function test_attach_file_creates_message_with_attachment(): void
    {
        $a = $this->register("Alice", "alice@example.com");
        $b = $this->register("Bob", "bob@example.com");
        $conv = $this->withToken($a["token"])
            ->postJson("/api/v1/conversations/start", ["user_id" => $b["id"]])
            ->assertCreated();
        $convId = $conv->json("id");
        // эмулируем, будто файл уже загружен по этому ключу
        $key = "attachments/{$convId}/example.txt";
        $resp = $this->withToken($a["token"])->postJson(
            "/api/v1/conversations/{$convId}/attachments/attach",
            [
                "key" => $key,
                "mime" => "text/plain",
                "size" => 12,
                "name" => "example.txt",
                "body" => "see file",
            ]
        )->assertCreated();
        $this->assertSame($convId, $resp->json("conversation_id"));
        $this->assertSame("see file", $resp->json("body"));
        $this->assertIsArray($resp->json("attachments"));
        $this->assertSame($key, $resp->json("attachments.0.key"));
        $this->assertStringContainsString($key, $resp->json("attachments.0.url"));
        // в списке сообщений тоже должны быть вложения
        $list = $this->withToken($a["token"])->getJson("/api/v1/conversations/{$convId}/messages")->assertOk();
        $this->assertGreaterThanOrEqual(1, $list->json("total"));
        $this->assertStringContainsString($key, $list->json("data.0.attachments.0.url"));
    }
}