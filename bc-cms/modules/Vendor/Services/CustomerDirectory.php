<?php

namespace Modules\Vendor\Services;

use App\Support\ListQuery;
use Illuminate\Validation\ValidationException;
use Modules\Vendor\Models\VendorCustomer;

/** The vendor's customer records: how they are searched, filtered and cleaned. Used by the portal screen and the API. */
class CustomerDirectory
{
    public const TYPES = ['repeat' => 'Repeat guests', 'once' => 'Booked once', 'none' => 'No bookings yet'];
    public const SORTS = ['recent' => ['last_booking_at', 'desc'], 'name' => ['first_name', 'asc'], 'spent' => ['total_spent', 'desc'], 'bookings' => ['bookings_count', 'desc'], 'newest' => ['id', 'desc']];

    /** The vendor's customers, narrowed. Everything is optional; unknown values are ignored. */
    public function filtered(?string $q = null, ?string $type = null, ?string $tag = null, ?string $sort = null)
    {
        $base = VendorCustomer::query();
        ListQuery::search($base, $q, ['first_name', 'last_name', 'email', 'phone']);
        if ($type === 'repeat') {
            $base->where('bookings_count', '>', 1);
        } elseif ($type === 'once') {
            $base->where('bookings_count', 1);
        } elseif ($type === 'none') {
            $base->where(fn ($w) => $w->whereNull('bookings_count')->orWhere('bookings_count', 0));
        }
        if ($tag !== null && $tag !== '' && in_array($tag, $this->tags(), true)) {
            $base->whereJsonContains('tags', $tag);
        }
        ListQuery::sort($base, $sort, self::SORTS, 'recent');

        return $base;
    }

    /** Tags in use (free text), so a filter offers only real ones. */
    public function tags(): array
    {
        return VendorCustomer::whereNotNull('tags')->limit(500)->pluck('tags')->flatten()->filter()->unique()->sort()->values()->all();
    }

    /**
     * Turns validated input into what is stored: e-mail lower-cased, phone normalised, tags as a list.
     * A record needs an e-mail or a phone, or it could never be matched to a booking later.
     *
     * @throws ValidationException
     */
    public function clean(array $data, ?VendorCustomer $existing = null): array
    {
        $email = array_key_exists('email', $data) ? $data['email'] : $existing?->email;
        $phone = array_key_exists('phone', $data) ? $data['phone'] : $existing?->phone;
        if (empty($email) && empty($phone)) {
            throw ValidationException::withMessages(['email' => __('A customer needs at least an email or a phone number.')]);
        }
        if (array_key_exists('email', $data)) {
            $data['email'] = $data['email'] ? mb_strtolower(trim($data['email'])) : null;
        }
        if (array_key_exists('phone', $data)) {
            $data['phone'] = VendorCustomer::normalisePhone($data['phone']);
        }
        if (array_key_exists('tags', $data)) {
            $tags = is_array($data['tags']) ? $data['tags'] : explode(',', (string) $data['tags']);
            $data['tags'] = array_values(array_unique(array_filter(array_map(fn ($t) => trim((string) $t), $tags)))) ?: null;
        }

        return $data;
    }
}
