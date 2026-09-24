<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** One entry in a booking's timeline: a note, or a message sent or received. */
class BookingComm extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_booking_comms';

    public const CHANNELS = ['note' => 'Note', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'call' => 'Call'];

    protected $fillable = ['vendor_id', 'booking_id', 'channel', 'direction', 'subject', 'body', 'created_by'];
}
