<?php

/**
 * Tanova OS: the kinds of business the portal serves. A company operates one or more; its plan decides how many. This is the single
 * place that says what "Stay OS" is: which listing types it covers, which sidebar entries belong to it, what it is called to a customer.
 *
 * types: the listing types (post_type keys of a plan's meta) the OS lets a company create
 * menu:  the vendor sidebar entry keys that appear when the company operates the OS
 */
return [

    'os' => [
        'stay'    => ['name' => 'Stay OS',    'label' => 'Stays',      'tagline' => 'Lodges, hotels, guest houses and villas', 'icon' => 'icofont-hotel',        'types' => ['hotel', 'space'], 'menu' => ['hotel', 'space']],
        'exp'     => ['name' => 'Exp OS',     'label' => 'Activities', 'tagline' => 'Tours, guides and experiences',            'icon' => 'icofont-travelling',   'types' => ['tour'],           'menu' => ['tour', 'departures']],
        'trans'   => ['name' => 'Trans OS',   'label' => 'Transport',  'tagline' => 'Transfers, car hire and boats',            'icon' => 'icofont-car-alt-4',    'types' => ['car', 'boat'],    'menu' => ['car', 'boat']],
        'event'   => ['name' => 'Event OS',   'label' => 'Events',     'tagline' => 'Events and ticketing',                     'icon' => 'icofont-ticket',       'types' => ['event'],          'menu' => ['event']],
        'airline' => ['name' => 'Airline OS', 'label' => 'Flights',    'tagline' => 'Airlines and flight ticketing',            'icon' => 'icofont-airplane',     'types' => ['flight'],         'menu' => ['flight']],
        'visa'    => ['name' => 'Visa OS',    'label' => 'Visas',      'tagline' => 'Visa and travel-document services',        'icon' => 'icofont-id-card',      'types' => [],                 'menu' => ['visa']],
    ],

    /** Sidebar entries (menu key) shown under the OS they belong to, with plain names. */
    'entry_titles' => [
        'hotel' => 'Hotels & rooms', 'space' => 'Spaces', 'tour' => 'Tours', 'departures' => 'Departures', 'car' => 'Cars', 'boat' => 'Boats',
        'event' => 'Events', 'flight' => 'Flights', 'visa' => 'Visas',
    ],

    /** Default prices, in US dollars for the South African market. Editable per plan in the admin plan builder. */
    'plans' => [
        ['name' => 'Hana', 'tagline' => 'One kind of business: a guide, a lodge, a transfer company', 'price' => 19,  'os_limit' => 1, 'max_staff' => 2,  'addon_price' => 12, 'listings' => 25,  'highlight' => 0],
        ['name' => 'Liam', 'tagline' => 'Any three kinds of business',                                'price' => 49,  'os_limit' => 3, 'max_staff' => 5,  'addon_price' => 10, 'listings' => 100, 'highlight' => 1],
        ['name' => 'Kuda', 'tagline' => 'Any four kinds of business',                                 'price' => 79,  'os_limit' => 4, 'max_staff' => 10, 'addon_price' => 8,  'listings' => 250, 'highlight' => 0],
        ['name' => 'Hina', 'tagline' => 'Everything, every OS, no limits',                            'price' => 149, 'os_limit' => 0, 'max_staff' => 0,  'addon_price' => 0,  'listings' => 0,   'highlight' => 0],
    ],
];
