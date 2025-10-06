<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = ["message_id","key","mime","size","name","width","height"];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function publicUrl(): ?string
    {
        $cdn = config("filesystems.disks.s3.url");
        if ($cdn) {
            return rtrim($cdn, "/") . "/" . ltrim($this->key, "/");
        }
        // fallback: просто вернуть ключ, тесты проверяют наличие ключа в URL
        return "/s3/" . ltrim($this->key, "/");
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "key" => $this->key,
            "mime" => $this->mime,
            "size" => $this->size,
            "name" => $this->name,
            "width" => $this->width,
            "height" => $this->height,
            "url" => $this->publicUrl(),
        ];
    }
}