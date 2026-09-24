<?php

namespace Pro\Integrations\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A supplier the vendor buys from — bus company, charter airline, lodge.
 *
 * Distinct from bc_vendor_api_keys (keys the vendor issues to its own customers)
 * and from the inbound feed connectors in this module (Wetu et al).
 */
class Operator extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_operators';

    public const TYPES = ['transport', 'air', 'lodging', 'dining', 'activity'];

    protected $fillable = [
        'vendor_id', 'name', 'type', 'contact_name', 'contact_email', 'contact_phone',
        'website', 'address', 'commission_rate', 'payment_terms', 'currency',
        'connection_status', 'last_synced_at', 'notes', 'status',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'last_synced_at'  => 'datetime',
    ];

    /** What a supplier record must satisfy: the portal form and the API share it. */
    public static function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:191'],
            'type'            => ['required', 'in:' . implode(',', self::TYPES)],
            'contact_name'    => ['nullable', 'string', 'max:191'],
            'contact_email'   => ['nullable', 'email', 'max:191'],
            'contact_phone'   => ['nullable', 'string', 'max:40'],
            'website'         => ['nullable', 'string', 'max:191'],
            'address'         => ['nullable', 'string', 'max:1000'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_terms'   => ['nullable', 'string', 'max:60'],
            'currency'        => ['nullable', 'string', 'max:8'],
            'notes'           => ['nullable', 'string', 'max:2000'],
            'status'          => ['nullable', 'in:active,inactive'],
        ];
    }

    public static function routeRules(): array
    {
        return [
            'origin'           => ['required', 'string', 'max:191'],
            'destination'      => ['required', 'string', 'max:191'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'vehicle_type'     => ['nullable', 'string', 'max:60'],
            'days_of_week'     => ['nullable', 'string', 'max:20'],
            'departure_time'   => ['nullable', 'date_format:H:i'],
            'arrival_time'     => ['nullable', 'date_format:H:i'],
        ];
    }

    public static function fareRules(): array
    {
        return [
            'route_id'        => ['nullable', 'integer'],
            'fare_class'      => ['required', 'string', 'max:40'],
            'nett_price'      => ['required', 'numeric', 'min:0'],
            'sell_price'      => ['nullable', 'numeric', 'min:0'],
            'available_seats' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'valid_from'      => ['nullable', 'date'],
            'valid_to'        => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    public function routes(): HasMany
    {
        return $this->hasMany(OperatorRoute::class, 'operator_id');
    }

    public function fares(): HasMany
    {
        return $this->hasMany(OperatorFare::class, 'operator_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(OperatorSyncLog::class, 'operator_id')->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /** Margin on a fare, if both sides of the price are known. */
    public function marginOn(OperatorFare $fare): ?float
    {
        if ($fare->sell_price === null) {
            return null;
        }

        return round((float) $fare->sell_price - (float) $fare->nett_price, 2);
    }
}
