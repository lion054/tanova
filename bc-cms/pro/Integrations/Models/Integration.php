<?php

namespace Pro\Integrations\Models;

use App\BaseModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class Integration extends BaseModel
{
    protected $table = 'bc_integrations';

    const STATUS_CONNECTED    = 'connected';
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_ERROR        = 'error';

    protected $fillable = [
        'slug', 'author_id', 'category', 'status', 'credentials',
        'last_verified_at', 'last_error', 'create_user', 'update_user',
    ];

    protected $casts = [
        'last_verified_at' => 'datetime',
    ];

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /** Always scope to the current authenticated vendor. */
    public function scopeForVendor($query, ?int $authorId = null)
    {
        return $query->where('author_id', $authorId ?? Auth::id());
    }

    // ── Accessors / Mutators ───────────────────────────────────────────────────

    public function getCredentialsAttribute(?string $value): array
    {
        if (empty($value)) {
            return [];
        }
        try {
            return json_decode(Crypt::decryptString($value), true) ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function setCredentialsAttribute(array $value): void
    {
        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return $this->credentials[$key] ?? $default;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED;
    }

    /**
     * Get or create the integration record for the given slug + current vendor.
     * Always vendor-scoped — never returns a row belonging to another vendor.
     */
    public static function forSlug(string $slug, ?int $authorId = null): self
    {
        $authorId = $authorId ?? Auth::id();

        return static::firstOrCreate(
            ['slug' => $slug, 'author_id' => $authorId],
            [
                'category'    => 'unknown',
                'status'      => self::STATUS_DISCONNECTED,
                'create_user' => $authorId,
            ]
        );
    }

    public function roomChannels()
    {
        return $this->hasMany(IntegrationRoomChannel::class, 'integration_slug', 'slug');
    }
}
