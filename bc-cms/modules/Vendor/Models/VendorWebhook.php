<?php

namespace Modules\Vendor\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VendorWebhook extends Model
{
    protected $table = 'bc_vendor_webhooks';

    protected $fillable = ['vendor_id', 'url', 'secret', 'events', 'active'];

    // Secret is shown once on creation via makeVisible() — never returned by default
    protected $hidden = ['secret'];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public static $supportedEvents = [
        'booking.confirmed',
        'booking.cancelled',
        'booking.completed',
        'booking.created',
        'booking.paid',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(VendorWebhookDelivery::class, 'webhook_id');
    }

    public static function generate(int $vendorId, string $url, array $events): static
    {
        return static::create([
            'vendor_id' => $vendorId,
            'url'       => $url,
            'secret'    => Str::random(32),
            'events'    => $events,
            'active'    => true,
        ]);
    }

    /** Build the HMAC signature header value for a payload. */
    public function sign(string $payload): string
    {
        return 't=' . time() . ',v1=' . hash_hmac('sha256', $payload, $this->secret);
    }

    /** Find all active webhooks for a vendor that listen to a specific event. */
    public static function forVendorEvent(int $vendorId, string $event): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('vendor_id', $vendorId)
            ->where('active', true)
            ->whereJsonContains('events', $event)
            ->get();
    }
}
