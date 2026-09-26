<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\Http;

/**
 * Translates pieces of a company's service text with DeepSeek (config/services.php, key in .env as DEEPSEEK_API_KEY). Takes a map of key => text and returns the same keys translated. Names of
 * places and brands, numbers, prices and times are kept; line breaks are kept; nothing is added. A piece the model did not return is
 * simply left out, so the caller leaves it as the original text.
 */
class AiTranslator
{
    private const CHARS_PER_CALL = 5500;
    private const PIECES_PER_CALL = 40;

    public static function configured(): bool
    {
        return (bool) config('services.deepseek.key');
    }

    /**
     * @param  array<string,string> $pieces key => text
     * @return array<string,string> key => translation
     * @throws \RuntimeException when the AI service cannot be reached or refuses
     */
    public static function translate(array $pieces, string $target, string $source, ?string $context = null): array
    {
        if (!self::configured()) {
            throw new \RuntimeException(__('Automatic translation is not set up on this platform.'));
        }
        $out = [];
        foreach (self::chunks($pieces) as $chunk) {
            $out += self::call($chunk, $target, $source, $context);
        }

        return $out;
    }

    private static function chunks(array $pieces): array
    {
        $chunks = [];
        $cur = [];
        $size = 0;
        foreach ($pieces as $k => $text) {
            if ($cur && ($size + mb_strlen($text) > self::CHARS_PER_CALL || count($cur) >= self::PIECES_PER_CALL)) {
                $chunks[] = $cur;
                $cur = [];
                $size = 0;
            }
            $cur[$k] = $text;
            $size += mb_strlen($text);
        }
        if ($cur) {
            $chunks[] = $cur;
        }

        return $chunks;
    }

    private static function call(array $chunk, string $target, string $source, ?string $context = null): array
    {
        $system = ($context ?: "You translate texts for a travel company's listings (hotels, tours, transfers, activities)") . " from {$source} into {$target}. "
            . "You receive a JSON object of keys to texts. Reply with ONLY a valid JSON object with exactly the same keys and each text translated. "
            . 'Keep place names, brand names, people names, numbers, prices, currencies, times and dates unchanged. Keep line breaks. '
            . 'Short labels stay short. Do not explain, add, or leave anything out. Write natural, warm, accurate travel copy.';
        $res = Http::withToken((string) config('services.deepseek.key'))->acceptJson()
            ->timeout(180)->retry(2, 2000, fn ($e) => in_array(optional($e->response ?? null)->status(), [429, 500, 503], true), throw: false)
            ->post((string) config('services.deepseek.url'), [
                'model' => config('services.deepseek.model'),
                'max_tokens' => 8000,
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ],
            ]);
        if (!$res->successful()) {
            \Log::warning('AiTranslator: ' . $res->status() . ' ' . mb_substr((string) $res->body(), 0, 300));
            $why = [
                401 => __('The translation service rejected the platform\'s key.'),
                402 => __('The translation service account is out of credit.'),
                429 => __('The translation service is busy. Wait a minute and press the button again: what was translated is saved.'),
            ][$res->status()] ?? __('The translation service did not answer (:s). Try again in a moment.', ['s' => $res->status()]);
            throw new \RuntimeException($why);
        }
        $text = (string) $res->json('choices.0.message.content');
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        $data = $start !== false && $end !== false ? json_decode(substr($text, $start, $end - $start + 1), true) : null;
        if (!is_array($data)) {
            throw new \RuntimeException(__('The translation service sent something unreadable. Try again.'));
        }
        $out = [];
        foreach ($chunk as $k => $_) {
            if (isset($data[$k]) && is_string($data[$k]) && trim($data[$k]) !== '') {
                $out[$k] = trim($data[$k]);
            }
        }

        return $out;
    }
}
