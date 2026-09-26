<?php

namespace App\Support;

use Modules\Core\Models\Terms;
use Modules\Vendor\Services\ServiceTranslations;

/**
 * Everything the public page of a service needs, in one plain array, whatever the kind of service. The page itself (themes/GoTrip/Layout/
 * public/service.blade.php) only draws it, so hotels, tours, spaces, cars, boats and events all look and behave the same.
 *
 * Only what a guest may see is here: no owner e-mail, phone, profile link or internal ids beyond what the booking widget needs.
 */
class PublicServicePage
{
    public const KINDS = [
        'hotel' => 'Hotel', 'tour' => 'Tour', 'space' => 'Space', 'car' => 'Car', 'boat' => 'Boat', 'event' => 'Event',
    ];

    /** Written out (not built from the kind) so each can be translated as a whole sentence and found by the UI string scan. */
    public static function about(string $type): string
    {
        return [
            'hotel' => __('About this hotel'), 'tour' => __('About this tour'), 'space' => __('About this space'),
            'car' => __('About this car'), 'boat' => __('About this boat'), 'event' => __('About this event'),
        ][$type] ?? __('About this service');
    }

    public static function kindName(string $type): string
    {
        return [
            'hotel' => __('Hotel'), 'tour' => __('Tour'), 'space' => __('Space'), 'car' => __('Car'), 'boat' => __('Boat'), 'event' => __('Event'),
        ][$type] ?? ucfirst($type);
    }

