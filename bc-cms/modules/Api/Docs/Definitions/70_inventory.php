<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;

$types = ['tour', 'hotel', 'car', 'boat', 'space', 'event', 'flight', 'visa'];

// ── Listings ─────────────────────────────────────────────────────────────────

Doc::schema('Listing', S::obj([
    'type' => S::enum($types, 'The kind of listing.', 'tour'),
    'id' => S::int('', 123), 'title' => S::str('', 'Tandem Gorge Swing'),
    'status' => S::enum(['publish', 'draft', 'pending'], '`publish`: offered. `draft`: hidden. `pending`: awaiting review.'),
    'price' => S::nullable(S::num('Base price. Null for types that have none (boats, flights).', 200)),
    'location' => S::nullable(S::obj(['id' => S::int('', 7), 'name' => S::nullable(S::str('', 'Victoria Falls'))])),
    'image_url' => S::nullable(S::url('Main photo.', 'https://portal.example.com/uploads/tsokanew/activities/swing.jpg')),
    'is_featured' => S::bool('', false),
    'deleted' => S::bool('True in the recovery bin.', false),
    'created_at' => S::nullable(S::dt()), 'updated_at' => S::nullable(S::dt()),
], ['type', 'id', 'title', 'status']));

Doc::op('GET', '/services')->tag('Listings')->scope('services:read')
    ->summary('List everything you sell')
    ->description("Tours, hotels, cars, boats, spaces, events, flights and visa services in **one list**, with one shape, searchable and filterable. Use it for a catalogue screen or a sync. For the full details of a tour or hotel use its own endpoint (`/services/tours/{id}`).\n\nThis is a compact view; it does not include descriptions, galleries or availability.")
    ->query([
        P::q('title (or an id)'),
        P::str('type', 'One or more types, comma separated: ' . implode(', ', $types) . '.', ['example' => 'tour,hotel']),
        P::enum('status', ['publish', 'draft', 'pending'], 'Only this status.'),
        P::int('location_id', 'Only listings in this place. Types with no place are left out.', ['example' => 7]),
        P::enum('deleted', ['only'], '`only`: show the recovery bin instead.'),
        P::sort(['newest' => 'newest first', 'oldest' => 'oldest first', 'title' => 'name A to Z', 'title_desc' => 'name Z to A', 'updated' => 'recently updated', 'price_asc' => 'price, low to high', 'price_desc' => 'price, high to low'], 'newest'),
        ...P::paging(),
    ])
    ->returns(200, S::page('Listing'));

Doc::op('GET', '/services/{type}/{id}')->tag('Listings')->scope('services:read')
    ->summary('Get one listing, compactly')
    ->path(['type' => ['string', 'One of: ' . implode(', ', $types) . '.']])
    ->description('The same compact shape as the list, for any type, including types that have no endpoint of their own (spaces, flights, visas).')
    ->returns(200, S::one('Listing'));

Doc::op('PATCH', '/services/{type}/{id}/status')->tag('Listings')->scope('services:write')
    ->summary('Publish or hide a listing')
    ->path(['type' => ['string', 'One of: ' . implode(', ', $types) . '.']])
    ->description('A hidden listing is not offered to guests or on your site. Bookings already made are untouched.')
    ->body(S::obj(['status' => S::enum(['publish', 'draft'], '', 'draft')], ['status']))
    ->returns(200, S::one('Listing'));

Doc::op('DELETE', '/services/{type}/{id}')->tag('Listings')->scope('services:write')
    ->summary('Delete a listing (to the recovery bin)')
    ->path(['type' => ['string', 'One of: ' . implode(', ', $types) . '.']])
    ->description('Moves it to the recovery bin, from where `POST .../restore` brings it back. Bookings already made are untouched.')
    ->returns(204);

Doc::op('POST', '/services/{type}/{id}/restore')->tag('Listings')->scope('services:write')
    ->summary('Restore a deleted listing')
    ->path(['type' => ['string', 'One of: ' . implode(', ', $types) . '.']])
    ->returns(200, S::one('Listing'));

Doc::op('GET', '/locations')->tag('Listings')->scope('services:read')
    ->summary('Places')->description('The destinations a listing can be in, to use as `location_id`.')
    ->query([P::q('name'), ...P::paging()])
    ->returns(200, S::page('Place'));
Doc::schema('Place', S::obj(['id' => S::int('', 7), 'name' => S::str('', 'Victoria Falls'), 'parent_id' => S::nullable(S::int('The country or region it is in.', 1))], ['id', 'name']));

