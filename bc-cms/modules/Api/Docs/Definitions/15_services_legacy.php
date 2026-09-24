<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;

$paged = fn (string $item) => S::obj(['data' => S::obj([
    'current_page' => S::int('', 1), 'data' => S::arr(S::ref($item)), 'per_page' => S::int('', 15), 'total' => S::int('', 248), 'last_page' => S::int('', 17),
    'from' => S::nullable(S::int()), 'to' => S::nullable(S::int()), 'first_page_url' => S::url(), 'last_page_url' => S::url(), 'next_page_url' => S::nullable(S::url()), 'prev_page_url' => S::nullable(S::url()), 'path' => S::url(),
])]);
$legacyNote = 'The list is a framework paginator: rows are in `data.data`, and `data` also carries the paging fields. The record is the stored listing plus the website-ready fields named in its description; more fields than listed may appear.';
$listQuery = fn (array $extra = []) => array_merge([
    P::q('title (or an id)'), P::enum('status', ['publish', 'draft', 'pending'], 'Only this status.'), P::int('location_id', 'Only listings in this place.'),
    P::sort(['newest' => 'newest first (default)', 'oldest' => 'oldest first', 'title' => 'name A to Z', 'title_desc' => 'name Z to A', 'updated' => 'recently updated', 'price_asc' => 'price, low to high', 'price_desc' => 'price, high to low'], 'newest'),
], $extra, [P::page(), P::int('per_page', 'Rows per page, up to 100.', ['default' => 15])]);

Doc::schema('TourRecord', S::obj([
    'id' => S::int('', 123), 'title' => S::str('', 'Tandem Gorge Swing'), 'slug' => S::str('', 'tandem-gorge-swing'), 'content' => S::nullable(S::str('HTML description.', '<p>...</p>')),
    'short_desc' => S::nullable(S::str()), 'price' => S::str('Decimal string.', '200.00'), 'sale_price' => S::nullable(S::str()), 'duration' => S::nullable(S::int('Hours.', 3)),
    'category_id' => S::nullable(S::int('', 3)), 'location_id' => S::nullable(S::int('', 6)), 'address' => S::nullable(S::str()), 'map_lat' => S::nullable(S::str()), 'map_lng' => S::nullable(S::str()),
    'min_people' => S::nullable(S::int()), 'max_people' => S::nullable(S::int('The tour\'s usual number of seats.', 20)), 'status' => S::enum(['publish', 'draft', 'pending']),
    'is_featured' => S::nullable(S::bool()), 'image_id' => S::nullable(S::int()), 'activity_type' => S::nullable(S::str('', 'Adventure')), 'thrill' => S::nullable(S::enum(['easy', 'moderate', 'thrill'])),
    'hero_url' => S::nullable(S::url('Main photo, ready to display.')), 'gallery_urls' => S::arr(S::url(), 'Gallery photos.'), 'faqs_parsed' => S::arr(S::obj(['title' => S::str(), 'content' => S::str()]), 'Questions and answers.'),
    'duration_hours' => S::int('Same as `duration`.', 3), 'category_name' => S::nullable(S::str('', 'Adventure')),
    'tiers' => S::arr(S::ref('TourOption'), 'Its Classic / Signature / Sublime options, when set up.'), 'from_price' => S::nullable(S::num('The cheapest option, per person.', 106.67)),
    'created_at' => S::dt(), 'updated_at' => S::dt(),
], ['id', 'title', 'status'], 'A tour as stored, with the website-ready fields added.'));
Doc::schema('HotelRecord', S::obj([
    'id' => S::int('', 45), 'title' => S::str('', 'Falls Lodge'), 'slug' => S::str(), 'content' => S::nullable(S::str()), 'price' => S::str('Decimal string.', '150.00'), 'star_rate' => S::nullable(S::int('', 4)),
    'location_id' => S::nullable(S::int()), 'address' => S::nullable(S::str()), 'map_lat' => S::nullable(S::str()), 'map_lng' => S::nullable(S::str()), 'phone' => S::nullable(S::str()),
    'check_in_time' => S::nullable(S::str('', '14:00')), 'check_out_time' => S::nullable(S::str('', '10:00')), 'status' => S::enum(['publish', 'draft', 'pending']), 'image_id' => S::nullable(S::int()),
    'hero_url' => S::nullable(S::url('Main photo, ready to display.')), 'created_at' => S::dt(), 'updated_at' => S::dt(),
], ['id', 'title', 'status'], 'A stay as stored, with `hero_url` added.'));
Doc::schema('ServiceRecord', S::obj([
    'id' => S::int('', 9), 'title' => S::str('', 'Harare City Pass'), 'slug' => S::str(), 'content' => S::nullable(S::str()), 'price' => S::nullable(S::str('Decimal string. Boats have none.', '25.00')),
    'location_id' => S::nullable(S::int()), 'status' => S::enum(['publish', 'draft', 'pending']), 'created_at' => S::dt(), 'updated_at' => S::dt(),
], ['id', 'title', 'status'], 'A car, boat or event as stored. The type-specific fields (seats, gears, dates) are present too and not listed here.'));

