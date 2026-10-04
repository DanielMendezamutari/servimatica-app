<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppConversationModel extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'phone_number',
        'customer_name',
        'status',
        'human_handoff_until',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'human_handoff_until' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessageModel::class, 'conversation_id')->orderBy('id');
    }

    public function isHumanHandoffActive(): bool
    {
        if ($this->status !== 'human_agent') {
            return false;
        }

        if ($this->human_handoff_until && now()->lt($this->human_handoff_until)) {
            return true;
        }

        return false;
    }
}
