<?php

namespace Pro\Tanova\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Pro\Tanova\Models\TanovaMeal;

/**
 * Tanova port, phase 2 — vendor self-service management of the meals catalog.
 *
 * Isolation is automatic: TanovaMeal uses BelongsToVendor, so every query here is
 * already scoped to the current vendor and vendor_id is stamped on create. Route
 * model binding is likewise safe — a meal belonging to another vendor resolves to
 * a 404 rather than leaking.
 */
class MealPortalController extends Controller
{
    public function index(Request $request)
    {
        $base = TanovaMeal::query();
        $everything = (clone $base)->count();
        ListQuery::search($base, $request->query('s'), ['name', 'cuisine', 'location']);
        $types = []; foreach (TanovaMeal::MEAL_TYPES as $t) { $types[$t] = __(ucfirst($t)); }
        $bar = FilterBar::make($request)->search('s', __('Search name, cuisine or place'))->select('meal_type', __('Meal'), $types, __('Any meal'));
        if (isset($types[(string) $request->query('meal_type')])) { $base->where('meal_type', $request->query('meal_type')); }
        $cuisines = TanovaMeal::whereNotNull('cuisine')->where('cuisine', '!=', '')->distinct()->orderBy('cuisine')->limit(40)->pluck('cuisine')->all();
        if (count($cuisines) > 1) {
            $bar->select('cuisine', __('Cuisine'), array_combine($cuisines, $cuisines), __('Any cuisine'));
            if (in_array((string) $request->query('cuisine'), $cuisines, true)) { $base->where('cuisine', $request->query('cuisine')); }
        }
        $key = (string) $request->query('sort', 'order');
        $map = ['name' => ['name', 'asc'], 'cheap' => ['adult_price', 'asc'], 'dear' => ['adult_price', 'desc'], 'newest' => ['id', 'desc']];
        if (isset($map[$key])) { ListQuery::sort($base, $key, $map, 'name'); } else { $base->orderBy('sort_order')->orderBy('name'); }
        $fb = $bar->sort(['order' => __('Your order'), 'name' => __('Name A to Z'), 'cheap' => __('Price, low to high'), 'dear' => __('Price, high to low'), 'newest' => __('Newest added')], 'order')->perPage()->noun(__('meals'))->total((clone $base)->reorder()->count(), $everything)->toArray();
        $rows = $base->paginate(ListQuery::perPage($request))->withQueryString();

        return view('vendor.meals.index', [
            'rows'        => $rows,
            'fb'          => $fb,
            'meal_types'  => TanovaMeal::MEAL_TYPES,
            'filter_type' => $request->query('meal_type'),
            'search'      => $request->query('s'),
            'page_title'  => __('Meals & Dining'),
        ]);
    }

    public function store(Request $request)
    {
        TanovaMeal::create($this->validateMeal($request));

        return redirect()->route('vendor.meals.index')
            ->with('success', __('Meal added to your catalog.'));
    }

    public function update(Request $request, TanovaMeal $meal)
    {
        $meal->update($this->validateMeal($request));

        return redirect()->route('vendor.meals.index')
            ->with('success', __('Meal updated.'));
    }

    public function destroy(TanovaMeal $meal)
    {
        $meal->delete();

        return redirect()->route('vendor.meals.index')
            ->with('success', __('Meal deleted.'));
    }

    private function validateMeal(Request $request): array
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:191'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'meal_type'        => ['required', 'in:' . implode(',', TanovaMeal::MEAL_TYPES)],
            'cuisine'          => ['nullable', 'string', 'max:60'],
            'location'         => ['nullable', 'string', 'max:191'],
            'adult_price'      => ['required', 'numeric', 'min:0'],
            'child_price'      => ['nullable', 'numeric', 'min:0'],
            'infant_price'     => ['nullable', 'numeric', 'min:0'],
            'min_pax'          => ['nullable', 'integer', 'min:0', 'max:65535'],
            'max_pax'          => ['nullable', 'integer', 'min:0', 'max:65535', 'gte:min_pax'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'allergen_notes'   => ['nullable', 'string', 'max:2000'],
            'status'           => ['nullable', 'in:publish,draft'],
            'sort_order'       => ['nullable', 'integer'],
        ], [
            'max_pax.gte' => __('Maximum party size cannot be smaller than the minimum.'),
        ]);

        $data['dietary_vegetarian'] = $request->boolean('dietary_vegetarian');
        $data['dietary_vegan']      = $request->boolean('dietary_vegan');
        $data['dietary_halal']      = $request->boolean('dietary_halal');
        $data['status']             = $data['status'] ?? 'publish';
        $data['sort_order']         = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
