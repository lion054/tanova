<?php

namespace Pro\Tanova\Services;

use Pro\Tanova\Models\ItineraryDay;
use Pro\Tanova\Models\ItineraryTemplate;

/**
 * Turns an extracted document into a draft itinerary.
 *
 * Operators keep itineraries in Word and PDF; retyping one into the builder is the
 * reason they never adopt the builder. This splits a document on its day headings
 * and creates a draft they can correct.
 *
 * It is deliberately dumb pattern matching, not AI: the result is predictable, it
 * costs nothing per import, and it never invents a day that was not in the file.
 * The output is always a DRAFT — a human confirms it before it goes anywhere.
 */
class ItineraryDocumentImporter
{
    /**
     * Matches the ways operators actually write day headings:
     *   "Day 1", "DAY 1:", "Day 1 - Arrival", "Day 1 – Arrival", "Day 01 — Arrival"
     *
     * The /u modifier is load-bearing: en- and em-dashes are multibyte, and without
     * it the separator class matches only the first byte and leaves the remainder
     * stuck to the front of the title.
     */
    private const DAY_HEADING = '/^\s*day\s*0*(\d{1,2})\s*[:.\x{2010}-\x{2015}\-]?\s*(.*)$/iu';

    /**
     * @return array{template: ItineraryTemplate, days: int, unmatched: bool}
     */
    public function import(string $text, string $name): array
    {
        $blocks = $this->splitIntoDays($text);

        // Nothing that looks like a day heading — still create the itinerary and
        // park the whole document in day 1, so the work is not lost.
        $unmatched = empty($blocks);

        if ($unmatched) {
            $blocks = [1 => ['title' => null, 'body' => $this->firstLines($text, 400)]];
        }

        $template = ItineraryTemplate::create([
            'name'         => $name,
            'total_days'   => count($blocks),
            'total_nights' => max(0, count($blocks) - 1),
            'status'       => 'draft',
            'description'  => __('Imported from a document. Check every day before publishing.'),
        ]);

        $number = 0;

        foreach ($blocks as $block) {
            $number++;
            ItineraryDay::create([
                'template_id' => $template->id,
                'day_number'  => $number,
                'title'       => $block['title'] ? mb_substr($block['title'], 0, 191) : null,
                'description' => $block['body'] ?: null,
            ]);
        }

        return ['template' => $template, 'days' => $number, 'unmatched' => $unmatched];
    }

    /**
     * @return array<int, array{title: ?string, body: string}> keyed by the day number
     *         found in the document, in document order
     */
    private function splitIntoDays(string $text): array
    {
        $lines   = preg_split('/\n/', $text) ?: [];
        $days    = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match(self::DAY_HEADING, $line, $m)) {
                $current = (int) $m[1];

                // A repeated day number means the document restarts (e.g. two
                // itineraries in one file) — keep the first, ignore the rest.
                if (isset($days[$current])) {
                    $current = null;
                    continue;
                }

                $days[$current] = [
                    'title' => trim($m[2]) !== '' ? trim($m[2]) : null,
                    'body'  => '',
                ];

                continue;
            }

            if ($current !== null && trim($line) !== '') {
                $days[$current]['body'] .= trim($line) . "\n";
            }
        }

        foreach ($days as $n => $day) {
            $days[$n]['body'] = trim($day['body']);
        }

        ksort($days);

        return $days;
    }

    private function firstLines(string $text, int $limit): string
    {
        return mb_substr(trim($text), 0, $limit);
    }
}
