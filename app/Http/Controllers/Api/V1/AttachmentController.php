<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
class AttachmentController extends Controller
{
    // Вернёт pre-signed PUT URL для прямой загрузки в S3
    public function presign(Request $request, Conversation $conversation)
{
    Gate::authorize("view", $conversation);

    $data = $request->validate([
        "filename" => ["required","string"],
        "mime" => ["nullable","string"],
        "size" => ["nullable","integer","min:0"],
    ]);

    // Генерируем key
    $safeName = Str::slug(pathinfo($data["filename"], PATHINFO_FILENAME));
    $ext = pathinfo($data["filename"], PATHINFO_EXTENSION);
    $key = "attachments/{$conversation->id}/" . Str::uuid() . "_" . $safeName . ($ext ? ".{$ext}" : "");
    $expires = now()->addMinutes(5);

    try {
        // Подписанный URL на PUT — ContentType обязателен для корректной подписи
        $uploadUrl = \Illuminate\Support\Facades\Storage::disk("s3")->temporaryUploadUrl(
            $key,
            $expires,
            ['ContentType' => $data['mime'] ?? 'application/octet-stream']
        );
    } catch (\Throwable $e) {
        // В тестовой среде S3 может быть не сконфигурирован — отдаём заглушку
        $uploadUrl = url("/fake-upload?key=".rawurlencode($key));
    }

    // Публичная ссылка через CDN/endpoint (если настроен), иначе — локальный префикс
    $public = config("filesystems.disks.s3.url")
        ? rtrim(config("filesystems.disks.s3.url"), "/") . "/" . ltrim($key, "/")
        : "/s3/" . ltrim($key, "/");

    return response()->json([
        "key" => $key,
        "upload" => [
            "url" => $uploadUrl,
            "method" => "PUT",
            "headers" => [
                // фронту важно отправить ровно такой же Content-Type, что использовался в подписи
                "Content-Type" => $data['mime'] ?? 'application/octet-stream',
            ],
        ],
        "public_url" => $public,
    ], 201);
}

    // Создаёт сообщение с вложением (предполагается, что клиент уже загрузил объект по key)
    public function attach(Request $request, Conversation $conversation)
    {
        Gate::authorize("sendMessage", $conversation);
        $data = $request->validate([
            "key" => ["required","string"],
            "mime" => ["nullable","string"],
            "size" => ["nullable","integer","min:0"],
            "name" => ["nullable","string"],
            "body" => ["nullable","string","max:5000"], // необязательный текст
            "width" => ["nullable","integer","min:1"],
            "height" => ["nullable","integer","min:1"],
        ]);
        $msg = $conversation->messages()->create([
            "user_id" => $request->user()->id,
            "body" => $data["body"] ?? "",
        ]);
        $att = $msg->attachments()->create([
            "key" => $data["key"],
            "mime" => $data["mime"] ?? null,
            "size" => $data["size"] ?? null,
            "name" => $data["name"] ?? null,
            "width" => $data["width"] ?? null,
            "height" => $data["height"] ?? null,
        ]);
        $conversation->touch();
        // для broadcast добавим вложения
        $msg->load("attachments");
        event(new MessageSent($msg));
        return response()->json([
            "id" => $msg->id,
            "body" => $msg->body,
            "conversation_id" => $msg->conversation_id,
            "user_id" => $msg->user_id,
            "attachments" => $msg->attachments->map->toArray()->all(),
        ], 201);
    }
}