<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\DB;
use Modules\Vendor\Models\VendorCustomer;

/**
 * Derives vendor CRM records from bookings.
 *
 * Dedupe rule (decided during the Tanova port — see TANOVA_PORT_PLAN.md §8):
 *   1. Match on email within the vendor, if the booking has one.
 *   2. Otherwise match on normalised phone (digits only) within the vendor.
 *   3. Otherwise create a new record.
 *
 * A booking with neither an email nor a phone cannot identify a person, so it is
 * skipped rather than creating an anonymous row per booking.
 *
 * Rollups (bookings_count, total_spent, first/last_booking_at) are recomputed from
 * scratch on each run, so running this repeatedly is safe and self-correcting.
 */
class CustomerSyncService
{
    /**
     * @return array{created:int, updated:int, skipped:int}
     */
    public function syncVendor(int $vendorId): array
    {
        $created = $updated = $skipped = 0;

        $bookings = DB::table('bc_bookings')
            ->where('vendor_id', $vendorId)
            ->whereNotIn('status', ['draft'])
            ->orderBy('id')
            ->get(['id', 'customer_id', 'email', 'first_name', 'last_name', 'phone', 'total', 'created_at']);

        // Aggregate per identity first, then write once per person.
        $people = [];

        foreach ($bookings as $b) {
            $email = $b->email ? mb_strtolower(trim($b->email)) : null;
            $phone = VendorCustomer::normalisePhone($b->phone);

            if (!$email && !$phone) {
                $skipped++;
                continue;
            }

            $key = $email ? 'e:' . $email : 'p:' . $phone;

            if (!isset($people[$key])) {
                $people[$key] = [
                    'user_id'    => $b->customer_id ?: null,
                    'first_name' => $b->first_name,
                    'last_name'  => $b->last_name,
                    'email'      => $email,
                    'phone'      => $phone,
                    'count'      => 0,
                    'total'      => 0.0,
                    'first_at'   => $b->created_at,
                    'last_at'    => $b->created_at,
                ];
            }

            $p = &$people[$key];
            $p['count']++;
            $p['total'] += (float) $b->total;
            $p['first_at'] = min($p['first_at'], $b->created_at);
            $p['last_at']  = max($p['last_at'], $b->created_at);
            // Later bookings carry fresher contact details.
            $p['first_name'] = $b->first_name ?: $p['first_name'];
            $p['last_name']  = $b->last_name ?: $p['last_name'];
            $p['phone']      = $phone ?: $p['phone'];
            unset($p);
        }

        foreach ($people as $p) {
            $existing = VendorCustomer::withoutVendorScope()
                ->where('vendor_id', $vendorId)
                ->where(function ($q) use ($p) {
                    if ($p['email']) {
                        $q->orWhere('email', $p['email']);
                    }
                    if ($p['phone']) {
                        $q->orWhere('phone', $p['phone']);
                    }
                })
                ->first();

            $payload = [
                'vendor_id'        => $vendorId,
                'user_id'          => $p['user_id'],
                'first_name'       => $p['first_name'],
                'last_name'        => $p['last_name'],
                'email'            => $p['email'],
                'phone'            => $p['phone'],
                'bookings_count'   => $p['count'],
                'total_spent'      => round($p['total'], 2),
                'first_booking_at' => $p['first_at'],
                'last_booking_at'  => $p['last_at'],
                'source'           => 'booking',
            ];

            if ($existing) {
                // Never overwrite manually curated fields with nulls.
                $existing->fill(array_filter($payload, fn ($v) => $v !== null));
                $existing->save();
                $updated++;
            } else {
                VendorCustomer::withoutVendorScope()->create($payload);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }
}
