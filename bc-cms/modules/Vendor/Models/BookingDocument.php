<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** A file the guest should have: a voucher, a ticket, a confirmation. */
class BookingDocument extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_booking_documents';

    protected $fillable = ['vendor_id', 'booking_id', 'name', 'file_id', 'external_url', 'visible_to_customer'];

    protected $casts = ['visible_to_customer' => 'boolean'];

    public function url(): ?string
    {
        return $this->external_url ?: ($this->file_id ? get_file_url($this->file_id, 'full', false) : null);
    }
}
