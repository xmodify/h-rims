<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatSession extends Model
{
    use HasFactory;

    protected $table = 'ai_chat_sessions';

    protected $fillable = [
        'user_id',
        'title',
        'context_scope',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(AiChatMessage::class, 'session_id')->orderBy('id', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(AiChatMessage::class, 'session_id')->latestOfMany();
    }
}
