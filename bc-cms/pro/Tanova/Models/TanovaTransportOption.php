<?php

namespace Pro\Tanova\Models;

use Illuminate\Database\Eloquent\Model;

/** A priced variant of a TanovaTransport row (e.g. sedan vs SUV under "Car Hire"). */
class TanovaTransportOption extends Model
{
    protected $table = 'bc_tanova_transport_options';

    protected $fillable = [
        'transport_id', 'option_name', 'option_value', 'price_modifier',
        'per_person', 'capacity', 'description', 'icon', 'status',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'per_person'     => 'boolean',
        'capacity'       => 'integer',
    ];

    public function transport()
    {
        return $this->belongsTo(TanovaTransport::class, 'transport_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    public function calculateTotalModifier(int $days, int $people = 1): float
    {
        $dailyCost = (float) $this->price_modifier * $days;
        return $this->per_person ? ($dailyCost * $people) : $dailyCost;
    }
}
