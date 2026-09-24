<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\DB;
use Modules\Vendor\Models\VendorUpsell;
use Modules\Vendor\Models\VendorUpsellService;

/** Where an add-on is offered: which of the vendor's own services it is attached to, and at what price. */
class UpsellCatalog
{
    public const SERVICE_TABLES = ['tour' => 'bc_tours', 'hotel' => 'bc_hotels', 'car' => 'bc_cars', 'boat' => 'bc_boats', 'event' => 'bc_events', 'space' => 'bc_spaces'];

    /**
     * Keeps only services the vendor owns; anything else is dropped.
     *
     * @param array<int,array{object_model:string,object_id:int,price_override?:mixed,is_highlighted?:mixed}> $services
     */
    public function ownedLinks(int $vendorId, array $services): array
    {
        $out = [];
        foreach (array_values($services) as $i => $s) {
            $table = self::SERVICE_TABLES[$s['object_model']] ?? null;
            if (!$table || !DB::table($table)->where('id', (int) $s['object_id'])->where('author_id', $vendorId)->exists()) {
                continue;
            }
            $out[] = [
                'object_model'   => $s['object_model'],
                'object_id'      => (int) $s['object_id'],
                'price_override' => isset($s['price_override']) && $s['price_override'] !== '' ? (float) $s['price_override'] : null,
                'is_highlighted' => !empty($s['is_highlighted']),
                'sort_order'     => $i,
            ];
        }

        return $out;
    }

    public function sync(VendorUpsell $upsell, array $links): void
    {
        VendorUpsellService::where('upsell_id', $upsell->id)->delete();
        foreach ($links as $l) {
            VendorUpsellService::create(['upsell_id' => $upsell->id] + $l);
        }
    }

    public function delete(VendorUpsell $upsell): void
    {
        VendorUpsellService::where('upsell_id', $upsell->id)->delete();
        $upsell->delete();
    }
}
