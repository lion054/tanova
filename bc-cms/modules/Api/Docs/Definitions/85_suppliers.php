<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;
use Pro\Integrations\Models\Operator;

$types = Operator::TYPES;

Doc::schema('Supplier', S::obj([
    'id' => S::int('', 5), 'name' => S::str('', 'Intercape'), 'type' => S::enum($types), 'status' => S::enum(['active', 'inactive']),
    'contact' => S::obj(['name' => S::nullable(S::str('', 'Tendai')), 'email' => S::nullable(S::email()), 'phone' => S::nullable(S::str())]),
    'website' => S::nullable(S::str()), 'address' => S::nullable(S::str()), 'commission_rate' => S::nullable(S::num('Percent.', 10)), 'payment_terms' => S::nullable(S::str('', '30 days')),
    'currency' => S::str('', 'USD'), 'notes' => S::nullable(S::str()), 'connection_status' => S::nullable(S::str('Whether a live feed is connected.', 'none')), 'last_synced_at' => S::nullable(S::dt()),
    'routes_count' => S::int('', 4), 'fares_count' => S::int('', 8), 'created_at' => S::dt(),
], ['id', 'name', 'type']));
Doc::schema('SupplierRoute', S::obj([
    'id' => S::int('', 9), 'origin' => S::str('', 'Harare'), 'destination' => S::str('', 'Victoria Falls'), 'duration_minutes' => S::nullable(S::int('', 660)), 'vehicle_type' => S::nullable(S::str('', 'coach')),
    'days_of_week' => S::nullable(S::str('', '1,3,5')), 'departure_time' => S::nullable(S::str('HH:MM', '06:30')), 'arrival_time' => S::nullable(S::str('HH:MM', '17:30')), 'active' => S::bool(),
], ['id', 'origin', 'destination']));
Doc::schema('SupplierFare', S::obj([
    'id' => S::int('', 12), 'route_id' => S::nullable(S::int()), 'fare_class' => S::str('', 'standard'), 'nett_price' => S::num('What you pay.', 40), 'sell_price' => S::nullable(S::num('What you charge.', 55)),
    'margin' => S::nullable(S::num('Sell minus nett, when both are known.', 15)), 'available_seats' => S::nullable(S::int()), 'valid_from' => S::nullable(S::date()), 'valid_to' => S::nullable(S::date()),
], ['id', 'fare_class', 'nett_price']));

$fields = [
    'name' => S::str('', 'Intercape'), 'type' => S::enum($types), 'contact_name' => S::str(), 'contact_email' => S::email(), 'contact_phone' => S::str(), 'website' => S::str(), 'address' => S::str(),
    'commission_rate' => S::num('0 to 100.', 10), 'payment_terms' => S::str('', '30 days'), 'currency' => S::str('Default USD.', 'USD'), 'notes' => S::str(), 'status' => S::enum(['active', 'inactive'], 'Default active.'),
];
$idPath = ['id' => ['integer', 'The supplier id.']];

Doc::op('GET', '/suppliers')->tag('Suppliers')->scope('suppliers:read')->summary('List your suppliers')
    ->query([P::q('name, contact name or contact e-mail'), P::enum('type', $types, 'Only this kind.'), P::enum('status', ['active', 'inactive'], 'Only this status.'), P::sort(['name' => 'name A to Z', 'newest' => 'newest first'], 'name'), ...P::paging()])
    ->returns(200, S::page('Supplier'));
Doc::op('GET', '/suppliers/{id}')->tag('Suppliers')->scope('suppliers:read')->summary('One supplier with its routes and fares')->path($idPath)
    ->returns(200, S::obj(['data' => ['allOf' => [S::ref('Supplier'), S::obj(['routes' => S::arr(S::ref('SupplierRoute')), 'fares' => S::arr(S::ref('SupplierFare'))])]]]));
Doc::op('POST', '/suppliers')->tag('Suppliers')->scope('suppliers:write')->summary('Add a supplier')->body(S::obj($fields, ['name', 'type']))->returns(201, S::one('Supplier'));
Doc::op('PUT', '/suppliers/{id}')->tag('Suppliers')->scope('suppliers:write')->summary('Change a supplier')->path($idPath)->description('Send only what changes.')->body(S::obj($fields), false)->returns(200, S::one('Supplier'));
Doc::op('DELETE', '/suppliers/{id}')->tag('Suppliers')->scope('suppliers:write')->summary('Remove a supplier')->path($idPath)->description('Its routes and fares go with it.')->returns(204);
Doc::op('POST', '/suppliers/{id}/routes')->tag('Suppliers')->scope('suppliers:write')->summary('Add a route')->path($idPath)
    ->body(S::obj(['origin' => S::str('', 'Harare'), 'destination' => S::str('', 'Victoria Falls'), 'duration_minutes' => S::int('', 660), 'vehicle_type' => S::str('', 'coach'), 'days_of_week' => S::str('', '1,3,5'), 'departure_time' => S::str('HH:MM', '06:30'), 'arrival_time' => S::str('HH:MM', '17:30')], ['origin', 'destination']))
    ->returns(201, S::one('SupplierRoute'));
Doc::op('DELETE', '/suppliers/{id}/routes/{routeId}')->tag('Suppliers')->scope('suppliers:write')->summary('Remove a route')->path($idPath + ['routeId' => ['integer', 'The route id.']])->returns(204);
Doc::op('POST', '/suppliers/{id}/fares')->tag('Suppliers')->scope('suppliers:write')->summary('Add a fare')->path($idPath)
    ->body(S::obj(['route_id' => S::int('One of this supplier\'s routes.'), 'fare_class' => S::str('', 'standard'), 'nett_price' => S::num('What you pay.', 40), 'sell_price' => S::num('What you charge.', 55), 'available_seats' => S::int(), 'valid_from' => S::date(), 'valid_to' => S::date('Not before `valid_from`.')], ['fare_class', 'nett_price']))
    ->errors(['route_not_found' => [422, 'That route does not belong to this supplier.']])->returns(201, S::one('SupplierFare'));
Doc::op('DELETE', '/suppliers/{id}/fares/{fareId}')->tag('Suppliers')->scope('suppliers:write')->summary('Remove a fare')->path($idPath + ['fareId' => ['integer', 'The fare id.']])->returns(204);