Doc::op('GET', '/categories/tours')->tag('Listings')->scope('services:read')
    ->summary('Tour categories')->returns(200, S::many('Category'));
Doc::schema('Category', S::obj(['id' => S::int('', 3), 'name' => S::str('', 'Adventure')], ['id', 'name']));

// ── Departures and seats ─────────────────────────────────────────────────────

Doc::schema('Departure', S::obj([
    'id' => S::int('', 88),
    'tour' => S::obj(['id' => S::int('', 123), 'title' => S::str('', 'Tandem Gorge Swing')]),
    'date' => S::date('The day it runs.', '2026-11-03'), 'end_date' => S::date('Same as `date` for a one-day departure.', '2026-11-03'),
    'capacity' => S::nullable(S::int('Seats. This departure\'s own, else the tour\'s usual. Null: no limit.', 20)),
    'taken' => S::int('Seats gone: paid, in progress or confirmed, plus unpaid bookings still inside their 30-minute hold.', 17),
    'held' => S::int('Of those, how many are unpaid bookings still holding seats.', 2),
    'seats_left' => S::nullable(S::int('', 3)),
    'price' => S::nullable(S::num('A price for this day that overrides the tour\'s.', 220)),
    'active' => S::bool('False when you have closed the day.'),
    'status' => S::enum(['open', 'filling', 'full', 'closed'], '`filling`: 20% or 2 seats left, whichever is more. `full`: none left. `closed`: you switched it off.'),
], ['id', 'tour', 'date', 'status']));

Doc::op('GET', '/departures')->tag('Seats and options')->scope('services:read')
    ->summary('The departure board')
    ->description("The days your tours run with seats taken and left, across **all tours** (for one tour's days, including days that need no departure row, use `GET /services/tours/{id}/departures`). Default window: today for 60 days. Same board as **Departures & seats** in the portal.\n\n`meta.summary` counts full departures, filling ones and seats left across the whole window, not only this page.")
    ->query([
        P::date('from', 'Window start. Default today.'), P::date('to', 'Window end. Default 60 days on.'),
        P::int('tour_id', 'Only this tour.'), P::q('tour name'),
        P::enum('status', ['open', 'filling', 'full', 'closed'], 'Only this state.'),
        ...P::paging(),
    ])
    ->returns(200, S::obj(['data' => S::arr(S::ref('Departure')), 'meta' => ['allOf' => [S::ref('PageMeta'), S::obj(['summary' => S::obj(['full' => S::int('', 3), 'filling' => S::int('', 5), 'seats_left' => S::int('', 84)])])]]]));

Doc::op('POST', '/departures')->tag('Seats and options')->scope('services:write')
    ->summary('Schedule departures')
    ->description("Adds departures for one or more of **your** tours: one day, or a stretch of days on chosen weekdays (`0` is Sunday). A day that already has a departure is **updated**, never doubled. Up to 2,000 departures in one call.\n\nOnce a tour has departures and is set to *only on listed days* (`PUT /services/tours/{id}/capacity`), guests can book only those days.")
    ->body(S::obj([
        'tour_ids' => S::arr(S::int('', 123), 'Your tours. Up to 50.'),
        'from' => S::date('Today or later.', '2026-11-01'), 'to' => S::date('Omit for a single day.', '2026-11-30'),
        'weekdays' => S::arr(S::int('0 (Sunday) to 6 (Saturday).', 6), 'Only these weekdays. Omit for every day.'),
        'capacity' => S::int('Seats on each departure.', 20), 'price' => S::num('Optional price for these days.', 220),
    ], ['tour_ids', 'from', 'capacity']))
    ->errors(['no_tours' => [404, 'None of the tours is yours.'], 'no_days' => [422, 'No day in the range falls on the weekdays given.'], 'too_many' => [422, 'More than 2,000 departures in one call.']])
    ->returns(201, S::obj(['data' => S::obj(['made' => S::int('Created.', 12), 'changed' => S::int('Already existed and updated.', 3), 'first' => S::date(), 'last' => S::date('', '2026-11-30')])]));

Doc::op('PATCH', '/departures/{id}')->tag('Seats and options')->scope('services:write')
    ->summary('Change or close a departure')
    ->description("Send only what changes. `active: false` closes the day: it stops selling, nothing already booked changes. **A departure cannot hold fewer seats than are already booked** (`below_booked`).")
    ->body(S::obj(['capacity' => S::int('', 24), 'price' => S::nullable(S::num('Send null to fall back to the tour\'s price.', 240)), 'active' => S::bool('', true)]), false)
    ->errors(['below_booked' => [409, 'People have already booked more seats than that.']])
    ->returns(200, S::one('Departure'));

