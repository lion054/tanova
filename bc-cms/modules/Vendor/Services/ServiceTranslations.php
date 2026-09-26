<?php

namespace Modules\Vendor\Services;

use App\User;
use Illuminate\Support\Facades\Schema;
use Modules\Boat\Models\Boat;
use Modules\Car\Models\Car;
use Modules\Event\Models\Event;
use Modules\Hotel\Models\Hotel;
use Modules\Hotel\Models\HotelRoom;
use Modules\Language\Models\Language;
use Modules\Space\Models\Space;
use Modules\Tour\Models\Tour;

/**
 * The words of a company's services in the languages it sells in. The service itself holds the default language; every other language
 * is a row in the service's translation table (see App\Traits\HasTranslations). A guest reading in French gets the French row, and
 * anything not translated falls back to the default text, so a half-translated service never shows a blank.
 *
 * Three kinds of field are edited here:
 *   text  a line (title, short description, cancellation policy)
 *   html  stored as HTML (description, terms): shown as text, saved back as paragraphs
 *   list  a list of items stored as JSON (FAQs, what is included, itinerary, policies, specifications, nearby places). Only the words
 *         in each item are translated (title, content, desc, name); pictures and other settings are always taken from the original,
 *         so a translation can never drift from the service's structure.
 */
class ServiceTranslations
{
    /** type => [model, label, listing type the plan controls, fields in the order they are shown] */
    public static function types(): array
    {
        return [
            'hotel' => ['class' => Hotel::class,     'label' => 'Hotels',      'plan' => 'hotel', 'fields' => ['title', 'content', 'policy', 'surrounding']],
            'room'  => ['class' => HotelRoom::class, 'label' => 'Hotel rooms', 'plan' => 'hotel', 'fields' => ['title', 'content']],
            'tour'  => ['class' => Tour::class,      'label' => 'Tours',       'plan' => 'tour',  'fields' => ['title', 'short_desc', 'content', 'itinerary', 'include', 'exclude', 'faqs']],
            'space' => ['class' => Space::class,     'label' => 'Spaces',      'plan' => 'space', 'fields' => ['title', 'content', 'faqs', 'surrounding']],
            'car'   => ['class' => Car::class,       'label' => 'Cars',        'plan' => 'car',   'fields' => ['title', 'content', 'faqs']],
            'boat'  => ['class' => Boat::class,      'label' => 'Boats',       'plan' => 'boat',  'fields' => ['title', 'content', 'specs', 'include', 'exclude', 'cancel_policy', 'terms_information', 'faqs']],
            'event' => ['class' => Event::class,     'label' => 'Events',      'plan' => 'event', 'fields' => ['title', 'content', 'faqs', 'surrounding']],
        ];
    }

    /** field => label, kind, and for lists the words inside an item (key => label). */
    public const FIELDS = [
        'title'             => ['label' => 'Title', 'kind' => 'text'],
        'short_desc'        => ['label' => 'Short description', 'kind' => 'text'],
        'content'           => ['label' => 'Description', 'kind' => 'html'],
        'cancel_policy'     => ['label' => 'Cancellation policy', 'kind' => 'text'],
        'terms_information' => ['label' => 'Terms', 'kind' => 'html'],
        'itinerary'         => ['label' => 'Itinerary', 'kind' => 'list', 'item' => 'Day', 'words' => ['title' => 'Title', 'desc' => 'Short line', 'content' => 'Details']],
        'include'           => ['label' => 'What is included', 'kind' => 'list', 'item' => 'Item', 'words' => ['title' => 'Item']],
        'exclude'           => ['label' => 'What is not included', 'kind' => 'list', 'item' => 'Item', 'words' => ['title' => 'Item']],
        'faqs'              => ['label' => 'Questions and answers', 'kind' => 'list', 'item' => 'FAQ', 'words' => ['title' => 'Question', 'content' => 'Answer']],
        'policy'            => ['label' => 'Policies', 'kind' => 'list', 'item' => 'Policy', 'words' => ['title' => 'Title', 'content' => 'Details']],
        'specs'             => ['label' => 'Specifications', 'kind' => 'list', 'item' => 'Spec', 'words' => ['title' => 'Name', 'content' => 'Value']],
        'surrounding'       => ['label' => 'Nearby places', 'kind' => 'list', 'item' => 'Place', 'words' => ['name' => 'Place', 'desc' => 'Note']],
    ];

