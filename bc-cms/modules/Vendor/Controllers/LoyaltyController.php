<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\LoyaltyAccount;
use Modules\Vendor\Models\LoyaltyRule;
use Modules\Vendor\Models\LoyaltyTier;
use Modules\Vendor\Models\LoyaltyTransaction;

/**
 * Phase 3 — Loyalty program management for the current vendor.
 * All models use BelongsToVendor → auto-scoped + auto-stamped.
 */
class LoyaltyController extends Controller
{
    public function index(Request $request)
    {
        $tiers = LoyaltyTier::orderBy('min_points')->get();
        $base = LoyaltyAccount::with('tier');
        $everything = LoyaltyAccount::count();
        ListQuery::search($base, $request->query('s'), ['customer_name', 'customer_email']);
        $tierOpts = $tiers->pluck('name', 'id')->map(fn ($n) => (string) $n)->all() + ['none' => __('No tier yet')];
        $t = (string) $request->query('tier', '');
        if ($t === 'none') { $base->whereNull('tier_id'); } elseif (isset($tierOpts[$t]) && $t !== '') { $base->where('tier_id', (int) $t); }
        ListQuery::sort($base, $request->query('sort'), ['points' => ['points', 'desc'], 'name' => ['customer_name', 'asc'], 'recent' => ['updated_at', 'desc'], 'least' => ['points', 'asc']], 'points');
        $fb = FilterBar::make($request)->search('s', __('Search members'))->select('tier', __('Tier'), $tierOpts, __('Any tier'))
            ->sort(['points' => __('Most points'), 'least' => __('Fewest points'), 'name' => __('Name A to Z'), 'recent' => __('Recently active')], 'points')
            ->perPage()->noun(__('members'))->total((clone $base)->reorder()->count(), $everything)->toArray();
        $accounts = $base->paginate(ListQuery::perPage($request))->withQueryString();

        // What each member has done with us, from the bookings themselves.
        $trips = app(\Modules\Vendor\Services\LoyaltyPoints::class)->stats((int) resolve_current_vendor_id(), $accounts->pluck('customer_email')->map(fn ($e) => strtolower($e))->all());

        return view('vendor.loyalty.index', [
            'fb'         => $fb,
            'tiers'      => $tiers,
            'accounts'   => $accounts,
            'trips'      => $trips,
            'history'    => LoyaltyTransaction::whereIn('account_id', $accounts->pluck('id'))->orderByDesc('id')->get()->groupBy('account_id'),
            'rule'       => LoyaltyRule::current(),
            'stats'      => [
                'members' => LoyaltyAccount::count(),
                'points'  => (int) LoyaltyAccount::sum('points'),
                'top'     => optional($tiers->last())->name,
            ],
            'page_title' => __('Loyalty'),
        ]);
    }

    public function saveRule(Request $request)
    {
        $data = $request->validate(['spend_per_point' => ['required', 'numeric', 'min:0.01', 'max:100000']]);
        $rule = LoyaltyRule::first() ?? new LoyaltyRule();
        $rule->fill(['enabled' => $request->boolean('enabled'), 'spend_per_point' => $data['spend_per_point']])->save();

        return back()->with('success', __('Earning rule saved.'));
    }

    public function storeTier(Request $request)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:191'],
            'min_points'      => ['required', 'integer', 'min:0'],
            'earn_multiplier' => ['nullable', 'numeric', 'min:0'],
            'perks'           => ['nullable', 'string'],
        ]);

        $data['earn_multiplier'] = $data['earn_multiplier'] ?? 1;

        LoyaltyTier::create($data);

        return back()->with('success', __('Tier created.'));
    }

    public function destroyTier(LoyaltyTier $tier)
    {
        $tier->delete();
        app(\Modules\Vendor\Services\LoyaltyPoints::class)->retier((int) resolve_current_vendor_id());

        return back()->with('success', __('Tier deleted.'));
    }

    /** Manually grant or redeem points for a customer (by email). */
    public function adjust(Request $request)
    {
        $data = $request->validate([
            'customer_email' => ['required', 'email', 'max:191'],
            'customer_name'  => ['nullable', 'string', 'max:191'],
            'points'         => ['required', 'integer'],
            'reason'         => ['nullable', 'string', 'max:191'],
        ]);

        app(\Modules\Vendor\Services\LoyaltyPoints::class)->adjust((int) resolve_current_vendor_id(), $data['customer_email'], $data['customer_name'] ?? null, (int) $data['points'], $data['reason'] ?? null);

        return back()->with('success', __('Points updated.'));
    }
}
