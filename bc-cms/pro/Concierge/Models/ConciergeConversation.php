<?php

namespace Pro\Concierge\Models;

use App\BaseModel;
use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Booking\Models\Booking;

class ConciergeConversation extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_concierge_conversations';

    const STATUS_OPEN      = 'open';
    const STATUS_RESOLVED  = 'resolved';
    const STATUS_ESCALATED = 'escalated';

    protected $fillable = [
        'user_id', 'vendor_id', 'booking_id',
        'guest_name', 'guest_email', 'guest_phone', 'channel',
        'chatbot_name', 'category', 'priority', 'status', 'initiated_by',
        'assigned_to', 'resolution_reason',
        'last_message', 'last_message_at',
        'create_user', 'update_user',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function messages()
    {
        return $this->hasMany(ConciergeMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(ConciergeMessage::class, 'conversation_id')->latestOfMany();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function guestDisplayName(): string
    {
        return $this->guest_name ?? $this->user?->name ?? 'Guest';
    }

    public function addMessage(string $body, string $senderType = 'guest', ?int $senderId = null, bool $aiDraft = false): ConciergeMessage
    {
        $msg = $this->messages()->create([
            'body'        => $body,
            'sender_type' => $senderType,
            'sender_id'   => $senderId,
            'ai_draft'    => (int) $aiDraft,
            'approved'    => $aiDraft ? 0 : 1,
            'create_user' => auth()?->id(),
        ]);

        $this->update([
            'last_message'    => substr($body, 0, 255),
            'last_message_at' => now(),
        ]);

        \Illuminate\Support\Facades\Log::info('Concierge message added', [
            'conversation_id' => $this->id,
            'message_id' => $msg->id,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'ai_draft' => $aiDraft,
        ]);

        return $msg;
    }

    public function resolve(string $reason = ''): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolution_reason' => $reason,
        ]);
    }

    public function escalate(string $reason = ''): void
    {
        $this->update([
            'status' => self::STATUS_ESCALATED,
            'resolution_reason' => $reason,
        ]);
    }
}
