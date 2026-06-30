<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Vendor\Models\VendorConversation;

class PortalConciergeController extends Controller
{
    /**
     * Display concierge conversations dashboard
     */
    public function index(Request $request)
    {
        // Merged into the unified Inbox.
        return redirect()->route('vendor.inbox.index');
    }

    public function legacyIndex(Request $request)
    {
        $vendor = Auth::user();

        try {
            // Query conversations directly from database
            $query = VendorConversation::where('vendor_id', $vendor->id);

            // Apply filters
            if ($request->has('status') && $request->get('status') !== 'all') {
                $query->where('status', $request->get('status', 'active'));
            }

            if ($request->has('channel') && $request->get('channel')) {
                $query->where('channel', $request->get('channel'));
            }

            $conversations = $query
                ->with('messages')
                ->orderBy('updated_at', 'desc')
                ->paginate(20);

            // Get statistics
            $stats = [
                'total_conversations' => VendorConversation::where('vendor_id', $vendor->id)->count(),
                'open_conversations' => VendorConversation::where('vendor_id', $vendor->id)
                    ->where('status', 'active')->count(),
                'escalated_conversations' => VendorConversation::where('vendor_id', $vendor->id)
                    ->where('status', 'escalated')->count(),
                'resolved_conversations' => VendorConversation::where('vendor_id', $vendor->id)
                    ->where('status', 'closed')->count(),
            ];

            return view('vendor.concierge.index', [
                'conversations' => $conversations,
                'stats' => $stats,
                'filters' => [
                    'status' => $request->get('status', 'active'),
                    'channel' => $request->get('channel'),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Concierge dashboard error', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendor->id,
            ]);
            return back()->with('error', 'Error loading conversations');
        }
    }

    /**
     * Show conversation details and message history
     */
    public function show(Request $request, $conversationId)
    {
        $vendor = Auth::user();

        try {
            // Fetch conversation from database
            $conversation = VendorConversation::where('vendor_id', $vendor->id)
                ->findOrFail($conversationId);

            // Fetch messages with pagination
            $messagesQuery = $conversation->messages();
            $messages = $messagesQuery->orderBy('created_at', 'asc')
                ->paginate(50);

            return view('vendor.concierge.show', [
                'conversation' => $conversation,
                'messages' => $messages,
            ]);

        } catch (\Exception $e) {
            Log::error('Concierge detail error', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
            ]);
            return back()->with('error', 'Conversation not found');
        }
    }

    /**
     * Close a conversation
     */
    public function close(Request $request, $conversationId)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $vendor = Auth::user();

        try {
            $conversation = VendorConversation::where('vendor_id', $vendor->id)
                ->findOrFail($conversationId);

            $conversation->update([
                'status' => 'closed',
            ]);

            return back()->with('success', 'Conversation closed successfully');

        } catch (\Exception $e) {
            Log::error('Close conversation error', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
            ]);
            return back()->with('error', 'Error closing conversation');
        }
    }

    /**
     * Get statistics dashboard
     */
    public function statistics(Request $request)
    {
        $vendor = Auth::user();

        try {
            $stats = [
                'total_conversations' => VendorConversation::where('vendor_id', $vendor->id)->count(),
                'active_conversations' => VendorConversation::where('vendor_id', $vendor->id)
                    ->where('status', 'active')->count(),
                'closed_conversations' => VendorConversation::where('vendor_id', $vendor->id)
                    ->where('status', 'closed')->count(),
                'by_channel' => VendorConversation::where('vendor_id', $vendor->id)
                    ->selectRaw('channel, COUNT(*) as count')
                    ->groupBy('channel')
                    ->pluck('count', 'channel')
                    ->toArray(),
            ];

            return view('vendor.concierge.statistics', [
                'stats' => $stats,
                'period' => $request->get('period', '7days'),
            ]);

        } catch (\Exception $e) {
            Log::error('Statistics error', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendor->id,
            ]);
            return back()->with('error', 'Error loading statistics');
        }
    }

    /**
     * Create new conversation (manual escalation)
     */
    public function store(Request $request)
    {
        $request->validate([
            'guest_name' => 'required|string',
            'guest_email' => 'required|email',
            'guest_phone' => 'nullable|string',
            'message' => 'required|string',
        ]);

        $vendor = Auth::user();

        try {
            $conversation = VendorConversation::create([
                'vendor_id' => $vendor->id,
                'guest_name' => $request->input('guest_name'),
                'guest_email' => $request->input('guest_email'),
                'guest_phone' => $request->input('guest_phone'),
                'channel' => 'manual',
                'status' => 'active',
                'current_step' => 'destination',
                'message_count' => 1,
            ]);

            $conversation->messages()->create([
                'role' => 'user',
                'content' => $request->input('message'),
            ]);

            return back()->with('success', 'Conversation started');

        } catch (\Exception $e) {
            Log::error('Create conversation error', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendor->id,
            ]);
            return back()->with('error', 'Error creating conversation');
        }
    }

    /**
     * Get setup/integration guide
     */
    public function setup()
    {
        $vendor = Auth::user();

        return view('vendor.concierge.setup', [
            'apiKey' => $vendor->api_key,
            'vendorId' => $vendor->id,
        ]);
    }
}
