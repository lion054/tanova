<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Illuminate\Validation\ValidationException;
use Modules\Vendor\Models\VendorCustomer;
use Modules\Vendor\Services\CustomerSyncService;

/**
 * Tanova port, phase 4 — vendor CRM.
 *
 * Records are mostly derived from bookings by CustomerSyncService; this screen
 * lets a vendor curate them (notes, tags, passport/nationality) and add walk-ins
 * by hand. Isolation is automatic via BelongsToVendor.
 */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->keepInStep();

        $base = VendorCustomer::query();
        $all = (clone $base)->count();
        ListQuery::search($base, $request->query('s'), ['first_name', 'last_name', 'email', 'phone']);

        $types = ['repeat' => __('Repeat guests'), 'once' => __('Booked once'), 'none' => __('No bookings yet')];
        $bar = FilterBar::make($request)->search('s', __('Search name, email or phone'))->select('type', __('Guests'), $types, __('All guests'));
        $type = (string) $request->query('type', '');
        if ($type === 'repeat') { $base->where('bookings_count', '>', 1); }
        elseif ($type === 'once') { $base->where('bookings_count', 1); }
        elseif ($type === 'none') { $base->where(fn ($q) => $q->whereNull('bookings_count')->orWhere('bookings_count', 0)); }

        // Tags are free text, so offer the ones this vendor actually uses.
        $tags = VendorCustomer::whereNotNull('tags')->limit(500)->pluck('tags')->flatten()->filter()->unique()->sort()->values()->all();
        if ($tags) {
            $bar->select('tag', __('Tag'), array_combine($tags, $tags), __('Any tag'));
            $tag = (string) $request->query('tag', '');
            if (in_array($tag, $tags, true)) { $base->whereJsonContains('tags', $tag); }
        }

        ListQuery::sort($base, $request->query('sort'), ['recent' => ['last_booking_at', 'desc'], 'name' => ['first_name', 'asc'], 'spent' => ['total_spent', 'desc'], 'bookings' => ['bookings_count', 'desc'], 'newest' => ['id', 'desc']], 'recent');
        $bar->sort(['recent' => __('Last booked'), 'name' => __('Name A to Z'), 'spent' => __('Most spent'), 'bookings' => __('Most bookings'), 'newest' => __('Newest added')], 'recent')->perPage([25, 50, 100])->noun(__('customers'));
        $fb = $bar->total((clone $base)->reorder()->count(), $all)->toArray();
        $rows = $base->paginate(ListQuery::perPage($request, [25, 50, 100]))->withQueryString();

        return view('vendor.customers.index', [
            'rows'       => $rows,
            'fb'         => $fb,
            'stats'      => [
                'total'    => VendorCustomer::count(),
                'repeat'   => VendorCustomer::where('bookings_count', '>', 1)->count(),
                'revenue'  => (float) VendorCustomer::sum('total_spent'),
            ],
            'page_title' => __('Customers'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCustomer($request);
        $data['source'] = 'manual';

        VendorCustomer::create($data);

        return redirect()->route('vendor.customers.index')
            ->with('success', __('Customer added.'));
    }

    public function update(Request $request, VendorCustomer $customer)
    {
        $customer->update($this->validateCustomer($request, $customer));

        return redirect()->route('vendor.customers.index')
            ->with('success', __('Customer updated.'));
    }

    public function destroy(VendorCustomer $customer)
    {
        $customer->delete();

        return redirect()->route('vendor.customers.index')
            ->with('success', __('Customer deleted.'));
    }

    /** Rebuild the CRM from this vendor's bookings. Safe to run repeatedly. */
    public function sync(CustomerSyncService $service)
    {
        $vendorId = resolve_current_vendor_id();

        if (!$vendorId) {
            return back()->with('error', __('No vendor context.'));
        }

        $result = $service->syncVendor($vendorId);

        return redirect()->route('vendor.customers.index')->with('success', __(
            'Sync complete — :created added, :updated updated, :skipped bookings skipped (no email or phone).',
            $result
        ));
    }

    private function validateCustomer(Request $request, ?VendorCustomer $customer = null): array
    {
        $data = $request->validate([
            'first_name'      => ['nullable', 'string', 'max:191'],
            'last_name'       => ['nullable', 'string', 'max:191'],
            'email'           => ['nullable', 'email', 'max:191'],
            'phone'           => ['nullable', 'string', 'max:40'],
            'date_of_birth'   => ['nullable', 'date'],
            'nationality'     => ['nullable', 'string', 'max:80'],
            'passport_number' => ['nullable', 'string', 'max:60'],
            'notes'           => ['nullable', 'string', 'max:5000'],
            'tags'            => ['nullable', 'string', 'max:500'],
        ]);
        unset($data['tags']);

        $data = app(\Modules\Vendor\Services\CustomerDirectory::class)->clean($data + ['tags' => $request->input('tags')], $customer);

        return $data;
    }

    /**
     * The list follows the bookings without anyone pressing a button: when a booking has
     * changed since the list was last built, build it again (cheap, and safe to repeat).
     */
    private function keepInStep(): void
    {
        $vendorId = (int) resolve_current_vendor_id();
        $latest = \Illuminate\Support\Facades\DB::table('bc_bookings')->where('vendor_id', $vendorId)->where('status', '!=', 'draft')->max('updated_at');
        $key = "vendor_customers_synced_{$vendorId}";
        $last = \Illuminate\Support\Facades\Cache::get($key);
        if ($latest && (!$last || $latest > $last || VendorCustomer::count() === 0)) {
            try {
                app(\Modules\Vendor\Services\CustomerSyncService::class)->syncVendor($vendorId);
                \Illuminate\Support\Facades\Cache::forever($key, now()->toDateTimeString());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Customer sync failed: ' . $e->getMessage());
            }
        }
    }
}
