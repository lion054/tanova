<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;
use Modules\Vendor\Models\ScheduledMessage;

$triggers = array_keys(ScheduledMessage::TRIGGERS);
$channels = ScheduledMessage::CHANNELS;

Doc::schema('ScheduledMessage', S::obj([
    'id' => S::int('', 4), 'name' => S::str('Your name for it.', 'Guest details, a week out'),
    'trigger' => S::enum($triggers, "What it is about. `pre_trip` / `check_in`: around the trip's start date. `departure` / `welcome_home` / `review_request` / `photo_delivery` / `loyalty_offer`: around its end date. `guest_form`: only bookings whose traveller form is not filled in. `payment_due`: only bookings with a balance. `occasion`: birthdays and anniversaries."),
    'offset_days' => S::int('Days after the date. Negative is before: -7 is a week before the trip.', -7),
    'channel' => S::enum($channels, 'Sent on your own connected channel (e-mail, WhatsApp, Telegram...).', 'email'),
    'subject' => S::nullable(S::str('E-mail subject. May use placeholders.', 'Who is travelling on {trip}?')),
    'body' => S::str('The message. May use placeholders such as {name}, {trip}, {date}, {balance}.', "Hello {name},\n\nPlease tell us who is travelling: {guest_form_link}"),
    'active' => S::bool('Paused messages are never sent.', true),
    'sent_count' => S::int('How many times it has gone out.', 12), 'created_at' => S::dt(),
], ['id', 'name', 'trigger', 'channel', 'body', 'active']));

Doc::schema('MessageLogEntry', S::obj([
    'id' => S::int('', 31), 'message_id' => S::int('', 4), 'message' => S::nullable(S::str('', 'Guest details, a week out')), 'booking_id' => S::nullable(S::int('', 41)),
    'channel' => S::str('', 'email'), 'recipient' => S::nullable(S::str('', 'ann@example.com')), 'status' => S::enum(['sent', 'failed', 'skipped']),
    'error' => S::nullable(S::str('Why it failed or was skipped.', 'whatsapp_not_connected')), 'sent_at' => S::nullable(S::dt()),
]));

Doc::schema('Campaign', S::obj([
    'id' => S::int('', 3), 'subject' => S::str('', 'Our new Zambezi sunset cruise'), 'body' => S::str('HTML is allowed.', '<p>Hello!</p>'),
    'audience' => S::enum(['all_customers', 'completed', 'upcoming']), 'status' => S::enum(['draft', 'sending', 'sent']),
    'sent_count' => S::int('How many were sent.', 214), 'sent_at' => S::nullable(S::dt()), 'created_at' => S::dt(),
], ['id', 'subject', 'audience', 'status']));

Doc::op('GET', '/messages/options')->tag('Messages')->scope('messages:read')
    ->summary('What a message can use')
    ->description('The triggers, the channels, and the `{placeholders}` you may write in a message, with what each stands for. Use it to build an editor.')
    ->returns(200, S::obj(['data' => S::obj([
        'triggers' => S::arr(S::obj(['key' => S::str('', 'guest_form'), 'label' => S::str('', 'Guest form not filled in')])),
        'channels' => S::arr(S::str('', 'email')),
        'placeholders' => S::arr(S::obj(['key' => S::str('', '{name}'), 'label' => S::str('', 'Guest first name')])),
    ])]));

Doc::op('GET', '/messages/scheduled')->tag('Messages')->scope('messages:read')
    ->summary('List scheduled messages')
    ->description("Messages that go out by themselves around a trip. The dispatcher checks once a day. Each message goes to each booking (or, for occasions, each person each year) **once**.")
    ->query([P::q('name, subject or body'), P::enum('trigger', $triggers, 'Only this trigger.'), P::enum('channel', $channels, 'Only this channel.'), P::enum('state', ['active', 'paused'], 'Only active or paused ones.'), P::sort(['newest' => 'newest', 'name' => 'name A to Z'], 'newest'), ...P::paging()])
    ->returns(200, S::page('ScheduledMessage'));
Doc::op('GET', '/messages/scheduled/{id}')->tag('Messages')->scope('messages:read')->summary('Get a scheduled message')->returns(200, S::one('ScheduledMessage'));

$body = [
    'name' => S::str('', 'Balance reminder'), 'trigger' => S::enum($triggers, '', 'payment_due'), 'offset_days' => S::int('Default 0.', -14), 'channel' => S::enum($channels, '', 'email'),
    'subject' => S::str('', 'Your balance for {trip}'), 'body' => S::str('', 'Hello {name}, {balance} is still due for booking {reference}.'), 'active' => S::bool('Default true.', true),
];
Doc::op('POST', '/messages/scheduled')->tag('Messages')->scope('messages:write')
    ->summary('Create a scheduled message')
    ->description('New messages are **active** unless you send `active: false`, so they start going out at the next daily check. To start with reviewed wording, use `POST /messages/scheduled/starter` (paused).')
    ->body(S::obj($body, ['name', 'trigger', 'channel', 'body']))->returns(201, S::one('ScheduledMessage'));
