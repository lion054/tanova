<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Services\VendorChannelDispatcher;
use Pro\Concierge\Models\ConciergeConversation;
use Pro\Concierge\Services\ConciergeAiService;
use Pro\Tanova\Models\TanovaTrip;

/**
 * Unified Inbox — merges the old "AI Requests" (marketplace) and "Concierge"
 * (chatbot/manual) screens. Both already share bc_concierge_conversations, so this
 * is one list over all conversations, with multi-channel replies (email / WhatsApp /
 * Telegram via VendorChannelDispatcher) and a Bookings tab to start a chat from any
 * booking on the customer's preferred channel.
 */
class InboxController extends Controller
{
    private array $channels = ['email', 'whatsapp', 'telegram', 'sms'];

    public function index(Request $request)
    {
        $vendorId = resolve_current_vendor_id();
        $tab = $request->input('tab') === 'bookings' ? 'bookings' : 'conversations';

        $q = ConciergeConversation::where('vendor_id', $vendorId);

        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        if ($channel = $request->input('channel')) {
            $q->where('channel', $channel);
        }
        if ($request->input('source') === 'marketplace') {
            // Conversations tied to a Tanova marketplace booking (auto vendor-scoped).
            $mcp = TanovaTrip::where('source', 'mcp')->whereNotNull('booking_id')->pluck('booking_id');
            $q->whereIn('booking_id', $mcp);
        }
        if ($term = $request->input('q')) {
            $q->where(fn ($w) => $w->where('guest_name', 'like', "%{$term}%")->orWhere('guest_email', 'like', "%{$term}%"));
        }

        $conversations = $q->orderByDesc('last_message_at')->orderByDesc('id')->paginate(20);

        $bookings = $tab === 'bookings'
            ? Booking::where('vendor_id', $vendorId)->whereNotIn('status', Booking::$notAcceptedStatus)->orderByDesc('id')->paginate(20)
            : null;

        return view('vendor.inbox.index', [
            'tab'           => $tab,
            'conversations' => $conversations,
            'bookings'      => $bookings,
            'channels'      => $this->channels,
            'filters'       => $request->only(['status', 'channel', 'source', 'q']),
            'openCount'     => ConciergeConversation::where('vendor_id', $vendorId)->whereNotIn('status', ['resolved', 'closed'])->count(),
            'page_title'    => __('Inbox'),
        ]);
    }

    public function show($id)
    {
        $vendorId = resolve_current_vendor_id();
        $c = ConciergeConversation::where('vendor_id', $vendorId)->findOrFail($id);

        return view('vendor.inbox.show', [
            'c'          => $c,
            'messages'   => $c->messages()->orderBy('created_at')->get(),
            'booking'    => $c->booking,
            'channels'   => $this->channels,
            'page_title' => __('Conversation'),
        ]);
    }

    public function reply(Request $request, $id)
    {
        $vendorId = resolve_current_vendor_id();
        $c = ConciergeConversation::where('vendor_id', $vendorId)->findOrFail($id);

        $data = $request->validate([
            'body'    => ['required', 'string', 'max:3000'],
            'channel' => ['nullable', 'in:email,whatsapp,telegram,sms,web'],
        ]);
        $channel = $data['channel'] ?: ($c->channel ?: 'email');

        $c->addMessage($data['body'], 'ai', auth()->id());
        if ($channel !== $c->channel) {
            $c->update(['channel' => $channel]);
        }

        $res = app(VendorChannelDispatcher::class)->send(
            (int) $vendorId, $channel,
            ['email' => $c->guest_email, 'phone' => $c->guest_phone],
            __('Message from your travel provider'), $data['body']
        );

        return $res['status'] === 'sent'
            ? back()->with('success', __('Reply sent via :c.', ['c' => ucfirst($channel)]))
            : back()->with('warning', __('Reply saved. Not delivered via :c (:e).', ['c' => $channel, 'e' => $res['error'] ?? 'unavailable']));
    }

    public function aiDraft($id)
    {
        $vendorId = resolve_current_vendor_id();
        $c = ConciergeConversation::where('vendor_id', $vendorId)->findOrFail($id);

        $service = app(ConciergeAiService::class);
        if (! $service->isConfigured()) {
            return back()->with('error', __('AI is not configured.'));
        }
        $draft = $service->generateReply($c);
        if (! $draft) {
            return back()->with('error', __('Could not generate a draft.'));
        }
        $c->addMessage($draft, 'ai', null, true);

        return back()->with('success', __('AI draft created — review and send below.'));
    }

    public function startFromBooking(Request $request, $bookingId)
    {
        $vendorId = resolve_current_vendor_id();
        $booking = Booking::where('vendor_id', $vendorId)->findOrFail($bookingId);

        $data = $request->validate([
            'channel' => ['required', 'in:email,whatsapp,telegram,sms'],
            'body'    => ['required', 'string', 'max:3000'],
        ]);

        $c = ConciergeConversation::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'vendor_id'    => $vendorId,
                'guest_name'   => trim($booking->first_name . ' ' . $booking->last_name) ?: $booking->email,
                'guest_email'  => $booking->email,
                'guest_phone'  => $booking->phone,
                'channel'      => $data['channel'],
                'status'       => ConciergeConversation::STATUS_OPEN,
                'initiated_by' => 'vendor',
            ]
        );
        $c->update(['channel' => $data['channel']]);
        $c->addMessage($data['body'], 'ai', auth()->id());

        $res = app(VendorChannelDispatcher::class)->send(
            (int) $vendorId, $data['channel'],
            ['email' => $booking->email, 'phone' => $booking->phone],
            __('Message from your travel provider'), $data['body']
        );

        return redirect()->route('vendor.inbox.show', $c->id)->with(
            $res['status'] === 'sent' ? 'success' : 'warning',
            $res['status'] === 'sent' ? __('Conversation started and message sent.') : __('Conversation started. Message not delivered (:e).', ['e' => $res['error'] ?? 'unavailable'])
        );
    }

    public function count()
    {
        return response()->json([
            'count' => ConciergeConversation::where('vendor_id', resolve_current_vendor_id())
                ->whereNotIn('status', ['resolved', 'closed'])->count(),
        ]);
    }
}
