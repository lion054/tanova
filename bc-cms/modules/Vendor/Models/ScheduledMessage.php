<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class ScheduledMessage extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_scheduled_messages';

    const TRIGGERS = [
        'pre_trip'       => 'Pre-trip reminder',
        'departure'      => 'Departure day',
        'check_in'       => 'Check-in',
        'welcome_home'   => 'Welcome home',
        'review_request' => 'Review request',
        'occasion'       => 'Occasion (birthday/anniversary)',
    ];

    const CHANNELS = ['email', 'whatsapp', 'telegram', 'sms'];

    protected $fillable = [
        'vendor_id', 'name', 'trigger', 'offset_days', 'channel',
        'subject', 'body', 'active',
    ];

    protected $casts = [
        'offset_days' => 'integer',
        'active'      => 'boolean',
    ];

    public function logs()
    {
        return $this->hasMany(ScheduledMessageLog::class, 'scheduled_message_id');
    }

    /** The booking date column this trigger keys off. */
    public function dateColumn(): string
    {
        return in_array($this->trigger, ['departure', 'welcome_home', 'review_request'], true)
            ? 'end_date'
            : 'start_date';
    }
}
