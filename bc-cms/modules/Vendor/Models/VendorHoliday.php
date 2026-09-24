<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorHoliday extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_holidays';

    public const TYPES    = ['public', 'religious', 'company', 'custom'];
    public const CHANNELS = ['email', 'whatsapp', 'sms'];

    protected $fillable = [
        'vendor_id', 'name', 'type', 'date', 'recurs_annually',
        'channel', 'custom_subject', 'custom_body', 'active',
    ];

    protected $casts = [
        'date'            => 'date',
        'recurs_annually' => 'boolean',
        'active'          => 'boolean',
    ];

    public function sends(): HasMany
    {
        return $this->hasMany(VendorHolidaySend::class, 'holiday_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /** Holidays falling on a given date, honouring annual recurrence. */
    public function scopeFallingOn($query, \DateTimeInterface $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereDate('date', $date->format('Y-m-d'))
              ->orWhere(function ($q2) use ($date) {
                  $q2->where('recurs_annually', true)
                     ->whereMonth('date', $date->format('m'))
                     ->whereDay('date', $date->format('d'));
              });
        });
    }

    /** The date this holiday next falls on, given recurrence. */
    public function occurrenceIn(int $year): \Carbon\Carbon
    {
        return $this->recurs_annually
            ? $this->date->copy()->setYear($year)
            : $this->date->copy();
    }

    public function subjectLine(): string
    {
        return $this->custom_subject ?: __(':name greetings', ['name' => $this->name]);
    }
}
