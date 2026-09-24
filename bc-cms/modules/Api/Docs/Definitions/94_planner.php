<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;
use Pro\Tanova\Services\DayPlanning;

$occasions = array_keys(DayPlanning::OCCASIONS);
$interests = array_keys(DayPlanning::INTEREST_WORDS);
$whens = ['rest', 'morning', 'afternoon', 'evening', 'full_day'];

// ── Multi-day trips ──────────────────────────────────────────────────────────

Doc::schema('TripSummary', S::obj([
    'id' => S::int('', 31), 'title' => S::str('', 'Trip to Victoria Falls'), 'destination' => S::str('', 'Victoria Falls'),
    'dates' => S::obj(['start' => S::date(), 'end' => S::date(), 'nights' => S::int('', 3)]), 'guests' => S::int('', 2), 'status' => S::str('', 'created'),
    'pricing' => S::obj(['estimated' => S::num('The first package\'s total.', 1240), 'currency' => S::str('', 'USD')]),
    'packages' => S::int('How many ready-made packages came out of it.', 3),
    'first_package' => S::nullable(S::obj(['total_cost' => S::num(), 'activity_cost' => S::num(), 'stay_cost' => S::num(), 'days' => S::int()])),
    'weather' => S::arr(S::obj([]), 'A forecast per day, when one was available.'), 'created_at' => S::dt(),
    'links' => ['type' => 'object', 'description' => 'Where to read it again.', 'additionalProperties' => S::url()],
], ['id', 'title', 'dates']));

Doc::op('POST', '/tanova/generate')->tag('Planner')->scope('planner:write')
    ->summary('Plan a trip')
    ->description("Builds ready-made packages (stay, activities, prices, a day-by-day itinerary) **from your own catalogue** for a place and dates, and saves the result so it can be read again.\n\nThe response is a summary; `GET /tanova/trips/{id}` has the full itinerary. It takes a few seconds. If your catalogue has no accommodation at that place, generation fails with `trip_generation_failed`.")
    ->body(S::obj([
        'destination' => S::str('A name to show.', 'Victoria Falls'), 'place_id' => S::int('The destination id (`GET /destinations`). Defaults to Victoria Falls, so **always send it**.', 6),
        'start_date' => S::date(), 'end_date' => S::date('After the start.'), 'guests' => S::int('At least 1.', 2),
        'budget' => S::enum(['budget', 'mid-range', 'luxury'], 'Default mid-range.'), 'trip_type' => S::str('', 'honeymoon'), 'notes' => S::str('Up to 1000 characters.'),
    ], ['destination', 'start_date', 'end_date', 'guests']))
    ->errors(['trip_generation_failed' => [500, 'Nothing could be built for that place and those dates (often: no accommodation listed there).']])
    ->returns(201, S::one('TripSummary'));
Doc::op('GET', '/tanova/trips')->tag('Planner')->scope('planner:read')->summary('Trips planned with your key')
    ->description('Newest first, as a framework page: the rows are in `data` and the paging fields sit beside it.')
    ->legacy('The page is a framework paginator (`current_page`, `data`, `total`, `last_page`...), not `{data, meta}`.')
    ->query([P::int('per_page', 'Up to 100.', ['default' => 15]), P::page()])
    ->returns(200, S::obj([
        'current_page' => S::int(), 'data' => S::arr(S::obj(['id' => S::int(), 'title' => S::str(), 'destination' => S::str(), 'start_date' => S::date(), 'end_date' => S::date(), 'guests' => S::int(), 'trip_type' => S::nullable(S::str()), 'estimated_price' => S::nullable(S::str('', '1240.00')), 'currency' => S::str('', 'USD'), 'status' => S::str(), 'created_at' => S::dt()])),
        'per_page' => S::int(), 'total' => S::int(), 'last_page' => S::int(),
    ]));
Doc::op('GET', '/tanova/trips/{trip}')->tag('Planner')->scope('planner:read')->summary('A trip with its full itinerary')
    ->path(['trip' => ['integer', 'The trip id.']])
    ->description('Every package with its days, activities, stay and costs, exactly as the planner made it. The shape follows the planner, so read what you need rather than everything.')
    ->errors(['forbidden' => [403, 'The trip belongs to another business.']])
    ->returns(200, S::obj(['data' => S::obj([
        'id' => S::int(), 'title' => S::str(), 'destination' => S::str(), 'start_date' => S::date(), 'end_date' => S::date(), 'guests' => S::int(), 'trip_type' => S::nullable(S::str()),
        'itinerary' => S::arr(S::obj(['total_cost' => S::num(), 'activity_cost' => S::num(), 'stay_cost' => S::num(), 'itinerary' => S::arr(S::obj([]), 'The days.')]), 'The packages, best first.'),
        'daily_weather' => S::arr(S::obj([])), 'estimated_price' => S::nullable(S::str()), 'currency' => S::str(), 'status' => S::str(), 'created_at' => S::dt(),
    ])]));

