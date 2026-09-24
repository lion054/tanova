<?php

namespace Modules\Vendor\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VendorApiKey extends Model
{
    protected $table = 'bc_vendor_api_keys';

    protected $fillable = [
        'vendor_id',
        'name',
        'type',       // 'secret' (full access) | 'publishable' (read-only, browser-safe)
        'mode',       // 'live' | 'test' (sandbox)
        'scopes',     // optional JSON list; null = type default
        'domain',     // nullable — the website this key is issued for
        'key',
        'key_hash',
        'rate_limit', // annual request cap (0 = unlimited)
        'active',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'scopes'       => 'array',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    protected $hidden = ['key', 'key_hash'];

    // ── Relationships ────────────────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function usage(): HasMany
    {
        return $this->hasMany(VendorApiUsage::class, 'vendor_api_key_id');
    }

    // ── Factory ──────────────────────────────────────────────────────────────

    /** Prefix for a given key type + mode, e.g. pk_live_ / sk_test_. */
    public static function prefixFor(string $type, string $mode = 'live'): string
    {
        $t = $type === 'publishable' ? 'pk' : 'sk';
        $m = $mode === 'test' ? 'test' : 'live';
        return "{$t}_{$m}_";
    }

    /** Publishable keys are read-only and safe to embed in a website's front-end. */
    public function isPublishable(): bool
    {
        return $this->type === 'publishable';
    }

    /** Whether this key is allowed to perform write operations. */
    public function canWrite(): bool
    {
        return !$this->isPublishable();
    }

    /** Sandbox key — no subscription required, not counted against the annual cap. */
    public function isTest(): bool
    {
        return $this->mode === 'test';
    }

    /**
     * Generate a new API key.
     *
     * @param  string      $type    'secret' | 'publishable'
     * @param  string      $mode    'live' | 'test'
     * @param  string|null $domain  e.g. "dare2travel.com" — auto-registers CORS origin
     */
    public static function generate(User $vendor, string $name, int $rateLimit = 10000, ?string $domain = null, string $type = 'secret', string $mode = 'live', ?array $scopes = null): static
    {
        $type  = $type === 'publishable' ? 'publishable' : 'secret';
        $mode  = $mode === 'test' ? 'test' : 'live';
        $plain = self::prefixFor($type, $mode) . Str::random(40);

        $normalised = $domain ? self::normaliseDomain($domain) : null;

        $model = static::create([
            'vendor_id'  => $vendor->id,
            'name'       => $name,
            'type'       => $type,
            'mode'       => $mode,
            // A publishable key is read-only whatever it is given; a secret key with no scopes has full access.
            'scopes'     => $type === 'publishable' ? ['*:read'] : (self::cleanScopes($scopes) ?: null),
            'domain'     => $normalised,
            'key'        => null,
            'key_hash'   => hash_hmac('sha256', $plain, config('app.key')),
            'rate_limit' => $rateLimit,
            'active'     => true,
        ]);

        // Auto-register the domain as an allowed CORS origin so the vendor doesn't
        // need a separate step — the key IS the domain authorization.
        if ($normalised) {
            $origin = 'https://' . ltrim($normalised, '/');
            VendorAllowedOrigin::firstOrCreate([
                'vendor_id' => $vendor->id,
                'origin'    => $origin,
            ]);
            Cache::forget("vendor_cors:{$vendor->id}:" . md5($origin));
        }

        $model->setRawAttributes(array_merge($model->getRawOriginal(), ['key' => $plain]), true);
        $model->makeVisible('key');

        return $model;
    }

    // ── Lookup ───────────────────────────────────────────────────────────────

    public static function findByKey(string $plain): ?static
    {
        return static::where('key_hash', hash_hmac('sha256', $plain, config('app.key')))->first();
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function isValid(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Test (sandbox) keys are never capped.
        if ($this->isTest()) {
            return true;
        }

        // rate_limit = 0 means unlimited
        if ($this->rate_limit > 0 && $this->annualUsageCount() >= $this->rate_limit) {
            return false;
        }

        return true;
    }

    // ── Annual usage count ───────────────────────────────────────────────────

    /**
     * Requests used this calendar year. Cached 60s per key, busted on recordUsage().
     */
    public function annualUsageCount(): int
    {
        return Cache::remember($this->usageCacheKey(), 60, function () {
            return $this->usage()
                ->where('created_at', '>=', now()->startOfYear())
                ->count();
        });
    }

    public function usageCacheKey(): string
    {
        return "vendor_api_key:{$this->id}:annual_count:" . now()->format('Y');
    }

    /**
     * Requests used this calendar month. Cached 60s per key.
     */
    public function monthlyUsageCount(): int
    {
        return Cache::remember("vendor_api_key:{$this->id}:monthly_count:" . now()->format('Y-m'), 60, function () {
            return $this->usage()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();
        });
    }

    // ── Usage tracking ───────────────────────────────────────────────────────

    public function recordUsage(string $endpoint, string $method, int $statusCode, int $ms): void
    {
        VendorApiUsage::create([
            'vendor_api_key_id' => $this->id,
            'endpoint'          => $endpoint,
            'method'            => strtoupper($method),
            'status_code'       => $statusCode,
            'response_time_ms'  => $ms,
            'created_at'        => now(),
        ]);

        Cache::forget($this->usageCacheKey());

        $this->updateQuietly(['last_used_at' => now()]);
    }

    // ── Rotation ─────────────────────────────────────────────────────────────

    /** Only real scopes, once each; null or empty when none. */
    public static function cleanScopes(?array $scopes): ?array
    {
        if (!$scopes) {
            return null;
        }
        $ok = array_values(array_unique(array_intersect(array_map('strval', $scopes), \App\Support\ApiScopes::all())));

        return $ok ?: null;
    }

    public function rotate(): string
    {
        // Keep the same key type + mode/prefix when rotating.
        $plain = self::prefixFor($this->type ?? 'secret', $this->mode ?? 'live') . Str::random(40);

        $this->update([
            'key'      => null,
            'key_hash' => hash_hmac('sha256', $plain, config('app.key')),
        ]);

        return $plain;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Strip scheme, www, trailing slash — store bare hostname */
    public static function normaliseDomain(string $input): string
    {
        $input = trim($input);
        $input = preg_replace('#^https?://#i', '', $input);
        $input = ltrim($input, 'www.');
        return rtrim(strtolower($input), '/');
    }
}
