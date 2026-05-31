<?php

namespace App\Services\Adapters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ChatbotService;

class TelegramAdapter
{
    protected $chatbotService;
    protected $botToken;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
        $this->botToken = config('services.telegram.bot_token');
    }

    /**
     * Handle incoming Telegram webhook
     */
    public function handleWebhook(Request $request): array
    {
        try {
            $update = $request->json()->all();

            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }

            return ['status' => 'ok'];

        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->json()->all(),
            ]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle incoming text message
     */
    private function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'];
        $messageId = $message['message_id'];
        $text = $message['text'] ?? null;

        if (!$text) {
            return; // Ignore non-text messages for now
        }

        try {
            $this->processMessage($chatId, $userId, $text);
        } catch (\Exception $e) {
            Log::error('Telegram message processing error', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'user_id' => $userId,
            ]);
            $this->sendMessage($chatId, 'Sorry, something went wrong. Please try again.');
        }
    }

    /**
     * Handle button callback (inline buttons)
     */
    private function handleCallbackQuery(array $query): void
    {
        $chatId = $query['message']['chat']['id'];
        $userId = $query['from']['id'];
        $data = $query['data'];
        $queryId = $query['id'];

        try {
            // Answer the callback query (removes loading state)
            $this->answerCallbackQuery($queryId);

            // Process the selected option
            $this->processMessage($chatId, $userId, $data);

        } catch (\Exception $e) {
            Log::error('Telegram callback processing error', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
            $this->sendMessage($chatId, 'Sorry, something went wrong. Please try again.');
        }
    }

    /**
     * Process message through chatbot
     */
    private function processMessage(string $chatId, string $userId, string $text): void
    {
        // Get user info from Telegram
        $userInfo = [
            'telegram_user_id' => $userId,
            'chat_id' => $chatId,
        ];

        // Get vendor (Telegram bot is connected to one vendor)
        $vendorId = $this->getVendorIdFromTelegram();

        if (!$vendorId) {
            Log::warning('Telegram: Vendor not found');
            $this->sendMessage($chatId, 'Service unavailable. Please try again later.');
            return;
        }

        // Process through chatbot
        $response = $this->chatbotService->processMessage(
            vendorId: $vendorId,
            userId: "telegram_$userId",
            message: $text,
            channel: 'telegram',
            metadata: $userInfo
        );

        // Send response
        $this->sendMessage($chatId, $response['content']);

        // Send buttons if available
        if (!empty($response['picks'])) {
            $this->sendInlineButtons(
                $chatId,
                'Choose a destination:',
                array_map(fn($pick) => [
                    'text' => $pick['name'] . ', ' . $pick['country_name'],
                    'callback_data' => $pick['name'],
                ], $response['picks'])
            );
        }

        // Send action buttons
        if (!empty($response['buttons'])) {
            $this->sendInlineButtons(
                $chatId,
                $response['content'],
                array_map(fn($btn) => [
                    'text' => $btn['label'],
                    'callback_data' => $btn['label'],
                ], $response['buttons'])
            );
        }
    }

    /**
     * Get vendor ID from Telegram bot token
     */
    private function getVendorIdFromTelegram(): ?int
    {
        // Store mapping of bot token → vendor ID in config or database
        // For now, return vendor ID (would be fetched from config)
        $vendor = \App\Models\Vendor::where('telegram_bot_token', $this->botToken)->first();
        return $vendor?->id;
    }

    /**
     * Send text message to Telegram
     */
    private function sendMessage(string $chatId, string $text): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
            return false;
        }
    }

    /**
     * Send inline buttons to Telegram
     */
    private function sendInlineButtons(
        string $chatId,
        string $text,
        array $buttons
    ): bool {
        $inlineKeyboard = [array_map(fn($btn) => [
            'text' => substr($btn['text'], 0, 64),
            'callback_data' => substr($btn['callback_data'], 0, 64),
        ], array_slice($buttons, 0, 8))]; // Telegram limit: 8 per row

        try {
            $response = \Illuminate\Support\Facades\Http::post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $inlineKeyboard,
                    ]),
                    'parse_mode' => 'HTML',
                ]
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram buttons', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
            return false;
        }
    }

    /**
     * Send typing indicator
     */
    public function sendTypingIndicator(string $chatId): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::post(
                "https://api.telegram.org/bot{$this->botToken}/sendChatAction",
                [
                    'chat_id' => $chatId,
                    'action' => 'typing',
                ]
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::warning('Failed to send Telegram typing indicator', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Answer callback query (shows notification to user)
     */
    private function answerCallbackQuery(string $queryId, ?string $text = null): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::post(
                "https://api.telegram.org/bot{$this->botToken}/answerCallbackQuery",
                [
                    'callback_query_id' => $queryId,
                    'text' => $text ?? 'Processing...',
                ]
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::warning('Failed to answer Telegram callback', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Set webhook URL for Telegram
     * Run this once to set up the webhook
     */
    public function setWebhook(string $webhookUrl): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::post(
                "https://api.telegram.org/bot{$this->botToken}/setWebhook",
                [
                    'url' => $webhookUrl,
                    'allowed_updates' => ['message', 'callback_query'],
                ]
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to set Telegram webhook', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get(
                "https://api.telegram.org/bot{$this->botToken}/getWebhookInfo"
            );

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Failed to get Telegram webhook info', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::post(
                "https://api.telegram.org/bot{$this->botToken}/deleteWebhook"
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to delete Telegram webhook', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
