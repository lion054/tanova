<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor Public API  — /api/v/...
|--------------------------------------------------------------------------
| Auth: sk_live_xxx API key (ResolveVendorApiKey middleware)
| Every response is scoped 100% to the authenticated vendor.
| Register via add_action('API_ROUTES') in Api/RouterServiceProvider.
|--------------------------------------------------------------------------
*/

// Preflight OPTIONS — must bypass auth so browsers can complete CORS handshake
// The browser cannot say which business it is (a preflight carries no key), so it is let through; the real answer then only
// carries Access-Control-Allow-Origin for origins that business registered (VendorCors).
Route::options('v/{any}', function (\Illuminate\Http\Request $r) {
    $origin = (string) $r->headers->get('Origin', '');

    return response('', 204, $origin === '' ? [] : [
        'Access-Control-Allow-Origin' => $origin, 'Vary' => 'Origin', 'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Authorization, Content-Type, Accept, X-Requested-With, Idempotency-Key, X-Customer-Token, Tsoka-Version, If-None-Match', 'Access-Control-Max-Age' => '86400',
    ]);
})->where('any', '.*')->middleware('api');

// The documentation is public: no key, so people can read it before they have one.
Route::prefix('v')->middleware(['api', 'throttle:60,1'])->group(function () {
    Route::get('openapi.json', 'DocsController@openapi');
    Route::get('postman.json', 'DocsController@postman');
    Route::get('docs',         'DocsController@page');
    Route::get('swagger',      'DocsController@swagger');
});

