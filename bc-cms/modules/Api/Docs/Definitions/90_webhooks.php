<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;
use Modules\Vendor\Services\WebhookEvents;

$eventTypes = array_keys(WebhookEvents::CATALOGUE);

Doc::schema('Webhook', S::obj([
    'id' => S::int('', 4), 'url' => S::url('Where events are sent. Always https.', 'https://example.com/hooks/tsoka'),
    'events' => S::arr(S::str('', 'booking.paid'), 'The events it listens for, or `["*"]` for all of them.'),
    'active' => S::bool('An inactive endpoint is skipped.'), 'deliveries' => S::int('How many deliveries were logged.', 42),
    'last_triggered_at' => S::nullable(S::dt()), 'created_at' => S::dt(),
], ['id', 'url', 'events', 'active']));
Doc::schema('WebhookWithSecret', S::obj([
    'id' => S::int('', 4), 'url' => S::url(), 'events' => S::arr(S::str()), 'active' => S::bool(), 'deliveries' => S::int(), 'last_triggered_at' => S::nullable(S::dt()), 'created_at' => S::dt(),
    'secret' => S::str('Signs every delivery. **Shown only in this response**: store it now.', 'whsec_2b7f...'),
], ['id', 'url', 'secret']));
Doc::schema('WebhookDelivery', S::obj([
    'id' => S::int('', 88), 'event_id' => S::str('Stable across retries: use it to ignore duplicates.', 'evt_9f2c1d0a7b34e5f6a1b2c3d4'), 'event' => S::str('', 'booking.paid'),
    'success' => S::bool('True when your endpoint answered 2xx.'), 'status_code' => S::nullable(S::int('What your endpoint answered. Null when it could not be reached.', 200)),
    'attempts' => S::int('How many times we tried.', 1), 'next_attempt_at' => S::nullable(S::dt('When the next retry is due. Null when done or given up.')),
    'duration_ms' => S::nullable(S::int('', 180)), 'response' => S::nullable(S::str('The first part of your answer.', 'ok')), 'delivered_at' => S::nullable(S::dt()), 'created_at' => S::dt(),
], ['id', 'event_id', 'event', 'success']));

// The envelope and the object inside it for each kind of event.
Doc::schema('WebhookEnvelope', S::obj([
    'id' => S::str('Unique per event and the same on every retry.', 'evt_9f2c1d0a7b34e5f6a1b2c3d4'), 'type' => S::enum($eventTypes, 'What happened.', 'booking.paid'),
    'created' => S::int('Unix time.', 1790000000), 'api_version' => S::str('', '2026-06-30'),
    'data' => S::obj(['object' => ['type' => 'object', 'description' => 'The thing the event is about. Its fields depend on `type`: see the event objects.', 'additionalProperties' => true]]),
], ['id', 'type', 'created', 'data'], 'The body of every delivery. Booking events also carry `event` and `booking` at the top level, as they did before the envelope existed.'));
Doc::schema('BookingWebhook', S::obj([
    'code' => S::str('', 'K7Q2X9'), 'status' => S::str('', 'confirmed'), 'object_model' => S::nullable(S::str('', 'tour')), 'object_id' => S::nullable(S::int()), 'total' => S::nullable(S::str('', '400.00')), 'currency' => S::str('', 'USD'),
    'customer' => S::obj(['name' => S::str('', 'Ann Ray'), 'email' => S::nullable(S::email()), 'phone' => S::nullable(S::str())]),
    'check_in' => S::nullable(S::str('', '2026-11-03 08:00:00')), 'check_out' => S::nullable(S::str()), 'created_at' => S::nullable(S::dt()), 'updated_at' => S::nullable(S::dt()),
], [], 'Event objects for `booking.created`, `.paid`, `.confirmed`, `.cancelled`, `.completed`.'));
Doc::schema('BookingPaymentWebhook', S::obj([
    'code' => S::str('', 'K7Q2X9'), 'status' => S::str('', 'confirmed'), 'amount' => S::num('', 100), 'method' => S::str('', 'cash'), 'reference' => S::nullable(S::str()), 'paid' => S::num('Paid in total.', 100),
    'balance' => S::num('Still owing.', 300), 'currency' => S::str('', 'USD'), 'occurred_at' => S::nullable(S::dt()),
], [], 'Event objects for `booking.payment_received` and `booking.refund_recorded`.'));
Doc::schema('BookingTravellersWebhook', S::obj(['code' => S::str('', 'K7Q2X9'), 'travellers' => S::int('How many were filled in.', 2), 'expected' => S::int('How many the booking has.', 2)], [], 'Event object for `booking.travellers_submitted`.'));
Doc::schema('WaitlistWebhook', S::obj([
    'id' => S::int('', 9), 'name' => S::str('', 'Ann Ray'), 'email' => S::nullable(S::email()), 'phone' => S::nullable(S::str()), 'tour_id' => S::nullable(S::int()), 'party_size' => S::int('', 2),
    'preferred_date' => S::nullable(S::date()), 'status' => S::str('', 'waiting'), 'source' => S::enum(['vendor', 'app']),
], [], 'Event objects for `waitlist.joined`, `.notified`, `.converted`.'));
Doc::schema('CustomerWebhook', S::obj(['id' => S::int('', 5), 'name' => S::str('', 'Ann Ray'), 'email' => S::nullable(S::email()), 'phone' => S::nullable(S::str()), 'source' => S::nullable(S::str('', 'booking'))], [], 'Event object for `customer.created`.'));
Doc::schema('LoyaltyWebhook', S::obj(['email' => S::str('', 'ann@example.com'), 'name' => S::nullable(S::str()), 'points_added' => S::int('', 40), 'balance' => S::int('', 240), 'tier' => S::nullable(S::str('', 'Gold')), 'booking_code' => S::nullable(S::str('', 'K7Q2X9'))], [], 'Event object for `loyalty.points_earned`.'));
Doc::schema('InvoiceWebhook', S::obj([
    'id' => S::int('', 3), 'number' => S::str('', 'INV-2026-003'), 'status' => S::str('', 'paid'), 'total' => S::num('', 400), 'amount_paid' => S::num('', 400), 'balance' => S::num('', 0),
    'currency' => S::str('', 'USD'), 'booking_id' => S::nullable(S::int()), 'customer_id' => S::nullable(S::int()),
], [], 'Event objects for `invoice.created`, `.paid`, `.voided`.'));

