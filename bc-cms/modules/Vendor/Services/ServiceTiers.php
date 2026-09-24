<?php

namespace Modules\Vendor\Services;

use Modules\Vendor\Models\VendorServiceTier;
use Modules\Vendor\Models\VendorUpsell;

/**
 * What a tier costs for a party, and who it takes.
 *
 * A group band is a fixed total for a range of party sizes ("4 to 6 people: $2,400"),
 * and wins when the party falls in one. Otherwise the tier's price applies: per
 * person, or as one total for the whole group. A tier can limit the party size it
 * takes; outside that it is not offered.
 */
class ServiceTiers
{
    /** @return array{total: float, basis: string} basis = band | per_person | group */
    public function priceFor(VendorServiceTier $tier, int $guests): array
    {
        $guests = max(1, $guests);
        foreach ($this->bands($tier) as $b) {
            if ($guests >= $b['min'] && ($b['max'] === null || $guests <= $b['max'])) {
                return ['total' => round($b['total'], 2), 'basis' => 'band'];
            }
        }

        return $tier->price_per_person
            ? ['total' => round((float) $tier->price * $guests, 2), 'basis' => 'per_person']
            : ['total' => round((float) $tier->price, 2), 'basis' => 'group'];
    }

    /** Does the tier take this party size? */
    public function accepts(VendorServiceTier $tier, int $guests): bool
    {
        return ($tier->min_guests === null || $guests >= $tier->min_guests)
            && ($tier->max_guests === null || $guests <= $tier->max_guests);
    }

    /**
     * The "from" price shown to guests. A tier priced for the whole group with no bands
     * is shown as that group price. Otherwise everything is brought to a per-person
     * figure (a band's total spread over the largest party it covers), so a per-person
     * price and a group band are compared like with like, and the cheapest wins.
     */
    public function from(VendorServiceTier $tier): array
    {
        $bands = $this->bands($tier);
        if (!$tier->price_per_person && !$bands) {
            return ['amount' => round((float) $tier->price, 2), 'per' => 'group'];
        }

        $options = [];
        if ($tier->price_per_person && $tier->price > 0) {
            $options[] = (float) $tier->price;
        } elseif (!$tier->price_per_person && $tier->price > 0) {
            $options[] = (float) $tier->price / max(1, $tier->max_guests ?: ($tier->min_guests ?: 1));
        }
        foreach ($bands as $b) {
            $options[] = $b['total'] / max(1, $b['max'] ?? $b['min']);
        }

        return ['amount' => round(min($options ?: [0.0]), 2), 'per' => 'person'];
    }

