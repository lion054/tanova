<?php

namespace Pro\Concierge\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Pro\Concierge\Models\ConciergeConversation;
use Pro\Concierge\Services\ConciergeAiService;

class ConciergeAdminController extends Controller
{
    protected ConciergeAiService $ai;

    public function __construct(ConciergeAiService $ai)
    {
        $this->ai = $ai;
    }

    /** Conversation list */
    public function index(Request $request)
    {
        $query = ConciergeConversation::with('latestMessage')
            ->where('vendor_id', auth()->id())
            ->latest('last_message_at');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($channel = $request->get('channel')) {
            $query->where('channel', $channel);
        }
        if ($search = $request->get('s')) {
            $query->where(function ($q) use ($search) {
                $q->where('guest_name', 'like', "%{$search}%")
                  ->orWhere('guest_email', 'like', "%{$search}%");
            });
        }

        $conversations = $query->paginate(25);

        return view('Concierge::admin.index', compact('conversations'));
    }

    /** Single conversation thread */
    public function show(ConciergeConversation $conversation)
    {
        abort_if($conversation->vendor_id !== auth()->id(), 403);
        $conversation->load('messages');
        $conversation->messages->each->markRead();

        // Pre-generate an AI draft reply
        $aiDraft = $this->ai->generateReply($conversation);

        return view('Concierge::admin.conversation', compact('conversation', 'aiDraft'));
    }

    /** Staff sends a reply */
    public function reply(Request $request, ConciergeConversation $conversation)
    {
        abort_if($conversation->vendor_id !== auth()->id(), 403);
        $request->validate(['body' => 'required|string|max:3000']);

        $conversation->addMessage($request->body, 'agent', auth()->id(), false);

        return back()->with('success', 'Reply sent.');
    }

    /** Approve and send an AI-drafted reply */
    public function approveAiReply(Request $request, ConciergeConversation $conversation)
    {
        abort_if($conversation->vendor_id !== auth()->id(), 403);
        $request->validate(['body' => 'required|string|max:3000']);

        $msg = $conversation->addMessage($request->body, 'ai', null, false);
        $msg->approve();

        return back()->with('success', 'AI reply approved and sent.');
    }

    public function resolve(ConciergeConversation $conversation)
    {
        abort_if($conversation->vendor_id !== auth()->id(), 403);
        $conversation->resolve();
        return back()->with('success', 'Conversation resolved.');
    }

    public function escalate(ConciergeConversation $conversation)
    {
        abort_if($conversation->vendor_id !== auth()->id(), 403);
        $conversation->escalate();
        return back()->with('success', 'Conversation escalated.');
    }

    /** Start a new conversation manually (walk-in, phone, etc.) */
    public function create()
    {
        return view('Concierge::admin.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guest_name'  => 'required|string|max:255',
            'guest_email' => 'nullable|email|max:255',
            'channel'     => 'required|in:web,whatsapp,email,phone',
            'message'     => 'required|string|max:3000',
        ]);

        $conversation = ConciergeConversation::create([
            'vendor_id'   => auth()->id(),
            'guest_name'  => $validated['guest_name'],
            'guest_email' => $validated['guest_email'] ?? null,
            'channel'     => $validated['channel'],
            'status'      => ConciergeConversation::STATUS_OPEN,
            'create_user' => auth()->id(),
            'update_user' => auth()->id(),
        ]);

        $conversation->addMessage($validated['message'], 'guest');

        return redirect()->route('admin.concierge.show', $conversation)
            ->with('success', 'Conversation created.');
    }
}
