<?php

namespace Modules\Api\Controllers\Vendor;

use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pro\Concierge\Controllers\Api\ConciergeApiController;
use Pro\Concierge\Models\ConciergeConversation;
use Pro\Concierge\Services\ConciergeAiService;

/**
 * The portal's Concierge inbox, driven with a key. Listing, starting, reading and replying come from the core
 * controller (VendorContext is set by ResolveVendorApiKey, so its queries are already scoped to the vendor).
 * The rest is here, on the same conversation model the inbox screen uses.
 */
class VendorConciergeController extends ConciergeApiController
{
    public function __construct(ConciergeAiService $ai)
    {
        parent::__construct($ai);
    }

    /** Same as the core, but answers with the whole record (empty columns included), as the list and read do. */
    public function start(Request $request): JsonResponse
    {
        $r = parent::start($request);
        if ($r->getStatusCode() === 201 && ($id = $r->getData(true)['data']['id'] ?? null)) {
            $r->setData(['data' => ConciergeConversation::with('messages')->find($id)]);
        }

        return $r;
    }

    /** One conversation's messages, oldest first. */
    public function messages(Request $request, int $id): JsonResponse
    {
        $c = $this->mine($id);
        $per = max(1, min($request->integer('per_page', 50), 100));
        $q = $c->messages()->orderBy('created_at')->orderBy('id');
        if ($request->filled('sender_type')) {
            $q->where('sender_type', (string) $request->query('sender_type'));
        }
        $page = $q->paginate($per);

        return response()->json(['data' => $page->items(), 'meta' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]]);
    }

    /** Marks the conversation resolved, with an optional reason. */
    public function close(Request $request, int $id): JsonResponse
    {
        $c = $this->mine($id);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $c->resolve($data['reason'] ?? 'closed_by_vendor');

        return response()->json(['data' => $c->fresh()]);
    }

    /** Numbers for a period (default the last 30 days). */
    public function statistics(Request $request): JsonResponse
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = isset($data['from']) ? \Carbon\Carbon::parse($data['from'])->startOfDay() : now()->subDays(30)->startOfDay();
        $to = isset($data['to']) ? \Carbon\Carbon::parse($data['to'])->endOfDay() : now();

        $base = fn () => ConciergeConversation::where('vendor_id', VendorContext::id())->whereBetween('created_at', [$from, $to]);
        $conversations = $base()->count();
        $messages = DB::table('bc_concierge_messages')->whereIn('conversation_id', $base()->select('id'))->count();

        return response()->json(['data' => [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => ['total_conversations' => $conversations, 'total_messages' => $messages, 'avg_messages_per_conversation' => $conversations ? round($messages / $conversations, 2) : 0],
            'by_channel' => $base()->selectRaw('channel, COUNT(*) as n')->groupBy('channel')->pluck('n', 'channel')->map(fn ($n) => (int) $n),
            'by_status' => $base()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')->map(fn ($n) => (int) $n),
        ]]);
    }

    private function mine(int $id): ConciergeConversation
    {
        return ConciergeConversation::where('vendor_id', VendorContext::id())->findOrFail($id);
    }
}
