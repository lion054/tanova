<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingUpsell;
use Modules\Vendor\Models\VendorUpsell;
use Modules\Vendor\Models\VendorUpsellService;

/**
 * Add-ons and upsells: the vendor's catalogue of extras (photography, a transfer,
 * equipment), where each is offered, and attaching them to a booking.
 * Catalogue models use BelongsToVendor (auto-scoped). Bookings are not tenant-scoped
 * at the model layer, so they are resolved explicitly against the current vendor.
 */
class UpsellController extends Controller
{
    /** Tables the "offered on" picker searches, by service type. */
    private const SERVICE_TABLES = [
        'tour'  => ['bc_tours', 'Activity or package'],
        'hotel' => ['bc_hotels', 'Stay'],
        'car'   => ['bc_cars', 'Transport'],
        'boat'  => ['bc_boats', 'Boat'],
        'event' => ['bc_events', 'Event'],
        'space' => ['bc_spaces', 'Space'],
    ];

    public function index(Request $request)
    {
        $category = (string) $request->query('category', '');
        $status   = (string) $request->query('status', '');
        $search   = trim((string) $request->query('q', ''));

        $all = VendorUpsell::with('services')->orderBy('sort_order')->orderBy('id')->get();

        $rows = $all
            ->when($category !== '', fn ($c) => $c->where('category', $category))
            ->when($status !== '', fn ($c) => $c->where('status', $status))
            ->when($search !== '', fn ($c) => $c->filter(fn ($u) => stripos($u->name . ' ' . $u->short_description, $search) !== false))
            ->values();

        return view('vendor.upsells.index', [
            'rows'       => $rows,
            'counts'     => $all->groupBy('category')->map->count(),
            'total'      => $all->count(),
            'category'   => $category,
            'status'     => $status,
            'search'     => $search,
            'serviceLabels' => $this->serviceNames($all),
            'page_title' => __('Upsells & Add-ons'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUpsell($request);
        $upsell = VendorUpsell::create($data['upsell']);
        $this->syncServices($upsell, $data['services']);

        return redirect()->route('vendor.upsells.index')
            ->with('success', __('Add-on created.'));
    }

    public function update(Request $request, VendorUpsell $upsell)
    {
        $data = $this->validateUpsell($request);
        $upsell->update($data['upsell']);
        $this->syncServices($upsell, $data['services']);

        return redirect()->route('vendor.upsells.index')
            ->with('success', __('Add-on updated.'));
    }

    public function destroy(VendorUpsell $upsell)
    {
        app(\Modules\Vendor\Services\UpsellCatalog::class)->delete($upsell);

        return redirect()->route('vendor.upsells.index')
            ->with('success', __('Add-on deleted.'));
    }

    /** Publish or hide an add-on without opening it. */
    public function toggle(VendorUpsell $upsell)
    {
        $upsell->update(['status' => $upsell->status === 'publish' ? 'draft' : 'publish']);

        return back()->with('success', $upsell->status === 'publish'
            ? __(':name is on again.', ['name' => $upsell->name])
            : __(':name is hidden.', ['name' => $upsell->name]));
    }

    /** Star or unstar: featured add-ons are offered first. */
    public function feature(VendorUpsell $upsell)
    {
        $upsell->update(['is_featured' => !$upsell->is_featured]);

        return back();
    }

    /** JSON for the "offered on" picker: this vendor's services matching a search. */
    public function services(Request $request): JsonResponse
    {
        $q    = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', 'tour');
        abort_unless(isset(self::SERVICE_TABLES[$type]), 422);

        [$table, $label] = self::SERVICE_TABLES[$type];
        $rows = DB::table($table)
            ->where('author_id', resolve_current_vendor_id())
            ->where('status', 'publish')
            ->whereNull('deleted_at')
            ->when($q !== '', fn ($query) => $query->where('title', 'like', '%' . $q . '%'))
            ->orderBy('title')
            ->limit(30)
            ->get(['id', 'title']);

        return response()->json([
            'data' => $rows->map(fn ($r) => [
                'object_model' => $type,
                'object_id'    => (int) $r->id,
                'title'        => trim((string) $r->title),
                'type_label'   => __($label),
            ])->all(),
        ]);
    }

    /** Attach an add-on to a booking, snapshotting its name and the price that applied. */
    public function attach(Request $request, $bookingId)
    {
        $booking = $this->vendorBooking($bookingId);

        $request->validate([
            'upsell_id' => ['required', 'integer'],
            'qty'       => ['nullable', 'integer', 'min:1'],
        ]);

        // findOrFail respects the vendor global scope → cross-vendor catalog 404s.
        $upsell = VendorUpsell::findOrFail($request->integer('upsell_id'));
        app(\Modules\Vendor\Services\BookingAddons::class)->attach($booking, $upsell, max(1, $request->integer('qty', 1)));

        return back()->with('success', __('Add-on attached to booking.'));
    }

    public function detach($id)
    {
        $item = BookingUpsell::findOrFail($id);
        $booking = Booking::where('vendor_id', resolve_current_vendor_id())->find($item->booking_id);
        if ($booking) {
            app(\Modules\Vendor\Services\BookingAddons::class)->detach($booking, $item);
        } else {
            $item->delete();
        }

        return back()->with('success', __('Add-on removed from booking.'));
    }

    /**
     * @return array{upsell: array<string,mixed>, services: array<int,array<string,mixed>>}
     */
    private function validateUpsell(Request $request): array
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:191'],
            'category'          => ['required', Rule::in(array_keys(VendorUpsell::CATEGORIES))],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'image_id'          => ['nullable', 'integer'],
            'price'             => ['required', 'numeric', 'min:0', 'max:1000000'],
            'price_type'        => ['required', Rule::in(array_keys(VendorUpsell::PRICE_TYPES))],
            'sort_order'        => ['nullable', 'integer'],
            'is_featured'       => ['nullable', 'boolean'],
            'offered'           => ['required', Rule::in(['everywhere', 'services'])],
            'services'                    => ['nullable', 'array', 'max:200'],
            'services.*.object_model'     => ['required', Rule::in(VendorUpsell::SERVICE_TYPES)],
            'services.*.object_id'        => ['required', 'integer'],
            'services.*.price_override'   => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'services.*.is_highlighted'   => ['nullable', 'boolean'],
        ]);

        $global = $data['offered'] === 'everywhere';
        $upsell = [
            'name'              => trim($data['name']),
            'category'          => $data['category'],
            'short_description' => $data['short_description'] ?? null,
            'description'       => $data['description'] ?? null,
            'image_id'          => $data['image_id'] ?? null,
            'price'             => $data['price'],
            'price_type'        => $data['price_type'],
            'sort_order'        => (int) ($data['sort_order'] ?? 0),
            // The "On sale" switch sends nothing when it is off.
            'status'            => $request->has('status') ? 'publish' : 'draft',
            'is_featured'       => $request->boolean('is_featured'),
            'is_global'         => $global,
        ];

        // Only this vendor's own services can be picked; anything else is dropped.
        $services = $global ? [] : app(\Modules\Vendor\Services\UpsellCatalog::class)->ownedLinks((int) resolve_current_vendor_id(), (array) ($data['services'] ?? []));

        return ['upsell' => $upsell, 'services' => $services];
    }

    private function syncServices(VendorUpsell $upsell, array $services): void
    {
        app(\Modules\Vendor\Services\UpsellCatalog::class)->sync($upsell, $services);
    }

    /** "Bamba Tram" for each assigned service, so the list can say where an add-on is offered. */
    private function serviceNames($upsells): array
    {
        $names = [];
        $links = $upsells->flatMap->services;
        foreach ($links->groupBy('object_model') as $type => $group) {
            if (!isset(self::SERVICE_TABLES[$type])) {
                continue;
            }
            $rows = DB::table(self::SERVICE_TABLES[$type][0])->whereIn('id', $group->pluck('object_id'))->pluck('title', 'id');
            foreach ($group as $link) {
                $names[$type . ':' . $link->object_id] = trim((string) ($rows[$link->object_id] ?? ('#' . $link->object_id)));
            }
        }

        return $names;
    }

    private function vendorBooking($id): Booking
    {
        return Booking::where('vendor_id', resolve_current_vendor_id())->findOrFail($id);
    }
}