// ── Tours ────────────────────────────────────────────────────────────────────

Doc::op('GET', '/services/tours')->tag('Listings')->scope('services:read')->legacy($legacyNote)
    ->summary('List your tours (full records)')
    ->description("Everything about your tours for a website: prices, photos, FAQs, the category name and each tour's options. For a lighter, all-types list use `GET /services`. Add `category_id` to filter by category.")
    ->query($listQuery([P::int('category_id', 'Only this category.')]))->returns(200, $paged('TourRecord'));
Doc::op('GET', '/services/tours/trending')->tag('Insights')->scope('services:read')
    ->summary('Trending tours')
    ->description("Your tours ranked by what is being booked lately: bookings in the last 30 days, the last 7 counting double; ties go to the better review score; unpaid and cancelled bookings do not count. Tours you pinned come first. Each is a full tour record plus a `shelf` block saying why it is there.")
    ->query([P::int('limit', '1 to 30.', ['default' => 10])])->returns(200, S::obj(['data' => S::arr(['allOf' => [S::ref('TourRecord'), S::obj(['shelf' => S::obj(['reason' => S::str('Why it is on the shelf.', '3 bookings this week'), 'pinned' => S::bool(), 'bookings_total' => S::int('', 12), 'bookings_30d' => S::int('', 5)])])]])]));
Doc::op('GET', '/services/tours/bestsellers')->tag('Insights')->scope('services:read')
    ->summary('Bestselling tours')->description('Your most booked tours of all time (unpaid and cancelled bookings do not count), pinned ones first. Same shape as trending.')
    ->query([P::int('limit', '1 to 30.', ['default' => 10])])->returns(200, S::obj(['data' => S::arr(['allOf' => [S::ref('TourRecord'), S::obj(['shelf' => S::obj(['reason' => S::str('', '48 trips booked'), 'pinned' => S::bool(), 'bookings_total' => S::int(), 'bookings_30d' => S::int()])])]])]));
Doc::op('GET', '/services/tours/{id}')->tag('Listings')->scope('services:read')->summary('Get a tour (full record)')->returns(200, S::one('TourRecord'));

