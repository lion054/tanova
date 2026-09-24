<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;

Doc::schema('WaitlistEntry', S::obj([
    'id' => S::int('Entry id', 9),
    'customer' => S::obj(['name' => S::str('', 'Ann Ray'), 'email' => S::nullable(S::email()), 'phone' => S::nullable(S::str('', '+263 77 000 0000'))]),
    'tour' => S::nullable(S::obj(['id' => S::int('', 123), 'title' => S::nullable(S::str('', 'Tandem Gorge Swing'))]), 'The experience they want, when they named one.'),
    'party_size' => S::int('How many people', 2),
    'preferred_date' => S::nullable(S::date('The day they want.', '2026-11-03')),
    'status' => S::enum(['waiting', 'notified', 'converted', 'cancelled', 'expired'], '`waiting`: in the queue. `notified`: told a seat opened. `converted`: they booked. `cancelled`: removed. `expired`: their day passed.'),
    'source' => S::enum(['vendor', 'app'], 'Who added them: you, or the guest from your app.'),
    'seats_free' => S::nullable(S::int('Seats free right now on that tour and day. Null when no limit is set, or no tour and day was named.', 3)),
    'fits' => S::bool('True when the whole party fits into the seats free right now.'),
    'notified_at' => S::nullable(S::dt('When they were last told.')),
    'notified_count' => S::int('How many times they have been told.', 1),
    'notes' => S::nullable(S::str('', 'Wants the morning slot')),
    'booking_id' => S::nullable(S::int('The booking that ended the wait, if any.', 41)),
    'created_at' => S::dt(),
], ['id', 'customer', 'party_size', 'status']));

Doc::op('GET', '/waitlist')->tag('Waitlist')->scope('waitlist:read')
    ->summary('List the waitlist')
    ->description("Everyone waiting for a day that was full. The default order is the order guests are told: waiting first, longest waiting first. Entries whose day has passed are marked `expired` when you look.")
    ->query([
        P::q('name, e-mail or phone'),
        P::enum('status', ['waiting', 'notified', 'converted', 'cancelled', 'expired'], 'Only this status.'),
        P::int('tour_id', 'Only guests waiting for this tour.', ['example' => 123]),
        P::date('from', 'Preferred day on or after.'),
        P::date('to', 'Preferred day on or before.'),
        P::sort(['queue' => 'queue order', 'newest' => 'newest first', 'day' => 'day, soonest', 'name' => 'name A to Z', 'party' => 'biggest party'], 'queue'),
        ...P::paging(),
    ])
    ->returns(200, S::page('WaitlistEntry'));

Doc::op('GET', '/waitlist/{id}')->tag('Waitlist')->scope('waitlist:read')->summary('Get one entry')->returns(200, S::one('WaitlistEntry'));

Doc::op('POST', '/waitlist')->tag('Waitlist')->scope('waitlist:write')
    ->summary('Add someone to the waitlist')
    ->description('For a guest who asked you by phone or message. A guest who joins from your app appears here too, with `source: app`.')
    ->body(S::obj([
        'customer_name' => S::str('Name', 'Ann Ray'),
        'customer_email' => S::email('Needed to tell them by e-mail. Give this or a phone number.'),
        'customer_phone' => S::str('', '+263 77 000 0000'),
        'tour_id' => S::int('The experience they want. Must be yours.', 123),
        'party_size' => S::int('Default 1.', 2),
        'preferred_date' => S::date('The day they want.', '2026-11-03'),
        'notes' => S::str('', 'Wants the morning slot'),
    ], ['customer_name']))
    ->errors(['contact_required' => [422, 'Neither an e-mail nor a phone number was given.']])
    ->returns(201, S::one('WaitlistEntry'));

Doc::op('PATCH', '/waitlist/{id}')->tag('Waitlist')->scope('waitlist:write')
    ->summary('Change an entry\'s status')
    ->description('For example mark someone `converted` when they booked by phone, or `cancelled` when they no longer want it.')
    ->body(S::obj(['status' => S::enum(['waiting', 'notified', 'converted', 'cancelled', 'expired'], '', 'converted')], ['status']))
    ->returns(200, S::one('WaitlistEntry'));

