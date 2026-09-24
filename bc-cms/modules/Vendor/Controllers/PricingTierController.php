<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\VendorPricingTier;
use Modules\Vendor\Models\VendorServiceTier;
use Modules\Vendor\Models\VendorUpsell;
use Modules\Vendor\Services\ServiceTiers;

/**
 * Phase 1 — Vendor self-service management of pricing tiers (markup rules).
 * Isolation is automatic: VendorPricingTier uses BelongsToVendor, so every query
 * is scoped to the current vendor and vendor_id is auto-stamped on create.
 */
class PricingTierController extends Controller
{
    public function index(Request $request)
    {
        $rows = VendorPricingTier::orderBy('sort_order')->orderBy('id')->get();

        // Package tiers: Classic / Signature / Sublime for one tour at a time.
        $tours = Tour::forVendor(resolve_current_vendor_id())->orderBy('title')->get(['id', 'title', 'price']);
        $tour = $tours->firstWhere('id', (int) $request->input('tour')) ?: $tours->first();
        $tiers = $tour ? VendorServiceTier::forService('tour', (int) $tour->id)->get()->keyBy('tier_key') : collect();

        return view('vendor.pricing-tiers.index', [
            'rows'       => $rows,
            'tours'      => $tours,
            'tour'       => $tour,
            'tiers'      => $tiers,
            'upsells'    => VendorUpsell::where('status', 'publish')->orderBy('name')->get(['id', 'name']),
            'calc'       => app(ServiceTiers::class),
            'keys'       => VendorServiceTier::KEYS,
            'page_title' => __('Pricing'),
        ]);
    }

    /** Create or change one tier of a tour. */
    public function saveTier(Request $request)
    {
        $data = $request->validate([
            'tour_id'          => ['required', 'integer'],
            'tier_key'         => ['required', 'in:' . implode(',', array_keys(VendorServiceTier::KEYS))],
            'name'             => ['required', 'string', 'max:80'],
            'tagline'          => ['nullable', 'string', 'max:160'],
            'description'      => ['nullable', 'string', 'max:3000'],
            'price'            => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'price_per_person' => ['required', 'in:0,1'],
            'min_guests'       => ['nullable', 'integer', 'min:1', 'max:1000'],
            'max_guests'       => ['nullable', 'integer', 'min:1', 'max:1000', 'gte:min_guests'],
            'bands'            => ['nullable', 'array', 'max:12'],
            'bands.*.min'      => ['nullable', 'integer', 'min:1'],
            'bands.*.max'      => ['nullable', 'integer', 'min:1'],
            'bands.*.total'    => ['nullable', 'numeric', 'min:0'],
            'inclusions'       => ['nullable', 'string', 'max:3000'],
            'upsell_ids'       => ['nullable', 'array'],
            'upsell_ids.*'     => ['integer'],
        ]);

        $tour = Tour::forVendor(resolve_current_vendor_id())->find($data['tour_id']);
        if (!$tour) {
            abort(404);
        }

        try {
            $tier = app(ServiceTiers::class)->save((int) $tour->id, $data['tier_key'], [
                'name' => $data['name'], 'tagline' => $data['tagline'] ?? null, 'description' => $data['description'] ?? null,
                'price' => (float) ($data['price'] ?? 0), 'price_per_person' => $data['price_per_person'] === '1',
                'min_guests' => $data['min_guests'] ?? null, 'max_guests' => $data['max_guests'] ?? null,
                'bands' => $data['bands'] ?? [], 'inclusions' => (string) ($data['inclusions'] ?? ''), 'upsell_ids' => $data['upsell_ids'] ?? [],
                'recommended' => $request->boolean('recommended'), 'active' => $request->boolean('active', true),
            ]);
        } catch (\Modules\Vendor\Services\TierRuleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('vendor.pricing_tiers.index', ['tour' => $tour->id])->with('success', __(':name saved.', ['name' => $tier->name]));
    }

    public function deleteTier(VendorServiceTier $tier)
    {
        $tourId = $tier->object_id;
        $tier->delete();

        return redirect()->route('vendor.pricing_tiers.index', ['tour' => $tourId])->with('success', __('Tier removed. Bookings already made keep their price.'));
    }

    public function store(Request $request)
    {
        $data = $this->validateTier($request);

        VendorPricingTier::create($data);

        return redirect()->route('vendor.pricing_tiers.index')
            ->with('success', __('Pricing tier created.'));
    }

    public function update(Request $request, VendorPricingTier $pricingTier)
    {
        $data = $this->validateTier($request);

        $pricingTier->update($data);

        return redirect()->route('vendor.pricing_tiers.index')
            ->with('success', __('Pricing tier updated.'));
    }

    public function destroy(VendorPricingTier $pricingTier)
    {
        $pricingTier->delete();

        return redirect()->route('vendor.pricing_tiers.index')
            ->with('success', __('Pricing tier deleted.'));
    }

    private function validateTier(Request $request): array
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:191'],
            'markup_type'  => ['required', 'in:percentage,fixed'],
            'markup_value' => ['required', 'numeric'],
            'is_default'   => ['nullable', 'boolean'],
            'sort_order'   => ['nullable', 'integer'],
            'status'       => ['nullable', 'in:publish,draft'],
        ]);

        $data['slug']       = \Illuminate\Support\Str::slug($data['name']);
        $data['is_default'] = $request->boolean('is_default');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['status']     = $data['status'] ?? 'publish';

        return $data;
    }
}
