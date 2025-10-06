<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ["conversation_id","user_id","body"];

    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany{return $this->hasMany(\App\Models\MessageAttachment::class);}
}