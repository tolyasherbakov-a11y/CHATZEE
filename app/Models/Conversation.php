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
        return $this->belongsToMany(User::class, 'conversation_user', 'conversation_id', 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public static function oneToOne(\App\Models\User $a, \App\Models\User $b): self
{
    // Всегда упорядочим пару (a,b) по id, чтобы поиск был детерминированным
    [$u1, $u2] = ((int)$a->id < (int)$b->id) ? [$a, $b] : [$b, $a];

    // 1) Попробуем найти существующий диалог a<->b (is_group = false)
    $existing = static::query()
        ->where('is_group', false)
        ->whereHas('users', fn ($q) => $q->whereKey($u1->id))
        ->whereHas('users', fn ($q) => $q->whereKey($u2->id))
        ->first();

    if ($existing) {
        // touch, чтобы поднять наверх в списке
        $existing->touch();
        return $existing;
    }

    // 2) Создаём новый и привязываем обоих
    $conv = static::create([
        'is_group' => false,
    ]);

    $conv->users()->sync([$u1->id, $u2->id]); // без детачей — свежая связка
    $conv->touch();

    return $conv;
}
}