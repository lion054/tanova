<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Vendor\Models\VendorWebhook;
use Modules\Vendor\Models\VendorWebhookDelivery;
use Modules\Vendor\Services\WebhookDeliverer;
use Modules\Vendor\Services\WebhookEvents;

/** Webhook endpoints managed with a key, so a system can register itself. (The portal's Integrations screen does the same.) */
class VendorWebhookApiController extends VendorApiController
{
    public function __construct(private WebhookDeliverer $deliverer) {}

    /** The events you can subscribe to. */
    public function events(): JsonResponse
    {
        return $this->success(collect(WebhookEvents::CATALOGUE)->map(fn ($v, $type) => ['type' => $type, 'description' => $v[0], 'object' => $v[1]])->values()->all());
    }

    public function index(Request $request): JsonResponse
    {
        return $this->page(VendorWebhook::where('vendor_id', $this->vendorId())->withCount('deliveries')->orderByDesc('id'), $request, fn ($w) => $this->shape($w));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success($this->shape($this->mine($id)->loadCount('deliveries')));
    }

    /** The secret is shown once, here. Store it: it signs everything sent to this endpoint. */
    public function store(Request $request): JsonResponse
    {
        $d = $this->rules($request, true);
        $hook = VendorWebhook::generate($this->vendorId(), $d['url'], $d['events']);

        return $this->created($this->shape($hook->loadCount('deliveries')) + ['secret' => $hook->makeVisible('secret')->secret]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $hook = $this->mine($id);
        $hook->update($this->rules($request, false));

        return $this->success($this->shape($hook->fresh()->loadCount('deliveries')));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->mine($id)->delete();

        return $this->noContent();
    }

    /** A new secret. The old one stops working at once, so update your receiver first or accept a short gap. */
    public function rotateSecret(int $id): JsonResponse
    {
        $hook = $this->mine($id);
        $hook->update(['secret' => Str::random(32)]);

        return $this->success($this->shape($hook->loadCount('deliveries')) + ['secret' => $hook->makeVisible('secret')->secret]);
    }

    /** Sends a `ping` to the endpoint now and shows what it answered. Nothing else happens. */
    public function test(int $id): JsonResponse
    {
        $hook = $this->mine($id);
        $payload = WebhookEvents::envelope('ping', ['message' => 'This is a test from the Tsoka portal.'], []);
        $d = VendorWebhookDelivery::create(['webhook_id' => $hook->id, 'event' => 'ping', 'event_id' => $payload['id'], 'payload' => $payload, 'attempts' => 0, 'success' => false]);
        $d = $this->deliverer->send($hook, $d);
        $d->update(['next_attempt_at' => null]);   // a ping is never retried

        return $this->success($this->deliveryShape($d));
    }

    public function deliveries(Request $request, int $id): JsonResponse
    {
        $hook = $this->mine($id);
        $q = VendorWebhookDelivery::where('webhook_id', $hook->id)->orderByDesc('id');
        if ($request->has('success')) {
            $q->where('success', filter_var($request->query('success'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('event')) {
            $q->where('event', (string) $request->query('event'));
        }

        return $this->page($q, $request, fn ($d) => $this->deliveryShape($d));
    }

    private function deliveryShape(VendorWebhookDelivery $d): array
    {
        return [
            'id' => $d->id, 'event_id' => $d->event_id, 'event' => $d->event, 'success' => (bool) $d->success, 'status_code' => $d->status_code,
            'attempts' => (int) $d->attempts, 'next_attempt_at' => optional($d->next_attempt_at)->toIso8601String(), 'duration_ms' => $d->duration_ms,
            'response' => $d->response_body, 'delivered_at' => optional($d->delivered_at)->toIso8601String(), 'created_at' => optional($d->created_at)->toIso8601String(),
        ];
    }

    public function showDelivery(int $id, int $deliveryId): JsonResponse
    {
        $d = VendorWebhookDelivery::where('webhook_id', $this->mine($id)->id)->findOrFail($deliveryId);

        return $this->success($this->deliveryShape($d) + ['payload' => $d->payload]);
    }

    /** Send an event again, with the same event id and body, now. */
    public function redeliver(int $id, int $deliveryId): JsonResponse
    {
        $hook = $this->mine($id);
        $d = VendorWebhookDelivery::where('webhook_id', $hook->id)->findOrFail($deliveryId);

        return $this->success($this->deliveryShape($this->deliverer->send($hook, $d)));
    }

    private function mine(int $id): VendorWebhook
    {
        return VendorWebhook::where('vendor_id', $this->vendorId())->findOrFail($id);
    }

    private function rules(Request $request, bool $create): array
    {
        $d = $request->validate([
            'url'      => [$create ? 'required' : 'sometimes', 'url:https', 'max:500'],
            'events'   => [$create ? 'required' : 'sometimes', 'array', 'min:1'],
            'events.*' => ['string', \Illuminate\Validation\Rule::in(WebhookEvents::types())],
            'active'   => ['sometimes', 'boolean'],
        ]);
        if (isset($d['url']) && !WebhookDeliverer::urlIsSafe($d['url'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['url' => 'The address must be a public https address (not a private or internal one).']);
        }
        if (isset($d['events'])) {
            $d['events'] = array_values(array_unique($d['events']));
        }

        return $d;
    }

    private function shape(VendorWebhook $w): array
    {
        return [
            'id' => $w->id, 'url' => $w->url, 'events' => $w->events, 'active' => (bool) $w->active,
            'deliveries' => (int) ($w->deliveries_count ?? 0), 'last_triggered_at' => optional($w->last_triggered_at)->toIso8601String(), 'created_at' => optional($w->created_at)->toIso8601String(),
        ];
    }
}