$tourBody = [
    'title' => S::str('', 'Tandem Gorge Swing'), 'content' => S::str('HTML description.', '<p>...</p>'), 'status' => S::enum(['publish', 'draft', 'pending'], 'Default draft.', 'draft'),
    'location_id' => S::int('', 6), 'category_id' => S::int('', 3), 'duration' => S::num('Hours.', 3), 'price' => S::num('', 200),
    'min_people' => S::int('', 1), 'max_people' => S::int('**Set this**, or the tour cannot be booked: it is the number of guests it takes.', 20),
    'activity_type' => S::str('', 'Adventure'), 'time_slot' => S::int('0 to 5: which part of the day.', 1), 'start_time' => S::str('HH:MM', '08:00'), 'zone' => S::int('1 to 4.', 2),
    'thrill' => S::enum(['easy', 'moderate', 'thrill']), 'min_age' => S::int('0 to 99.', 12), 'address' => S::str(), 'map_lat' => S::num('-90 to 90.', -17.92), 'map_lng' => S::num('-180 to 180.', 25.85),
    'image_id' => S::int('A photo from your media library.', 440), 'is_featured' => S::bool(),
    'include' => S::arr(S::obj(['title' => S::str('', 'Guide')]), 'What is included.'), 'exclude' => S::arr(S::obj(['title' => S::str('', 'Lunch')]), 'What is not.'),
    'stages' => S::arr(S::obj(['at' => S::int('Minutes from the start.', 0), 'title' => S::str('', 'Meet at the gate'), 'detail' => S::str()]), 'The steps of a package.'),
    'package_nights' => S::int('0 to 60.', 3), 'package_board' => S::enum(['full_board', 'half_board', 'bed_and_breakfast']),
];
Doc::op('POST', '/services/tours')->tag('Listings')->scope('services:write')
    ->summary('Create a tour')
    ->description("Creates a listing that starts as a **draft**. Your plan may limit how many listings you can create (`plan_limit_reached`). Then: set its seats (`PUT /services/tours/{id}/capacity`), its options (`PUT /services/tours/{id}/tiers/{key}`) and publish it (`PATCH /services/tour/{id}/status`).")
    ->body(S::obj($tourBody, ['title']))->errors(['plan_limit_reached' => [403, 'Your plan allows no more listings of this kind.']])->returns(201, S::obj(['message' => S::str('', 'Tour created.'), 'data' => S::ref('TourRecord')]));
Doc::op('PUT', '/services/tours/{id}')->tag('Listings')->scope('services:write')->summary('Change a tour')
    ->description('Send only what changes.')->body(S::obj($tourBody), false)->returns(200, S::obj(['message' => S::str('', 'Tour updated.'), 'data' => S::ref('TourRecord')]));
Doc::op('DELETE', '/services/tours/{id}')->tag('Listings')->scope('services:write')->summary('Delete a tour')
    ->description('Moves it to the recovery bin (restore it with `POST /services/tour/{id}/restore`).')->legacy('Answers 200 with a message rather than 204.')->returns(200, S::obj(['message' => S::str('', 'Tour deleted.')]));

// ── Hotels ───────────────────────────────────────────────────────────────────

Doc::op('GET', '/services/hotels')->tag('Listings')->scope('services:read')->legacy($legacyNote)->summary('List your stays (full records)')
    ->query($listQuery())->returns(200, $paged('HotelRecord'));
Doc::op('GET', '/services/hotels/{id}')->tag('Listings')->scope('services:read')->summary('Get a stay')->returns(200, S::one('HotelRecord'));
$hotelBody = [
    'title' => S::str('', 'Falls Lodge'), 'content' => S::str(), 'status' => S::enum(['publish', 'draft', 'pending'], 'Default draft.', 'draft'), 'location_id' => S::int('', 6),
    'address' => S::str(), 'star_rate' => S::int('1 to 5.', 4), 'price' => S::num('', 150), 'map_lat' => S::num('', -17.92), 'map_lng' => S::num('', 25.85),
    'image_id' => S::int('A photo from your media library.', 440), 'phone' => S::str(), 'website' => S::str(), 'is_featured' => S::bool(),
];
Doc::op('POST', '/services/hotels')->tag('Listings')->scope('services:write')->summary('Create a stay')
    ->description('Starts as a draft. Rooms are managed in the portal.')->body(S::obj($hotelBody, ['title']))
    ->errors(['plan_limit_reached' => [403, 'Your plan allows no more listings of this kind.']])->returns(201, S::obj(['message' => S::str('', 'Hotel created.'), 'data' => S::ref('HotelRecord')]));
