<?php

namespace Pro\Integrations\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Pro\Integrations\Models\Operator;
use Pro\Integrations\Models\OperatorFare;
use Pro\Integrations\Models\OperatorRoute;

/**
 * Tanova port, phase 6 — supplier directory.
 *
 * NOTE ON CREDENTIALS: the source stored per-operator API keys alongside the
 * operator record. That is deliberately not ported. Supplier credentials belong in
 * the existing integrations credential path, not in a table a portal screen edits;
 * this module records only whether a connection exists and when it last ran.
 */
class OperatorPortalController extends Controller
{
    public function index(Request $request)
    {
        $base = Operator::withCount(['routes', 'fares']);
        $everything = Operator::count();
        ListQuery::search($base, $request->query('s'), ['name', 'contact_name', 'contact_email']);
        $typeOpts = []; foreach (Operator::TYPES as $t) { $typeOpts[$t] = __(ucfirst($t)); }
        if (isset($typeOpts[(string) $request->query('type')])) { $base->where('type', $request->query('type')); }
        $type = $request->query('type');
        ListQuery::sort($base, $request->query('sort'), ['name' => ['name', 'asc'], 'newest' => ['id', 'desc']], 'name');
        $fb = FilterBar::make($request)->search('s', __('Search name or contact'))->select('type', __('Type'), $typeOpts, __('Any type'))
            ->sort(['name' => __('Name A to Z'), 'newest' => __('Newest added')], 'name')->perPage()->noun(__('suppliers'))->total((clone $base)->reorder()->count(), $everything)->toArray();
        $rows = $base->paginate(ListQuery::perPage($request))->withQueryString();

        return view('vendor.operators.index', [
            'rows'       => $rows,
            'fb'         => $fb,
            'types'      => Operator::TYPES,
            'filterType' => $type,
            'page_title' => __('Operators & Suppliers'),
        ]);
    }

    public function store(Request $request)
    {
        Operator::create($this->validateOperator($request));

        return redirect()->route('vendor.operators.index')
            ->with('success', __('Operator added.'));
    }

    public function show(Operator $operator)
    {
        $operator->load(['routes', 'fares.route', 'syncLogs']);

        return view('vendor.operators.show', [
            'operator'   => $operator,
            'page_title' => $operator->name,
        ]);
    }

    public function update(Request $request, Operator $operator)
    {
        $operator->update($this->validateOperator($request));

        return back()->with('success', __('Operator updated.'));
    }

    public function destroy(Operator $operator)
    {
        $operator->delete();   // routes, fares and logs cascade

        return redirect()->route('vendor.operators.index')
            ->with('success', __('Operator removed.'));
    }

    public function addRoute(Request $request, Operator $operator)
    {
        $data = $request->validate(Operator::routeRules());

        $data['operator_id'] = $operator->id;
        OperatorRoute::create($data);

        return back()->with('success', __('Route added.'));
    }

    public function deleteRoute(Operator $operator, OperatorRoute $route)
    {
        abort_unless($route->operator_id === $operator->id, 404);

        $route->delete();

        return back()->with('success', __('Route removed.'));
    }

    public function addFare(Request $request, Operator $operator)
    {
        $data = $request->validate(Operator::fareRules());

        // Only accept a route belonging to this operator.
        if (!empty($data['route_id']) && !$operator->routes()->whereKey($data['route_id'])->exists()) {
            $data['route_id'] = null;
        }

        $data['operator_id'] = $operator->id;
        OperatorFare::create($data);

        return back()->with('success', __('Fare added.'));
    }

    public function deleteFare(Operator $operator, OperatorFare $fare)
    {
        abort_unless($fare->operator_id === $operator->id, 404);

        $fare->delete();

        return back()->with('success', __('Fare removed.'));
    }

    private function validateOperator(Request $request): array
    {
        $data = $request->validate(Operator::rules());

        $data['currency'] = $data['currency'] ?? 'USD';
        $data['status']   = $data['status'] ?? 'active';

        return $data;
    }
}
