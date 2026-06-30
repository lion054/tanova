<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Pro\Concierge\Models\ConciergeConversation;
use Pro\Concierge\Services\ConciergeAiService;
use Pro\Tanova\Models\TanovaTrip;

/**
 * Phase 5 — AI Requests inbox: marketplace (MCP) bookings for the current vendor,
 * with the traveller↔operator message thread and one-click "Draft with AI".
 *
 * TanovaTrip uses BelongsToVendor, so the list is auto-scoped to this vendor.
 * Conversations are resolved by booking_id and re-checked against the vendor.
 */
class AiRequestController extends Controller
{
    public function index(Request $request)
    {
        // Merged into the unified Inbox (filtered to marketplace conversations).
        return redirect()->route('vendor.inbox.index', ['source' => 'marketplace']);
    }

    /** Lightweight count for the sidebar polling badge (open AI conversations). */
    public function count()
    {
        return response()->json([
            'count' => ConciergeConversation::where('vendor_id', resolve_current_vendor_id())
                ->where('status', ConciergeConversation::STATUS_OPEN)->count(),
        ]);
    }

    public function show($id)
    {
        // Merged into the unified Inbox. If this marketplace booking has a
        // conversation, open it directly; otherwise land on the marketplace inbox.
        $trip = TanovaTrip::where('source', 'mcp')->find($id);
        $conversation = $trip && $trip->booking_id
            ? ConciergeConversation::where('booking_id', $trip->booking_id)->first()
            : null;

        return $conversation
            ? redirect()->route('vendor.inbox.show', $conversation->id)
            : redirect()->route('vendor.inbox.index', ['source' => 'marketplace']);
    }

    public function reply(Request $request, $id)
    {
        $trip = TanovaTrip::where('source', 'mcp')->findOrFail($id);
        abort_if(! $trip->booking_id, 404);

        $body = $request->validate(['body' => ['required', 'string', 'max:3000']])['body'];

        $convo = $this->conversationFor($trip);
        $convo->addMessage($body, 'ai', auth()->id());  // operator reply (approved)

        return back()->with('success', __('Reply sent.'));
    }

    /** Generate (not send) an AI draft reply in Evalyne's style for operator review. */
    public function aiDraft(Request $request, $id)
    {
        $trip = TanovaTrip::where('source', 'mcp')->findOrFail($id);
        abort_if(! $trip->booking_id, 404);

        $service = app(ConciergeAiService::class);
        if (! $service->isConfigured()) {
            return back()->with('error', __('AI is not configured.'));
        }

        $convo = $this->conversationFor($trip);
        $draft = $service->generateReply($convo);

        if (! $draft) {
            return back()->with('error', __('Could not generate a draft.'));
        }

        // Stored as an unapproved AI draft for the operator to review/approve.
        $convo->addMessage($draft, 'ai', null, true);

        return back()->with('success', __('AI draft created — review and approve below.'));
    }

    private function conversationFor(TanovaTrip $trip): ConciergeConversation
    {
        return ConciergeConversation::firstOrCreate(
            ['booking_id' => $trip->booking_id],
            [
                'vendor_id'    => $trip->vendor_id,
                'guest_name'   => $trip->guest_name,
                'guest_email'  => $trip->guest_email,
                'channel'      => 'web',
                'status'       => ConciergeConversation::STATUS_OPEN,
                'initiated_by' => 'guest',
            ]
        );
    }
}
