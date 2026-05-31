<?php

namespace Pro\Concierge\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pro\Concierge\Models\ConciergeConversation;

/**
 * Generates a Claude-powered AI reply for a concierge conversation.
 * Passes the last N messages as context so replies stay coherent.
 */
class ConciergeAiService
{
    protected string $apiKey;
    protected string $model;
    protected int    $maxTokens;
    protected int    $contextMessages = 10;

    public function __construct()
    {
        $this->apiKey    = setting_item('anthropic_api_key', '');
        $this->model     = setting_item('anthropic_model', 'claude-sonnet-4-6');
        $this->maxTokens = 1200;
    }

    public function generateReply(ConciergeConversation $conversation): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $history = $conversation->messages()
            ->where('approved', 1)
            ->latest()
            ->take($this->contextMessages)
            ->get()
            ->reverse()
            ->values();

        $messages = $history->map(function ($msg) {
            return [
                'role'    => $msg->sender_type === 'guest' ? 'user' : 'assistant',
                'content' => $msg->body,
            ];
        })->toArray();

        // Ensure we end on a user turn
        if (empty($messages) || end($messages)['role'] !== 'user') {
            return null;
        }

        $system = $this->buildSystemPrompt($conversation);

        try {
            Log::info('Concierge AI: generating reply for conversation', [
                'conversation_id' => $conversation->id,
                'message_count' => count($messages),
            ]);

            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => $this->maxTokens,
                'system'     => $system,
                'messages'   => $messages,
            ]);

            if ($response->failed()) {
                Log::error('Concierge AI error', [
                    'conversation_id' => $conversation->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $content = $response->json('content');
            if (!is_array($content) || empty($content) || !isset($content[0]['text'])) {
                Log::warning('Concierge AI: unexpected response structure', [
                    'conversation_id' => $conversation->id,
                    'response' => $response->json(),
                ]);
                return null;
            }

            $reply = trim($content[0]['text']);
            Log::info('Concierge AI: reply generated', [
                'conversation_id' => $conversation->id,
                'reply_length' => strlen($reply),
            ]);

            return $reply;
        } catch (\Throwable $e) {
            Log::error('Concierge AI exception', [
                'conversation_id' => $conversation->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    protected function buildSystemPrompt(ConciergeConversation $conversation): string
    {
        $guest      = $conversation->guestDisplayName();
        $channel    = $conversation->channel;
        $botName    = $conversation->chatbot_name ?? 'Concierge';
        $category   = $conversation->category ?? 'general';

        $categoryContext = match($category) {
            'booking' => 'The guest is inquiring about bookings or availability.',
            'complaint' => 'The guest is reporting an issue or complaint.',
            'general' => 'The guest is asking general questions.',
            default => '',
        };

        return <<<SYSTEM
You are {$botName}, a professional hospitality concierge assistant for a luxury African travel property.
You are helping guest: {$guest} via the {$channel} channel.
{$categoryContext}

Your tone is warm, professional, and knowledgeable about African safari and travel experiences.
Keep replies concise (2–4 sentences unless detail is needed).
If the guest asks about bookings or availability, politely note that a team member will confirm details.
Never invent prices, dates, or availability — say a team member will confirm.
Always close with a helpful offer or question to keep the conversation moving.
SYSTEM;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
