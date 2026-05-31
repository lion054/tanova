<?php

namespace Pro\Concierge\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pro\Concierge\Models\ConciergeConversation;
use Pro\Concierge\Services\ConciergeAiService;

class ConciergeApiController extends Controller
{
    protected ConciergeAiService $ai;

    public function __construct(ConciergeAiService $ai)
    {
        $this->ai = $ai;
    }

    /** GET /api/concierge/health — check AI service status */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => $this->ai->isConfigured() ? 'operational' : 'misconfigured',
            'ai_available' => $this->ai->isConfigured(),
        ]);
    }

    /** GET /api/concierge/conversations */
    public function index(Request $request): JsonResponse
    {
        $query = ConciergeConversation::query()
            ->where('status', '!=', ConciergeConversation::STATUS_RESOLVED)
            ->latest('last_message_at')
            ->with('latestMessage');

        if (VendorContext::active()) {
            $query->where('vendor_id', VendorContext::id());
        } else {
            $query->where('user_id', Auth::id());
        }

        return response()->json($query->paginate(min($request->integer('per_page', 15), 100)));
    }

    /** GET /api/concierge/conversations/{id} */
    public function show(ConciergeConversation $conversation): JsonResponse
    {
        if (VendorContext::active()) {
            abort_if($conversation->vendor_id !== VendorContext::id(), 403);
        } else {
            $this->authorize('view', $conversation);
            abort_if($conversation->user_id !== Auth::id(), 403);
        }

        $conversation->load(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc')->cursorPaginate(50);
        }]);
        return response()->json(['data' => $conversation]);
    }

    /** POST /api/concierge/conversations — start a new conversation */
    public function start(Request $request): JsonResponse
    {
        if (VendorContext::active()) {
            // Vendor API key — guest identity comes from request body, not auth user
            $validated = $request->validate([
                'message'      => 'required|string|max:3000',
                'channel'      => 'nullable|in:web,whatsapp,sms,email',
                'guest_name'   => 'nullable|string|max:200',
                'guest_email'  => 'nullable|email|max:255',
                'guest_phone'  => 'nullable|string|max:30',
                'chatbot_name' => 'nullable|string|max:100',
                'category'     => 'nullable|in:booking,complaint,general,other',
                'priority'     => 'nullable|in:low,normal,high',
            ]);

            $conversation = ConciergeConversation::create([
                'user_id'      => null,
                'vendor_id'    => VendorContext::id(),
                'guest_name'   => $validated['guest_name'] ?? 'Guest',
                'guest_email'  => $validated['guest_email'] ?? '',
                'guest_phone'  => $validated['guest_phone'] ?? '',
                'channel'      => $validated['channel'] ?? 'web',
                'chatbot_name' => $validated['chatbot_name'] ?? VendorContext::get()?->chatbot_name ?? 'Concierge',
                'category'     => $validated['category'] ?? 'general',
                'priority'     => $validated['priority'] ?? 'normal',
                'status'       => ConciergeConversation::STATUS_OPEN,
                'initiated_by' => 'vendor',
            ]);

            $conversation->addMessage($validated['message'], 'guest', null);
        } else {
            // Consumer Sanctum token — guest = the logged-in user
            $validated = $request->validate([
                'message'   => 'required|string|max:3000',
                'channel'   => 'nullable|in:web,whatsapp,sms,email',
                'vendor_id' => 'nullable|integer|exists:bc_users,id',
                'category'  => 'nullable|in:booking,complaint,general,other',
                'priority'  => 'nullable|in:low,normal,high',
            ]);

            /** @var \App\User $user */
            $user = Auth::user();

            $conversation = ConciergeConversation::create([
                'user_id'     => Auth::id(),
                'vendor_id'   => $validated['vendor_id'] ?? null,
                'guest_name'  => $user?->name ?? 'Guest',
                'guest_email' => $user?->email ?? '',
                'guest_phone' => $user?->phone ?? '',
                'channel'     => $validated['channel'] ?? 'web',
                'category'    => $validated['category'] ?? 'general',
                'priority'    => $validated['priority'] ?? 'normal',
                'status'      => ConciergeConversation::STATUS_OPEN,
                'initiated_by' => 'guest',
            ]);

            $conversation->addMessage($validated['message'], 'guest', Auth::id());
        }

        $aiReply = $this->ai->generateReply($conversation);
        if ($aiReply) {
            $conversation->addMessage($aiReply, 'ai', null, true);
        }

        $conversation->load('messages');
        return response()->json(['data' => $conversation], 201);
    }

    /** POST /api/concierge/conversations/{id}/send */
    public function sendMessage(Request $request, ConciergeConversation $conversation): JsonResponse
    {
        if (VendorContext::active()) {
            abort_if($conversation->vendor_id !== VendorContext::id(), 403);
            abort_if(VendorContext::id() !== Auth::id(), 403);
        } else {
            abort_if($conversation->user_id !== Auth::id(), 403);
            $this->authorize('update', $conversation);
        }

        // Rate limiting: max 1 message per 2 seconds per conversation
        if ($conversation->last_message_at && now()->diffInSeconds($conversation->last_message_at) < 2) {
            return response()->json(['error' => 'Please wait before sending another message'], 429);
        }

        $validated = $request->validate(['body' => 'required|string|max:3000']);

        $guestSenderId = VendorContext::active() ? null : Auth::id();
        $msg = $conversation->addMessage($validated['body'], 'guest', $guestSenderId);

        $aiReply = $this->ai->generateReply($conversation);
        $aiMsg   = null;
        if ($aiReply) {
            $aiMsg = $conversation->addMessage($aiReply, 'ai', null, true);
        }

        return response()->json([
            'data' => ['your_message' => $msg, 'ai_reply' => $aiMsg],
        ]);
    }
}
