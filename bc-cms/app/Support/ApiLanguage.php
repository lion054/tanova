<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Serves services in the language a client asks for (`?lang=fr`, or an Accept-Language header: see SetLanguageForApi). A service keeps its
 * default-language text in the service itself and each other language in its translation table; this puts the asked-for language's text
 * over the default in the response, so a client gets French text in the same fields it reads English from. Anything not translated stays in
 * the default language, never blank. Each service also says which language its text is in and which others are available.
 *
 * Nothing here is ever saved: it only shapes what is sent.
 */
class ApiLanguage
{
    /** service type => translation table */
    public const TABLES = [
        'hotel' => 'bc_hotel_translations', 'tour' => 'bc_tour_translations', 'space' => 'bc_space_translations',
        'car' => 'bc_car_translations', 'boat' => 'bc_boat_translations', 'event' => 'bc_event_translations', 'room' => 'bc_hotel_room_translations',
    ];

    /** The words of a service that a translation may replace. */
    public const FIELDS = ['title', 'short_desc', 'content', 'address', 'faqs', 'include', 'exclude', 'itinerary', 'policy', 'specs', 'cancel_policy', 'terms_information', 'surrounding'];

    public static function defaultLocale(): string
    {
        return (string) (setting_item('site_locale') ?: 'en');
    }

    /** The asked-for language when it is not the default (and multi-language is on); null otherwise. */
    public static function requested(): ?string
    {
        if (!is_enable_multi_lang()) {
            return null;
        }
        $l = app()->getLocale();

        return $l && $l !== self::defaultLocale() ? $l : null;
    }

    private static function columns(string $type): array
    {
        static $memo = [];

        return $memo[$type] ??= array_values(array_intersect(self::FIELDS, Schema::getColumnListing(self::TABLES[$type])));
    }

    /** service id => the other languages it has text in */
    public static function available(string $type, $ids): array
    {
        $ids = collect($ids)->filter()->unique()->values();
        if ($ids->isEmpty() || !is_enable_multi_lang()) {
            return [];
        }
        $out = [];
        foreach (DB::table(self::TABLES[$type])->whereIn('origin_id', $ids)->where('locale', '!=', self::defaultLocale())->get(['origin_id', 'locale']) as $r) {
            $out[$r->origin_id][] = $r->locale;
        }

        return array_map(fn ($l) => array_values(array_unique($l)), $out);
    }

    /**
     * Eloquent services: the requested language's text goes over the default, and `language` and `available_languages` are added.
     * With `?lang=all` (or `translations=1`) every language's text is also sent under `translations`.
     * @param Collection|iterable $models
     */
    public static function models(string $type, $models, ?bool $withAll = null): void
    {
        $models = collect($models)->filter();
        if ($models->isEmpty()) {
            return;
        }
        $locale = self::requested();
        $withAll ??= request()->query('translations') === '1' || request()->query('lang') === 'all';
        $avail = self::available($type, $models->pluck('id'));
        $rows = $locale ? self::rows($type, $models->pluck('id'), [$locale]) : collect();
        $all = $withAll ? self::rows($type, $models->pluck('id'), null) : collect();
        $cols = self::columns($type);

        foreach ($models as $m) {
            $tr = $locale ? $rows->get($m->id . ':' . $locale) : null;
            if ($tr) {
                foreach ($cols as $f) {
                    $v = $tr->{$f} ?? null;
                    if ($v !== null && $v !== '' && $v !== '[]') {
                        self::put($m, $f, $v);
                    }
                }
            }
            $m->setAttribute('language', $tr ? $locale : self::defaultLocale());
            $m->setAttribute('available_languages', $avail[$m->id] ?? []);
            if ($withAll) {
                $m->setAttribute('translations', $all->filter(fn ($r, $k) => str_starts_with($k, $m->id . ':'))->mapWithKeys(fn ($r) => [$r->locale => self::pack($r, $cols)])->all());
            }
        }
    }

    /** Plain rows (a query-builder result): same overlay, for services read straight from the table. */
    public static function objects(string $type, Collection $rows, string $field = 'id'): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        $locale = self::requested();
        $tr = $locale ? self::rows($type, $rows->pluck($field), [$locale]) : collect();
        $cols = self::columns($type);
        foreach ($rows as $r) {
            $t = $tr->get($r->{$field} . ':' . $locale);
            if ($t) {
                foreach ($cols as $f) {
                    $v = $t->{$f} ?? null;
                    if ($v !== null && $v !== '' && $v !== '[]' && property_exists($r, $f)) {
                        $r->{$f} = $v;
                    }
                }
            }
        }
    }

    /** @return Collection keyed "id:locale" */
    private static function rows(string $type, $ids, ?array $locales): Collection
    {
        $q = DB::table(self::TABLES[$type])->whereIn('origin_id', collect($ids)->filter()->unique()->values());
        if ($locales !== null) {
            $q->whereIn('locale', $locales);
        } else {
            $q->where('locale', '!=', self::defaultLocale());
        }

        return $q->get()->keyBy(fn ($r) => $r->origin_id . ':' . $r->locale);
    }

    private static function pack($row, array $cols): array
    {
        $o = [];
        foreach ($cols as $f) {
            $v = $row->{$f} ?? null;
            $o[$f] = is_string($v) && in_array($f, ['faqs', 'include', 'exclude', 'itinerary', 'policy', 'specs', 'surrounding'], true) ? (json_decode($v, true) ?? $v) : $v;
        }

        return $o;
    }

    /** Sets one field on a model the way it stores it: lists as arrays when the model casts them, otherwise as they came. */
    private static function put($model, string $field, $value): void
    {
        $isList = in_array($field, ['faqs', 'include', 'exclude', 'itinerary', 'policy', 'specs', 'surrounding'], true);
        if ($isList && $model->hasCast($field, ['array', 'json'])) {
            $value = is_string($value) ? (json_decode($value, true) ?? []) : $value;
        }
        $model->setAttribute($field, $value);
    }
}
