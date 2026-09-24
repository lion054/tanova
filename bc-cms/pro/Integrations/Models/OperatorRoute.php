<?php

namespace Pro\Integrations\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorRoute extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_operator_routes';

    protected $fillable = [
        'vendor_id', 'operator_id', 'origin', 'destination', 'duration_minutes',
        'vehicle_type', 'days_of_week', 'departure_time', 'arrival_time', 'active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'active'           => 'boolean',
    ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function label(): string
    {
        return $this->origin . ' → ' . $this->destination;
    }

    /** Day numbers ("1,3,5") rendered as short names. */
    public function dayLabels(): array
    {
        if (!$this->days_of_week) {
            return [];
        }

        $names = [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];

        return array_values(array_filter(array_map(
            fn ($d) => $names[(int) trim($d)] ?? null,
            explode(',', $this->days_of_week)
        )));
    }
}
