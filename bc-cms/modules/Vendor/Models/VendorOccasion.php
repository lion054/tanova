<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class VendorOccasion extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_occasions';

    const TYPES = ['birthday', 'anniversary', 'custom'];

    protected $fillable = [
        'vendor_id', 'customer_name', 'customer_email', 'customer_phone',
        'type', 'source', 'source_key', 'occasion_date', 'notes',
    ];

    protected $casts = [
        'occasion_date' => 'date',
    ];

    /** The next time this comes round (today counts), as a date. */
    public function nextOn(?\Carbon\Carbon $from = null): \Carbon\Carbon
    {
        $from = ($from ?: now())->copy()->startOfDay();
        $d = $this->occasion_date;
        $next = \Carbon\Carbon::create($from->year, $d->month, min($d->day, \Carbon\Carbon::create($from->year, $d->month, 1)->daysInMonth));
        if ($next->lt($from)) {
            $year = $from->year + 1;
            $next = \Carbon\Carbon::create($year, $d->month, min($d->day, \Carbon\Carbon::create($year, $d->month, 1)->daysInMonth));
        }

        return $next;
    }
}
