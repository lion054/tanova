<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * An extra a vendor sells on top of a booking: photography, a transfer, equipment.
 * Offered everywhere (is_global) or on chosen services, at a set price or a
 * price of its own on one service (price_override).
 */
class VendorUpsell extends Model
{
    use BelongsToVendor;
    use SoftDeletes;

    protected $table = 'bc_vendor_upsells';

    protected $fillable = [
        'vendor_id', 'name', 'category', 'description', 'short_description', 'image_id',
        'price', 'price_type', 'sort_order', 'status', 'is_featured', 'is_global',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'sort_order'  => 'integer',
        'is_featured' => 'boolean',
        'is_global'   => 'boolean',
    ];

    /** value => [label, icon] */
    public const CATEGORIES = [
        'transport'     => ['Transport', 'icofont-car'],
        'accommodation' => ['Accommodation', 'icofont-hotel'],
        'activity'      => ['Activity', 'icofont-flash'],
        'service'       => ['Service', 'icofont-tools-alt-2'],
        'equipment'     => ['Equipment', 'icofont-package'],
        'memory'        => ['Photos & memories', 'icofont-camera'],
    ];

    /** value => label. per_night stays for stays; per_day is for tours and trips. */
    public const PRICE_TYPES = [
        'per_booking' => 'Per booking',
        'per_person'  => 'Per person',
        'per_day'     => 'Per day',
        'per_night'   => 'Per night',
        'per_item'    => 'Per item',
    ];

    /** The kinds of service an add-on can be offered on. */
    public const SERVICE_TYPES = ['tour', 'hotel', 'car', 'boat', 'event', 'space'];

    public function services()
    {
        return $this->hasMany(VendorUpsellService::class, 'upsell_id');
    }

    /**
     * The line total for a booking: the unit price times what its price type
     * counts. [$qty] is how many were asked for (items, or just one).
     */
    public function computeTotal(int $qty = 1, int $guests = 1, int $nights = 1, ?int $days = null, ?float $unit = null): float
    {
        $unit ??= (float) $this->price;
        $days ??= max(1, $nights);
        $multiplier = match ($this->price_type) {
            'per_person' => max(1, $guests),
            'per_night'  => max(1, $nights),
            'per_day'    => max(1, $days),
            default      => 1,
        };

        return round($unit * $multiplier * max(1, $qty), 2);
    }

    /** "$25 per person", as a customer would read it. */
    public function priceLabel(?float $unit = null): string
    {
        $unit ??= (float) $this->price;
        $suffix = match ($this->price_type) {
            'per_person' => ' pp',
            'per_day'    => '/day',
            'per_night'  => '/night',
            'per_item'   => '/item',
            default      => '',
        };

        return '$' . rtrim(rtrim(number_format($unit, 2), '0'), '.') . $suffix;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category][0] ?? ucfirst((string) $this->category);
    }

    public function imageUrl(string $size = 'medium'): ?string
    {
        return $this->image_id ? get_file_url($this->image_id, $size) : null;
    }

    /**
     * What is offered on one service, best first: the add-ons assigned to it and
     * the global ones, published only, each with the price that applies there
     * (its own override, else the standard price). Highlighted ones lead, then
     * featured, then the vendor's order.
     *
     * @return Collection<int, array<string,mixed>>
     */
    public static function offeredOn(string $objectModel, int $objectId): Collection
    {
        $assigned = VendorUpsellService::where('object_model', $objectModel)
            ->where('object_id', $objectId)
            ->get()
            ->keyBy('upsell_id');

        return static::where('status', 'publish')
            ->where(function ($q) use ($assigned) {
                $q->where('is_global', true);
                if ($assigned->isNotEmpty()) {
                    $q->orWhereIn('id', $assigned->keys());
                }
            })
            ->get()
            ->map(function (VendorUpsell $u) use ($assigned) {
                $link = $assigned->get($u->id);
                $unit = $link && $link->price_override !== null ? (float) $link->price_override : (float) $u->price;

                return [
                    'upsell'      => $u,
                    'price'       => $unit,
                    'highlighted' => (bool) ($link->is_highlighted ?? false),
                    'sort'        => (int) ($link->sort_order ?? 999),
                ];
            })
            ->sortBy([
                fn ($a, $b) => (int) $b['highlighted'] <=> (int) $a['highlighted'],
                fn ($a, $b) => (int) $b['upsell']->is_featured <=> (int) $a['upsell']->is_featured,
                fn ($a, $b) => $a['sort'] <=> $b['sort'],
                fn ($a, $b) => $a['upsell']->sort_order <=> $b['upsell']->sort_order,
                fn ($a, $b) => strcmp($a['upsell']->name, $b['upsell']->name),
            ])
            ->values();
    }

    /** How many services this is offered on, for the list ("Everywhere", "3 services"). */
    public function offeredLabel(): string
    {
        if ($this->is_global) {
            return __('Everywhere');
        }
        $n = $this->relationLoaded('services') ? $this->services->count() : $this->services()->count();

        return $n === 0 ? __('Nowhere yet') : trans_choice(':n service|:n services', $n, ['n' => $n]);
    }
}