    /** Kept for the page's older references. */
    public const FIELD_LABELS = ['title' => 'Title', 'short_desc' => 'Short description', 'content' => 'Description', 'policy' => 'Policies'];
    /** Fields stored as HTML. */
    public const HTML_FIELDS = ['content', 'terms_information'];

    public static function kind(string $field): string
    {
        return self::FIELDS[$field]['kind'] ?? 'text';
    }

    /** A stored list as an array, whether it came back cast or as JSON text. */
    public static function listValue($v): array
    {
        if (is_string($v)) {
            $v = json_decode($v, true);
        }

        return is_array($v) ? $v : [];
    }

    /**
     * Every translatable piece of words in a list, in order: [path, word key, text]. A path like "2|title" is the item and word;
     * lists grouped by category ("3|0|name") work the same way.
     * @return array<int,array{path:string,key:string,text:string}>
     */
    public static function entries(string $field, $value): array
    {
        $words = array_keys(self::FIELDS[$field]['words'] ?? []);
        $out = [];
        $walk = function ($node, array $trail) use (&$walk, &$out, $words) {
            foreach ((array) $node as $k => $v) {
                if (is_array($v)) {
                    $walk($v, array_merge($trail, [$k]));
                } elseif (is_string($v) && in_array((string) $k, $words, true) && trim($v) !== '') {
                    $out[] = ['path' => implode('|', array_merge($trail, [$k])), 'key' => (string) $k, 'text' => trim($v)];
                }
            }
        };
        $walk(self::listValue($value), []);

        return $out;
    }

    private static function setPath(array &$data, string $path, string $text): void
    {
        $ref = &$data;
        foreach (explode('|', $path) as $step) {
            if (!is_array($ref) || !array_key_exists($step, $ref)) {
                return;   // the original no longer has that place
            }
            $ref = &$ref[$step];
        }
        if (is_string($ref)) {
            $ref = $text;
        }
    }

    /** What a list field looks like for the page: each piece of words beside its translation. */
    public static function listRows($model, $row, string $field, array $marks = []): array
    {
        $trMap = collect($row ? self::entries($field, $row->getAttribute($field)) : [])->keyBy('path');
        $rows = [];
        foreach (self::entries($field, $model->getAttribute($field)) as $i => $e) {
            $tr = optional($trMap->get($e['path']))['text'] ?? '';
            $differs = $tr !== '' && $tr !== $e['text'];
            $done = $differs || isset($marks[$field . ':' . $e['path']]);   // marked: translated on purpose and the same in this language (a name, a loan word)
            $rows[] = ['path' => $e['path'], 'label' => self::entryLabel($field, $e), 'src' => $e['text'], 'tr' => $done ? ($differs ? $tr : $e['text']) : '', 'done' => $done];
        }

        return $rows;
    }

    private static function entryLabel(string $field, array $e): string
    {
        $meta = self::FIELDS[$field];
        $parts = explode('|', $e['path']);
        $n = (int) ($parts[count($parts) - 2] ?? 0) + 1;

        $word = $meta['words'][$e['key']] ?? $e['key'];

        return __($meta['item']) . ' ' . $n . ($word === $meta['item'] ? '' : ' · ' . __($word));
    }

    public static function enabled(): bool
    {
        return is_enable_multi_lang();
    }

