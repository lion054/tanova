<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pro\Integrations\Models\Operator;
use Pro\Integrations\Models\OperatorFare;
use Pro\Integrations\Models\OperatorRoute;

/** The operators and suppliers you buy from, with their routes and fares. The portal's Operators screen uses the same rules. */
class VendorSuppliersController extends VendorApiController
{
    public function index(Request $request): JsonResponse
    {
        $q = Operator::withCount(['routes', 'fares']);
        ListQuery::search($q, $request->query('q'), ['name', 'contact_name', 'contact_email']);
        if (in_array($request->query('type'), Operator::TYPES, true)) {
            $q->where('type', $request->query('type'));
        }
        if (in_array($request->query('status'), ['active', 'inactive'], true)) {
            $q->where('status', $request->query('status'));
        }
        ListQuery::sort($q, $request->query('sort'), ['name' => ['name', 'asc'], 'newest' => ['id', 'desc']], 'name');

        return $this->page($q, $request, fn (Operator $o) => $this->shape($o));
    }

    public function show(int $id): JsonResponse
    {
        $o = Operator::withCount(['routes', 'fares'])->findOrFail($id);

        return $this->success($this->shape($o) + [
            'routes' => $o->routes()->orderBy('id')->get()->map(fn ($r) => $this->routeShape($r))->all(),
            'fares'  => $o->fares()->orderBy('id')->get()->map(fn ($f) => $this->fareShape($f, $o))->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $o = Operator::create($this->operatorData($request, true));

        return $this->created($this->shape($o->loadCount(['routes', 'fares'])));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $o = Operator::findOrFail($id);
        $o->update($this->operatorData($request, false));

        return $this->success($this->shape($o->fresh()->loadCount(['routes', 'fares'])));
    }

    public function destroy(int $id): JsonResponse
    {
        Operator::findOrFail($id)->delete();   // its routes, fares and sync log go with it

        return $this->noContent();
    }

    public function addRoute(Request $request, int $id): JsonResponse
    {
        $o = Operator::findOrFail($id);
        $route = OperatorRoute::create($request->validate(Operator::routeRules()) + ['operator_id' => $o->id]);

        return $this->created($this->routeShape($route->fresh()));
    }

    public function deleteRoute(int $id, int $routeId): JsonResponse
    {
        Operator::findOrFail($id)->routes()->findOrFail($routeId)->delete();

        return $this->noContent();
    }

    public function addFare(Request $request, int $id): JsonResponse
    {
        $o = Operator::findOrFail($id);
        $data = $request->validate(Operator::fareRules());
        if (!empty($data['route_id']) && !$o->routes()->whereKey($data['route_id'])->exists()) {
            return $this->error('route_not_found', 'That route does not belong to this supplier.', 422);
        }
        $fare = OperatorFare::create($data + ['operator_id' => $o->id]);

        return $this->created($this->fareShape($fare->fresh(), $o));
    }

    public function deleteFare(int $id, int $fareId): JsonResponse
    {
        Operator::findOrFail($id)->fares()->findOrFail($fareId)->delete();

        return $this->noContent();
    }

    private function operatorData(Request $request, bool $create): array
    {
        $rules = Operator::rules();
        if (!$create) {
            foreach (['name', 'type'] as $k) {
                array_unshift($rules[$k], 'sometimes');
            }
        }
        $d = $request->validate($rules);
        if ($create) {
            $d['currency'] = $d['currency'] ?? 'USD';
            $d['status'] = $d['status'] ?? 'active';
        }

        return $d;
    }

    private function shape(Operator $o): array
    {
        return [
            'id' => $o->id, 'name' => $o->name, 'type' => $o->type, 'status' => $o->status,
            'contact' => ['name' => $o->contact_name, 'email' => $o->contact_email, 'phone' => $o->contact_phone],
            'website' => $o->website, 'address' => $o->address, 'commission_rate' => $o->commission_rate !== null ? (float) $o->commission_rate : null,
            'payment_terms' => $o->payment_terms, 'currency' => $o->currency, 'notes' => $o->notes,
            'connection_status' => $o->connection_status, 'last_synced_at' => optional($o->last_synced_at)->toIso8601String(),
            'routes_count' => (int) ($o->routes_count ?? 0), 'fares_count' => (int) ($o->fares_count ?? 0), 'created_at' => optional($o->created_at)->toIso8601String(),
        ];
    }

    private function routeShape(OperatorRoute $r): array
    {
        return [
            'id' => $r->id, 'origin' => $r->origin, 'destination' => $r->destination, 'duration_minutes' => $r->duration_minutes, 'vehicle_type' => $r->vehicle_type,
            'days_of_week' => $r->days_of_week, 'departure_time' => $r->departure_time ? substr((string) $r->departure_time, 0, 5) : null,
            'arrival_time' => $r->arrival_time ? substr((string) $r->arrival_time, 0, 5) : null, 'active' => (bool) $r->active,
        ];
    }

    private function fareShape(OperatorFare $f, Operator $o): array
    {
        return [
            'id' => $f->id, 'route_id' => $f->route_id, 'fare_class' => $f->fare_class, 'nett_price' => (float) $f->nett_price,
            'sell_price' => $f->sell_price !== null ? (float) $f->sell_price : null, 'margin' => $o->marginOn($f), 'available_seats' => $f->available_seats,
            'valid_from' => optional($f->valid_from)->toDateString(), 'valid_to' => optional($f->valid_to)->toDateString(),
        ];
    }
}
