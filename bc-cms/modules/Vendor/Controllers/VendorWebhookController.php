<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Vendor\Models\VendorWebhook;
use Modules\Vendor\Models\VendorWebhookDelivery;

class VendorWebhookController extends Controller
{
    public function index(): JsonResponse
    {
        $webhooks = VendorWebhook::where('vendor_id', Auth::id())
            ->withCount('deliveries')
            ->get();

        return response()->json(['data' => $webhooks]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'url'    => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'in:' . implode(',', \Modules\Vendor\Services\WebhookEvents::types()),
        ]);

        $webhook = VendorWebhook::generate(Auth::id(), $request->url, $request->events);

        return response()->json([
            'message' => 'Webhook registered. Store the secret — it will not be shown again.',
            'data'    => $webhook->makeVisible('secret'),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $webhook = VendorWebhook::where('vendor_id', Auth::id())->findOrFail($id);

        $request->validate([
            'url'      => 'sometimes|url|max:500',
            'events'   => 'sometimes|array|min:1',
            'events.*' => 'in:' . implode(',', \Modules\Vendor\Services\WebhookEvents::types()),
            'active'   => 'sometimes|boolean',
        ]);

        $webhook->update($request->only(['url', 'events', 'active']));

        return response()->json(['message' => 'Webhook updated.', 'data' => $webhook->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        VendorWebhook::where('vendor_id', Auth::id())->findOrFail($id)->delete();
        return response()->json(['message' => 'Webhook deleted.']);
    }

    public function deliveries(int $id): JsonResponse
    {
        $webhook = VendorWebhook::where('vendor_id', Auth::id())->findOrFail($id);

        $deliveries = VendorWebhookDelivery::where('webhook_id', $webhook->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($deliveries);
    }
}
