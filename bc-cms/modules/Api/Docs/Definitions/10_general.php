<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;
use Modules\Vendor\Models\VendorUpsell;

Doc::op('GET', '/me')->tag('Getting started')->auth('key')
    ->summary('Who this key belongs to')
    ->description("The account, its plan and subscription, and its active keys with how much each has been used this month. A good first call to check a key works. The response header `X-Tsoka-Mode` says whether it was a `test` or `live` key.\n\nNeeds no scope: any valid key can call it.")
    ->returns(200, S::obj(['data' => S::obj([
        'vendor' => S::obj(['id' => S::int('', 7), 'name' => S::str('', 'Luxsav'), 'email' => S::email(), 'plan' => S::nullable(S::str('', 'Enterprise Plan')), 'plan_expires_at' => S::nullable(S::str('', '2027-04-01 02:00:00')),
            'os' => S::arr(S::obj(['key' => S::str('The Tanova OS: stay, exp, trans, event, airline or visa.', 'stay'), 'name' => S::str('', 'Stay OS'), 'label' => S::str('', 'Stays')]), 'The kinds of business this company operates, so its own website can show the same badges. Which listing types you can create follows from these.')]),
        'subscription' => S::nullable(S::obj(['status' => S::str('', 'active'), 'ends_at' => S::nullable(S::dt()), 'plan' => S::nullable(S::str()), 'billing_cycle' => S::nullable(S::str())])),
        'api_keys' => S::arr(S::obj(['id' => S::int('', 3), 'name' => S::str('', 'Website (read-only)'), 'rate_limit' => S::int('The requests this key may make per calendar year (0 = unlimited; test keys are never capped).', 100000), 'used_this_month' => S::int('', 5400), 'last_used_at' => S::nullable(S::dt())])),
    ])]));

Doc::op('GET', '/destinations')->tag('Catalogue')->scope('services:read')
    ->summary('The places you have something in')
    ->description('Destinations (cities, areas) with something published, with their country, a teaser, coordinates and counts of what is on offer. Use a destination `id` as `location_id` for `GET /catalogue`.')
    ->returns(200, S::obj(['data' => S::obj([
        'countries' => S::arr(S::obj(['id' => S::int('', 1), 'name' => S::str('', 'Zimbabwe'), 'description' => S::nullable(S::str()), 'image' => S::nullable(S::url())])),
        'destinations' => S::arr(S::obj([
            'id' => S::int('', 6), 'name' => S::str('', 'Victoria Falls'), 'country_id' => S::int('', 1), 'country' => S::str('', 'Zimbabwe'),
            'teaser' => S::nullable(S::str()), 'description' => S::nullable(S::str()), 'image' => S::nullable(S::url()), 'lat' => S::nullable(S::num('', -17.92)), 'lng' => S::nullable(S::num('', 25.85)),
            'counts' => ['type' => 'object', 'description' => 'How many of each kind of thing is offered there.', 'additionalProperties' => S::int(), 'example' => ['activities' => 46, 'stays' => 11]],
        ])),
    ])]));

Doc::op('GET', '/catalogue')->tag('Catalogue')->scope('services:read')
    ->summary('Everything one destination offers, in one request')
    ->description("Activities, packages, stays, transport and restaurants for one destination, in the shape the LuxSav app uses. One call instead of five, and `updated_since` makes a sync cheap. Ids are prefixed by kind (`day_trip-1`, `stay-4`) and `source_id` is the id in your own listing.\n\nOnly published listings appear. Each item's fields are documented as the ones you can rely on; more may be added.")
    ->query([
        P::int('location_id', 'The destination. Required (or `location`).', ['required' => true, 'example' => 6]),
        P::str('location', 'The destination by name, when you do not have its id.', ['example' => 'Victoria Falls']),
        P::str('types', 'Comma separated: `activities`, `packages`, `stays`, `transports`, `restaurants`. Default all.', ['example' => 'activities,stays']),
        P::str('updated_since', 'A date or date-time: only what changed since then.', ['example' => '2026-09-01']),
        P::str('lang', 'The language for names and descriptions, e.g. `fr`; where something is not translated it stays in your default language. Also read from `Accept-Language`. The response says which under `language`.', ['example' => 'fr']),
    ])
    ->errors(['location_required' => [422, 'Neither `location_id` nor `location` was given.'], 'location_not_found' => [404, 'No such destination.']])
    ->returns(200, S::obj(['data' => S::obj([
        'location' => S::obj(['id' => S::int('', 6), 'name' => S::str('', 'Victoria Falls'), 'lat' => S::nullable(S::num()), 'lng' => S::nullable(S::num())]),
        'generated_at' => S::dt(),
        'activities' => S::arr(S::obj([
            'id' => S::str('', 'day_trip-1'), 'source_id' => S::int('Your listing id.', 463), 'category' => S::str('', 'activity'), 'subtype' => S::str('', 'day_trip'), 'name' => S::str('', 'Zambezi sunset cruise'),
            'destination_id' => S::int('', 6), 'price' => S::nullable(S::num('', 60)), 'sale_price' => S::nullable(S::num()), 'price_unit' => S::str('', 'per person'), 'duration_hours' => S::nullable(S::int('', 3)),
            'type' => S::nullable(S::str('', 'Safari')), 'image' => S::nullable(S::url()), 'images' => S::arr(S::url()), 'description' => S::nullable(S::str()),
            'includes' => S::arr(S::str('', 'Naturalist guide')), 'excludes' => S::arr(S::str('', 'Equipment rental')), 'address' => S::nullable(S::str()),
            'slot' => S::nullable(S::str('Part of the day it runs.', 'morning')), 'start' => S::nullable(S::str('', '08:00')), 'start_minutes' => S::nullable(S::int('', 480)),
            'min_age' => S::nullable(S::int()), 'min_pax' => S::nullable(S::int()), 'max_pax' => S::nullable(S::int()), 'thrill' => S::nullable(S::str('', 'easy')),
            'lat' => S::nullable(S::num()), 'lng' => S::nullable(S::num()), 'zone' => S::nullable(S::int('Where it is within the destination.', 2)), 'featured' => S::bool(), 'stages' => S::arr(S::obj([]), 'The steps of a package.'),
            'location_approx' => S::bool('True when the coordinates are the destination\'s, not the place\'s own.', false),
        ]), 'Activities and day trips. `packages`, `stays`, `transports` and `restaurants` follow the same idea and appear when asked for.'),
        'counts' => ['type' => 'object', 'additionalProperties' => S::int(), 'example' => ['activities' => 46]],
    ])]));

