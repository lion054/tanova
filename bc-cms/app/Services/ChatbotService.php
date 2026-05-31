<?php

namespace App\Services;

use Modules\Vendor\Models\VendorConversation;
use Modules\Vendor\Models\VendorConversationMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenAI\Client as OpenAIClient;

/**
 * ChatbotService - Tsoka AI Concierge
 * Handles multi-step conversation flow + Claude AI integration
 *
 * SaaS Model:
 * - Per-vendor conversations (vendor_id isolation)
 * - Per-channel support (Web, WhatsApp, Facebook, Telegram)
 * - Conversation persistence in database
 * - Rate limiting per vendor
 */
class ChatbotService
{
    private $vendor;
    private $conversation;
    private $conversationFlow;
    private $places = [];
    private $openaiClient;

    const CHIP_POOL = [
        // Pain-first: hotels & lodges
        ['label' => 'Booking.com is eating our margins', 'prompt' => "We're doing most of our bookings through Booking.com and Expedia and it's costing us 18–20% every time. How does Tsoka actually fix this — not just add another channel, but drive real direct bookings?"],
        ['label' => 'My front desk drowns in WhatsApp', 'prompt' => "Our front desk team spends half their day answering the same WhatsApp questions from guests — check-in time, what's included, restaurant hours. How does the Tsoka AI Concierge take this off their plate?"],
        ['label' => 'We had double bookings last week', 'prompt' => "We manage rooms across multiple platforms and we keep getting overbookings because the inventory doesn't sync in time. How does Tsoka eliminate this with real-time OTA sync?"],
        ['label' => 'My rates are flat — should they be?', 'prompt' => "We charge the same rate year-round with minor seasonal adjustments. I feel like we're leaving money on the table. How does Tsoka's Revenue Intelligence price dynamically and what kind of uplift should I expect?"],
        ['label' => 'Quotes take my team 3 days to build', 'prompt' => "We're a tour operator and every custom quote takes our consultants 2–3 days. By the time we send it, the client has already booked somewhere else. How does Tsoka fix this?"],
        // Travel
        ['label' => 'Plan my Tanzania safari', 'prompt' => "I want to plan a Tanzania safari — Serengeti, Ngorongoro, maybe Zanzibar after. Help me figure out the best route, best time to go, and roughly what to budget."],
        ['label' => 'Best Indian Ocean beach?', 'prompt' => "I'm choosing between Zanzibar, Mauritius, and Mozambique for a beach holiday. What are the differences and which is right for me?"],
    ];

    public function __construct()
    {
        $this->vendor = Auth::user();

        if (!$this->vendor) {
            throw new \Exception('Unauthorized: Vendor authentication required');
        }

        // Initialize OpenAI client
        if (config('services.openai.api_key')) {
            $this->openaiClient = \OpenAI::client(config('services.openai.api_key'));
        }

        // Load places from database
        $this->loadPlaces();
    }

    /**
     * Load all available destinations
     */
    private function loadPlaces(): void
    {
        $this->places = DB::table('bc_locations')
            ->select('id', 'name', 'country_name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'country_name' => $p->country_name])
            ->toArray();
    }

    /**
     * Create or get vendor conversation
     */
    public function getOrCreateConversation(string $channel = 'web', ?string $guestEmail = null): VendorConversation
    {
        if ($this->conversation) {
            return $this->conversation;
        }

        // Get or create conversation
        $this->conversation = VendorConversation::firstOrCreate(
            [
                'vendor_id' => $this->vendor->id,
                'channel' => $channel,
                'guest_email' => $guestEmail,
                'status' => 'active',
            ],
            [
                'guest_name' => null,
                'metadata' => [
                    'step' => null,
                    'searchData' => [],
                    'created_at' => now()->toIso8601String(),
                ],
            ]
        );

        // Initialize conversation flow
        $this->conversationFlow = new ConversationFlow($this->conversation);

        return $this->conversation;
    }

    /**
     * Process user message and return AI response
     *
     * Steps:
     * 1. Save user message
     * 2. Determine current conversation step
     * 3. Route to appropriate handler
     * 4. Get AI response (Claude)
     * 5. Save assistant message
     * 6. Return response with metadata
     */
    public function processMessage(string $userMessage, ?string $channel = 'web'): array
    {
        $this->getOrCreateConversation($channel);

        // Save user message
        VendorConversationMessage::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'user',
            'content' => $userMessage,
            'metadata' => ['ip' => request()->ip()],
        ]);

        // Determine conversation step
        $step = $this->conversationFlow->getStep();

        // Route to step handler
        $response = match($step) {
            'destination' => $this->conversationFlow->handleDestinationText(strtolower($userMessage), $this->places),
            'dates' => $this->conversationFlow->handleDates($userMessage),
            'travelers' => $this->conversationFlow->handleTravelers($userMessage),
            'budget' => $this->conversationFlow->handleBudget($userMessage),
            'confirm' => $this->conversationFlow->handleConfirm(strtolower($userMessage)),
            default => $this->handleGeneralQuery($userMessage),
        };