    public static function make(string $type, $row, $translation, $related = []): array
    {
        $text = fn ($v) => trim(strip_tags((string) $v));
        $list = fn ($v) => ServiceTranslations::listValue($v);

        $gallery = [];
        foreach ((array) $row->getGallery(true) as $g) {
            if (!empty($g['large'])) {
                $gallery[] = ['large' => $g['large'], 'thumb' => $g['thumb'] ?? $g['large']];
            }
        }
        if (!$gallery && !empty($row->image_id)) {
            // getGallery() ignores the featured picture when there is no gallery: a service with only that one still gets its photo.
            $one = get_file_url($row->image_id, 'full');
            if ($one) {
                $gallery[] = ['large' => $one, 'thumb' => get_file_url($row->image_id, 'medium') ?: $one];
            }
        }
        $location = null;
        try {
            $location = $row->location && $row->location->name ? ($row->location->translate()->name ?? $row->location->name) : null;
        } catch (\Throwable $e) {
        }

        $facts = [];
        $add = function (string $label, $value, string $icon = '') use (&$facts) {
            if ($value !== null && $value !== '' && $value !== 0 && $value !== '0') {
                $facts[] = ['label' => $label, 'value' => (string) $value, 'icon' => $icon];
            }
        };
        switch ($type) {
            case 'tour':
                $add(__('Duration'), $row->duration ? duration_format($row->duration, true) : null, 'clock');
                $add(__('Group size'), $row->max_people ? trans_choice(':n person|:n people', (int) $row->max_people, ['n' => $row->max_people]) : null, 'users');
                $cat = optional($row->category_tour)->name ? optional($row->category_tour->translate())->name : null;
                $add(__('Type'), $cat, 'tag');
                break;
            case 'hotel':
                $add(__('Check-in'), $row->check_in_time ?? null, 'clock');
                $add(__('Check-out'), $row->check_out_time ?? null, 'clock');
                break;
            case 'space':
                $add(__('Guests'), $row->max_guests ?? null, 'users');
                $add(__('Beds'), $row->bed ?? null, 'bed');
                $add(__('Bathrooms'), $row->bathroom ?? null, 'bath');
                $add(__('Size'), !empty($row->square) ? $row->square . ' m²' : null, 'size');
                break;
            case 'car':
                $add(__('Passengers'), $row->passenger ?? null, 'users');
                $add(__('Gear'), $row->gear ?? null, 'gear');
                $add(__('Doors'), $row->door ?? null, 'door');
                $add(__('Baggage'), $row->baggage ?? null, 'bag');
                break;
            case 'boat':
                $add(__('Guests'), $row->max_guest ?? null, 'users');
                $add(__('Cabins'), $row->cabin ?? null, 'bed');
                $add(__('Length'), $row->length ?? null, 'size');
                $add(__('Speed'), $row->speed ?? null, 'gear');
                break;
            case 'event':
                $add(__('Duration'), $row->duration ? duration_format($row->duration, true) : null, 'clock');
                $add(__('Starts'), $row->start_time ?? null, 'clock');
                break;
        }

        // Amenities, features and other grouped terms.
        $termIds = $type === 'tour' ? $row->tour_term->pluck('term_id') : ($row->terms ? $row->terms->pluck('term_id') : collect());
        $groups = [];
        if ($termIds && count($termIds)) {
            foreach ((array) Terms::getTermsById($termIds) as $attr) {
                if (!empty($attr['parent']['hide_in_single'])) {
                    continue;
                }
                $items = [];
                foreach ($attr['child'] as $term) {
                    $t = $term->translate();
                    $items[] = ['name' => $t->name, 'icon' => $term->icon ?: '', 'image' => !empty($term->image_id) ? get_file_url($term->image_id, 'full') : ''];
                }
                if ($items) {
                    $groups[] = ['name' => $attr['parent']->translate()->name, 'items' => $items];
                }
            }
        }

        $nearby = [];
        foreach ($list($translation->surrounding ?? null) as $cat => $items) {
            foreach ((array) $items as $it) {
                if (is_array($it) && !empty($it['name'])) {
                    $nearby[] = ['name' => (string) $it['name'], 'note' => (string) ($it['desc'] ?? '')];
                }
            }
        }

        $review = null;
        if (setting_item($type . '_enable_review') && method_exists($row, 'getScoreReview')) {
            $r = $row->getScoreReview();
            if (!empty($r['total_review'])) {
                $review = ['score' => $r['score_total'] ?? 0, 'count' => (int) $r['total_review']];
            }
        }

        $cards = [];
        foreach ($related as $m) {
            $t = $m->translate();
            $img = !empty($m->image_id) ? get_file_url($m->image_id, 'medium') : '';
            $cards[] = ['title' => strip_tags((string) $t->title), 'url' => $m->getDetailUrl(), 'image' => $img,
                'place' => optional($m->location)->name ? optional($m->location->translate())->name : '', 'price' => $m->display_price ?? ''];
        }

        $vendor = $row->author;

        return [
            'type' => $type,
            'kind' => self::kindName($type),
            'title' => $text($translation->title),
            'address' => $text($translation->address ?? ''),
            'location' => $location,
            'stars' => $type === 'hotel' ? (int) ($row->star_rate ?? 0) : 0,
            'gallery' => $gallery,
            'video' => !empty($row->video) ? handleVideoUrl($row->video) : null,
            'price' => (string) ($row->display_price ?? ''),
            'sale_price' => (string) ($row->display_sale_price ?? ''),
            'review' => $review,
            'facts' => $facts,
            'overview' => (string) ($translation->content ?? ''),
            'short' => $text($translation->short_desc ?? ''),
            'include' => collect($list($translation->include ?? null))->pluck('title')->filter()->values()->all(),
            'exclude' => collect($list($translation->exclude ?? null))->pluck('title')->filter()->values()->all(),
            'itinerary' => array_values(array_filter($list($translation->itinerary ?? null), fn ($i) => is_array($i) && (!empty($i['title']) || !empty($i['content'])))),
            'faqs' => array_values(array_filter($list($translation->faqs ?? null), fn ($i) => is_array($i) && !empty($i['title']))),
            'policy' => array_values(array_filter($list($translation->policy ?? null), fn ($i) => is_array($i) && !empty($i['title']))),
            'specs' => array_values(array_filter($list($translation->specs ?? null), fn ($i) => is_array($i) && !empty($i['title']))),
            'cancel_policy' => $text($translation->cancel_policy ?? ''),
            'terms_information' => (string) ($translation->terms_information ?? ''),
            'amenities' => $groups,
            'nearby' => $nearby,
            'map' => ($row->map_lat && $row->map_lng) ? ['lat' => (float) $row->map_lat, 'lng' => (float) $row->map_lng, 'zoom' => (int) ($row->map_zoom ?: 12)] : null,
            'book_mode' => method_exists($row, 'getBookingEnquiryType') ? $row->getBookingEnquiryType() : 'book',
            'url' => $row->getDetailUrl(),
            'preview' => $row->status !== 'publish',
            'offered_by' => $vendor && $vendor->id ? ($vendor->business_name ?: $vendor->getDisplayName()) : '',
            'related' => $cards,
        ];
    }
}