Doc::op('DELETE', '/departures/{id}')->tag('Seats and options')->scope('services:write')
    ->summary('Delete a departure')
    ->description('Only when nobody has booked that day. Otherwise **close** it instead.')
    ->errors(['has_bookings' => [409, 'People have booked that day. Close it instead.']])->returns(204);

Doc::op('PUT', '/services/tours/{id}/capacity')->tag('Seats and options')->scope('services:write')
    ->summary('Set a tour\'s usual seats and how it runs')
    ->description("`capacity` is the number of guests the tour takes on a day with no departure of its own (`0` or null: no limit). `only_listed: true` makes the tour bookable **only on days you have listed** as departures; otherwise it runs any day.")
    ->body(S::obj(['capacity' => S::nullable(S::int('Null or 0: no limit.', 20)), 'only_listed' => S::bool('Only on listed departures.', false)]), false)
    ->returns(200, S::obj(['data' => S::obj(['tour_id' => S::int('', 123), 'capacity' => S::nullable(S::int('', 20)), 'only_listed' => S::bool('', false)])]));

// ── Options (tiers) ──────────────────────────────────────────────────────────

Doc::schema('TourOption', S::obj([
    'id' => S::int('', 2), 'key' => S::enum(['classic', 'signature', 'sublime']), 'name' => S::str('', 'Signature'), 'tagline' => S::nullable(S::str('', 'The considered choice')),
    'description' => S::nullable(S::str()), 'recommended' => S::bool('', true),
    'price' => S::num('Base price.', 190), 'price_per_person' => S::bool('False: one price for the whole group.', true),
    'from_price' => S::num('The cheapest a guest could pay, as a per-person figure when a per-person price or a group price applies. Shown as "from".', 106.67), 'from_per' => S::enum(['person', 'group']),
    'min_guests' => S::nullable(S::int('', 2)), 'max_guests' => S::nullable(S::int('', 8)),
    'bands' => S::arr(S::obj(['min' => S::int('', 4), 'max' => S::nullable(S::int('Null: and up.', 6)), 'total' => S::money('The fixed total for a party in this range.', 640.0)]), 'Group prices. They win over the base price when the party falls inside one.'),
    'inclusions' => S::arr(S::str('', 'Riverside lunch')),
    'included_addons' => S::arr(S::obj(['id' => S::int('', 2), 'name' => S::str('', 'Airport transfer')]), 'Add-ons bundled in at no charge.'),
    'available_for_party' => S::bool('Only with `?guests=`: does this option take that party?', true), 'total_for_party' => S::money('Only with `?guests=`: the price for that party.', 640.0),
], ['id', 'key', 'name', 'price']));

Doc::op('GET', '/services/tours/{id}/tiers')->tag('Seats and options')->scope('services:read')
    ->summary('The Classic / Signature / Sublime options of a tour')
    ->description("Up to three named ways to buy one tour, each with its own price, group prices and inclusions. Add `?guests=N` and each option says whether it takes that party and what it would cost. Every tour in the tour list also carries its options as `tiers` and a `from_price`.\n\nBook one by sending its `id` as `tier_id` when you create the booking.")
    ->query([P::int('guests', 'The party size to price for.', ['example' => 5])])
    ->returns(200, S::many('TourOption'));

Doc::op('PUT', '/services/tours/{id}/tiers/{key}')->tag('Seats and options')->scope('services:write')
    ->summary('Create or replace an option')
    ->path(['key' => ['string', 'classic, signature or sublime.']])
    ->description("Sets one option of a tour. **Group prices** (`bands`) are fixed totals for a range of party sizes and **must not overlap**; a `max` of null means \"and up\". The option needs a price or at least one group price. Only one option per tour can be `recommended`. `addon_ids` bundles those of **your** add-ons in free.")
    ->body(S::obj([
        'name' => S::str('', 'Signature'), 'tagline' => S::str('', 'The considered choice'), 'description' => S::str(),
        'price' => S::num('Base price.', 190), 'price_per_person' => S::bool('Default true.', true),
        'min_guests' => S::int('', 2), 'max_guests' => S::int('', 8),
        'bands' => S::arr(S::obj(['min' => S::int('', 4), 'max' => S::int('', 6), 'total' => S::money('', 640.0)], ['min', 'total'])),
        'inclusions' => S::arr(S::str('', 'Riverside lunch')), 'addon_ids' => S::arr(S::int('', 2)),
        'recommended' => S::bool('', true), 'active' => S::bool('Offered to guests. Default true.', true),
    ], ['name']))
    ->errors(['unknown_tier' => [404, 'The key is not classic, signature or sublime.'], 'band_overlap' => [422, 'Two group prices cover the same party sizes.'], 'band_range' => [422, 'A group price runs to a number below where it starts.'], 'price_required' => [422, 'No price and no group price.']])
    ->returns(200, S::one('TourOption'));

