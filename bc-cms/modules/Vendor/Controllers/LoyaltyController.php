<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Vendor\Models\LoyaltyAccount;
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
        return view('vendor.loyalty.index', [
            'tiers'      => LoyaltyTier::orderBy('min_points')->get(),
            'accounts'   => LoyaltyAccount::with('tier')->orderByDesc('points')->paginate(20),
            'page_title' => __('Loyalty'),
        ]);
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

        DB::transaction(function () use ($data) {
            $account = LoyaltyAccount::firstOrCreate(
                ['customer_email' => $data['customer_email']],
                ['customer_name' => $data['customer_name'] ?? null, 'points' => 0]
            );

            LoyaltyTransaction::create([
                'account_id' => $account->id,
                'points'     => $data['points'],
                'type'       => $data['points'] >= 0 ? 'earn' : 'redeem',
                'reason'     => $data['reason'] ?? null,
            ]);

            $account->points = max(0, $account->points + $data['points']);
            $account->tier_id = optional(LoyaltyTier::forPoints($account->points))->id;
            $account->save();
        });

        return back()->with('success', __('Points updated.'));
    }
}
