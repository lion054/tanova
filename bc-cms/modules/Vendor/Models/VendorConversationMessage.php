<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorConversationMessage extends Model
{
    protected $table = 'bc_concierge_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get parent conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(VendorConversation::class, 'conversation_id');
    }

    /**
     * Scope: Get user messages only
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope: Get assistant messages only
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Check if this is a user message
     */
    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if this is an assistant message
     */
    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Get message preview (first 100 chars)
     */
    public function getPreview(): string
    {
        return substr($this->content, 0, 100) . (strlen($this->content) > 100 ? '...' : '');
    }

    /**
     * Get next step from message metadata
     */
    public function getNextStep(): ?string
    {
        return $this->metadata['step'] ?? null;
    }

    /**
     * Get pending action from metadata
     */
    public function getPendingAction(): ?array
    {
        return $this->metadata['pendingAction'] ?? null;
    }
}