        // Handle special actions
        if (isset($response['action'])) {
            if ($response['action'] === 'generate') {
                $searchData = $this->conversationFlow->getSearchData();
                return [
                    'content' => "Building your itineraries now — this usually takes about 60 seconds…",
                    'action' => 'triggerGenerate',
                    'searchData' => $searchData,
                    'status' => 'success',
                ];
            }
        }

        // Update conversation step
        if (isset($response['step'])) {
            $this->conversationFlow->setStep($response['step']);
            $this->conversationFlow->saveState();
        }

        // Save assistant message
        $assistantMessage = VendorConversationMessage::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'assistant',
            'content' => $response['message'] ?? $response['content'] ?? '',
            'metadata' => [
                'step' => $response['step'] ?? $step,
                'pendingAction' => $response['pendingAction'] ?? null,
                'picks' => $response['picks'] ?? null,
                'buttons' => $response['buttons'] ?? null,
            ],
        ]);

        return [
            'id' => $assistantMessage->id,
            'content' => $response['message'] ?? $response['content'] ?? '',
            'step' => $response['step'] ?? $step,
            'picks' => $response['picks'] ?? null,
            'buttons' => $response['buttons'] ?? null,
            'pendingAction' => $response['pendingAction'] ?? null,
            'status' => 'success',
        ];
    }

    /**
     * Handle general query (non-booking flow)
     * Uses Claude to answer questions about Tsoka or travel
     */
    private function handleGeneralQuery(string $message): array
    {
        // Get conversation history
        $messages = VendorConversationMessage::where('conversation_id', $this->conversation->id)
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->toArray();

        $messages[] = ['role' => 'user', 'content' => $message];

        $systemPrompt = "You are Tanova, an AI travel expert for Tsoka. You help with two things:

1. TRIP PLANNING: If the user mentions travel, destinations, dates, or budgets, guide them through the booking flow with friendly questions. Ask about destination, dates, group size, and budget.

2. TSOKA BUSINESS: If they ask about Tsoka's platform, explain how it helps tourism businesses avoid OTA commissions, manage bookings, and handle customer service.

Keep responses concise (1-2 sentences) and friendly. If appropriate, suggest they 'Plan My Trip' to get itineraries.";

        try {
            if (!$this->openaiClient) {
                return [
                    'message' => "Let me help — are you planning a trip, or do you have a question about Tsoka?",
                    'step' => null,
                ];
            }

            // Call Claude API
            $response = $this->openaiClient->messages()->create([
                'model' => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 300,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

            $content = $response->content[0]->text;

            // Check if response suggests booking flow
            if (preg_match('/(plan|trip|safari|destination|where|when|date)/i', $content)) {
                $this->conversationFlow->setStep('destination');
                return [
                    'message' => $content . "<br><br>Let's build this. Where in Africa are you headed?",
                    'picks' => array_slice($this->places, 0, 8),
                    'step' => 'destination',
                ];
            }

            return [
                'message' => $content,
                'step' => null,
            ];
        } catch (\Exception $e) {
            \Log::error('Claude API error: ' . $e->getMessage());

            // Fallback response if Claude unavailable
            if (preg_match('/(travel|safari|trip|plan)/i', $message)) {
                $this->conversationFlow->setStep('destination');
                return [
                    'message' => "Great! Let's plan your trip. Where in Africa are you headed?",
                    'picks' => array_slice($this->places, 0, 8),
                    'step' => 'destination',
                ];
            }

            return [
                'message' => "I'm here to help with trip planning or questions about Tsoka. What would you like to know?",
                'step' => null,
            ];
        }
    }

    /**
     * Get welcome message with random chips
     */
    public function getWelcomeMessage(): array
    {
        $chips = array_slice(array_map(fn() => self::CHIP_POOL[rand(0, count(self::CHIP_POOL) - 1)], range(0, 4)), 0, 5);

        return [
            'message' => "Hi, I'm Tanova. What's costing your business the most right now — OTA commissions, manual work, or revenue you're not capturing? Tell me and I'll show you exactly what Tsoka fixes. Or if you're a traveller, tell me where you want to go.",
            'chips' => $chips,
        ];
    }

    /**
     * Get conversation history for vendor
     */
    public function getConversationHistory(): array
    {
        return VendorConversationMessage::where('conversation_id', $this->conversation->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content,
                'timestamp' => $m->created_at->toIso8601String(),
                'metadata' => $m->metadata,
            ])
            ->toArray();
    }

    /**
     * Close conversation
     */
    public function closeConversation(?string $reason = null): void
    {
        $this->conversation->update([
            'status' => 'closed',
            'metadata' => array_merge($this->conversation->metadata ?? [], [
                'closed_at' => now()->toIso8601String(),
                'close_reason' => $reason,
            ]),
        ]);
    }
}
