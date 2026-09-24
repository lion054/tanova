<?php

namespace Modules\Api\Controllers\Vendor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\MarketplaceListing;
use Modules\Vendor\Models\VendorShelfPin;
use Modules\Vendor\Services\Occupancy;
use Modules\Vendor\Services\Shelves;

/** Occupancy, the trending pins, and which experiences AI assistants can discover. */
class VendorInsightsController extends VendorApiController
{
    /** How full your tours are over the coming days: seats sold against seats available, on the days that are running. */
    public function occupancy(Request $request, Occupancy $occ): JsonResponse
    {
        $d = $request->validate(['days' => ['nullable', 'integer', 'min:1', 'max:120']]);

        return $this->success($occ->forVendor($this->vendorId(), (int) ($d['days'] ?? 30)) + ['days' => (int) ($d['days'] ?? 30)]);
    }

    public function pins(): JsonResponse
    {
        $rows = VendorShelfPin::orderBy('id')->get()->groupBy('shelf');
        $out = [];
        foreach (array_keys(VendorShelfPin::SHELVES) as $shelf) {
            $out[$shelf] = ($rows[$shelf] ?? collect())->pluck('object_id')->map(fn ($i) => (int) $i)->values()->all();
        }

        return $this->success($out);
    }

    public function pin(string $shelf, int $tourId, Shelves $shelves): JsonResponse
    {
        abort_unless(isset(VendorShelfPin::SHELVES[$shelf]), 404);
        abort_unless(Tour::forVendor()->whereKey($tourId)->exists(), 404);
        $shelves->pin($shelf === 'bestsellers' ? 'bestseller' : $shelf, $this->vendorId(), $tourId, true);

        return $this->pins();
    }

    public function unpin(string $shelf, int $tourId, Shelves $shelves): JsonResponse
    {
        abort_unless(isset(VendorShelfPin::SHELVES[$shelf]), 404);
        $shelves->pin($shelf === 'bestsellers' ? 'bestseller' : $shelf, $this->vendorId(), $tourId, false);

        return $this->noContent();
    }

    // ── Marketplace ───────────────────────────────────────────────────────────

    public function marketplace(Request $request): JsonResponse
    {
        $q = Tour::forVendor()->where('status', 'publish');
        \App\Support\ListQuery::search($q, $request->query('q'), ['title'], 'id');
        $on = MarketplaceListing::where('vendor_id', $this->vendorId())->where('object_model', 'tour')->where('visible', true)->pluck('object_id')->all();
        if ($request->query('state') === 'on') {
            $q->whereIn('id', $on ?: [0]);
        } elseif ($request->query('state') === 'off') {
            $q->whereNotIn('id', $on);
        }
        \App\Support\ListQuery::sort($q, $request->query('sort'), ['newest' => ['id', 'desc'], 'title' => ['title', 'asc']], 'newest');

        return $this->page($q, $request, fn ($t) => ['tour_id' => (int) $t->id, 'title' => trim((string) $t->title), 'price' => (float) $t->price, 'on_marketplace' => in_array($t->id, $on, true)]);
    }

    public function setMarketplace(Request $request, int $tourId): JsonResponse
    {
        $d = $request->validate(['on' => ['required', 'boolean']]);
        abort_unless(Tour::forVendor()->where('status', 'publish')->whereKey($tourId)->exists(), 404);
        MarketplaceListing::updateOrCreate(['vendor_id' => $this->vendorId(), 'object_model' => 'tour', 'object_id' => $tourId], ['visible' => (bool) $d['on']]);

        return $this->success(['tour_id' => $tourId, 'on_marketplace' => (bool) $d['on']]);
    }
}