Doc::op('GET', '/tanova/transports')->tag('Planner')->scope('planner:read')->summary('Ground transport at a destination')
    ->description('Transfers and other transport the planner can add, with their options and prices, for one destination. Yours and the platform\'s shared ones.')
    ->query([P::int('location_id', 'The destination.', ['required' => true, 'example' => 6])])
    ->returns(200, S::obj(['data' => S::arr(S::obj([
        'id' => S::int(), 'name' => S::str('', 'Airport transfer'), 'description' => S::nullable(S::str()), 'active_options' => S::arr(S::obj(['id' => S::int(), 'name' => S::str(), 'price' => S::str('', '35.00')])),
    ]))]));

// ── One day ──────────────────────────────────────────────────────────────────

Doc::op('GET', '/tanova/occasions')->tag('Planner')->scope('planner:read')->summary('What a day can be for')
    ->description('Occasions that shape a day plan, for example a dinner date or a family day: when in the day it happens, which meals it has, what kind of things suit it and how thrilling they may be.')
    ->returns(200, S::obj(['data' => S::arr(S::obj([
        'id' => S::enum($occasions, '', 'family_day'), 'when' => S::enum($whens), 'meals' => S::arr(S::enum(['lunch', 'dinner'])), 'interests' => S::arr(S::str('', 'family')), 'max_thrill' => S::enum(['easy', 'moderate', 'thrill']),
    ]))]));

$dayBody = [
    'location_id' => S::int('The destination.', 6), 'date' => S::date('Optional.'), 'budget' => S::num('For the whole party. Optional.', 200), 'occasion' => S::enum($occasions),
    'party' => S::arr(S::obj(['age' => S::int('0 to 120.', 34), 'child' => S::bool()]), '1 to 7 people. Ages keep out what they may not do.'),
];
Doc::schema('DayStop', S::obj([
    'service_id' => S::str('An id from your catalogue: `activity-256`, `restaurant:40`, `day_trip-1`.', 'activity-256'), 'kind' => S::enum(['activity', 'meal']), 'meal' => S::nullable(S::enum(['lunch', 'dinner'])),
    'start_minutes' => S::int('Minutes after midnight.', 480), 'end_minutes' => S::int('', 540),
], ['service_id', 'kind', 'start_minutes', 'end_minutes']));
Doc::op('POST', '/tanova/day')->tag('Planner')->scope('planner:write')
    ->summary('Plan the rest of a day')
    ->description("Up to three ways to spend a day (or what is left of it) in one place, from **your** catalogue: `best_fit`, `easiest` and more, each a run of stops with times that fit each other, travel between them and a total price for the party. Pass `now_minutes` so today's plan starts from now, `tomorrow: true` for the next day, and `weather` to keep outdoor stops out of bad weather.\n\nStops carry ids, not details: look each one up in `GET /catalogue`. An empty `options` means nothing fits the time left.")
    ->body(S::obj($dayBody + [
        'when' => S::enum($whens, 'Which part of the day. Default `rest` (what is left).'), 'tomorrow' => S::bool(), 'now_minutes' => S::int('Minutes after midnight now, so the plan starts after that.', 600),
        'interests' => S::arr(S::enum($interests), 'Up to 12.'), 'from' => S::obj(['lat' => S::num('', -17.92), 'lng' => S::num('', 25.85)], [], 'Where they are, so nearer things come first.'),
        'weather' => S::obj(['fit' => S::enum(['indoor', 'water', 'outdoor'], 'What the weather allows.'), 'sunset_minutes' => S::int('Minutes after midnight the sun sets.', 1050)]),
    ], ['location_id', 'party']))
    ->returns(200, S::obj(['data' => S::obj([
        'window' => S::nullable(S::obj(['start' => S::int(), 'end' => S::int(), 'outdoor_end' => S::int(), 'tomorrow' => S::bool()], [], 'The time available, in minutes after midnight. Null when the day is over.')),
        'options' => S::arr(S::obj(['style' => S::str('', 'best_fit'), 'stops' => S::arr(S::ref('DayStop')), 'total' => S::num('The party\'s total.', 234), 'travel_minutes' => S::int('', 5)])),
    ])]));