Doc::op('PUT', '/services/hotels/{id}')->tag('Listings')->scope('services:write')->summary('Change a stay')->description('Send only what changes.')
    ->body(S::obj($hotelBody), false)->returns(200, S::obj(['message' => S::str('', 'Hotel updated.'), 'data' => S::ref('HotelRecord')]));
Doc::op('DELETE', '/services/hotels/{id}')->tag('Listings')->scope('services:write')->summary('Delete a stay')->legacy('Answers 200 with a message rather than 204.')
    ->returns(200, S::obj(['message' => S::str('', 'Hotel deleted.')]));

// ── Cars, boats, events (read) ───────────────────────────────────────────────

foreach ([['cars', 'cars', 'a car'], ['boats', 'boats', 'a boat'], ['events', 'events', 'an event']] as [$seg, $plural, $one]) {
    Doc::op('GET', "/services/{$seg}")->tag('Listings')->scope('services:read')->legacy($legacyNote)->summary("List your {$plural} (full records)")
        ->query($listQuery())->returns(200, $paged('ServiceRecord'));
    Doc::op('GET', "/services/{$seg}/{id}")->tag('Listings')->scope('services:read')->summary("Get {$one}")->returns(200, S::one('ServiceRecord'));
}

// ── Availability and departures of one tour ─────────────────────────────────

Doc::op('GET', '/services/{type}/{id}/availability')->tag('Seats and options')->scope('services:read')
    ->summary('Availability of one listing')
    ->path(['type' => ['string', 'tour, hotel, car, boat or event.']])
    ->description("What can be booked and when, as the booking engine sees it. For a tour, pass the month you want; for a stay, pass dates and party to get the rooms. The shape depends on the type, so it is returned as the engine gives it, with `booking_data` describing what a booking needs.\n\nFor a **tour's seats**, prefer `GET /services/tours/{id}/departures`, which is designed for calendars.")
    ->query([P::date('start', 'Range start (tours and cars).'), P::date('end', 'Range end.'), P::date('start_date', 'Check-in (stays).'), P::date('end_date', 'Check-out (stays).'), P::int('adults', 'Party (stays).'), P::int('children', 'Children (stays).')])
    ->errors(['invalid_type' => [422, 'That type cannot be booked.'], 'availability_error' => [500, 'The engine could not answer.']])
    ->returns(200, S::obj(['data' => S::obj(['availability' => ['description' => 'Days or rooms, depending on the type.'], 'booking_data' => ['type' => 'object', 'description' => 'What a booking of this listing needs.', 'additionalProperties' => true]])]));

Doc::op('GET', '/services/tours/{id}/departures')->tag('Seats and options')->scope('services:read')
    ->summary('The days a tour can be booked, with seats left')
    ->description("Built for a calendar: each day says whether it is `open`, `filling` (20% or 2 seats left, whichever is more), `full` or `closed`, with `seats_left`, or null when you set no limit. A tour that runs any day lists every day in the range up to its usual capacity; one set to *only on listed days* lists just its departures. A booking that is not paid yet holds its seats for 30 minutes.\n\nUp to 120 days per call. Booking more than the seats left answers `409 sold_out`.")
    ->query([P::date('from', 'Default today.'), P::date('to', 'Default 30 days on.')])
    ->returns(200, S::obj(['data' => S::obj([
        'tour_id' => S::int('', 123), 'mode' => S::enum(['any_day', 'listed_days'], '`any_day`: every day is listed. `listed_days`: only your own departures.'), 'capacity' => S::nullable(S::int('The tour\'s usual seats.', 20)),
        'days' => S::arr(S::obj(['date' => S::date(), 'status' => S::enum(['open', 'filling', 'full', 'closed']), 'capacity' => S::nullable(S::int('', 20)), 'seats_left' => S::nullable(S::int('', 17)), 'price' => S::nullable(S::num('A price for this day.', 220))])),
    ])]));