Doc::op('PUT', '/messages/scheduled/{id}')->tag('Messages')->scope('messages:write')->summary('Change a scheduled message')->description('Send only what changes.')->body(S::obj($body), false)->returns(200, S::one('ScheduledMessage'));
Doc::op('PATCH', '/messages/scheduled/{id}')->tag('Messages')->scope('messages:write')->summary('Pause or resume a scheduled message')
    ->body(S::obj(['active' => S::bool('', false)], ['active']))->returns(200, S::one('ScheduledMessage'));
Doc::op('DELETE', '/messages/scheduled/{id}')->tag('Messages')->scope('messages:write')->summary('Delete a scheduled message')->returns(204);
Doc::op('POST', '/messages/scheduled/starter')->tag('Messages')->scope('messages:write')
    ->summary('Add the recommended messages')
    ->description('Nine ready-written messages (guest details, balance reminder, day of the trip, welcome home, photos, review request, loyalty offer, birthday...). Only the ones you do not have yet are added, and **all start paused** so nothing goes out until you have read them and switched them on.')
    ->returns(201, S::obj(['data' => S::arr(S::ref('ScheduledMessage')), 'meta' => S::obj(['added' => S::int('', 9)])]));
Doc::op('GET', '/messages/scheduled/log')->tag('Messages')->scope('messages:read')
    ->summary('What was sent')
    ->query([P::int('message_id', 'Only this message.'), P::enum('status', ['sent', 'failed', 'skipped'], 'Only this result.'), ...P::paging()])
    ->returns(200, S::page('MessageLogEntry'));

Doc::op('GET', '/messages/campaigns/audiences')->tag('Messages')->scope('messages:read')
    ->summary('Who a campaign can go to')
    ->description('Each group and how many people are in it right now. Recipients are always drawn from **your own** bookings, so a campaign can never reach another vendor\'s guests.')
    ->returns(200, S::many('CampaignAudience'));
Doc::schema('CampaignAudience', S::obj(['key' => S::str('', 'upcoming'), 'label' => S::str('', 'Guests with a trip coming up'), 'recipients' => S::int('', 48)]));
Doc::op('GET', '/messages/campaigns')->tag('Messages')->scope('messages:read')
    ->summary('List campaigns')->query([P::q('subject'), P::enum('status', ['draft', 'sending', 'sent'], 'Only this status.'), P::sort(['newest' => 'newest', 'oldest' => 'oldest', 'reach' => 'most sent'], 'newest'), ...P::paging()])->returns(200, S::page('Campaign'));
Doc::op('GET', '/messages/campaigns/{id}')->tag('Messages')->scope('messages:read')->summary('Get a campaign')->returns(200, S::one('Campaign'));
Doc::op('POST', '/messages/campaigns')->tag('Messages')->scope('messages:write')
    ->summary('Write a campaign (as a draft)')
    ->body(S::obj(['subject' => S::str('', 'Our new sunset cruise'), 'body' => S::str('HTML allowed.', '<p>Hello!</p>'), 'audience' => S::enum(['all_customers', 'completed', 'upcoming'])], ['subject', 'body', 'audience']))
    ->returns(201, S::one('Campaign'));
Doc::op('PUT', '/messages/campaigns/{id}')->tag('Messages')->scope('messages:write')->summary('Change a draft campaign')
    ->errors(['not_a_draft' => [409, 'A campaign that is sending or sent cannot be changed.']])
    ->body(S::obj(['subject' => S::str(), 'body' => S::str(), 'audience' => S::enum(['all_customers', 'completed', 'upcoming'])]), false)->returns(200, S::one('Campaign'));
Doc::op('DELETE', '/messages/campaigns/{id}')->tag('Messages')->scope('messages:write')->summary('Delete a campaign')->returns(204);
Doc::op('POST', '/messages/campaigns/{id}/send')->tag('Messages')->scope('messages:write')
    ->summary('Send a campaign')
    ->description("Sends the e-mail to every distinct address in the audience **after** this response (`202`), then marks the campaign `sent` with the count. A campaign is sent once.\n\n**A test key sends nothing**: it answers `200` with `simulated: true` and `recipients`, and the campaign stays a draft.")
    ->errors(['already_sent' => [409, 'This campaign was already sent.']])
    ->returns(202, S::obj(['data' => ['allOf' => [S::ref('Campaign'), S::obj(['recipients' => S::int('How many it goes to.', 214)])]]]), 'Queued.');
