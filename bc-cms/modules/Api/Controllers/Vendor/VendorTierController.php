<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Services\ServiceTiers;

/**
 * GET /api/v/services/tours/{id}/tiers[?guests=N]: the ways to buy a tour (Classic,
 * Signature, Sublime), what each includes and costs. With ?guests, each also says
 * whether it takes that party and what it would cost.
 */
class VendorTierController extends Controller
{
    use ApiResponse;

    public function index(Request $request, int $id, ServiceTiers $tiers): JsonResponse
    {
        $tour = Tour::forVendor()->find($id);
        if (!$tour) {
            return $this->notFound('Tour');
        }
        $guests = $request->filled('guests') ? max(1, (int) $request->query('guests')) : null;

        return $this->success($tiers->forService('tour', (int) $tour->id)->map(fn ($t) => $tiers->shape($t, $guests))->values()->all());
    }

    /** Create or replace one option (classic, signature or sublime) of a tour. */
    public function save(Request $request, int $id, string $key, ServiceTiers $tiers): JsonResponse
    {
        $tour = Tour::forVendor()->find($id);
        if (!$tour) {
            return $this->notFound('Tour');
        }
        $d = $request->validate([
            'name'             => ['required', 'string', 'max:80'],
            'tagline'          => ['nullable', 'string', 'max:160'],
            'description'      => ['nullable', 'string', 'max:3000'],
            'price'            => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'price_per_person' => ['nullable', 'boolean'],
            'min_guests'       => ['nullable', 'integer', 'min:1', 'max:1000'],
            'max_guests'       => ['nullable', 'integer', 'min:1', 'max:1000', 'gte:min_guests'],
            'bands'            => ['nullable', 'array', 'max:12'],
            'bands.*.min'      => ['required_with:bands', 'integer', 'min:1'],
            'bands.*.max'      => ['nullable', 'integer', 'min:1'],
            'bands.*.total'    => ['required_with:bands', 'numeric', 'min:0.01'],
            'inclusions'       => ['nullable', 'array', 'max:30'],
            'inclusions.*'     => ['string', 'max:200'],
            'addon_ids'        => ['nullable', 'array', 'max:50'],
            'addon_ids.*'      => ['integer'],
            'recommended'      => ['nullable', 'boolean'],
            'active'           => ['nullable', 'boolean'],
        ]);
        $d['upsell_ids'] = $d['addon_ids'] ?? [];
        try {
            $tier = $tiers->save((int) $tour->id, $key, $d);
        } catch (\Modules\Vendor\Services\TierRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), $e->errorCode === 'unknown_tier' ? 404 : 422);
        }

        return $this->success($tiers->shape($tier));
    }

    public function destroy(int $id, string $key): JsonResponse
    {
        $tour = Tour::forVendor()->find($id);
        if (!$tour) {
            return $this->notFound('Tour');
        }
        $n = \Modules\Vendor\Models\VendorServiceTier::forService('tour', (int) $tour->id)->where('tier_key', $key)->delete();

        return $n ? response()->json(null, 204) : $this->notFound('Option');
    }
}
