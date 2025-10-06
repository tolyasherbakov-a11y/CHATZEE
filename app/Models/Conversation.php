<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ["name","is_group"];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, "conversation_user");
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public static function oneToOne(User $a, User $b): self
    {
        $ids = [$a->id, $b->id];
        sort($ids);
        // простая реализация: ищем существующий диалог 1-1 по точному набору участников
        $candidate = static::where("is_group", false)
            ->whereHas("users", fn($q) => $q->whereIn("users.id", $ids))
            ->withCount(["users"])
            ->get()
            ->firstWhere("users_count", 2);

        if ($candidate) return $candidate;

        $conv = static::create(["is_group" => false]);
        $conv->users()->sync($ids);
        return $conv;
    }
}