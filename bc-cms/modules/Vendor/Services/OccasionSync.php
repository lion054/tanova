<?php

namespace Modules\Vendor\Services;

use Modules\Vendor\Models\BookingGuest;
use Modules\Vendor\Models\VendorCustomer;
use Modules\Vendor\Models\VendorOccasion;

/**
 * Birthdays already known to the portal, kept in Occasions: from customers, and
 * from the travellers on booking guest forms. One occasion per source person, so
 * running it again updates rather than repeats. Only a customer, or the lead
 * traveller (who has the booking's e-mail), can be sent a message; other
 * travellers still appear so the vendor sees the day coming.
 */
class OccasionSync
{
    /** @return int occasions created or updated */
    public function run(int $vendorId): int
    {
        $n = 0;

        foreach (VendorCustomer::withoutVendorScope()->where('vendor_id', $vendorId)->whereNotNull('date_of_birth')->get() as $c) {
            $n += $this->put($vendorId, 'customer:' . $c->id, 'customer', trim($c->first_name . ' ' . $c->last_name), $c->email, $c->phone, $c->date_of_birth);
        }

        $guests = BookingGuest::withoutVendorScope()->where('vendor_id', $vendorId)->whereNotNull('date_of_birth')->get();
        $bookings = \Modules\Booking\Models\Booking::where('vendor_id', $vendorId)->whereIn('id', $guests->pluck('booking_id'))->get()->keyBy('id');
        foreach ($guests as $g) {
            $b = $bookings->get($g->booking_id);
            $n += $this->put($vendorId, 'guest:' . $g->id, 'guest', $g->name, $g->is_lead && $b ? $b->email : null, $g->is_lead && $b ? $b->phone : null, $g->date_of_birth);
        }

        return $n;
    }

    private function put(int $vendorId, string $key, string $source, string $name, ?string $email, ?string $phone, $dob): int
    {
        if (!$dob || trim($name) === '') {
            return 0;
        }
        $email = $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;

        // A guest who is also a customer is one person: prefer the customer's row.
        if ($source === 'guest' && $email && VendorOccasion::withoutVendorScope()->where('vendor_id', $vendorId)->where('type', 'birthday')
            ->where('source', 'customer')->whereRaw('LOWER(customer_email) = ?', [$email])->exists()) {
            return 0;
        }

        $row = VendorOccasion::withoutVendorScope()->firstOrNew(['vendor_id' => $vendorId, 'source_key' => $key]);
        $row->fill(['customer_name' => $name, 'customer_email' => $email, 'customer_phone' => $phone, 'type' => 'birthday', 'source' => $source, 'occasion_date' => $dob]);
        $changed = !$row->exists || $row->isDirty();
        $row->save();

        return $changed ? 1 : 0;
    }
}
