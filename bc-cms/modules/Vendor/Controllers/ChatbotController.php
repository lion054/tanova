<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Vendor\Models\VendorConversation;

/**
 * ChatbotController - Tsoka AI Concierge API
 *
 * SaaS endpoints for vendor-owned chatbots:
 * - /api/v/concierge/conversations - List vendor's conversations
 * - /api/v/concierge/conversations/{id}/messages - Get conversation messages
 * - POST /api/v/concierge/message - Send message to chatbot
 *
 * Authentication: Bearer token (API key)
 * Rate Limiting: Per vendor per minute
 * Data Isolation: vendor_id filtering on all queries
 */
class ChatbotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * GET /api/v/concierge/conversations
     * List all conversations for vendor
     * Query params: channel, status, page, per_page
     */
    public function listConversations(Request $request)
    {
        $vendorId = Auth::id();

        $query = VendorConversation::where('vendor_id', $vendorId);

        // Filter by channel (web, whatsapp, facebook, telegram)
        if ($request->has('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        // Filter by status (active, closed, escalated)
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Pagination
        $perPage = min((int)$request->input('per_page', 15), 100);
        $conversations = $query
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $conversations->items(),
            'pagination' => [
                'total' => $conversations->total(),
                'per_page' => $conversations->perPage(),
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v/concierge/message
     * Send message and get chatbot response
     *
     * Body:
     * {
     *   "conversation_id": null (optional, creates new if null),
     *   "message": "Hello, I want to plan a trip",
     *   "channel": "web",
     *   "guest_email": "guest@example.com" (optional),
     *   "guest_name": "John" (optional)
     * }
     */
    public function sendMessage(Request $request)
    {
        // Rate limiting: 60 messages per minute per vendor
        $key = 'chatbot:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Max 60 messages per minute.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'conversation_id' => 'nullable|integer',
            'message' => 'required|string|max:500',
            'channel' => 'required|in:web,whatsapp,facebook,telegram',
            'guest_email' => 'nullable|email',
            'guest_name' => 'nullable|string|max:100',
        ]);

        try {
            $service = new ChatbotService();

            // Get or create conversation
            if ($validated['conversation_id'] ?? null) {
                $conversation = VendorConversation::where('vendor_id', Auth::id())
                    ->where('id', $validated['conversation_id'])
                    ->firstOrFail();
                $service->getOrCreateConversation($validated['channel']);
            } else {
                $service->getOrCreateConversation(
                    $validated['channel'],
                    $validated['guest_email'] ?? null
                );
            }

            // Update guest info if provided
            if ($validated['guest_name'] ?? null) {
                $service->getOrCreateConversation()->update([
                    'guest_name' => $validated['guest_name'],
                ]);
            }

            // Process message
            $response = $service->processMessage(
                $validated['message'],
                $validated['channel']
            );

            return response()->json(array_merge($response, [
                'conversation_id' => $service->getOrCreateConversation()->id,
                'timestamp' => now()->toIso8601String(),
            ]));
        } catch (\Exception $e) {
            \Log::error('Chatbot error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to process message',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * GET /api/v/concierge/conversations/{id}
     * Get conversation details
     */
    public function getConversation($id)
    {
        $conversation = VendorConversation::where('vendor_id', Auth::id())
            ->where('id', $id)
            ->with('messages')
            ->firstOrFail();

        return response()->json([
            'data' => $conversation,
            'messages' => $conversation->messages()
                ->orderBy('created_at')
                ->get(),
        ]);
    }

    /**
     * GET /api/v/concierge/conversations/{id}/messages
     * Get conversation message history
     * Query params: page, per_page, role
     */
    public function getMessages($id, Request $request)
    {
        // Verify ownership
        $conversation = VendorConversation::where('vendor_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $query = $conversation->messages();

        // Filter by role (user, assistant)
        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        $perPage = min((int)$request->input('per_page', 50), 100);
        $messages = $query
            ->orderBy('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $messages->items(),
            'pagination' => [
                'total' => $messages->total(),
                'per_page' => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v/concierge/conversations/{id}/close
     * Close conversation
     *
     * Body: { "reason": "customer_satisfied" }
     */
    public function closeConversation($id, Request $request)
    {
        $conversation = VendorConversation::where('vendor_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $reason = $request->input('reason', 'vendor_closed');

        $conversation->update([
            'status' => 'closed',
            'metadata' => array_merge($conversation->metadata ?? [], [
                'closed_at' => now()->toIso8601String(),
                'close_reason' => $reason,
            ]),
        ]);

        return response()->json([
            'message' => 'Conversation closed',
            'status' => 'success',
        ]);
    }

    /**
     * GET /api/v/concierge/statistics
     * Get chatbot statistics for vendor
     * Query params: from_date, to_date
     */
    public function getStatistics(Request $request)
    {
        $vendorId = Auth::id();
        $fromDate = $request->input('from_date', now()->subDays(30));
        $toDate = $request->input('to_date', now());

        $conversations = VendorConversation::where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();

        $messages = \DB::table('bc_concierge_messages')
            ->whereIn('conversation_id',
                VendorConversation::where('vendor_id', $vendorId)->pluck('id')
            )
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();

        $avgMessages = $conversations > 0 ? round($messages / $conversations, 2) : 0;

        $byChannel = VendorConversation::where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('channel')
            ->selectRaw('channel, COUNT(*) as count')
            ->get()
            ->keyBy('channel');

        $byStatus = VendorConversation::where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->get()
            ->keyBy('status');

        return response()->json([
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'summary' => [
                'total_conversations' => $conversations,
                'total_messages' => $messages,
                'avg_messages_per_conversation' => $avgMessages,
            ],
            'by_channel' => $byChannel,
            'by_status' => $byStatus,
        ]);
    }
}