Route::prefix('v')
    ->middleware([
        'api',
        \App\Http\Middleware\ApiVersion::class,           // date-based versioning (Tsoka-Version / X-Tsoka-Version)
        'throttle:vendor-api',                            // 120 req/min per API key (burst protection)
        \App\Http\Middleware\ResolveVendorApiKey::class,  // resolves vendor + key type/mode + subscription gate
        \App\Http\Middleware\VendorIdempotency::class,    // Idempotency-Key replay for write requests
        \App\Http\Middleware\VendorCors::class,           // dynamic CORS using resolved vendor's allowed origins
        \App\Http\Middleware\TrackVendorApiUsage::class,  // usage tracking + 80% alert (post-response)
        \App\Http\Middleware\VendorApiResponseHeaders::class, // X-RateLimit-*, X-Tsoka-Mode, ETag/Cache-Control
    ])
    ->group(function () {

        // ── Identity ──────────────────────────────────────────────────────────
        Route::get('me', 'Vendor\VendorMeController');

        // ── Catalogue (one place, everything, in the app's shape) ──────────────
        Route::middleware('api.scope:services:read')->group(function () {
            Route::get('destinations', 'Vendor\VendorCatalogueController@destinations');
            Route::get('catalogue', 'Vendor\VendorCatalogueController@show');
            Route::get('upsells',   'Vendor\VendorUpsellController@index');
        });

        // ── Services ────────────────────────────────────────────────────────

        Route::prefix('services')->middleware('api.scope:area,services')->group(function () {

            // Restaurants (the vendor's own; details parsed out of the description)
            Route::get('restaurants',         'Vendor\VendorRestaurantController@index');

            // Hotels
            Route::get('hotels',              'Vendor\VendorServiceController@indexHotels');
            Route::get('hotels/{id}',         'Vendor\VendorServiceController@showHotel');
            Route::post('hotels',             'Vendor\VendorServiceController@storeHotel');
            Route::put('hotels/{id}',         'Vendor\VendorServiceController@updateHotel');
            Route::delete('hotels/{id}',      'Vendor\VendorServiceController@destroyHotel');

            // Tours
            Route::get('tours',               'Vendor\VendorServiceController@indexTours');
            Route::get('tours/trending',      'Vendor\VendorShelvesController@trending');
            Route::get('tours/bestsellers',   'Vendor\VendorShelvesController@bestsellers');
            Route::get('tours/{id}',          'Vendor\VendorServiceController@showTour');
            Route::post('tours',              'Vendor\VendorServiceController@storeTour');
            Route::put('tours/{id}',          'Vendor\VendorServiceController@updateTour');
            Route::delete('tours/{id}',       'Vendor\VendorServiceController@destroyTour');

            // Cars
            Route::get('cars',                'Vendor\VendorServiceController@indexCars');
            Route::get('cars/{id}',           'Vendor\VendorServiceController@showCar');

            // Boats
            Route::get('boats',               'Vendor\VendorServiceController@indexBoats');
            Route::get('boats/{id}',          'Vendor\VendorServiceController@showBoat');

            // Events
            Route::get('events',              'Vendor\VendorServiceController@indexEvents');
            Route::get('events/{id}',         'Vendor\VendorServiceController@showEvent');

            // Availability (works for any service type)
            Route::get('{type}/{id}/availability', 'Vendor\VendorAvailabilityController@check');
            Route::get('tours/{id}/tiers', 'Vendor\VendorTierController@index')->whereNumber('id');
            Route::get('tours/{id}/departures', 'Vendor\VendorDepartureController@index')->whereNumber('id');

            // Options and seats: manage
            Route::put('tours/{id}/tiers/{key}', 'Vendor\VendorTierController@save')->whereNumber('id');
            Route::delete('tours/{id}/tiers/{key}', 'Vendor\VendorTierController@destroy')->whereNumber('id');
            Route::put('tours/{id}/capacity', 'Vendor\VendorDepartureManageController@usualCapacity')->whereNumber('id');

            // Every kind of listing in one place
            Route::get('/',                            'Vendor\VendorListingsController@index');
            $types = 'tour|hotel|car|boat|space|event|flight|visa';
            Route::get('{type}/{id}',                  'Vendor\VendorListingsController@show')->where(['type' => $types, 'id' => '[0-9]+']);
            Route::patch('{type}/{id}/status',         'Vendor\VendorListingsController@status')->where(['type' => $types, 'id' => '[0-9]+']);
            Route::post('{type}/{id}/restore',         'Vendor\VendorListingsController@restore')->where(['type' => $types, 'id' => '[0-9]+']);
            Route::delete('{type}/{id}',               'Vendor\VendorListingsController@destroy')->where(['type' => $types, 'id' => '[0-9]+']);
        });

        // ── Reference data ────────────────────────────────────────────────────
        Route::middleware('api.scope:services:read')->group(function () {
            Route::get('locations',          'Vendor\VendorListingsController@locations');
            Route::get('categories/tours',   'Vendor\VendorListingsController@tourCategories');
        });

        // ── Departures (the board across all tours) ───────────────────────────
        Route::prefix('departures')->middleware('api.scope:area,services')->group(function () {
            Route::get('/',        'Vendor\VendorDepartureManageController@index');
            Route::post('/',       'Vendor\VendorDepartureManageController@store');
            Route::patch('{id}',   'Vendor\VendorDepartureManageController@update')->whereNumber('id');
            Route::delete('{id}',  'Vendor\VendorDepartureManageController@destroy')->whereNumber('id');
        });

        // ── Add-ons catalogue ─────────────────────────────────────────────────
        Route::prefix('addons')->middleware('api.scope:area,services')->group(function () {
            Route::get('/',        'Vendor\VendorAddonController@index');
            Route::post('/',       'Vendor\VendorAddonController@store');
            Route::get('{id}',     'Vendor\VendorAddonController@show')->whereNumber('id');
            Route::put('{id}',     'Vendor\VendorAddonController@update')->whereNumber('id');
            Route::delete('{id}',  'Vendor\VendorAddonController@destroy')->whereNumber('id');
        });

        // ── Trending pins and the AI marketplace ──────────────────────────────
        Route::prefix('shelves')->middleware('api.scope:area,analytics')->group(function () {
            Route::get('pins',                    'Vendor\VendorInsightsController@pins');
            Route::put('{shelf}/pins/{tourId}',   'Vendor\VendorInsightsController@pin')->whereNumber('tourId');
            Route::delete('{shelf}/pins/{tourId}', 'Vendor\VendorInsightsController@unpin')->whereNumber('tourId');
        });
        Route::prefix('marketplace')->middleware('api.scope:area,marketplace')->group(function () {
            Route::get('tours',            'Vendor\VendorInsightsController@marketplace');
            Route::put('tours/{tourId}',   'Vendor\VendorInsightsController@setMarketplace')->whereNumber('tourId');
        });


        // ── Loyalty ───────────────────────────────────────────────────────────
        Route::prefix('loyalty')->group(function () {
            Route::middleware('api.scope:loyalty:read')->group(function () {
                Route::get('rule',            'Vendor\VendorLoyaltyController@rule');
                Route::get('tiers',           'Vendor\VendorLoyaltyController@tiers');
                Route::get('members',         'Vendor\VendorLoyaltyController@members');
                Route::get('members/{id}',    'Vendor\VendorLoyaltyController@member')->whereNumber('id');
            });
            Route::middleware('api.scope:loyalty:write')->group(function () {
                Route::put('rule',            'Vendor\VendorLoyaltyController@saveRule');
                Route::post('tiers',          'Vendor\VendorLoyaltyController@storeTier');
                Route::put('tiers/{id}',      'Vendor\VendorLoyaltyController@updateTier')->whereNumber('id');
                Route::delete('tiers/{id}',   'Vendor\VendorLoyaltyController@destroyTier')->whereNumber('id');
                Route::post('adjustments',    'Vendor\VendorLoyaltyController@adjust');
            });
        });

        // ── Waitlist (the vendor's side) ──────────────────────────────────────
        Route::prefix('waitlist')->group(function () {
            Route::middleware('api.scope:waitlist:read')->group(function () {
                Route::get('/',                'Vendor\VendorWaitlistManageController@index');
                Route::get('{id}',             'Vendor\VendorWaitlistManageController@show')->whereNumber('id');
            });
            Route::middleware('api.scope:waitlist:write')->group(function () {
                Route::post('/',               'Vendor\VendorWaitlistManageController@store');
                Route::post('notify-openings', 'Vendor\VendorWaitlistManageController@notifyOpenings');
                Route::patch('{id}',           'Vendor\VendorWaitlistManageController@update')->whereNumber('id');
                Route::post('{id}/notify',     'Vendor\VendorWaitlistManageController@notify')->whereNumber('id');
                Route::delete('{id}',          'Vendor\VendorWaitlistManageController@destroy')->whereNumber('id');
            });
        });

        // ── Occasions ─────────────────────────────────────────────────────────
        Route::prefix('occasions')->group(function () {
            Route::get('/',        'Vendor\VendorOccasionController@index')->middleware('api.scope:messages:read');
            Route::middleware('api.scope:messages:write')->group(function () {
                Route::post('/',       'Vendor\VendorOccasionController@store');
                Route::post('import',  'Vendor\VendorOccasionController@import');
                Route::delete('{id}',  'Vendor\VendorOccasionController@destroy')->whereNumber('id');
            });
        });

        // ── Suppliers ─────────────────────────────────────────────────────────
        Route::prefix('suppliers')->group(function () {
            Route::middleware('api.scope:suppliers:read')->group(function () {
                Route::get('/',        'Vendor\VendorSuppliersController@index');
                Route::get('{id}',     'Vendor\VendorSuppliersController@show')->whereNumber('id');
            });
            Route::middleware('api.scope:suppliers:write')->group(function () {
                Route::post('/',                          'Vendor\VendorSuppliersController@store');
                Route::put('{id}',                        'Vendor\VendorSuppliersController@update')->whereNumber('id');
                Route::delete('{id}',                     'Vendor\VendorSuppliersController@destroy')->whereNumber('id');
                Route::post('{id}/routes',                'Vendor\VendorSuppliersController@addRoute')->whereNumber('id');
                Route::delete('{id}/routes/{routeId}',    'Vendor\VendorSuppliersController@deleteRoute')->whereNumber(['id', 'routeId']);
                Route::post('{id}/fares',                 'Vendor\VendorSuppliersController@addFare')->whereNumber('id');
                Route::delete('{id}/fares/{fareId}',      'Vendor\VendorSuppliersController@deleteFare')->whereNumber(['id', 'fareId']);
            });
        });

        // ── Webhooks ──────────────────────────────────────────────────────────
        Route::prefix('webhooks')->group(function () {
            Route::middleware('api.scope:webhooks:read')->group(function () {
                Route::get('events',                          'Vendor\VendorWebhookApiController@events');
                Route::get('/',                               'Vendor\VendorWebhookApiController@index');
                Route::get('{id}',                            'Vendor\VendorWebhookApiController@show')->whereNumber('id');
                Route::get('{id}/deliveries',                 'Vendor\VendorWebhookApiController@deliveries')->whereNumber('id');
                Route::get('{id}/deliveries/{deliveryId}',    'Vendor\VendorWebhookApiController@showDelivery')->whereNumber(['id', 'deliveryId']);
            });
            Route::middleware('api.scope:webhooks:write')->group(function () {
                Route::post('/',                              'Vendor\VendorWebhookApiController@store');
                Route::put('{id}',                            'Vendor\VendorWebhookApiController@update')->whereNumber('id');
                Route::delete('{id}',                         'Vendor\VendorWebhookApiController@destroy')->whereNumber('id');
                Route::post('{id}/rotate-secret',             'Vendor\VendorWebhookApiController@rotateSecret')->whereNumber('id');
                Route::post('{id}/test',                      'Vendor\VendorWebhookApiController@test')->whereNumber('id');
                Route::post('{id}/deliveries/{deliveryId}/redeliver', 'Vendor\VendorWebhookApiController@redeliver')->whereNumber(['id', 'deliveryId']);
            });
        });

        // ── Scheduled messages and campaigns ──────────────────────────────────
        Route::prefix('messages')->group(function () {
            Route::middleware('api.scope:messages:read')->group(function () {
                Route::get('options',              'Vendor\VendorMessagesController@options');
                Route::get('scheduled',            'Vendor\VendorMessagesController@index');
                Route::get('scheduled/log',        'Vendor\VendorMessagesController@log');
                Route::get('scheduled/{id}',       'Vendor\VendorMessagesController@show')->whereNumber('id');
                Route::get('campaigns/audiences',  'Vendor\VendorMessagesController@audiences');
                Route::get('campaigns',            'Vendor\VendorMessagesController@campaigns');
                Route::get('campaigns/{id}',       'Vendor\VendorMessagesController@showCampaign')->whereNumber('id');
            });
            Route::middleware('api.scope:messages:write')->group(function () {
                Route::post('scheduled',           'Vendor\VendorMessagesController@store');
                Route::post('scheduled/starter',   'Vendor\VendorMessagesController@starter');
                Route::put('scheduled/{id}',       'Vendor\VendorMessagesController@update')->whereNumber('id');
                Route::patch('scheduled/{id}',     'Vendor\VendorMessagesController@setActive')->whereNumber('id');
                Route::delete('scheduled/{id}',    'Vendor\VendorMessagesController@destroy')->whereNumber('id');
                Route::post('campaigns',           'Vendor\VendorMessagesController@storeCampaign');
                Route::put('campaigns/{id}',       'Vendor\VendorMessagesController@updateCampaign')->whereNumber('id');
                Route::delete('campaigns/{id}',    'Vendor\VendorMessagesController@destroyCampaign')->whereNumber('id');
                Route::post('campaigns/{id}/send', 'Vendor\VendorMessagesController@sendCampaign')->whereNumber('id');
            });
        });

        // ── Customer records (CRM) ────────────────────────────────────────────
        Route::prefix('crm/customers')->group(function () {
            Route::middleware('api.scope:customers:read')->group(function () {
                Route::get('/',               'Vendor\VendorCrmController@index');
                Route::get('{id}',            'Vendor\VendorCrmController@show')->whereNumber('id');
                Route::get('{id}/bookings',   'Vendor\VendorCrmController@bookings')->whereNumber('id');
            });
            Route::middleware('api.scope:customers:write')->group(function () {
                Route::post('/',              'Vendor\VendorCrmController@store');
                Route::post('sync',           'Vendor\VendorCrmController@sync');
                Route::put('{id}',            'Vendor\VendorCrmController@update')->whereNumber('id');
                Route::delete('{id}',         'Vendor\VendorCrmController@destroy')->whereNumber('id');
            });
        });

        // ── Invoices ──────────────────────────────────────────────────────────
        Route::prefix('invoices')->group(function () {
            Route::middleware('api.scope:invoices:read')->group(function () {
                Route::get('/',                    'Vendor\VendorInvoiceController@index');
                Route::get('settings',             'Vendor\VendorInvoiceSettingsController@show');
                Route::get('reports/receivables',  'Vendor\VendorInvoiceReportsController@receivables');
                Route::get('reports/revenue',      'Vendor\VendorInvoiceReportsController@revenue');
                Route::get('reports/tax',          'Vendor\VendorInvoiceReportsController@tax');
                Route::get('reports/statement',    'Vendor\VendorInvoiceReportsController@statement');
                Route::get('{id}',                 'Vendor\VendorInvoiceController@show')->whereNumber('id');
                Route::get('{id}/pdf',             'Vendor\VendorInvoiceController@pdf')->whereNumber('id');
            });
            Route::middleware('api.scope:invoices:write')->group(function () {
                Route::put('settings',                    'Vendor\VendorInvoiceSettingsController@update');
                Route::post('/',                          'Vendor\VendorInvoiceController@store');
                Route::put('{id}',                        'Vendor\VendorInvoiceController@update')->whereNumber('id');
                Route::delete('{id}',                     'Vendor\VendorInvoiceController@destroy')->whereNumber('id');
                Route::post('{id}/lines',                 'Vendor\VendorInvoiceController@addLine')->whereNumber('id');
                Route::delete('{id}/lines/{lineId}',      'Vendor\VendorInvoiceController@removeLine')->whereNumber(['id', 'lineId']);
                Route::post('{id}/payments',              'Vendor\VendorInvoiceController@addPayment')->whereNumber('id');
                Route::delete('{id}/payments/{paymentId}','Vendor\VendorInvoiceController@removePayment')->whereNumber(['id', 'paymentId']);
                Route::post('{id}/payments/{paymentId}/{decision}', 'Vendor\VendorInvoiceController@decidePayment')->whereNumber(['id', 'paymentId'])->whereIn('decision', ['approve', 'reject']);
                Route::post('{id}/refunds',               'Vendor\VendorInvoiceController@refund')->whereNumber('id');
                Route::post('{id}/credit-note',           'Vendor\VendorInvoiceController@creditNote')->whereNumber('id');
                Route::put('{id}/schedule',               'Vendor\VendorInvoiceController@schedule')->whereNumber('id');
                Route::post('{id}/convert',               'Vendor\VendorInvoiceController@convert')->whereNumber('id');
                Route::post('{id}/duplicate',             'Vendor\VendorInvoiceController@duplicate')->whereNumber('id');
                Route::post('{id}/send',                  'Vendor\VendorInvoiceController@send')->whereNumber('id');
                Route::post('{id}/issue',                 'Vendor\VendorInvoiceController@issue')->whereNumber('id');
                Route::post('{id}/void',                  'Vendor\VendorInvoiceController@void')->whereNumber('id');
            });
        });

        // ── Supplier bills (scope: invoices) ──────────────────────────────────
        Route::prefix('bills')->group(function () {
            Route::middleware('api.scope:invoices:read')->group(function () {
                Route::get('/',        'Vendor\VendorBillController@index');
                Route::get('{id}',     'Vendor\VendorBillController@show')->whereNumber('id');
            });
            Route::middleware('api.scope:invoices:write')->group(function () {
                Route::post('/',                 'Vendor\VendorBillController@store');
                Route::put('{id}',               'Vendor\VendorBillController@update')->whereNumber('id');
                Route::delete('{id}',            'Vendor\VendorBillController@destroy')->whereNumber('id');
                Route::post('{id}/payments',     'Vendor\VendorBillController@addPayment')->whereNumber('id');
                Route::post('{id}/void',         'Vendor\VendorBillController@void')->whereNumber('id');
            });
        });

        // ── Bookings ─────────────────────────────────────────────────────────

        // A vendor's own customers: their account, and their own bookings.
        // (X-Customer-Token says who is signed in; see VendorCustomerController.)
        Route::prefix('customers')->middleware('api.scope:area,customers')->group(function () {
            Route::post('register',        'Vendor\VendorCustomerController@register')->middleware('throttle:login');
            Route::post('login',           'Vendor\VendorCustomerController@login')->middleware('throttle:login');
            Route::post('forgot-password', 'Vendor\VendorCustomerController@forgotPassword')->middleware('throttle:login');
            Route::get('me',               'Vendor\VendorCustomerController@me');
            Route::put('me',               'Vendor\VendorCustomerController@update');
            Route::delete('me',            'Vendor\VendorCustomerController@destroy');
            Route::post('change-password', 'Vendor\VendorCustomerController@changePassword');
            Route::post('logout',          'Vendor\VendorCustomerController@logout');
        });

        Route::prefix('customer/bookings')->middleware('api.scope:area,customers')->group(function () {
            Route::get('/',                'Vendor\VendorCustomerBookingController@index');
            Route::post('/',               'Vendor\VendorCustomerBookingController@store');
            Route::post('pay',             'Vendor\VendorCustomerBookingController@payAll');
            Route::get('{code}',           'Vendor\VendorCustomerBookingController@show');
            Route::post('{code}/pay',      'Vendor\VendorCustomerBookingController@pay');
            Route::post('{code}/cancel',   'Vendor\VendorCustomerBookingController@cancel');
        });

        Route::prefix('customer/waitlist')->middleware('api.scope:area,customers')->group(function () {
            Route::get('/',        'Vendor\VendorWaitlistController@index');
            Route::post('/',       'Vendor\VendorWaitlistController@store');
            Route::delete('{id}',  'Vendor\VendorWaitlistController@destroy');
        });

        Route::prefix('bookings')->group(function () {
            Route::middleware('api.scope:bookings:read')->group(function () {
                Route::get('/',                        'Vendor\VendorBookingController@index');
                Route::get('{code}',                   'Vendor\VendorBookingController@show');
                Route::get('{code}/overview',          'Vendor\VendorBookingOpsController@overview');
                Route::get('{code}/timeline',          'Vendor\VendorBookingOpsController@timeline');
                Route::get('{code}/travellers',        'Vendor\VendorBookingOpsController@travellers');
                Route::get('{code}/payments',          'Vendor\VendorBookingOpsController@payments');
                Route::get('{code}/documents',         'Vendor\VendorBookingOpsController@documents');
                Route::get('{code}/guest-form',        'Vendor\VendorBookingOpsController@guestForm');
                Route::get('{code}/addons',            'Vendor\VendorBookingOpsController@addons');
                Route::get('{code}/check-in',          'Vendor\VendorBookingOpsController@checkIn');
                Route::get('{code}/profit',            'Vendor\VendorBillController@profit');
            });
            Route::middleware('api.scope:bookings:write')->group(function () {
                Route::post('/',                       'Vendor\VendorCreateBookingController@store');   // server-side creation
                Route::patch('{code}/status',          'Vendor\VendorBookingOpsController@changeStatus');
                Route::post('{code}/timeline',         'Vendor\VendorBookingOpsController@addToTimeline');
                Route::post('{code}/travellers',       'Vendor\VendorBookingOpsController@addTraveller');
                Route::put('{code}/travellers/{id}',   'Vendor\VendorBookingOpsController@updateTraveller')->whereNumber('id');
                Route::delete('{code}/travellers/{id}', 'Vendor\VendorBookingOpsController@deleteTraveller')->whereNumber('id');
                Route::put('{code}/payment-plan',      'Vendor\VendorBookingOpsController@buildPlan');
                Route::delete('{code}/payment-plan',   'Vendor\VendorBookingOpsController@clearPlan');
                Route::post('{code}/payments',         'Vendor\VendorBookingOpsController@recordPayment');
                Route::post('{code}/refunds',          'Vendor\VendorBookingOpsController@recordRefund');
                Route::post('{code}/documents',        'Vendor\VendorBookingOpsController@addDocument');
                Route::delete('{code}/documents/{id}', 'Vendor\VendorBookingOpsController@deleteDocument')->whereNumber('id');
                Route::post('{code}/trip-brief',       'Vendor\VendorBookingOpsController@sendTripBrief');
                Route::post('{code}/guest-form',       'Vendor\VendorBookingOpsController@newGuestForm');
                Route::post('{code}/invoice',          'Vendor\VendorBookingOpsController@invoice');
                Route::post('{code}/addons',           'Vendor\VendorBookingOpsController@addAddon');
                Route::delete('{code}/addons/{id}',    'Vendor\VendorBookingOpsController@removeAddon')->whereNumber('id');
                Route::post('{code}/check-in',         'Vendor\VendorBookingOpsController@markCheckedIn');
                Route::post('{code}/check-out',        'Vendor\VendorBookingOpsController@markCheckedOut');
                Route::post('{code}/no-show',          'Vendor\VendorBookingOpsController@markNoShow');
            });
        });

        // ── Tanova AI Trip Planner ────────────────────────────────────────────

        Route::prefix('tanova')->middleware('api.scope:area,planner')->group(function () {
            Route::get('trips',                   'Vendor\VendorTanovaController@index');
            // Named because formatTripResponse() builds a self link with route('api.v.tanova.show').
            Route::get('trips/{trip}',            'Vendor\VendorTanovaController@show')->name('api.v.tanova.show');  // {trip} matches TanovaTrip $trip
            Route::post('generate',               'Vendor\VendorTanovaController@generate');
            // Location-scoped, not trip-scoped — generate() doesn't return transport.
            Route::get('transports',              'Vendor\VendorTanovaTransportController@index');

            // One day in one place
            Route::get('occasions',               'Vendor\VendorDayPlanController@occasions');
            Route::post('day',                    'Vendor\VendorDayPlanController@day');
            Route::post('day-trips',              'Vendor\VendorDayPlanController@dayTrips');
        });

        // ── Concierge Chat ────────────────────────────────────────────────────

        Route::prefix('concierge')->middleware('api.scope:area,planner')->group(function () {
            Route::get('conversations',                          'Vendor\VendorConciergeController@index');
            Route::post('conversations',                         'Vendor\VendorConciergeController@start');
            Route::get('conversations/{conversation}',           'Vendor\VendorConciergeController@show');        // {conversation} matches $conversation
            Route::post('conversations/{conversation}/send',     'Vendor\VendorConciergeController@sendMessage'); // {conversation} matches $conversation
            Route::get('conversations/{id}/messages',            'Vendor\VendorConciergeController@messages')->whereNumber('id');
            Route::post('conversations/{id}/close',              'Vendor\VendorConciergeController@close')->whereNumber('id');
            Route::get('statistics',                             'Vendor\VendorConciergeController@statistics');
        });

        // ── Analytics ─────────────────────────────────────────────────────────

        Route::prefix('analytics')->middleware('api.scope:area,analytics')->group(function () {
            Route::get('occupancy',           'Vendor\VendorInsightsController@occupancy');
            Route::get('summary',             'Vendor\VendorAnalyticsController@summary');
            Route::get('revenue',             'Vendor\VendorAnalyticsController@revenue');
            Route::get('api-usage',           'Vendor\VendorAnalyticsController@apiUsage');
        });

    });