    /** Active tiers of a service, in order. */
    public function forService(string $model, int $id, bool $activeOnly = true)
    {
        return VendorServiceTier::forService($model, $id)
            ->when($activeOnly, fn ($q) => $q->where('active', true))
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    /** Names of the add-ons bundled into a tier. */
    public function includedUpsells(VendorServiceTier $tier)
    {
        $ids = $tier->included_upsell_ids ?: [];

        return $ids ? VendorUpsell::whereIn('id', $ids)->get() : collect();
    }

    /** The tier as the API returns it. */
    public function shape(VendorServiceTier $tier, ?int $guests = null): array
    {
        $from = $this->from($tier);
        $out = [
            'id'           => $tier->id,
            'key'          => $tier->tier_key,
            'name'         => $tier->name,
            'tagline'      => $tier->tagline,
            'description'  => $tier->description,
            'recommended'  => (bool) $tier->recommended,
            'price'        => (float) $tier->price,
            'price_per_person' => (bool) $tier->price_per_person,
            'from_price'   => $from['amount'],
            'from_per'     => $from['per'],
            'min_guests'   => $tier->min_guests,
            'max_guests'   => $tier->max_guests,
            'bands'        => array_map(fn ($b) => ['min' => $b['min'], 'max' => $b['max'], 'total' => $b['total']], $this->bands($tier)),
            'inclusions'   => array_values($tier->inclusions ?: []),
            'included_addons' => $this->includedUpsells($tier)->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values()->all(),
        ];
        if ($guests !== null) {
            $out['available_for_party'] = $this->accepts($tier, $guests);
            $out['total_for_party'] = $this->priceFor($tier, $guests)['total'];
        }

        return $out;
    }

    /** @return array<int,array{min:int,max:?int,total:float}> sorted, cleaned bands */
    public function bands(VendorServiceTier $tier): array
    {
        $out = [];
        foreach ((array) $tier->bands as $b) {
            if (!isset($b['min'], $b['total']) || (float) $b['total'] <= 0 || (int) $b['min'] < 1) {
                continue;
            }
            $out[] = ['min' => (int) $b['min'], 'max' => isset($b['max']) && $b['max'] !== '' && $b['max'] !== null ? (int) $b['max'] : null, 'total' => (float) $b['total']];
        }
        usort($out, fn ($a, $b) => $a['min'] <=> $b['min']);

        return $out;
    }

    /**
     * Creates or changes one tier of a tour. Bands are cleaned and must not overlap; a tier needs a price or a band.
     * One tier per tour is the recommended one.
     *
     * @param array{name:string,tagline?:?string,description?:?string,price?:?float,price_per_person?:bool,min_guests?:?int,max_guests?:?int,bands?:array,inclusions?:string[]|string|null,upsell_ids?:int[],recommended?:bool,active?:bool} $d
     * @throws TierRuleException
     */
    public function save(int $tourId, string $key, array $d): VendorServiceTier
    {
        if (!isset(VendorServiceTier::KEYS[$key])) {
            throw new TierRuleException('unknown_tier', 'Tier must be classic, signature or sublime.');
        }
        $bands = collect($d['bands'] ?? [])->filter(fn ($b) => !empty($b['min']) && !empty($b['total']))
            ->map(fn ($b) => ['min' => (int) $b['min'], 'max' => !empty($b['max']) ? (int) $b['max'] : null, 'total' => round((float) $b['total'], 2)])
            ->sortBy('min')->values();
        foreach ($bands as $i => $b) {
            if ($b['max'] !== null && $b['max'] < $b['min']) {
                throw new TierRuleException('band_range', __('A group price runs from :a up to :b, and :b is lower.', ['a' => $b['min'], 'b' => $b['max']]));
            }
            $prev = $bands[$i - 1] ?? null;
            if ($prev && ($prev['max'] === null || $prev['max'] >= $b['min'])) {
                throw new TierRuleException('band_overlap', __('Two group prices cover the same number of guests. Make them follow on from each other.'));
            }
        }
        if ((float) ($d['price'] ?? 0) <= 0 && $bands->isEmpty()) {
            throw new TierRuleException('price_required', __('Give the tier a price, or at least one group price.'));
        }

        $inclusions = $d['inclusions'] ?? [];
        $inclusions = is_array($inclusions) ? $inclusions : preg_split('/\R/', (string) $inclusions);
        $own = VendorUpsell::whereIn('id', $d['upsell_ids'] ?? [])->pluck('id')->all();

        $tier = VendorServiceTier::firstOrNew(['object_model' => 'tour', 'object_id' => $tourId, 'tier_key' => $key]);
        $tier->fill([
            'name' => $d['name'], 'tagline' => $d['tagline'] ?? null, 'description' => $d['description'] ?? null,
            'price' => (float) ($d['price'] ?? 0), 'price_per_person' => (bool) ($d['price_per_person'] ?? true),
            'min_guests' => $d['min_guests'] ?? null, 'max_guests' => $d['max_guests'] ?? null,
            'bands' => $bands->all(), 'inclusions' => array_values(array_filter(array_map('trim', $inclusions))),
            'included_upsell_ids' => $own, 'recommended' => (bool) ($d['recommended'] ?? false), 'active' => (bool) ($d['active'] ?? true),
            'sort_order' => array_search($key, array_keys(VendorServiceTier::KEYS), true),
        ])->save();
        if ($tier->recommended) {
            VendorServiceTier::forService('tour', $tourId)->where('id', '!=', $tier->id)->update(['recommended' => false]);
        }

        return $tier;
    }
}