Doc::op('DELETE', '/services/tours/{id}/tiers/{key}')->tag('Seats and options')->scope('services:write')
    ->summary('Remove an option')->path(['key' => ['string', 'classic, signature or sublime.']])
    ->description('Bookings already made keep their price.')->returns(204);

// ── Add-ons catalogue ────────────────────────────────────────────────────────

$cats = array_keys(\Modules\Vendor\Models\VendorUpsell::CATEGORIES);
$priceTypes = array_keys(\Modules\Vendor\Models\VendorUpsell::PRICE_TYPES);

Doc::schema('Addon', S::obj([
    'id' => S::int('', 2), 'name' => S::str('', 'Photo package'), 'category' => S::enum($cats, 'What kind of extra.'),
    'short_description' => S::nullable(S::str('One line the guest sees.', 'A photographer joins you all morning')), 'description' => S::nullable(S::str()),
    'image_url' => S::nullable(S::url()), 'price' => S::num('', 50), 'price_type' => S::enum($priceTypes, '`per_booking`: once. `per_person`: times the party. `per_day`: times the days. `per_night`: times the nights. `per_item`: times the quantity you set on the booking.'),
    'price_label' => S::str('How a guest reads it.', '$50 pp'), 'status' => S::enum(['publish', 'draft']), 'is_featured' => S::bool('Featured add-ons are offered first.', false),
    'is_global' => S::bool('Offered on every service. Otherwise only on the ones in `services`.', false), 'sort_order' => S::int('', 0),
    'services' => S::arr(S::obj(['object_model' => S::str('', 'tour'), 'object_id' => S::int('', 123), 'title' => S::nullable(S::str()), 'price_override' => S::nullable(S::num('A different price on this service.', 40)), 'is_highlighted' => S::bool('Shown first on this service.', false)]), 'Where it is offered, when it is not global.'),
    'created_at' => S::dt(),
], ['id', 'name', 'price', 'price_type']));

$addonBody = [
    'name' => S::str('', 'Photo package'), 'category' => S::enum($cats, ''), 'short_description' => S::str(), 'description' => S::str(),
    'image_id' => S::int('A photo from your media library.', 440), 'price' => S::num('', 50), 'price_type' => S::enum($priceTypes, ''),
    'status' => S::enum(['publish', 'draft'], 'Default publish.'), 'is_featured' => S::bool('', false), 'sort_order' => S::int('', 0),
    'is_global' => S::bool('Offer everywhere. Default true unless you send `services`.', false),
    'services' => S::arr(S::obj(['object_model' => S::enum(['tour', 'hotel', 'car', 'boat', 'event', 'space'], '', 'tour'), 'object_id' => S::int('', 123), 'price_override' => S::num('', 40), 'is_highlighted' => S::bool('', false)], ['object_model', 'object_id']), 'Only your own services are accepted; others are dropped.'),
];

Doc::op('GET', '/addons')->tag('Seats and options')->scope('services:read')
    ->summary('Your add-on catalogue')
    ->description('The add-ons you maintain, drafts included. (`GET /upsells` is different: it is the guest-facing list of what to offer next to one service, with the price that applies there.)')
    ->query([
        P::q('name or one-line description'), P::enum('category', $cats, 'Only this kind.'), P::enum('status', ['publish', 'draft'], 'Only this status.'),
        P::bool('featured', 'Only featured (`true`) or not featured (`false`).'),
        P::sort(['order' => 'your order', 'name' => 'name A to Z', 'newest' => 'newest', 'price_asc' => 'price, low to high', 'price_desc' => 'price, high to low'], 'order'),
        ...P::paging(),
    ])
    ->returns(200, S::page('Addon'));
