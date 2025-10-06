<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ConversationRead extends Model
{
    protected $fillable = ["conversation_id","user_id","last_read_message_id","read_at"];
    public $incrementing = false;
    protected $primaryKey = null;
}