<?php

namespace Pro\Integrations\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorFare extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_operator_fares';

    protected $fillable = [
        'vendor_id', 'operator_id', 'route_id', 'fare_class',
        'nett_price', 'sell_price', 'available_seats', 'valid_from', 'valid_to',
    ];

    protected $casts = [
        'nett_price'      => 'decimal:2',
        'sell_price'      => 'decimal:2',
        'available_seats' => 'integer',
        'valid_from'      => 'date',
        'valid_to'        => 'date',
    ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(OperatorRoute::class, 'route_id');
    }

    public function margin(): ?float
    {
        return $this->sell_price === null
            ? null
            : round((float) $this->sell_price - (float) $this->nett_price, 2);
    }

    public function isCurrent(): bool
    {
        $today = now()->startOfDay();

        return (!$this->valid_from || $this->valid_from->lte($today))
            && (!$this->valid_to || $this->valid_to->gte($today));
    }
}