Doc::op('GET', '/addons/{id}')->tag('Seats and options')->scope('services:read')->summary('Get an add-on')->returns(200, S::one('Addon'));
Doc::op('POST', '/addons')->tag('Seats and options')->scope('services:write')->summary('Create an add-on')
    ->description('Guests see it when it is `publish`. Pair it with a booking through `POST /bookings/{code}/addons`, or bundle it free into an option with `addon_ids`.')
    ->body(S::obj($addonBody, ['name', 'category', 'price', 'price_type']))->returns(201, S::one('Addon'));
Doc::op('PUT', '/addons/{id}')->tag('Seats and options')->scope('services:write')->summary('Change an add-on')
    ->description('Send only what changes. Send `services` to replace where it is offered.')->body(S::obj($addonBody), false)->returns(200, S::one('Addon'));
Doc::op('DELETE', '/addons/{id}')->tag('Seats and options')->scope('services:write')->summary('Delete an add-on')
    ->description('Bookings that already have it keep their line.')->returns(204);

// ── Insights and marketplace ─────────────────────────────────────────────────

Doc::op('GET', '/analytics/occupancy')->tag('Insights')->scope('analytics:read')
    ->summary('How full your tours are')
    ->description("Seats sold against seats available over the coming days, only on days that are **running** (a day with bookings, or listed as a departure), so empty days do not water the figure down. Tours with no capacity set have nothing to be full of and are left out. Cancelled and unpaid-and-expired bookings do not count.")
    ->query([P::int('days', 'Window from today, 1 to 120.', ['default' => 30])])
    ->returns(200, S::obj(['data' => S::obj([
        'days' => S::int('', 30), 'sold' => S::int('Seats sold.', 158), 'capacity' => S::int('Seats available.', 280),
        'percent' => S::nullable(S::int('Null when there is no capacity to measure against.', 56)),
        'tours' => S::arr(S::obj(['title' => S::str('', 'Tandem Gorge Swing'), 'sold' => S::int('', 40), 'capacity' => S::int('', 60), 'percent' => S::int('', 67)]), 'The eight fullest tours.'),
    ])]));

Doc::op('GET', '/shelves/pins')->tag('Insights')->scope('analytics:read')
    ->summary('Tours you pinned')->description('Pinned tours go first on the `trending` and `bestsellers` shelves whatever the numbers say.')
    ->returns(200, S::obj(['data' => S::obj(['trending' => S::arr(S::int('Tour id', 123)), 'bestsellers' => S::arr(S::int('Tour id', 124))])]));
Doc::op('PUT', '/shelves/{shelf}/pins/{tourId}')->tag('Insights')->scope('analytics:write')
    ->summary('Pin a tour to a shelf')->path(['shelf' => ['string', '`trending` or `bestsellers`.'], 'tourId' => ['integer', 'One of your tours.']])
    ->description('Idempotent. Returns all pins.')->returns(200, S::obj(['data' => S::obj(['trending' => S::arr(S::int()), 'bestsellers' => S::arr(S::int())])]));
Doc::op('DELETE', '/shelves/{shelf}/pins/{tourId}')->tag('Insights')->scope('analytics:write')
    ->summary('Unpin a tour')->path(['shelf' => ['string', '`trending` or `bestsellers`.'], 'tourId' => ['integer', 'The tour id.']])->returns(204);

Doc::op('GET', '/marketplace/tours')->tag('Marketplace')->scope('marketplace:read')
    ->summary('Which experiences AI assistants can find')
    ->description('Your published tours and whether each is switched on for AI assistants (ChatGPT, Claude and others) to discover and book.')
    ->query([P::q('tour name'), P::enum('state', ['on', 'off'], '`on`: switched on. `off`: not.'), P::sort(['newest' => 'newest', 'title' => 'name A to Z'], 'newest'), ...P::paging()])
    ->returns(200, S::page('MarketplaceTour'));
Doc::schema('MarketplaceTour', S::obj(['tour_id' => S::int('', 123), 'title' => S::str('', 'Tandem Gorge Swing'), 'price' => S::num('', 200), 'on_marketplace' => S::bool('', true)]));
Doc::op('PUT', '/marketplace/tours/{tourId}')->tag('Marketplace')->scope('marketplace:write')
    ->summary('Switch an experience on or off for AI assistants')
    ->path(['tourId' => ['integer', 'A published tour of yours.']])
    ->body(S::obj(['on' => S::bool('', true)], ['on']))
    ->returns(200, S::obj(['data' => S::obj(['tour_id' => S::int('', 123), 'on_marketplace' => S::bool('', true)])]));