$idPath = ['id' => ['integer', 'The webhook endpoint id.']];

Doc::op('GET', '/webhooks/events')->tag('Webhooks')->scope('webhooks:read')
    ->summary('The events you can subscribe to')
    ->description("Every event type, what triggers it, and the name of the object schema its `data.object` follows. Subscribe to `*` to receive all of them, including ones added later.")
    ->returns(200, S::obj(['data' => S::arr(S::obj(['type' => S::enum($eventTypes, '', 'booking.paid'), 'description' => S::str('', 'A booking was paid in full.'), 'object' => S::str('The schema of `data.object`.', 'BookingWebhook')]))]));

Doc::op('GET', '/webhooks')->tag('Webhooks')->scope('webhooks:read')->summary('List your webhook endpoints')
    ->query(P::paging())->returns(200, S::page('Webhook'));
Doc::op('GET', '/webhooks/{id}')->tag('Webhooks')->scope('webhooks:read')->summary('Get one endpoint')->path($idPath)->returns(200, S::one('Webhook'));

Doc::op('POST', '/webhooks')->tag('Webhooks')->scope('webhooks:write')
    ->summary('Register an endpoint')
    ->description("We send the events you choose to `url`, signed with a secret that is **shown once, in this response**. The address must be public https (private, loopback and internal addresses are refused, and are checked again on every delivery). See the *Webhooks* guide for how to verify the signature.")
    ->body(S::obj(['url' => S::url('', 'https://example.com/hooks/tsoka'), 'events' => S::arr(S::enum(array_merge($eventTypes, ['*'])), 'At least one, or `["*"]`.'), 'active' => S::bool('Default true.')], ['url', 'events']))
    ->returns(201, S::one('WebhookWithSecret'));
Doc::op('PUT', '/webhooks/{id}')->tag('Webhooks')->scope('webhooks:write')->summary('Change an endpoint')->path($idPath)
    ->description('Send only what changes. The secret is not changed here: use `rotate-secret`.')
    ->body(S::obj(['url' => S::url(), 'events' => S::arr(S::enum(array_merge($eventTypes, ['*']))), 'active' => S::bool('Pause or resume it.')]), false)->returns(200, S::one('Webhook'));
Doc::op('DELETE', '/webhooks/{id}')->tag('Webhooks')->scope('webhooks:write')->summary('Remove an endpoint')->path($idPath)
    ->description('Its delivery log goes with it.')->returns(204);
Doc::op('POST', '/webhooks/{id}/rotate-secret')->tag('Webhooks')->scope('webhooks:write')->summary('Make a new secret')->path($idPath)
    ->description('The old secret stops signing **at once**, so update your receiver first or accept a short gap. The new secret is shown once.')->returns(200, S::one('WebhookWithSecret'));
Doc::op('POST', '/webhooks/{id}/test')->tag('Webhooks')->scope('webhooks:write')->summary('Send a test ping now')->path($idPath)
    ->description('Sends a `ping` event now and returns what your endpoint answered. Nothing else happens, and a ping is never retried. Use it to check your address and your signature check.')->returns(200, S::one('WebhookDelivery'));

Doc::op('GET', '/webhooks/{id}/deliveries')->tag('Webhooks')->scope('webhooks:read')->summary('The delivery log')->path($idPath)
    ->description('Newest first, with what your endpoint answered. Failed deliveries are retried after 1 minute, 5 minutes, 30 minutes, 2 hours and 12 hours, then marked failed.')
    ->query([P::bool('success', 'Only successful (`true`) or failed (`false`) deliveries.'), P::enum('event', $eventTypes + [99 => 'ping'], 'Only this event.'), ...P::paging()])->returns(200, S::page('WebhookDelivery'));
Doc::op('GET', '/webhooks/{id}/deliveries/{deliveryId}')->tag('Webhooks')->scope('webhooks:read')->summary('One delivery, with the body that was sent')
    ->path($idPath + ['deliveryId' => ['integer', 'The delivery id.']])
    ->returns(200, S::obj(['data' => ['allOf' => [S::ref('WebhookDelivery'), S::obj(['payload' => S::ref('WebhookEnvelope')])]]]));
Doc::op('POST', '/webhooks/{id}/deliveries/{deliveryId}/redeliver')->tag('Webhooks')->scope('webhooks:write')->summary('Send a delivery again')
    ->path($idPath + ['deliveryId' => ['integer', 'The delivery id.']])
    ->description('Sends the same body with the same event id now, so a receiver that de-duplicates by `id` handles it safely.')->returns(200, S::one('WebhookDelivery'));
