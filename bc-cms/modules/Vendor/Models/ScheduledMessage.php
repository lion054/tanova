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
        'guest_form'     => 'Guest form not filled in',
        'payment_due'    => 'Balance still owing',
        'photo_delivery' => 'Photo delivery',
        'loyalty_offer'  => 'Loyalty offer',
    ];

    /** What can be written between braces in a message. */
    const PLACEHOLDERS = [
        '{name}'            => 'Guest first name',
        '{reference}'       => 'Booking reference',
        '{trip}'            => 'Trip name',
        '{date}'            => 'Trip start date',
        '{guest_form_link}' => 'Link to the guest form',
        '{balance}'         => 'Amount still owing',
        '{points}'          => 'Loyalty points',
    ];

    /** Messages a vendor can switch on with one click. All start paused so nothing goes out unreviewed. */
    const STARTER = [
        ['name' => 'Guest details, a week out', 'trigger' => 'guest_form', 'offset_days' => -7, 'subject' => 'Who is travelling on {trip}?',
         'body' => "Hello {name},\n\nWe are nearly there for {trip} on {date}. Please tell us who is travelling so we can get everything ready:\n{guest_form_link}\n\nThank you."],
        ['name' => 'Balance reminder', 'trigger' => 'payment_due', 'offset_days' => -14, 'subject' => 'Your balance for {trip}',
         'body' => "Hello {name},\n\nA reminder that {balance} is still due for booking {reference} ({trip}, {date}). Reply to this message if you would like to settle it another way."],
        ['name' => 'See you soon', 'trigger' => 'pre_trip', 'offset_days' => -2, 'subject' => 'Two days to {trip}',
         'body' => "Hello {name},\n\n{trip} is on {date}. We are looking forward to hosting you. Reply here with any last questions."],
        ['name' => 'Day of the trip', 'trigger' => 'departure', 'offset_days' => 0, 'subject' => 'Today: {trip}',
         'body' => "Good morning {name},\n\nToday is the day. Reference {reference}. Have a wonderful time."],
        ['name' => 'Welcome home', 'trigger' => 'welcome_home', 'offset_days' => 1, 'subject' => 'Welcome home, {name}',
         'body' => "Hello {name},\n\nWe hope {trip} was everything you hoped for. Tell us how it went, we love to hear."],
        ['name' => 'Your photos', 'trigger' => 'photo_delivery', 'offset_days' => 3, 'subject' => 'Your photos from {trip}',
         'body' => "Hello {name},\n\nYour photos from {trip} are on their way to you. If you would like more, reply here."],
        ['name' => 'Review request', 'trigger' => 'review_request', 'offset_days' => 5, 'subject' => 'How was {trip}?',
         'body' => "Hello {name},\n\nWould you share a few words about {trip}? It helps other travellers and means a great deal to us."],
        ['name' => 'Come back offer', 'trigger' => 'loyalty_offer', 'offset_days' => 14, 'subject' => 'A thank-you from us',
         'body' => "Hello {name},\n\nYou have {points} loyalty points with us. Book your next trip and we will make it worth your while."],
        ['name' => 'Happy birthday', 'trigger' => 'occasion', 'offset_days' => 0, 'subject' => 'Happy birthday, {name}',
         'body' => "Happy birthday {name}! Wishing you a wonderful year of adventures."],
    ];

    /** Replace {placeholders} with values; an unknown one is left as written. */
    public static function render(string $text, array $vars): string
    {
        return strtr($text, $vars);
    }

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
        return in_array($this->trigger, ['departure', 'welcome_home', 'review_request', 'photo_delivery', 'loyalty_offer'], true)
            ? 'end_date'
            : 'start_date';
    }
}