Doc::op('POST', '/waitlist/{id}/notify')->tag('Waitlist')->scope('waitlist:write')
    ->summary('Tell one guest that a seat opened')
    ->description("Sends a message on your own e-mail or connected channel. Leave `message` out for the standard wording, which names the experience and day and links to it.\n\nThe guest moves to `notified` **only if the message really went**. If it could not be sent (no e-mail, channel not connected) they stay `waiting`, `sent` is false and `error` says why, so nobody is marked told who was not.")
    ->body(S::obj(['message' => S::str('Your own words. Optional.', 'Good news, a seat opened on the 3rd.')]), false)
    ->returns(200, S::obj([
        'data' => S::obj([
            'sent' => S::bool('Whether the message went out.'),
            'result' => S::enum(['sent', 'failed', 'skipped'], 'What happened.'),
            'error' => S::nullable(S::str('Why it did not, when it did not.', 'no_email')),
            'entry' => S::ref('WaitlistEntry'),
        ]),
    ]));

Doc::op('POST', '/waitlist/notify-openings')->tag('Waitlist')->scope('waitlist:write')
    ->summary('Tell everyone who now fits')
    ->description("Goes through the waiting guests first come first served and tells each one whose **whole party** fits into the seats free, counting seats already promised in this run, so one freed seat is never offered to two people. Narrow it to one tour and day with the body.\n\nThe same thing happens by itself when a booking is cancelled.")
    ->body(S::obj(['tour_id' => S::int('Only this tour.', 123), 'date' => S::date('Only this day.', '2026-11-03')]), false)
    ->returns(200, S::obj(['data' => S::obj(['told' => S::int('How many guests were told.', 2)])]));

Doc::op('DELETE', '/waitlist/{id}')->tag('Waitlist')->scope('waitlist:write')->summary('Remove an entry')->returns(204);

// ── Occasions ────────────────────────────────────────────────────────────────

Doc::schema('Occasion', S::obj([
    'id' => S::int('', 5),
    'name' => S::str('Whose it is', 'Tendai Moyo'),
    'email' => S::nullable(S::email()),
    'phone' => S::nullable(S::str('', '+263 77 000 0000')),
    'type' => S::enum(['birthday', 'anniversary', 'custom']),
    'date' => S::date('The date it first happened (the year is kept but only the day and month matter).', '1990-04-02'),
    'next_on' => S::date('The next time it comes round. Today counts.', '2027-04-02'),
    'days_until' => S::int('Days from today to `next_on`.', 190),
    'source' => S::enum(['manual', 'customer', 'guest'], '`manual`: you added it. `customer`: read from a customer record. `guest`: read from traveller details on a booking.'),
    'will_be_messaged' => S::bool('False when there is no e-mail: it shows here so you can see the day coming, but no message is sent.'),
    'notes' => S::nullable(S::str()),
], ['id', 'name', 'type', 'date', 'next_on']));

Doc::op('GET', '/occasions')->tag('Messages')->scope('messages:read')
    ->summary('List occasions, soonest first')
    ->description('Birthdays and anniversaries. Pair with a scheduled message whose trigger is `occasion` to greet people automatically once a year.')
    ->query([
        P::q('name or e-mail'),
        P::enum('type', ['birthday', 'anniversary', 'custom'], 'Only this type.'),
        P::int('month', 'Only this month, 1 to 12.', ['example' => 4]),
        P::enum('within', ['7', '30', '90'], 'Only what comes round in the next 7, 30 or 90 days.'),
        P::enum('mail', ['yes', 'no'], '`yes`: has an e-mail. `no`: has none, so no message is sent.'),
        P::sort(['soonest' => 'next to come round', 'name' => 'name A to Z'], 'soonest'),
        ...P::paging(),
    ])
    ->returns(200, S::page('Occasion'));

Doc::op('POST', '/occasions')->tag('Messages')->scope('messages:write')
    ->summary('Add an occasion')
    ->body(S::obj([
        'name' => S::str('', 'Tendai Moyo'), 'email' => S::email(), 'phone' => S::str('', '+263 77 000 0000'),
        'type' => S::enum(['birthday', 'anniversary', 'custom'], '', 'birthday'), 'date' => S::date('', '1990-04-02'), 'notes' => S::str('', ''),
    ], ['name', 'type', 'date']))
    ->returns(201, S::one('Occasion'));

Doc::op('DELETE', '/occasions/{id}')->tag('Messages')->scope('messages:write')->summary('Remove an occasion')->returns(204);

Doc::op('POST', '/occasions/import')->tag('Messages')->scope('messages:write')
    ->summary('Read birthdays from your customers and travellers')
    ->description("Adds a birthday for every customer record and every traveller on a booking that has a date of birth. Run it as often as you like: each person is kept once and updated if the date changed.\n\nOnly a customer, or the lead traveller (who has the booking's e-mail), can be messaged; other travellers still appear so you see the day coming.")
    ->returns(200, S::obj(['data' => S::obj(['changed' => S::int('Occasions created or updated.', 12)])]));