Doc::op('POST', '/tanova/day-trips')->tag('Planner')->scope('planner:write')
    ->summary('Day trips that fit, and where to eat')
    ->description('The day trips that suit the party and the hours, and restaurants near the place. Ids only: look each up in `GET /catalogue`.')
    ->body(S::obj($dayBody + ['start' => S::str('HH:MM: earliest start.', '08:00'), 'end' => S::str('HH:MM: latest finish.', '18:00')], ['location_id', 'party']))
    ->returns(200, S::obj(['data' => S::obj(['trips' => S::arr(S::str('', 'day_trip-1')), 'restaurants' => S::arr(S::str('', 'restaurant:40'))])]));

// ── Concierge ────────────────────────────────────────────────────────────────

Doc::schema('ConciergeConversation', S::obj([
    'id' => S::int('', 7), 'guest_name' => S::str('', 'Ann Ray'), 'guest_email' => S::str('', 'ann@example.com'), 'guest_phone' => S::str(),
    'channel' => S::enum(['web', 'whatsapp', 'sms', 'email']), 'chatbot_name' => S::str('', 'Concierge'), 'category' => S::enum(['booking', 'complaint', 'general', 'other']), 'priority' => S::enum(['low', 'normal', 'high']),
    'status' => S::enum(['open', 'escalated', 'resolved']), 'resolution_reason' => S::nullable(S::str()), 'booking_id' => S::nullable(S::int()),
    'last_message' => S::nullable(S::str('The first 255 characters of the latest.')), 'last_message_at' => S::nullable(S::dt()), 'created_at' => S::dt(), 'updated_at' => S::dt(),
], ['id', 'status'], 'A conversation in your Concierge inbox: the same ones you see in the portal.'));
Doc::schema('ConciergeMessage', S::obj([
    'id' => S::int('', 10), 'conversation_id' => S::int('', 7), 'sender_type' => S::enum(['guest', 'staff', 'ai'], 'Who wrote it.'), 'body' => S::str('', 'Can I move my booking?'),
    'ai_draft' => S::bool('An AI suggestion.'), 'approved' => S::bool('False for a draft nobody has approved yet. Drafts are for you to review in the portal before a guest sees them.'), 'read_at' => S::nullable(S::dt()), 'created_at' => S::dt(),
], ['id', 'sender_type', 'body']));

Doc::op('GET', '/concierge/conversations')->tag('Planner')->scope('planner:read')->summary('Open conversations')
    ->description('The conversations still open or escalated, most recently active first. Resolved ones drop out. Each carries its `latest_message`.')
    ->legacy('The page is a framework paginator (`current_page`, `data`, `total`, `last_page`...), not `{data, meta}`.')
    ->query([P::int('per_page', 'Up to 100.', ['default' => 15]), P::page()])
    ->returns(200, S::obj(['current_page' => S::int(), 'data' => S::arr(S::ref('ConciergeConversation')), 'per_page' => S::int(), 'total' => S::int(), 'last_page' => S::int()]));
Doc::op('POST', '/concierge/conversations')->tag('Planner')->scope('planner:write')
    ->summary('Start a conversation')
    ->description('Opens a conversation with a guest\'s first message (for a chat widget on your site or app). When AI drafting is set up, a **draft** reply appears as an unapproved `ai` message for you to review; nothing is sent to the guest automatically.')
    ->body(S::obj([
        'message' => S::str('Up to 3000 characters.', 'Can I move my booking?'), 'guest_name' => S::str('', 'Ann Ray'), 'guest_email' => S::email(), 'guest_phone' => S::str(),
        'channel' => S::enum(['web', 'whatsapp', 'sms', 'email'], 'Default web.'), 'chatbot_name' => S::str('', 'Concierge'), 'category' => S::enum(['booking', 'complaint', 'general', 'other']), 'priority' => S::enum(['low', 'normal', 'high']),
    ], ['message']))
    ->returns(201, S::obj(['data' => ['allOf' => [S::ref('ConciergeConversation'), S::obj(['messages' => S::arr(S::ref('ConciergeMessage'))])]]]));
Doc::op('GET', '/concierge/conversations/{conversation}')->tag('Planner')->scope('planner:read')->summary('One conversation with its messages')
    ->path(['conversation' => ['integer', 'The conversation id.']])->errors(['forbidden' => [403, 'It belongs to another business.']])
    ->returns(200, S::obj(['data' => ['allOf' => [S::ref('ConciergeConversation'), S::obj(['messages' => S::arr(S::ref('ConciergeMessage'))])]]]));
