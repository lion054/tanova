<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Vendor\Models\LoyaltyAccount;
use Modules\Vendor\Models\LoyaltyRule;
use Modules\Vendor\Models\LoyaltyTier;
use Modules\Vendor\Models\LoyaltyTransaction;
use Modules\Vendor\Services\LoyaltyPoints;

/** Loyalty: the earning rule, tiers, members and manual point adjustments. */
class VendorLoyaltyController extends VendorApiController
{
    public function __construct(private LoyaltyPoints $points) {}

    // ── Rule ──────────────────────────────────────────────────────────────────

    public function rule(): JsonResponse
    {
        return $this->success($this->ruleShape(LoyaltyRule::current()));
    }

    public function saveRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled'         => ['sometimes', 'boolean'],
            'spend_per_point' => ['sometimes', 'numeric', 'min:0.01', 'max:100000'],
        ]);
        $rule = LoyaltyRule::first() ?? new LoyaltyRule();
        $rule->fill([
            'enabled'         => $data['enabled'] ?? $rule->enabled ?? true,
            'spend_per_point' => $data['spend_per_point'] ?? $rule->spend_per_point ?? 10,
        ])->save();

        return $this->success($this->ruleShape($rule));
    }

    // ── Tiers ─────────────────────────────────────────────────────────────────

    public function tiers(): JsonResponse
    {
        return $this->success(LoyaltyTier::orderBy('min_points')->get()->map(fn ($t) => $this->tierShape($t))->all());
    }

    public function storeTier(Request $request): JsonResponse
    {
        $tier = LoyaltyTier::create($this->tierData($request, true));
        $this->points->retier($this->vendorId());

        return $this->created($this->tierShape($tier));
    }

    public function updateTier(Request $request, int $id): JsonResponse
    {
        $tier = LoyaltyTier::findOrFail($id);
        $tier->update($this->tierData($request, false));
        $this->points->retier($this->vendorId());

        return $this->success($this->tierShape($tier->fresh()));
    }

    public function destroyTier(int $id): JsonResponse
    {
        LoyaltyTier::findOrFail($id)->delete();
        $this->points->retier($this->vendorId());

        return $this->noContent();
    }

    // ── Members ───────────────────────────────────────────────────────────────

    public function members(Request $request): JsonResponse
    {
        $tiers = LoyaltyTier::orderBy('min_points')->get();
        $q = LoyaltyAccount::with('tier');
        ListQuery::search($q, $request->query('q'), ['customer_name', 'customer_email']);
        $tier = (string) $request->query('tier', '');
        if ($tier === 'none') {
            $q->whereNull('tier_id');
        } elseif ($tier !== '' && ctype_digit($tier)) {
            $q->where('tier_id', (int) $tier);
        }
        ListQuery::sort($q, $request->query('sort'), ['points' => ['points', 'desc'], 'least' => ['points', 'asc'], 'name' => ['customer_name', 'asc'], 'recent' => ['updated_at', 'desc']], 'points');

        $p = $q->paginate(ListQuery::perPage($request, [10, 25, 50, 100], 25));
        $stats = $this->points->stats($this->vendorId(), collect($p->items())->pluck('customer_email')->map(fn ($e) => strtolower($e))->all());

        return response()->json([
            'data' => collect($p->items())->map(fn ($a) => $this->memberShape($a, $tiers, $stats->get(strtolower($a->customer_email))))->all(),
            'meta' => ['page' => $p->currentPage(), 'per_page' => $p->perPage(), 'total' => $p->total(), 'last_page' => $p->lastPage()],
        ]);
    }

    public function member(int $id): JsonResponse
    {
        $a = LoyaltyAccount::with('tier')->findOrFail($id);
        $stats = $this->points->stats($this->vendorId(), [strtolower($a->customer_email)]);
        $out = $this->memberShape($a, LoyaltyTier::orderBy('min_points')->get(), $stats->get(strtolower($a->customer_email)));
        $out['history'] = LoyaltyTransaction::where('account_id', $a->id)->orderByDesc('id')->limit(50)->get()->map(fn ($t) => [
            'id' => $t->id, 'points' => (int) $t->points, 'type' => $t->type, 'reason' => $t->reason, 'booking_id' => $t->booking_id, 'created_at' => $t->created_at?->toIso8601String(),
        ])->all();

        return $this->success($out);
    }

    /** Give or take points by hand. */
    public function adjust(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'  => ['required', 'email', 'max:191'],
            'name'   => ['nullable', 'string', 'max:191'],
            'points' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'reason' => ['nullable', 'string', 'max:191'],
        ]);
        $a = $this->points->adjust($this->vendorId(), $data['email'], $data['name'] ?? null, (int) $data['points'], $data['reason'] ?? null);
        $stats = $this->points->stats($this->vendorId(), [strtolower($a->customer_email)]);

        return $this->created($this->memberShape($a->load('tier'), LoyaltyTier::orderBy('min_points')->get(), $stats->get(strtolower($a->customer_email))));
    }

    // ── Shapes ────────────────────────────────────────────────────────────────

    private function ruleShape(LoyaltyRule $r): array
    {
        return ['enabled' => (bool) $r->enabled, 'spend_per_point' => (float) $r->spend_per_point];
    }

    private function tierShape(LoyaltyTier $t): array
    {
        return ['id' => $t->id, 'name' => $t->name, 'min_points' => (int) $t->min_points, 'earn_multiplier' => (float) $t->earn_multiplier, 'perks' => $t->perks];
    }

    private function memberShape(LoyaltyAccount $a, $tiers, $stats): array
    {
        $next = $tiers->first(fn ($t) => $t->min_points > $a->points);

        return [
            'id'        => $a->id,
            'email'     => $a->customer_email,
            'name'      => $a->customer_name,
            'points'    => (int) $a->points,
            'tier'      => $a->tier ? ['id' => $a->tier->id, 'name' => $a->tier->name] : null,
            'next_tier' => $next ? ['id' => $next->id, 'name' => $next->name, 'points_needed' => (int) ($next->min_points - $a->points)] : null,
            'trips'     => (int) ($stats->trips ?? 0),
            'spent'     => round((float) ($stats->spent ?? 0), 2),
            'last_trip' => $stats && $stats->last_trip ? substr((string) $stats->last_trip, 0, 10) : null,
            'created_at' => $a->created_at?->toIso8601String(),
        ];
    }

    private function tierData(Request $request, bool $create): array
    {
        $req = $create ? 'required' : 'sometimes';
        $data = $request->validate([
            'name'            => [$req, 'string', 'max:191'],
            'min_points'      => [$req, 'integer', 'min:0', 'max:100000000'],
            'earn_multiplier' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'perks'           => ['nullable', 'string', 'max:2000'],
        ]);
        if ($create) {
            $data['earn_multiplier'] = $data['earn_multiplier'] ?? 1;
        }

        return $data;
    }
}
