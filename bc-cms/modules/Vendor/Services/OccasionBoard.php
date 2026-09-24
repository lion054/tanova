<?php

namespace Modules\Vendor\Services;

use App\Support\ListQuery;
use Illuminate\Support\Collection;
use Modules\Vendor\Models\VendorOccasion;

/** Birthdays and anniversaries, soonest first, narrowed the same way on the screen and in the API. */
class OccasionBoard
{
    /**
     * @param array{q?:?string,type?:?string,month?:?string,mail?:?string,within?:?string,sort?:?string} $f
     * @return Collection<int,VendorOccasion>
     */
    public function all(array $f): Collection
    {
        $base = VendorOccasion::query();
        ListQuery::search($base, $f['q'] ?? null, ['customer_name', 'customer_email']);
        if (in_array($f['type'] ?? null, VendorOccasion::TYPES, true)) {
            $base->where('type', $f['type']);
        }
        if (isset($f['month']) && ctype_digit((string) $f['month']) && (int) $f['month'] >= 1 && (int) $f['month'] <= 12) {
            $base->whereMonth('occasion_date', (int) $f['month']);
        }
        if (($f['mail'] ?? null) === 'yes') {
            $base->whereNotNull('customer_email')->where('customer_email', '!=', '');
        } elseif (($f['mail'] ?? null) === 'no') {
            $base->where(fn ($q) => $q->whereNull('customer_email')->orWhere('customer_email', ''));
        }

        $rows = $base->get();
        $within = (int) ($f['within'] ?? 0);
        if (in_array($within, [7, 30, 90], true)) {
            $rows = $rows->filter(fn ($o) => $o->nextOn()->lte(now()->startOfDay()->addDays($within)));
        }
        $rows = ($f['sort'] ?? 'soonest') === 'name'
            ? $rows->sortBy(fn ($o) => mb_strtolower($o->customer_name))
            : $rows->sortBy(fn ($o) => $o->nextOn()->timestamp);

        return $rows->values();
    }
}