Doc::op('GET', '/concierge/conversations/{id}/messages')->tag('Planner')->scope('planner:read')->summary('A conversation\'s messages, paged')
    ->path(['id' => ['integer', 'The conversation id.']])
    ->query([P::enum('sender_type', ['guest', 'staff', 'ai'], 'Only this sender.'), P::int('per_page', 'Up to 100.', ['default' => 50]), P::page()])
    ->errors(['not_found' => [404, 'No such conversation of yours.']])->returns(200, S::page('ConciergeMessage'));
Doc::op('POST', '/concierge/conversations/{conversation}/send')->tag('Planner')->scope('planner:write')->summary('Add a message from the guest')
    ->path(['conversation' => ['integer', 'The conversation id.']])
    ->description('Adds the guest\'s next message (your widget calls this). At most one message every two seconds per conversation (`429`). An AI draft may follow, for you to review. Your own replies to the guest are written in the portal inbox.')
    ->body(S::obj(['body' => S::str('Up to 3000 characters.', 'Thanks!')], ['body']))->errors(['too_fast' => [429, 'Wait two seconds between messages.'], 'forbidden' => [403, 'It belongs to another business.']])
    ->returns(200, S::obj(['data' => S::obj(['your_message' => S::ref('ConciergeMessage'), 'ai_reply' => S::nullable(S::ref('ConciergeMessage'))])]));
Doc::op('POST', '/concierge/conversations/{id}/close')->tag('Planner')->scope('planner:write')->summary('Resolve a conversation')
    ->path(['id' => ['integer', 'The conversation id.']])->body(S::obj(['reason' => S::str('Kept with the conversation. Up to 255 characters.', 'Sorted by phone')]), false)
    ->errors(['not_found' => [404, 'No such conversation of yours.']])->returns(200, S::one('ConciergeConversation'));
Doc::op('GET', '/concierge/statistics')->tag('Planner')->scope('planner:read')->summary('Conversation numbers')
    ->description('Conversations and messages in a period (default the last 30 days) and how they split by channel and status.')
    ->query([P::date('from', 'Start of the period.'), P::date('to', 'End of the period. Not before `from`.')])
    ->returns(200, S::obj(['data' => S::obj([
        'period' => S::obj(['from' => S::date(), 'to' => S::date()]),
        'summary' => S::obj(['total_conversations' => S::int('', 12), 'total_messages' => S::int('', 40), 'avg_messages_per_conversation' => S::num('', 3.33)]),
        'by_channel' => ['type' => 'object', 'additionalProperties' => S::int(), 'example' => ['web' => 8, 'whatsapp' => 4]], 'by_status' => ['type' => 'object', 'additionalProperties' => S::int(), 'example' => ['open' => 5, 'resolved' => 7]],
    ])]));

// ── Analytics (older summary endpoints) ─────────────────────────────────────

Doc::op('GET', '/analytics/summary')->tag('Insights')->scope('analytics:read')->summary('The headline numbers')
    ->description('All bookings, this month\'s, those still pending (draft or pending), and revenue counted from **completed** trips only: all time, this month and last month. Money is in your main currency.')
    ->returns(200, S::obj(['data' => S::obj([
        'total_bookings' => S::int('', 248), 'bookings_this_month' => S::int('', 19), 'pending_bookings' => S::int('', 4), 'total_revenue' => S::num('', 48200.5), 'revenue_this_month' => S::num('', 3900), 'revenue_last_month' => S::num('', 5100),
    ])]));
Doc::op('GET', '/analytics/revenue')->tag('Insights')->scope('analytics:read')->summary('Revenue by day')
    ->description('Completed bookings grouped by the day they were made, for the period. Days with none are absent, not zero.')
    ->query([P::enum('period', ['7d', '30d', '90d', '1y'], 'Default 30d.')])
    ->returns(200, S::obj(['data' => S::arr(S::obj(['date' => S::date(), 'revenue' => S::str('Decimal string.', '1200.00'), 'bookings' => S::int('', 3)]))]));
Doc::op('GET', '/analytics/api-usage')->tag('Insights')->scope('analytics:read')->summary('How your keys were used this month')
    ->description('Requests per endpoint since the start of the month with the average response time and how many failed (status 400 or more): the 50 busiest.')
    ->returns(200, S::obj(['data' => S::arr(S::obj(['endpoint' => S::str('', 'api/v/bookings'), 'method' => S::str('', 'GET'), 'total_requests' => S::int('', 1200), 'avg_ms' => S::str('', '42.5000'), 'errors' => S::str('Count as a string.', '3')]))]));