Doc::op('GET', '/upsells')->tag('Seats and options')->scope('services:read')
    ->summary('What to offer next to a service')
    ->description("The add-ons a guest can be offered, **published ones only**, highlighted first, then featured, then your order. With `service_type` and `service_id` you get that service's own add-ons plus the global ones, each at **the price that applies there**. (To maintain the catalogue itself, including drafts, use `/addons`.)")
    ->query([
        P::enum('service_type', ['tour', 'hotel', 'car', 'boat', 'event', 'space'], 'The kind of service. Needs `service_id`.'), P::int('service_id', 'The service. Needs `service_type`.'),
        P::enum('category', array_keys(VendorUpsell::CATEGORIES), 'Only this kind of extra.'),
    ])
    ->returns(200, S::many('Upsell'));
Doc::schema('Upsell', S::obj([
    'id' => S::int('', 2), 'name' => S::str('', 'Airport transfer'), 'category' => S::enum(array_keys(VendorUpsell::CATEGORIES)), 'short_description' => S::nullable(S::str()), 'description' => S::nullable(S::str()),
    'image' => S::nullable(S::url()), 'price' => S::num('The price that applies (the per-service price when there is one).', 30), 'price_type' => S::enum(array_keys(VendorUpsell::PRICE_TYPES)),
    'price_label' => S::str('How a guest reads it.', '$30'), 'is_featured' => S::bool(), 'is_highlighted' => S::bool('Shown first on this service.', false),
], ['id', 'name', 'price']));

Doc::op('GET', '/services/restaurants')->tag('Catalogue')->scope('services:read')
    ->summary('Your restaurants')
    ->description('The restaurants you list, with opening hours and dietary options parsed out of their descriptions.')
    ->returns(200, S::obj(['data' => S::obj(['data' => S::arr(S::obj([
        'id' => S::int('', 12), 'name' => S::str('', '40 Cork Road Restaurant'), 'location' => S::nullable(S::str('', 'Harare')), 'summary' => S::nullable(S::str()), 'cuisine' => S::nullable(S::str('', 'African')),
        'price_text' => S::nullable(S::str('', '$15 to $25 a person')), 'price_estimate' => S::nullable(S::num()), 'price_band' => S::nullable(S::str('', '$$')),
        'opens' => S::nullable(S::str('', '11:00')), 'closes' => S::nullable(S::str('', '21:00')), 'hours' => S::nullable(S::str('', '11:00–21:00')), 'url' => S::nullable(S::url()),
        'offered_by' => S::nullable(S::str()), 'is_partner' => S::bool(), 'lat' => S::nullable(S::num()), 'lng' => S::nullable(S::num()), 'dietary' => S::arr(S::str('', 'vegetarian')),
    ])), 'total' => S::int('', 101)])]));

// ── The documentation itself (public: no key) ────────────────────────────────

Doc::op('GET', '/openapi.json')->tag('Getting started')->auth('none')
    ->summary('This API as an OpenAPI 3.1 file')
    ->description('The whole API in one machine-readable file, for code generators, API clients and gateways. Built from the same description as this reference, so it cannot disagree with it. Needs no key.')
    ->returns(200, ['type' => 'object', 'description' => 'An OpenAPI 3.1 document.', 'additionalProperties' => true, 'example' => ['openapi' => '3.1.0', 'info' => ['title' => 'Tsoka Vendor API']]]);
Doc::op('GET', '/postman.json')->tag('Getting started')->auth('none')
    ->summary('A Postman collection')
    ->description('Import it into Postman, set the `api_key` variable to a test key, and every endpoint is one click away. Needs no key.')
    ->returns(200, ['type' => 'object', 'description' => 'A Postman collection v2.1.', 'additionalProperties' => true, 'example' => ['info' => ['name' => 'Tsoka Vendor API']]]);
Doc::op('GET', '/docs')->tag('Getting started')->auth('none')
    ->summary('This documentation, as a web page')
    ->description('The reference page with the guides. `?section=` picks a guide or an area. Needs no key.')
    ->file('text/html');
Doc::op('GET', '/swagger')->tag('Getting started')->auth('none')
    ->summary('This API in Swagger UI')
    ->description('The standard Swagger UI over the OpenAPI file, to browse and try every endpoint. Needs no key: enter one under *Authorize*.')
    ->file('text/html');
