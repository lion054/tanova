<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Vendor\Models\VendorUpsell;

/**
 * GET /api/v/upsells
 *
 * The add-ons a vendor offers: on one service (?service_type=tour&service_id=29, its
 * own add-ons and the global ones, at the price that applies there), or all of
 * them. Highlighted add-ons come first, then featured, then the vendor's order.
 * Only published add-ons are shown.
 */
class VendorUpsellController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_type' => ['nullable', Rule::in(VendorUpsell::SERVICE_TYPES), 'required_with:service_id'],
            'service_id'   => ['nullable', 'integer', 'required_with:service_type'],
            'category'     => ['nullable', Rule::in(array_keys(VendorUpsell::CATEGORIES))],
        ]);

        if (!empty($data['service_type'])) {
            $rows = VendorUpsell::offeredOn($data['service_type'], (int) $data['service_id']);
        } else {
            $rows = VendorUpsell::where('status', 'publish')
                ->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (VendorUpsell $u) => [
                    'upsell' => $u, 'price' => (float) $u->price, 'highlighted' => false,
                ]);
        }

        if (!empty($data['category'])) {
            $rows = $rows->filter(fn ($r) => $r['upsell']->category === $data['category'])->values();
        }

        return $this->success($rows->map(fn ($r) => $this->shape($r['upsell'], $r['price'], $r['highlighted']))->values()->all());
    }

    private function shape(VendorUpsell $u, float $price, bool $highlighted): array
    {
        return [
            'id'                => $u->id,
            'name'              => $u->name,
            'category'          => $u->category,
            'short_description' => $u->short_description,
            'description'       => $u->description,
            'image'             => $u->imageUrl('medium'),
            'price'             => $price,
            'price_type'        => $u->price_type,
            'price_label'       => $u->priceLabel($price),
            'is_featured'       => (bool) $u->is_featured,
            'is_highlighted'    => $highlighted,
        ];
    }
}
