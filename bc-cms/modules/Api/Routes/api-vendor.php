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
Route::options('v/{any}', fn() => response('', 204))->where('any', '.*')->middleware('api');

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

        // ── Services ────────────────────────────────────────────────────────

        Route::prefix('services')->group(function () {

            // Hotels
            Route::get('hotels',              'Vendor\VendorServiceController@indexHotels');
            Route::get('hotels/{id}',         'Vendor\VendorServiceController@showHotel');
            Route::post('hotels',             'Vendor\VendorServiceController@storeHotel');
            Route::put('hotels/{id}',         'Vendor\VendorServiceController@updateHotel');
            Route::delete('hotels/{id}',      'Vendor\VendorServiceController@destroyHotel');

            // Tours
            Route::get('tours',               'Vendor\VendorServiceController@indexTours');
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
        });

        // ── Bookings ─────────────────────────────────────────────────────────

        Route::prefix('bookings')->group(function () {
            Route::get('/',                   'Vendor\VendorBookingController@index');
            Route::post('/',                  'Vendor\VendorCreateBookingController@store');   // server-side creation
            Route::get('{code}',              'Vendor\VendorBookingController@show');
            Route::patch('{code}/status',     'Vendor\VendorBookingController@updateStatus');
        });

        // ── Tanova AI Trip Planner ────────────────────────────────────────────

        Route::prefix('tanova')->group(function () {
            Route::get('trips',                   'Vendor\VendorTanovaController@index');
            Route::get('trips/{trip}',            'Vendor\VendorTanovaController@show');  // {trip} matches TanovaTrip $trip
            Route::post('generate',               'Vendor\VendorTanovaController@generate');
        });

        // ── Concierge Chat ────────────────────────────────────────────────────

        Route::prefix('concierge')->group(function () {
            Route::get('conversations',                          'Vendor\VendorConciergeController@index');
            Route::post('conversations',                         'Vendor\VendorConciergeController@start');
            Route::get('conversations/{conversation}',           'Vendor\VendorConciergeController@show');        // {conversation} matches $conversation
            Route::post('conversations/{conversation}/send',     'Vendor\VendorConciergeController@sendMessage'); // {conversation} matches $conversation
        });

        // ── Analytics ─────────────────────────────────────────────────────────

        Route::prefix('analytics')->group(function () {
            Route::get('summary',             'Vendor\VendorAnalyticsController@summary');
            Route::get('revenue',             'Vendor\VendorAnalyticsController@revenue');
            Route::get('api-usage',           'Vendor\VendorAnalyticsController@apiUsage');
        });

    });
