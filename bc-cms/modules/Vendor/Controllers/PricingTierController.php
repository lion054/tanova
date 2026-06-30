<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vendor\Models\VendorPricingTier;

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

        return view('vendor.pricing-tiers.index', [
            'rows'       => $rows,
            'page_title' => __('Pricing Tiers'),
        ]);
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
