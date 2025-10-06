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
        [$u1, $u2] = ((string)$a->getKey() < (string)$b->getKey()) ? [$a, $b] : [$b, $a];
    
        $existing = static::query()
            ->where('is_group', false)
            ->whereHas('users', fn ($q) => $q->whereKey($u1->getKey()))
            ->whereHas('users', fn ($q) => $q->whereKey($u2->getKey()))
            ->first();
    
        if ($existing) {
            $existing->touch();
            return $existing;
        }
    
        $conv = static::create(['is_group' => false]);
        $conv->users()->sync([$u1->getKey(), $u2->getKey()]);
        $conv->touch();
    
        return $conv;
    }
}