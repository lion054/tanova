<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class VendorCustomer extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_customers';

    protected $fillable = [
        'vendor_id', 'user_id', 'first_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'nationality', 'passport_number', 'notes', 'tags',
        'bookings_count', 'total_spent', 'first_booking_at', 'last_booking_at', 'source',
    ];

    protected $casts = [
        'tags'             => 'array',
        'date_of_birth'    => 'date',
        'first_booking_at' => 'datetime',
        'last_booking_at'  => 'datetime',
        'bookings_count'   => 'integer',
        'total_spent'      => 'decimal:2',
    ];

    public function getFullNameAttribute(): string
    {
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));

        return $name !== '' ? $name : ($this->email ?: ($this->phone ?: __('Unnamed')));
    }

    /** Normalised phone used for dedupe — digits only, so "+255 718" matches "0718". */
    public static function normalisePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return $digits !== '' ? $digits : null;
    }

    protected static function booted(): void
    {
        static::created(fn (self $c) => \Modules\Vendor\Services\WebhookEvents::emit((int) $c->vendor_id, 'customer.created', [
            'id' => $c->id, 'name' => trim($c->first_name . ' ' . $c->last_name), 'email' => $c->email, 'phone' => $c->phone, 'source' => $c->source,
        ]));
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term = trim((string) $term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }
}
