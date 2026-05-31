<?php

namespace Pro\Concierge\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ConciergeMessage extends Model
{
    protected $table = 'bc_concierge_messages';

    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_id', 'body',
        'ai_draft', 'approved', 'read_at', 'create_user',
    ];

    protected $casts = [
        'read_at'  => 'datetime',
        'ai_draft' => 'boolean',
        'approved' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(ConciergeConversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isFromGuest(): bool
    {
        return $this->sender_type === 'guest';
    }

    public function isAiDraft(): bool
    {
        return (bool) $this->ai_draft;
    }

    public function approve(): void
    {
        $this->update(['approved' => 1]);
    }

    public function markRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
