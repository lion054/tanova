<?php

namespace Modules\Vendor\Services;

use App\Support\ListQuery;
use Modules\Vendor\Models\VendorWaitlist;

/** The waitlist, narrowed and ordered the same way on the portal screen and in the API. */
class WaitlistBoard
{
    public const STATUSES = ['waiting', 'notified', 'converted', 'cancelled', 'expired'];
    public const SORTS = ['newest' => ['id', 'desc'], 'day' => ['preferred_date', 'asc'], 'name' => ['customer_name', 'asc'], 'party' => ['party_size', 'desc']];

    /**
     * @param array{q?:?string,status?:?string,tour?:?int|string,from?:?string,to?:?string,sort?:?string} $f
     */
    public function query(array $f)
    {
        $base = VendorWaitlist::query();
        ListQuery::search($base, $f['q'] ?? null, ['customer_name', 'customer_email', 'customer_phone']);
        if (in_array($f['status'] ?? null, self::STATUSES, true)) {
            $base->where('status', $f['status']);
        }
        if (!empty($f['tour']) && ctype_digit((string) $f['tour'])) {
            $base->where('object_id', (int) $f['tour']);
        }
        if (!empty($f['from'])) {
            $base->whereDate('preferred_date', '>=', $f['from']);
        }
        if (!empty($f['to'])) {
            $base->whereDate('preferred_date', '<=', $f['to']);
        }
        // Queue order is the default: waiting first, longest waiting first. Everything else is a plain sort.
        $sort = (string) ($f['sort'] ?? 'queue');
        if (isset(self::SORTS[$sort])) {
            ListQuery::sort($base, $sort, self::SORTS, 'newest');
        } else {
            $base->orderByRaw("status = 'waiting' DESC")->orderBy('id');
        }

        return $base;
    }
}
