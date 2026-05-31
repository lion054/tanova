<?php

namespace App\Services\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ChatbotService;

class FacebookMessengerAdapter
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Handle incoming Facebook Messenger webhook
     */
    public function handleWebhook(Request $request): array
    {
        try {
            $data = $request->json()->all();

            // Webhook verification (GET with challenge)
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }

            // Process incoming messages (POST)
            if ($this->isIncomingMessage($data)) {
                $this->processIncomingMessage($data);
            }

            return ['status' => 'ok'];

        } catch (\Exception $e) {
            Log::error('Facebook Messenger webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->json()->all(),
            ]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify webhook (Facebook challenge)
     */
    private function verifyWebhook(Request $request): array
    {
        $verifyToken = config('services.facebook.verify_token');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($token !== $verifyToken) {
            Log::warning('Facebook webhook verification failed', ['token' => $token]);
            return ['status' => 'error'];
        }

        return [
            'status' => 'verified',
            'challenge' => $challenge,
        ];
    }

    /**
     * Check if this is an incoming message
     */
    private function isIncomingMessage(array $data): bool
    {
        return isset($data['entry'][0]['messaging'][0]['message']);
    }

    /**
     * Process incoming Facebook message
     */
    private function processIncomingMessage(array $data): void
    {
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['messaging'] ?? [] as $event) {
                if (isset($event['message'])) {
                    $this->handleMessageEvent($event);
                } elseif (isset($event['postback'])) {
                    $this->handlePostbackEvent($event);
                }
            }
        }
    }

    /**
     * Handle incoming message
     */
    private function handleMessageEvent(array $event): void
    {
        $senderId = $event['sender']['id'];
        $message = $event['message'];
        $timestamp = $event['timestamp'];

        // Extract text
        $text = $message['text'] ?? null;

        if (!$text) {
            return; // Ignore non-text messages for now
        }

        try {
            $this->processMessage($senderId, $text, $timestamp);
        } catch (\Exception $e) {
            Log::error('Facebook message processing error', [
                'error' => $e->getMessage(),
                'sender_id' => $senderId,
            ]);
            $this->sendMessage($senderId, 'Sorry, something went wrong. Please try again.');
        }
    }

    /**
     * Handle postback (button clicks)
     */
    private function handlePostbackEvent(array $event): void
    {
        $senderId = $event['sender']['id'];
        $payload = $event['postback']['payload'] ?? null;
        $timestamp = $event['timestamp'];

        if (!$payload) {
            return;
        }

        try {
            $this->processMessage($senderId, $payload, $timestamp);
        } catch (\Exception $e) {
            Log::error('Facebook postback processing error', [
                'error' => $e->getMessage(),
                'sender_id' => $senderId,
            ]);
            $this->sendMessage($senderId, 'Sorry, something went wrong. Please try again.');
        }
    }

    /**
     * Process message through chatbot
     */
    private function processMessage(string $senderId, string $text, int $timestamp): void
    {
        // Get vendor and user info
        $userProfile = $this->getUserProfile($senderId);

        $vendorId = $this->getVendorIdFromFacebook();

        if (!$vendorId) {
            Log::warning('Facebook: Vendor not found');
            $this->sendMessage($senderId, 'Service unavailable. Please try again later.');
            return;
        }

        // Process through chatbot
        $response = $this->chatbotService->processMessage(
            vendorId: $vendorId,
            userId: "facebook_$senderId",
            message: $text,
            channel: 'facebook',
            metadata: [
                'facebook_sender_id' => $senderId,
                'guest_name' => $userProfile['name'] ?? 'Guest',
                'guest_email' => $userProfile['email'] ?? null,
            ]
        );

        // Send response
        $this->sendMessage($senderId, $response['content']);

        // Send buttons if available
        if (!empty($response['picks'])) {
            $this->sendButtons(
                $senderId,
                'Choose a destination:',
                array_map(fn($pick) => [
                    'title' => $pick['name'] . ', ' . $pick['country_name'],
                    'payload' => $pick['name'],
                ], $response['picks'])
            );
        }
    }

    /**
     * Get user profile from Facebook
     */
    private function getUserProfile(string $userId): array
    {
        $accessToken = config('services.facebook.page_access_token');

        try {
            $response = \Illuminate\Support\Facades\Http::get(
                "https://graph.facebook.com/$userId",
                [
                    'fields' => 'first_name,last_name,profile_pic_url',
                    'access_token' => $accessToken,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                    'email' => null, // Facebook doesn't expose email via this API
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get Facebook user profile', ['error' => $e->getMessage()]);
        }

        return ['name' => 'Guest'];
    }

    /**
     * Get vendor ID associated with this Facebook page
     */
    private function getVendorIdFromFacebook(): ?int
    {
        $pageId = config('services.facebook.page_id');
        $vendor = \App\Models\Vendor::where('facebook_page_id', $pageId)->first();
        return $vendor?->id;
    }

    /**
     * Send text message to Facebook user
     */
    private function sendMessage(string $recipientId, string $text): bool
    {
        $accessToken = config('services.facebook.page_access_token');

        try {
            $response = \Illuminate\Support\Facades\Http::post(
                'https://graph.facebook.com/v18.0/me/messages',
                [
                    'recipient' => ['id' => $recipientId],
                    'message' => ['text' => $text],
                    'access_token' => $accessToken,
                ]
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to send Facebook message', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipientId,
            ]);
            return false;
        }
    }

    /**
     * Send quick reply buttons
     */
    private function sendButtons(
        string $recipientId,
        string $text,
        array $buttons
    ): bool {
        $accessToken = config('services.facebook.page_access_token');

        $quickReplies = array_map(fn($btn) => [
            'content_type' => 'text',
            'title' => substr($btn['title'], 0, 20),
            'payload' => $btn['payload'],
        ], array_slice($buttons, 0, 13)); // Facebook limit: 13 buttons

        try {
            $response = \Illuminate\Support\Facades\Http::post(
                'https://graph.facebook.com/v18.0/me/messages',
                [
                    'recipient' => ['id' => $recipientId],
                    'message' => [
                        'text' => $text,
                        'quick_replies' => $quickReplies,
                    ],
                    'access_token' => $accessToken,
                ]
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to send Facebook buttons', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipientId,
            ]);
            return false;
        }
    }

    /**
     * Send typing indicator (shows "typing..." to user)
     */
    public function sendTypingIndicator(string $recipientId): bool
    {
        $accessToken = config('services.facebook.page_access_token');

        try {
            $response = \Illuminate\Support\Facades\Http::post(
                'https://graph.facebook.com/v18.0/me/messages',
                [
                    'recipient' => ['id' => $recipientId],
                    'sender_action' => 'typing_on',
                    'access_token' => $accessToken,
                ]
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::warning('Failed to send Facebook typing indicator', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
