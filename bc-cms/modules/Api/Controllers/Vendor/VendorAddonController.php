<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Vendor\Models\VendorUpsell;
use Modules\Vendor\Services\UpsellCatalog;

/**
 * The add-on catalogue you maintain: photography, transfers, extra activities. (`GET /upsells` is the guest-facing
 * list of what to offer next to one service; this is where the catalogue itself is managed.)
 */
class VendorAddonController extends VendorApiController
{
    public function __construct(private UpsellCatalog $catalog) {}

    public function index(Request $request): JsonResponse
    {
        $q = VendorUpsell::with('services');
        ListQuery::search($q, $request->query('q'), ['name', 'short_description']);
        if (in_array($request->query('category'), array_keys(VendorUpsell::CATEGORIES), true)) {
            $q->where('category', $request->query('category'));
        }
        if (in_array($request->query('status'), ['publish', 'draft'], true)) {
            $q->where('status', $request->query('status'));
        }
        if ($request->has('featured')) {
            $q->where('is_featured', filter_var($request->query('featured'), FILTER_VALIDATE_BOOLEAN));
        }
        ListQuery::sort($q, $request->query('sort'), ['order' => ['sort_order', 'asc'], 'name' => ['name', 'asc'], 'newest' => ['id', 'desc'], 'price_asc' => ['price', 'asc'], 'price_desc' => ['price', 'desc']], 'order');
        $names = null;

        return $this->page($q, $request, function (VendorUpsell $u) use (&$names) {
            return $this->shape($u);
        });
    }

    public function show(int $id): JsonResponse
    {
        return $this->success($this->shape(VendorUpsell::with('services')->findOrFail($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $u = VendorUpsell::create($this->fields($request, true));
        $this->linkServices($request, $u);

        return $this->created($this->shape($u->load('services')));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $u = VendorUpsell::findOrFail($id);
        $u->update($this->fields($request, false));
        if ($request->has('services') || $request->has('is_global')) {
            $this->linkServices($request, $u->fresh());
        }

        return $this->success($this->shape($u->fresh()->load('services')));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->catalog->delete(VendorUpsell::findOrFail($id));

        return $this->noContent();
    }

    private function fields(Request $request, bool $create): array
    {
        $req = $create ? 'required' : 'sometimes';
        $d = $request->validate([
            'name'              => [$req, 'string', 'max:191'],
            'category'          => [$req, Rule::in(array_keys(VendorUpsell::CATEGORIES))],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'image_id'          => ['nullable', 'integer', Rule::exists('media_files', 'id')],
            'price'             => [$req, 'numeric', 'min:0', 'max:1000000'],
            'price_type'        => [$req, Rule::in(array_keys(VendorUpsell::PRICE_TYPES))],
            'sort_order'        => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status'            => ['sometimes', Rule::in(['publish', 'draft'])],
            'is_featured'       => ['sometimes', 'boolean'],
            'is_global'         => ['sometimes', 'boolean'],
            'services'                  => ['nullable', 'array', 'max:200'],
            'services.*.object_model'   => ['required_with:services', Rule::in(VendorUpsell::SERVICE_TYPES)],
            'services.*.object_id'      => ['required_with:services', 'integer'],
            'services.*.price_override' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'services.*.is_highlighted' => ['nullable', 'boolean'],
        ]);
        unset($d['services']);
        if ($create) {
            $d += ['status' => 'publish', 'is_global' => !$request->has('services'), 'sort_order' => 0];
        }

        return $d;
    }

    /** Attach to the vendor's own services (anything not theirs is dropped). Global add-ons are offered everywhere. */
    private function linkServices(Request $request, VendorUpsell $u): void
    {
        $links = $u->is_global ? [] : $this->catalog->ownedLinks($this->vendorId(), (array) $request->input('services', []));
        $this->catalog->sync($u, $links);
    }

    private function shape(VendorUpsell $u): array
    {
        $links = $u->relationLoaded('services') ? $u->services : $u->services()->get();
        $titles = [];
        foreach ($links->groupBy('object_model') as $type => $group) {
            $table = UpsellCatalog::SERVICE_TABLES[$type] ?? null;
            if ($table) {
                $titles[$type] = DB::table($table)->whereIn('id', $group->pluck('object_id'))->pluck('title', 'id');
            }
        }

        return [
            'id' => $u->id, 'name' => $u->name, 'category' => $u->category, 'short_description' => $u->short_description, 'description' => $u->description,
            'image_url' => $u->imageUrl('medium'), 'price' => (float) $u->price, 'price_type' => $u->price_type, 'price_label' => $u->priceLabel(),
            'status' => $u->status, 'is_featured' => (bool) $u->is_featured, 'is_global' => (bool) $u->is_global, 'sort_order' => (int) $u->sort_order,
            'services' => $u->is_global ? [] : $links->map(fn ($l) => [
                'object_model' => $l->object_model, 'object_id' => (int) $l->object_id, 'title' => isset($titles[$l->object_model][$l->object_id]) ? trim((string) $titles[$l->object_model][$l->object_id]) : null,
                'price_override' => $l->price_override !== null ? (float) $l->price_override : null, 'is_highlighted' => (bool) $l->is_highlighted,
            ])->values()->all(),
            'created_at' => optional($u->created_at)->toIso8601String(),
        ];
    }
}