    /** The types this company can have services in (its OS, its plan), with the fields that exist for each. */
    public static function typesFor(User $vendor, bool $isPlatform = false): array
    {
        $out = [];
        foreach (self::types() as $key => $t) {
            if (!$isPlatform && !CompanyOs::allowsType($vendor, $t['plan'])) {
                continue;
            }
            $transClass = (new $t['class']())->getTranslationModelName();
            $cols = Schema::getColumnListing((new $transClass())->getTable());
            $out[$key] = $t + ['fields' => array_values(array_intersect($t['fields'], $cols))];
        }

        return $out;
    }

    /** @return \Illuminate\Support\Collection<int,Language> the languages a service can be translated into: every active one except the default */
    public static function languages()
    {
        $default = (string) setting_item('site_locale');

        return collect(Language::getActive())->reject(fn ($l) => $l->locale === $default)->unique('locale')->values();
    }

    public static function query(string $type, int $vendorId)
    {
        $class = self::types()[$type]['class'];
        if ($type === 'room') {
            return $class::whereIn('parent_id', Hotel::where('author_id', $vendorId)->select('id'));
        }

        return $class::where('author_id', $vendorId);
    }

    /** Plain text from stored HTML: paragraphs and line breaks become new lines, everything else is dropped. */
    public static function toText(?string $html): string
    {
        $t = preg_replace('#</(p|div|li|h[1-6])>#i', "\n\n", (string) $html);
        $t = preg_replace('#<br\s*/?>#i', "\n", $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace("/[ \t]+\n/", "\n", $t);

        return trim(preg_replace("/\n{3,}/", "\n\n", $t));
    }

    /** Stored HTML from typed text: blank lines start a new paragraph, single line breaks stay line breaks. Anything typed as markup is shown as typed. */
    public static function toHtml(string $text): string
    {
        $text = trim(str_replace("\r\n", "\n", $text));
        if ($text === '') {
            return '';
        }

        return collect(preg_split("/\n{2,}/", $text))->map(fn ($p) => '<p>' . nl2br(e(trim($p)), false) . '</p>')->implode("\n");
    }

    private static function norm(?string $v, string $field): string
    {
        return self::kind($field) === 'html' ? self::toText($v) : trim((string) $v);
    }

    /**
     * How far a service is translated into a language: 'done' (everything that has words is translated), 'partial' or 'missing'.
     * A piece of words counts as translated when the language's row has text there that differs from the default text.
     * @return array{state:string,fields:array<string,float>} per field, the share translated (0 to 1); fields with nothing to translate are left out
     */
    public static function progress($model, ?object $row, array $fields, array $marks = []): array
    {
        $per = [];
        foreach ($fields as $f) {
            if (self::kind($f) === 'list') {
                $src = self::entries($f, $model->getAttribute($f));
                if (!$src) {
                    continue;
                }
                $tr = collect($row ? self::entries($f, $row->getAttribute($f)) : [])->keyBy('path');
                $done = collect($src)->filter(fn ($e) => (($t = $tr->get($e['path'])) && $t['text'] !== $e['text']) || isset($marks[$f . ':' . $e['path']]))->count();
                $per[$f] = $done / count($src);
                continue;
            }
            $src = self::norm($model->getAttribute($f), $f);
            if ($src === '') {
                continue;   // nothing to translate
            }
            $tr = $row ? self::norm($row->getAttribute($f), $f) : '';
            $per[$f] = (($tr !== '' && $tr !== $src) || isset($marks[$f])) ? 1.0 : 0.0;
        }
        $sum = array_sum($per);
        $state = !$per || $sum == 0 ? 'missing' : ($sum == count($per) ? 'done' : 'partial');

        return ['state' => $state, 'fields' => $per];
    }

    /** The language's rows for these services, keyed by service id. */
    public static function rows(string $type, string $locale, $ids): \Illuminate\Support\Collection
    {
        $tc = self::types()[$type]['class'];
        $class = (new $tc())->getTranslationModelName();

        return $class::where('locale', $locale)->whereIn('origin_id', $ids)->get()->keyBy('origin_id');
    }

    /**
     * Pieces that were translated on purpose but read the same in the language (a hotel's name, a loan word). Without this they could never
     * count as done, because "done" otherwise means "differs from the original".
     * @return array<int,array<string,bool>> service id => [piece => true]; a piece is a field name, or "field:path" inside a list
     */
    public static function marksFor(string $type, string $locale, $ids): array
    {
        $out = [];
        foreach (\Illuminate\Support\Facades\DB::table('vendor_translation_marks')->where('kind', $type)->where('locale', $locale)->whereIn('origin_id', $ids)->get(['origin_id', 'piece']) as $m) {
            $out[$m->origin_id][$m->piece] = true;
        }

        return $out;
    }

    private static function setMarks(string $type, string $locale, int $id, array $mark, array $unmark): void
    {
        $db = \Illuminate\Support\Facades\DB::table('vendor_translation_marks');
        foreach ($mark as $piece) {
            \Illuminate\Support\Facades\DB::table('vendor_translation_marks')->updateOrInsert(['kind' => $type, 'origin_id' => $id, 'locale' => $locale, 'piece' => $piece], ['updated_at' => now(), 'created_at' => now()]);
        }
        if ($unmark) {
            $db->where('kind', $type)->where('origin_id', $id)->where('locale', $locale)->whereIn('piece', $unmark)->delete();
        }
    }

    /** Counts for the dashboard: per type, how many services are done, partial or missing in this language. */
    public static function summary(array $types, int $vendorId, string $locale): array
    {
        $out = [];
        foreach ($types as $key => $t) {
            $models = self::query($key, $vendorId)->get();
            $rows = self::rows($key, $locale, $models->pluck('id'));
            $marks = self::marksFor($key, $locale, $models->pluck('id'));
            $c = ['done' => 0, 'partial' => 0, 'missing' => 0, 'total' => $models->count()];
            foreach ($models as $m) {
                $c[self::progress($m, $rows->get($m->id), $t['fields'], $marks[$m->id] ?? [])['state']]++;
            }
            $out[$key] = $c;
        }

        return $out;
    }

    /**
     * Saves what was typed for one service in one language. A field left empty is put back to the default text (so it falls back, never blank).
     * @param  array<string,string> $input field => text
     */
    public static function save($model, string $type, string $locale, array $input, array $fields): bool
    {
        if (!self::enabled()) {
            return false;   // with several languages off, translate() would hand back the service itself: never write then
        }
        $row = $model->translate($locale);
        $expect = $model->getTranslationModelName();
        if (!($row instanceof $expect)) {
            return false;
        }
        $changed = false;
        $mark = [];
        $unmark = [];
        foreach ($fields as $f) {
            if (!array_key_exists($f, $input)) {
                continue;
            }
            if (self::kind($f) === 'list') {
                // Start from the original list and put the typed words in; empty ones stay as the original words.
                $data = self::listValue($model->getAttribute($f));
                foreach ((array) $input[$f] as $path => $typed) {
                    if (is_string($typed) && trim($typed) !== '') {
                        self::setPath($data, (string) $path, trim($typed));
                        $mark[] = $f . ':' . $path;
                    } else {
                        $unmark[] = $f . ':' . $path;
                    }
                }
                $cast = in_array($row->getCasts()[$f] ?? null, ['array', 'json'], true);
                $new = $cast ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $old = $row->getAttribute($f);
                if (json_encode(self::listValue($old)) !== json_encode($data)) {
                    $row->setAttribute($f, $new);
                    $changed = true;
                }
                continue;
            }
            $typed = trim((string) $input[$f]);
            $typed === '' ? $unmark[] = $f : $mark[] = $f;
            $new = $typed === '' ? $model->getAttribute($f) : (self::kind($f) === 'html' ? self::toHtml($typed) : $typed);
            if ((string) $row->getAttribute($f) !== (string) $new) {
                $row->setAttribute($f, $new);
                $changed = true;
            }
        }
        if ($changed) {
            $row->save();   // a new row is only written when something differs from the default text
        }
        self::setMarks($type, $locale, (int) $model->getKey(), $mark, $unmark);

        return $changed;
    }

    /** What is typed in the boxes now, in the shape the page posts: translated words only (an untranslated piece is empty). */
    public static function currentInput($model, ?object $row, array $fields, array $marks = []): array
    {
        $in = [];
        foreach ($fields as $f) {
            if (self::kind($f) === 'list') {
                $in[$f] = [];
                foreach (self::listRows($model, $row, $f, $marks) as $e) {
                    if ($e['done']) {
                        $in[$f][$e['path']] = $e['tr'];
                    }
                }
                continue;
            }
            $src = self::norm($model->getAttribute($f), $f);
            $tr = $row ? self::norm($row->getAttribute($f), $f) : '';
            $in[$f] = ($src !== '' && $tr !== '' && $tr !== $src) ? $tr : (($src !== '' && isset($marks[$f])) ? ($tr !== '' ? $tr : $src) : '');
        }

        return $in;
    }

    /**
     * The pieces of a service still to translate, as key => [field, path|null, text]. Only what has words and is not yet translated.
     * @return array<string,array{field:string,path:?string,text:string}>
     */
    public static function missingPieces($model, ?object $row, array $fields, string $prefix, array $marks = []): array
    {
        $out = [];
        foreach ($fields as $f) {
            if (self::kind($f) === 'list') {
                foreach (self::listRows($model, $row, $f, $marks) as $e) {
                    if (!$e['done']) {
                        $out[$prefix . ':' . $f . ':' . $e['path']] = ['field' => $f, 'path' => $e['path'], 'text' => $e['src']];
                    }
                }
                continue;
            }
            $src = self::norm($model->getAttribute($f), $f);
            $tr = $row ? self::norm($row->getAttribute($f), $f) : '';
            if ($src !== '' && ($tr === '' || $tr === $src) && !isset($marks[$f])) {
                $out[$prefix . ':' . $f] = ['field' => $f, 'path' => null, 'text' => $src];
            }
        }

        return $out;
    }

    /**
     * Translates the given services with AI, filling only what is still missing (a translation someone wrote is never replaced).
     * @param  \Illuminate\Support\Collection $models the company's own services of one type
     * @return int how many pieces were written
     * @throws \RuntimeException
     */
    public static function autoTranslate($models, string $type, string $locale, string $targetName, string $sourceName, array $fields): int
    {
        $rows = self::rows($type, $locale, $models->pluck('id'));
        $marks = self::marksFor($type, $locale, $models->pluck('id'));
        $todo = [];
        foreach ($models as $m) {
            $todo[$m->id] = self::missingPieces($m, $rows->get($m->id), $fields, (string) $m->id, $marks[$m->id] ?? []);
        }
        $flat = [];
        foreach ($todo as $pieces) {
            foreach ($pieces as $key => $p) {
                $flat[$key] = $p['text'];
            }
        }
        if (!$flat) {
            return 0;
        }
        $done = AiTranslator::translate($flat, $targetName, $sourceName);
        $written = 0;
        foreach ($models as $m) {
            $input = self::currentInput($m, $rows->get($m->id), $fields, $marks[$m->id] ?? []);
            $any = false;
            foreach ($todo[$m->id] as $key => $p) {
                if (!isset($done[$key])) {
                    continue;
                }
                if ($p['path'] === null) {
                    $input[$p['field']] = $done[$key];
                } else {
                    $input[$p['field']][$p['path']] = $done[$key];
                }
                $any = true;
                $written++;
            }
            if ($any) {
                self::save($m, $type, $locale, $input, $fields);
            }
        }

        return $written;
    }
}
