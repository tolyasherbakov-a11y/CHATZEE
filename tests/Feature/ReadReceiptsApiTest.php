<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;
class ReadReceiptsApiTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
{
    parent::setUp();
    $this->withoutMiddleware(ThrottleRequests::class);
}
    private function register(string $name, string $email): array
{
    $resp = $this->postJson('/api/v1/auth/register', [
        'name' => $name,
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    return [
        'id'    => (string) $resp->json('id'),
        'token' => (string) $resp->json('token'),
    ];
}

    public function test_unread_counter_and_mark_read(): void
    {
        $a = $this->register("Alice", "alice@example.com");
        $b = $this->register("Bob", "bob@example.com");
        $conv = $this->withToken($a["token"])
            ->postJson("/api/v1/conversations/start", ["user_id" => $b["id"]])
            ->dump()
            ->assertCreated();
        $convId = $conv->json("id");
        // Alice пишет 2 сообщения
        $m1 = $this->withToken($a["token"])->postJson("/api/v1/conversations/{$convId}/messages", ["body" => "one"])->assertCreated();
        $m2 = $this->withToken($a["token"])->postJson("/api/v1/conversations/{$convId}/messages", ["body" => "two"])->assertCreated();
        // Bob видит ненулевой unread_count
        $list = $this->withToken($b["token"])->getJson("/api/v1/conversations")->assertOk();
        $convRow = collect($list->json("data"))->firstWhere("id", $convId);
        $this->assertSame(2, $convRow["unread_count"]);
        // Bob помечает прочитанным до первого сообщения
        $this->withToken($b["token"])->postJson("/api/v1/conversations/{$convId}/read-up-to", ["message_id" => $m1->json("id")])->assertOk();
        $list = $this->withToken($b["token"])->getJson("/api/v1/conversations")->assertOk();
        $convRow = collect($list->json("data"))->firstWhere("id", $convId);
        $this->assertSame(1, $convRow["unread_count"]);
        // Bob дочитывает до конца
        $this->withToken($b["token"])->postJson("/api/v1/conversations/{$convId}/read-up-to", ["message_id" => $m2->json("id")])->assertOk();
        $list = $this->withToken($b["token"])->getJson("/api/v1/conversations")->assertOk();
        $convRow = collect($list->json("data"))->firstWhere("id", $convId);
        $this->assertSame(0, $convRow["unread_count"]);
    }
}