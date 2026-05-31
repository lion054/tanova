<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorConversation extends Model
{
    protected $table = 'bc_concierge_conversations';

    protected $fillable = [
        'vendor_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'channel',
        'chatbot_name',
        'category',
        'priority',
        'initiated_by',
        'assigned_to',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get vendor who owns this conversation
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'vendor_id');
    }

    /**
     * Get all messages in this conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(VendorConversationMessage::class, 'conversation_id');
    }

    /**
     * Get assigned staff member (if escalated)
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /**
     * Scope: Get active conversations only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Get conversations for vendor
     */
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: Get conversations by channel
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Check if conversation should be escalated
     */
    public function shouldEscalate(): bool
    {
        // Escalate if:
        // 1. Customer complained
        // 2. More than 10 messages without resolution
        // 3. Marked as high priority
        // 4. Needs human review (AI confidence low)

        $messageCount = $this->messages()->count();
        $hasComplaint = $this->category === 'complaint';
        $isPriority = $this->priority === 'high';

        return $hasComplaint || ($messageCount > 10 && $this->status === 'active') || $isPriority;
    }

    /**
     * Get chat snippet (last message preview)
     */
    public function getChatSnippet(): string
    {
        $lastMessage = $this->messages()->latest()->first();
        if (!$lastMessage) {
            return '';
        }

        $text = $lastMessage->content;
        return substr($text, 0, 100) . (strlen($text) > 100 ? '...' : '');
    }
}
