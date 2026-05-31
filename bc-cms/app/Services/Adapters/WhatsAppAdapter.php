<?php

namespace App\Services\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ChatbotService;

class WhatsAppAdapter
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Handle incoming WhatsApp webhook
     * From WhatsApp Business API
     */
    public function handleWebhook(Request $request): array
    {
        try {
            $data = $request->json()->all();

            // WhatsApp webhook verification (GET request with challenge)
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }

            // Process incoming messages (POST)
            if ($this->isIncomingMessage($data)) {
                return $this->processIncomingMessage($data);
            }

            // Other webhook types (status updates, etc)
            return ['status' => 'ok'];

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->json()->all(),
            ]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify webhook (WhatsApp challenge)
     */
    private function verifyWebhook(Request $request): array
    {
        $verifyToken = config('services.whatsapp.verify_token');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($token !== $verifyToken) {
            Log::warning('WhatsApp webhook verification failed', ['token' => $token]);
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
        return isset($data['entry'][0]['changes'][0]['value']['messages'][0]);
    }

    /**
     * Process incoming WhatsApp message
     */
    private function processIncomingMessage(array $data): array
    {
        $message = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
        $contact = $data['entry'][0]['changes'][0]['value']['contacts'][0] ?? null;

        if (!$message || !$contact) {
            return ['status' => 'ok'];
        }

        $phoneNumber = $message['from'];
        $messageId = $message['id'];
        $guestName = $contact['profile']['name'] ?? 'Guest';

        // Extract message text (handle different message types)
        $text = $this->extractMessageText($message);

        if (!$text) {
            return ['status' => 'ok']; // Ignore non-text messages for now
        }

        // Get vendor ID from WhatsApp business account ID
        $vendorId = $this->getVendorIdFromPhone($phoneNumber);

        if (!$vendorId) {
            Log::warning('WhatsApp: Vendor not found for phone', ['phone' => $phoneNumber]);
            return ['status' => 'error'];
        }

        // Process through chatbot
        try {
            $response = $this->chatbotService->processMessage(
                vendorId: $vendorId,
                userId: "whatsapp_$phoneNumber",
                message: $text,
                channel: 'whatsapp',
                metadata: [
                    'whatsapp_message_id' => $messageId,
                    'phone_number' => $phoneNumber,
                    'guest_name' => $guestName,
                ]
            );

            // Send response back to WhatsApp
            $this->sendWhatsAppMessage($phoneNumber, $response['content']);

            // Send buttons if available
            if (!empty($response['picks'])) {
                $this->sendWhatsAppButtons(
                    $phoneNumber,
                    'Choose a destination:',
                    array_map(fn($pick) => [
                        'id' => $pick['id'],
                        'title' => $pick['name'] . ', ' . $pick['country_name'],
                    ], $response['picks'])
                );
            }

            return ['status' => 'success'];

        } catch (\Exception $e) {
            Log::error('WhatsApp chatbot error', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return ['status' => 'error'];
        }
    }

    /**
     * Extract text from different WhatsApp message types
     */
    private function extractMessageText(array $message): ?string
    {
        if (isset($message['text']['body'])) {
            return $message['text']['body'];
        }

        if (isset($message['button']['text'])) {
            return $message['button']['text'];
        }

        // Ignore media messages, location, etc for now
        return null;
    }

    /**
     * Get vendor ID for WhatsApp phone number
     * Maps WhatsApp phone → Vendor account
     */
    private function getVendorIdFromPhone(string $phoneNumber): ?int
    {
        // Query vendor with matching WhatsApp phone number
        $vendor = \App\Models\Vendor::where('whatsapp_phone', $phoneNumber)->first();
        return $vendor?->id;
    }

    /**
     * Send text message to WhatsApp
     */
    private function sendWhatsAppMessage(string $phoneNumber, string $text): bool
    {
        $accessToken = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post("https://graph.instagram.com/v18.0/$phoneNumberId/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $phoneNumber,
                    'type' => 'text',
                    'text' => [
                        'body' => $text,
                    ],
                ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Send interactive buttons to WhatsApp
     */
    private function sendWhatsAppButtons(
        string $phoneNumber,
        string $headerText,
        array $buttons
    ): bool {
        $accessToken = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneNumber,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => [
                    'text' => $headerText,
                ],
                'action' => [
                    'buttons' => array_map(fn($btn, $idx) => [
                        'type' => 'reply',
                        'reply' => [
                            'id' => "btn_$idx",
                            'title' => substr($btn['title'], 0, 20),
                        ],
                    ], $buttons, array_keys($buttons)),
                ],
            ],
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post("https://graph.instagram.com/v18.0/$phoneNumberId/messages", $payload);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp buttons', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead(string $messageId): bool
    {
        $accessToken = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post("https://graph.instagram.com/v18.0/$phoneNumberId/messages", [
                    'messaging_product' => 'whatsapp',
                    'status' => 'read',
                    'message_id' => $messageId,
                ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::warning('Failed to mark WhatsApp message as read', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
