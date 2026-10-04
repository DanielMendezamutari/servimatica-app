<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageModel extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'conversation_id',
        'direction',
        'message',
        'tokens_used',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversationModel::class, 'conversation_id');
    }
}
