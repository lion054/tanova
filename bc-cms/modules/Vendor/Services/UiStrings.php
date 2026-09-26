<?php

namespace Modules\Vendor\Services;

/**
 * The words around a service on its public page (buttons, headings, the booking box) come from resources/lang/{locale}.json. This finds
 * the ones a language is missing and has AI write them, so switching a page to Chinese changes the page, not just the description.
 * Existing translations are never replaced.
 */
class UiStrings
{
    /** Files whose __() and trans_choice() strings make up the public page. */
    private static function files(): array
    {
        $t = base_path('themes/GoTrip');
        $files = array_merge(
            glob($t . '/Layout/public.blade.php') ?: [],
            glob($t . '/Layout/public/*.php') ?: [],
            glob($t . '/Layout/common/detail/*.php') ?: [],
            glob($t . '/*/Views/frontend/detail.blade.php') ?: [],
            glob($t . '/*/Views/frontend/layouts/details/*.php') ?: [],
            glob(app_path('Support/PublicServicePage.php')) ?: [],
            glob(base_path('modules/Vendor/Services/ServiceTranslations.php')) ?: [],
        );

        return array_values(array_unique($files));
    }

    /** @return string[] every text the page asks the translator for */
    public static function used(): array
    {
        $found = [];
        foreach (self::files() as $f) {
            $src = (string) file_get_contents($f);
            if (preg_match_all('/(?:__|trans_choice)\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/s', $src, $m, PREG_SET_ORDER)) {
                foreach ($m as $x) {
                    $s = $x[1] !== '' ? stripcslashes(str_replace("\\'", "'", $x[1])) : stripcslashes($x[2] ?? '');
                    $s = trim($s);
                    if ($s !== '' && !str_contains($s, '$') && mb_strlen($s) < 400) {
                        $found[$s] = true;
                    }
                }
            }
        }

        return array_keys($found);
    }

    public static function path(string $locale): string
    {
        return resource_path('lang/' . preg_replace('/[^A-Za-z0-9_-]/', '', $locale) . '.json');
    }

    /** @return array<string,string> */
    public static function existing(string $locale): array
    {
        $f = self::path($locale);
        $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];

        return is_array($d) ? $d : [];
    }

    /** @return string[] the used texts this language has no translation for */
    public static function missing(string $locale): array
    {
        $have = self::existing($locale);

        return array_values(array_filter(self::used(), fn ($s) => !isset($have[$s]) || trim((string) $have[$s]) === ''));
    }

    /**
     * Writes the missing texts for a language. Placeholders (:name) and plural bars must survive, or the text is skipped.
     * @return int how many were added
     */
    public static function fill(string $locale, string $languageName, string $sourceName = 'English'): int
    {
        $missing = self::missing($locale);
        if (!$missing) {
            return 0;
        }
        $pieces = [];
        foreach ($missing as $i => $s) {
            $pieces['k' . $i] = $s;
        }
        $done = AiTranslator::translate($pieces, $languageName, $sourceName,
            'You translate the interface of a travel booking website (buttons, headings, short messages). Keep placeholders such as :name and :n exactly as they are, and keep a "|" between plural forms');
        $have = self::existing($locale);
        $added = 0;
        foreach ($pieces as $k => $src) {
            $tr = $done[$k] ?? null;
            if (!$tr) {
                continue;
            }
            preg_match_all('/:[A-Za-z_]+/', $src, $a);
            preg_match_all('/:[A-Za-z_]+/', $tr, $b);
            if (array_diff($a[0], $b[0]) || (substr_count($src, '|') && !substr_count($tr, '|'))) {
                continue;   // it lost a placeholder: leave it in English rather than break the sentence
            }
            $have[$src] = $tr;
            $added++;
        }
        if ($added) {
            file_put_contents(self::path($locale), json_encode($have, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        }

        return $added;
    }
}
