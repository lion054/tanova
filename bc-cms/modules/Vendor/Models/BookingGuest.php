<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** Someone travelling on a booking. */
class BookingGuest extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_booking_guests';

    protected $fillable = ['vendor_id', 'booking_id', 'is_lead', 'source', 'name', 'date_of_birth', 'nationality', 'passport_number', 'dietary', 'notes'];

    protected $casts = ['is_lead' => 'boolean', 'date_of_birth' => 'date'];
}
