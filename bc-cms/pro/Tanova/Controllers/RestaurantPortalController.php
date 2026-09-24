<?php

namespace Pro\Tanova\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Pro\Tanova\Models\TanovaRestaurant;

/**
 * Tanova port, phase 2 — vendor-curated restaurant partners.
 *
 * Complements, does not replace, the OpenStreetMap discovery path.
 */
class RestaurantPortalController extends Controller
{
    public function index(Request $request)
    {
        $base = TanovaRestaurant::query();
        $everything = (clone $base)->count();
        ListQuery::search($base, $request->query('s'), ['name', 'cuisine', 'location']);
        $bar = FilterBar::make($request)->search('s', __('Search name, cuisine or place'))->select('partners', __('Partners'), ['1' => __('Partners only')], __('All restaurants'));
        if ($request->query('partners') === '1') { $base->partners(); }
        $cuisines = TanovaRestaurant::whereNotNull('cuisine')->where('cuisine', '!=', '')->distinct()->orderBy('cuisine')->limit(40)->pluck('cuisine')->all();
        if (count($cuisines) > 1) {
            $bar->select('cuisine', __('Cuisine'), array_combine($cuisines, $cuisines), __('Any cuisine'));
            if (in_array((string) $request->query('cuisine'), $cuisines, true)) { $base->where('cuisine', $request->query('cuisine')); }
        }
        $key = (string) $request->query('sort', 'partners');
        $map = ['name' => ['name', 'asc'], 'newest' => ['id', 'desc']];
        if (isset($map[$key])) { ListQuery::sort($base, $key, $map, 'name'); } else { $base->orderByDesc('is_partner')->orderBy('sort_order')->orderBy('name'); }
        $fb = $bar->sort(['partners' => __('Partners first'), 'name' => __('Name A to Z'), 'newest' => __('Newest added')], 'partners')->perPage()->noun(__('restaurants'))->total((clone $base)->reorder()->count(), $everything)->toArray();
        $rows = $base->paginate(ListQuery::perPage($request))->withQueryString();

        return view('vendor.restaurants.index', [
            'rows'       => $rows,
            'fb'         => $fb,
            'page_title' => __('Restaurants'),
        ]);
    }

    public function store(Request $request)
    {
        TanovaRestaurant::create($this->validateRestaurant($request));

        return redirect()->route('vendor.restaurants.index')
            ->with('success', __('Restaurant added.'));
    }

    public function update(Request $request, TanovaRestaurant $restaurant)
    {
        $restaurant->update($this->validateRestaurant($request));

        return redirect()->route('vendor.restaurants.index')
            ->with('success', __('Restaurant updated.'));
    }

    public function destroy(TanovaRestaurant $restaurant)
    {
        $restaurant->delete();

        return redirect()->route('vendor.restaurants.index')
            ->with('success', __('Restaurant deleted.'));
    }

    private function validateRestaurant(Request $request): array
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:191'],
            'description'         => ['nullable', 'string', 'max:5000'],
            'cuisine'             => ['nullable', 'string', 'max:60'],
            'location'            => ['nullable', 'string', 'max:191'],
            'lat'                 => ['nullable', 'numeric', 'between:-90,90'],
            'lng'                 => ['nullable', 'numeric', 'between:-180,180'],
            'contact_name'        => ['nullable', 'string', 'max:191'],
            'contact_phone'       => ['nullable', 'string', 'max:40'],
            'contact_email'       => ['nullable', 'email', 'max:191'],
            'booking_policy'      => ['nullable', 'string', 'max:2000'],
            'negotiated_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'price_band'          => ['nullable', 'integer', 'min:1', 'max:4'],
            'capacity'            => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status'              => ['nullable', 'in:publish,draft'],
            'sort_order'          => ['nullable', 'integer'],
        ]);

        $data['is_partner']         = $request->boolean('is_partner');
        $data['dietary_vegetarian'] = $request->boolean('dietary_vegetarian');
        $data['dietary_vegan']      = $request->boolean('dietary_vegan');
        $data['dietary_halal']      = $request->boolean('dietary_halal');
        $data['status']             = $data['status'] ?? 'publish';
        $data['sort_order']         = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
