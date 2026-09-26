<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Support\Facades\Route;
Route::group(['prefix'=>'vendor'],function(){
    Route::post('/register','VendorController@register')->name('vendor.register');

});

Route::group(['prefix'=>'vendor','middleware' => ['auth']],function(){
    Route::match(['get'],'/payouts','PayoutController@index')->name("vendor.payout.index");
    Route::post('/storePayoutAccounts','PayoutController@storePayoutAccounts')->name("vendor.payout.storePayoutAccounts");
    Route::post('/createPayoutRequest','PayoutController@createPayoutRequest')->name("vendor.payout.createPayoutRequest");

    Route::get('/booking-report','VendorController@bookingReport')->name("vendor.bookingReport");

    Route::prefix('team')->name('vendor.team.')->group(function(){
        Route::get('/','TeamController@index')->name("index");
        Route::post('/add','TeamController@add')->name("add");
        Route::get('/edit/{vendorTeam}','TeamController@edit')->name("edit");
        Route::get('/reSendRequest/{vendorTeam}','TeamController@reSendRequest')->name("re-send-request");
        Route::post('/store/{vendorTeam}','TeamController@store')->name("store");
        Route::get('/delete/{vendorTeam}','TeamController@delete')->name("delete")->middleware('signed');
    });

});

Route::group(['prefix'=>'vendor/subscription','middleware'=>['auth']],function(){
    Route::get('/','SubscriptionController@index')->name('vendor.subscription.index');
    Route::get('/plans','SubscriptionController@plans')->name('vendor.subscription.plans');
    Route::post('/os','SubscriptionController@chooseOs')->name('vendor.subscription.os');
    Route::post('/order','SubscriptionController@order')->name('vendor.subscription.order');
    Route::post('/order/{id}/cancel','SubscriptionController@cancelOrder')->name('vendor.subscription.order.cancel');
});

// ── Languages: translate the company's services ───────────────────────────
Route::group(['prefix' => 'vendor/languages', 'middleware' => ['auth']], function () {
    Route::get('/',  'LanguagesController@index')->name('vendor.languages.index');
    Route::post('/', 'LanguagesController@store')->name('vendor.languages.store');
    Route::post('/add', 'LanguagesController@addLanguage')->name('vendor.languages.add');
    Route::post('/ai/queue', 'LanguagesController@aiQueue')->name('vendor.languages.ai.queue')->middleware('throttle:60,1');
    Route::post('/ai/run', 'LanguagesController@aiRun')->name('vendor.languages.ai.run')->middleware('throttle:240,1');
});

// ── API Key management portal (vendor self-service) ───────────────────────
Route::group(['prefix' => 'vendor/api-keys', 'middleware' => ['auth']], function () {
    Route::get('/',                     'ApiKeyPortalController@index')->name('vendor.api_keys.index');
    Route::post('/',                    'ApiKeyPortalController@store')->name('vendor.api_keys.store');
    Route::post('/{id}/revoke',         'ApiKeyPortalController@revoke')->name('vendor.api_keys.revoke');
    Route::post('/{id}/rotate',         'ApiKeyPortalController@rotate')->name('vendor.api_keys.rotate');
    Route::post('/origins',             'ApiKeyPortalController@storeOrigin')->name('vendor.api_keys.origins.store');
    Route::delete('/origins/{id}',      'ApiKeyPortalController@destroyOrigin')->name('vendor.api_keys.origins.destroy');
    Route::post('/webhooks',            'ApiKeyPortalController@storeWebhook')->name('vendor.api_keys.webhooks.store');
    Route::delete('/webhooks/{id}',     'ApiKeyPortalController@destroyWebhook')->name('vendor.api_keys.webhooks.destroy');
});

Route::group(['prefix'=>'vendor/enquiry-report'],function(){
    Route::get('/','EnquiryController@enquiryReport')->name("vendor.enquiry_report");
    Route::get('/bulkEdit/{id}','EnquiryController@enquiryReportBulkEdit')->name("vendor.enquiry_report.bulk_edit")->middleware(['signed']);
    Route::get('/{enquiry}/reply','EnquiryController@reply')->name('vendor.enquiry_report.reply');
    Route::post('/{enquiry}/reply/store','EnquiryController@replyStore')->name('vendor.enquiry_report.replyStore');
    Route::get('/del/{id}','EnquiryController@delete')->name('vendor.enquiry_report.delete');
});


Route::get('team-accept','TeamController@accept')->name('team-accept')->middleware('signed');

// ── Concierge & Support (Chat conversations) ─────────────────────────────
Route::group(['prefix' => 'user/concierge', 'middleware' => ['auth']], function () {
    Route::get('/',                     'PortalConciergeController@index')->name('user.concierge.index');
    Route::get('/setup',                'PortalConciergeController@setup')->name('user.concierge.setup');
    Route::get('/statistics',           'PortalConciergeController@statistics')->name('user.concierge.statistics');
    Route::get('/{id}',                 'PortalConciergeController@show')->name('user.concierge.show');
    Route::get('/{id}/messages',        'PortalConciergeController@messages')->name('user.concierge.messages');
    Route::post('/',                    'PortalConciergeController@store')->name('user.concierge.store');
    Route::post('/{id}/close',          'PortalConciergeController@close')->name('user.concierge.close');
});

// ── Operations: pricing tiers, upsells, quotes, waitlist, check-in, today ──
Route::group(['prefix' => 'vendor', 'middleware' => ['auth']], function () {

    // Pricing tiers (Phase 1)
    Route::get('/pricing-tiers',                 'PricingTierController@index')->name('vendor.pricing_tiers.index');
    Route::post('/pricing-tiers/package',        'PricingTierController@saveTier')->name('vendor.pricing_tiers.package.save');
    Route::delete('/pricing-tiers/package/{tier}', 'PricingTierController@deleteTier')->name('vendor.pricing_tiers.package.delete');
    Route::post('/pricing-tiers',                'PricingTierController@store')->name('vendor.pricing_tiers.store');
    Route::put('/pricing-tiers/{pricingTier}',   'PricingTierController@update')->name('vendor.pricing_tiers.update');
    Route::delete('/pricing-tiers/{pricingTier}', 'PricingTierController@destroy')->name('vendor.pricing_tiers.destroy');

    // Upsell catalog (Phase 1)
    Route::get('/upsells',           'UpsellController@index')->name('vendor.upsells.index');
    Route::post('/upsells',          'UpsellController@store')->name('vendor.upsells.store');
    Route::put('/upsells/{upsell}',  'UpsellController@update')->name('vendor.upsells.update');
    Route::delete('/upsells/{upsell}', 'UpsellController@destroy')->name('vendor.upsells.destroy');
    Route::get('/upsells/services',            'UpsellController@services')->name('vendor.upsells.services');
    Route::post('/upsells/{upsell}/toggle',    'UpsellController@toggle')->name('vendor.upsells.toggle');
    Route::post('/upsells/{upsell}/feature',   'UpsellController@feature')->name('vendor.upsells.feature');

    // Per-booking operations page (Phase 1 + 2)
    Route::get('/bookings/{booking}/ops', 'BookingOpsController@show')->name('vendor.bookings.ops');
    Route::post('/bookings/{booking}/status',            'BookingOpsController@status')->name('vendor.bookings.status');
    Route::post('/bookings/{booking}/comms',             'BookingOpsController@addComm')->name('vendor.bookings.comms.add');
    Route::delete('/bookings/{booking}/comms/{id}',      'BookingOpsController@deleteComm')->name('vendor.bookings.comms.delete');
    Route::post('/bookings/{booking}/guests',            'BookingOpsController@addGuest')->name('vendor.bookings.guests.add');
    Route::put('/bookings/{booking}/guests/{id}',        'BookingOpsController@updateGuest')->name('vendor.bookings.guests.update');
    Route::delete('/bookings/{booking}/guests/{id}',     'BookingOpsController@deleteGuest')->name('vendor.bookings.guests.delete');
    Route::post('/bookings/{booking}/guest-link',        'BookingOpsController@newGuestLink')->name('vendor.bookings.guests.link');
    Route::post('/bookings/{booking}/plan',              'BookingOpsController@buildPlan')->name('vendor.bookings.plan.build');
    Route::delete('/bookings/{booking}/plan',            'BookingOpsController@clearPlan')->name('vendor.bookings.plan.clear');
    Route::post('/bookings/{booking}/plan/{id}/waive',   'BookingOpsController@waivePlanRow')->name('vendor.bookings.plan.waive');
    Route::post('/bookings/{booking}/payments',          'BookingOpsController@recordPayment')->name('vendor.bookings.payments.add');
    Route::post('/bookings/{booking}/refunds',           'BookingOpsController@recordRefund')->name('vendor.bookings.refunds.add');
    Route::post('/bookings/{booking}/documents',         'BookingOpsController@addDocument')->name('vendor.bookings.documents.add');
    Route::delete('/bookings/{booking}/documents/{id}',  'BookingOpsController@deleteDocument')->name('vendor.bookings.documents.delete');
    Route::post('/bookings/{booking}/trip-brief',        'BookingOpsController@sendTripBrief')->name('vendor.bookings.trip_brief');

    // Booking add-ons (Phase 1)
    Route::post('/bookings/{booking}/upsells', 'UpsellController@attach')->name('vendor.upsells.attach');
    Route::delete('/booking-upsells/{item}',   'UpsellController@detach')->name('vendor.upsells.detach');

    // Quotes / counter-offers (Phase 1)
    Route::post('/bookings/{booking}/quotes', 'QuoteController@send')->name('vendor.quotes.send');
    Route::post('/quotes/{quote}/counter',    'QuoteController@counter')->name('vendor.quotes.counter');
    Route::post('/quotes/{quote}/accept',     'QuoteController@accept')->name('vendor.quotes.accept');
    Route::post('/quotes/{quote}/decline',    'QuoteController@decline')->name('vendor.quotes.decline');

    // Customers / CRM (Tanova port, phase 4)
    Route::get('/customers',              'CustomerController@index')->name('vendor.customers.index');
    Route::post('/customers',             'CustomerController@store')->name('vendor.customers.store');
    Route::post('/customers/sync',        'CustomerController@sync')->name('vendor.customers.sync');
    Route::put('/customers/{customer}',   'CustomerController@update')->name('vendor.customers.update');
    Route::delete('/customers/{customer}', 'CustomerController@destroy')->name('vendor.customers.destroy');

    // Invoices (Tanova port, phase 5) — own ledger, never writes to booking payments
    // Invoices moved to TourPay, the one invoicing module. Old bookmarks land there.
    Route::get('/invoices', fn () => redirect()->route('tourpay.vendor.index'))->name('vendor.invoices.index');
    Route::get('/invoices/{any}', fn () => redirect()->route('tourpay.vendor.index'))->where('any', '.*');

    // Holiday greetings (Tanova port, phase 4)
    Route::get('/holidays',                'HolidayController@index')->name('vendor.holidays.index');
    Route::post('/holidays',               'HolidayController@store')->name('vendor.holidays.store');
    Route::post('/holidays/{holiday}/send', 'HolidayController@send')->name('vendor.holidays.send');
    Route::put('/holidays/{holiday}',      'HolidayController@update')->name('vendor.holidays.update');
    Route::delete('/holidays/{holiday}',   'HolidayController@destroy')->name('vendor.holidays.destroy');

    // Waitlist (Phase 2)
    Route::get('/waitlist',              'WaitlistController@index')->name('vendor.waitlist.index');
    Route::post('/waitlist',             'WaitlistController@store')->name('vendor.waitlist.store');
    Route::post('/waitlist/notify-all',  'WaitlistController@notifyAll')->name('vendor.waitlist.notifyAll');
    Route::post('/waitlist/{waitlist}/notify', 'WaitlistController@notify')->name('vendor.waitlist.notify');
    Route::put('/waitlist/{waitlist}',   'WaitlistController@update')->name('vendor.waitlist.update');
    Route::delete('/waitlist/{waitlist}', 'WaitlistController@destroy')->name('vendor.waitlist.destroy');

    // Check-in (Phase 2)
    Route::get('/checkin',                       'CheckinController@index')->name('vendor.checkin.index');
    Route::post('/bookings/{booking}/check-in',  'CheckinController@checkIn')->name('vendor.checkin.in');
    Route::post('/bookings/{booking}/check-out', 'CheckinController@checkOut')->name('vendor.checkin.out');
    Route::post('/bookings/{booking}/no-show',   'CheckinController@noShow')->name('vendor.checkin.noshow');


    // Trending and bestsellers
    Route::get('/trending',               'ShelvesController@index')->name('vendor.shelves');
    Route::post('/trending/pin',          'ShelvesController@pin')->name('vendor.shelves.pin');

    // Today snapshot (Phase 2)
    Route::get('/today', 'TodayController@index')->name('vendor.today');
    Route::get('/departures',                'DepartureController@index')->name('vendor.departures.index');
    Route::post('/departures',               'DepartureController@store')->name('vendor.departures.store');
    Route::put('/departures/{id}',           'DepartureController@update')->name('vendor.departures.update');
    Route::delete('/departures/{id}',        'DepartureController@destroy')->name('vendor.departures.destroy');
    Route::post('/departures/capacity',      'DepartureController@capacity')->name('vendor.departures.capacity');
    Route::get('/departures/tours',          'DepartureController@tours')->name('vendor.departures.tours');

    // Loyalty (Phase 3)
    Route::get('/loyalty',                'LoyaltyController@index')->name('vendor.loyalty.index');
    Route::post('/loyalty/tiers',         'LoyaltyController@storeTier')->name('vendor.loyalty.tiers.store');
    Route::delete('/loyalty/tiers/{tier}', 'LoyaltyController@destroyTier')->name('vendor.loyalty.tiers.destroy');
    Route::post('/loyalty/rule',          'LoyaltyController@saveRule')->name('vendor.loyalty.rule');
    Route::post('/loyalty/adjust',        'LoyaltyController@adjust')->name('vendor.loyalty.adjust');

    // Scheduled messages (Phase 3)
    Route::get('/scheduled-messages',                       'ScheduledMessageController@index')->name('vendor.scheduled_messages.index');
    Route::post('/scheduled-messages',                      'ScheduledMessageController@store')->name('vendor.scheduled_messages.store');
    Route::post('/scheduled-messages/starter',              'ScheduledMessageController@starter')->name('vendor.scheduled_messages.starter');
    Route::put('/scheduled-messages/{scheduledMessage}',    'ScheduledMessageController@update')->name('vendor.scheduled_messages.update');
    Route::post('/scheduled-messages/{scheduledMessage}/toggle', 'ScheduledMessageController@toggle')->name('vendor.scheduled_messages.toggle');
    Route::delete('/scheduled-messages/{scheduledMessage}', 'ScheduledMessageController@destroy')->name('vendor.scheduled_messages.destroy');

    // Occasions (Phase 3)
    Route::get('/occasions',              'OccasionController@index')->name('vendor.occasions.index');
    Route::post('/occasions',             'OccasionController@store')->name('vendor.occasions.store');
    Route::post('/occasions/import',                        'OccasionController@import')->name('vendor.occasions.import');
    Route::delete('/occasions/{occasion}', 'OccasionController@destroy')->name('vendor.occasions.destroy');

    // Email campaigns (Phase 3)
    Route::get('/campaigns',              'CampaignController@index')->name('vendor.campaigns.index');
    Route::post('/campaigns',             'CampaignController@store')->name('vendor.campaigns.store');
    Route::post('/campaigns/{campaign}/send', 'CampaignController@send')->name('vendor.campaigns.send');
    Route::delete('/campaigns/{campaign}', 'CampaignController@destroy')->name('vendor.campaigns.destroy');

    // Analytics (Phase 4)
    Route::get('/analytics', 'AnalyticsController@index')->name('vendor.analytics');

    // Tanova marketplace listing control (Phase 5)
    Route::get('/marketplace',                 'MarketplaceController@index')->name('vendor.marketplace.index');
    Route::post('/marketplace/{tour}/toggle',  'MarketplaceController@toggle')->name('vendor.marketplace.toggle');

    // AI Requests inbox (Phase 5) — 'count' before '{id}' so it isn't captured as a param
    Route::get('/ai-requests',             'AiRequestController@index')->name('vendor.ai_requests.index');
    Route::get('/ai-requests/count',       'AiRequestController@count')->name('vendor.ai_requests.count');
    Route::get('/ai-requests/{id}',        'AiRequestController@show')->whereNumber('id')->name('vendor.ai_requests.show');
    Route::post('/ai-requests/{id}/reply', 'AiRequestController@reply')->whereNumber('id')->name('vendor.ai_requests.reply');
    Route::post('/ai-requests/{id}/ai-draft', 'AiRequestController@aiDraft')->whereNumber('id')->name('vendor.ai_requests.ai_draft');

    // Portal extras (Phase 6)
    Route::get('/go-live', 'PortalExtrasController@goLive')->name('vendor.go_live');
    Route::get('/help',    'PortalExtrasController@help')->name('vendor.help');
    Route::get('/api-docs','PortalExtrasController@apiDocs')->name('vendor.api_docs');

    // Unified Inbox (merges AI Requests + Concierge) — multi-channel messaging
    Route::get('/inbox',                          'InboxController@index')->name('vendor.inbox.index');
    Route::get('/inbox/count',                    'InboxController@count')->name('vendor.inbox.count');
    Route::post('/inbox/bookings/{booking}/start','InboxController@startFromBooking')->name('vendor.inbox.start');
    Route::get('/inbox/{id}',                     'InboxController@show')->whereNumber('id')->name('vendor.inbox.show');
    Route::post('/inbox/{id}/reply',              'InboxController@reply')->whereNumber('id')->name('vendor.inbox.reply');
    Route::post('/inbox/{id}/ai-draft',           'InboxController@aiDraft')->whereNumber('id')->name('vendor.inbox.ai_draft');
});

// ── Integrations (Multi-channel setup) ────────────────────────────────────
Route::group(['prefix' => 'user/integrations', 'middleware' => ['auth']], function () {
    // The same hub as /admin/integrations (same controller and views), on a business's own address: categories, connect / test / disconnect
    // with the signed-in business's own credentials, and its messaging channels as cards in Communications.
    $hub = \Pro\Integrations\Controllers\IntegrationsAdminController::class;
    Route::get('/',                                [$hub, 'hub'])->name('user.integrations.index');
    Route::get('/category/{cat}',                  [$hub, 'category'])->name('user.integrations.category');
    Route::post('/app/{slug}/connect',             [$hub, 'connect'])->name('user.integrations.app.connect');
    Route::post('/app/{slug}/disconnect',          [$hub, 'disconnect'])->name('user.integrations.app.disconnect');
    Route::post('/app/{slug}/test',                [$hub, 'test'])->name('user.integrations.app.test');
    Route::get('/wetu/itineraries',                [$hub, 'wetuItineraries'])->name('user.integrations.wetu.itineraries');
    Route::post('/wetu/sync',                      [$hub, 'wetuSync'])->name('user.integrations.wetu.sync');
    Route::post('/wetu/import/{identifier}',       [$hub, 'wetuImport'])->name('user.integrations.wetu.import');

    Route::match(['get', 'post'], '/whatsapp',     'IntegrationsController@setupWhatsApp')->name('user.integrations.whatsapp');
    Route::match(['get', 'post'], '/facebook',     'IntegrationsController@setupFacebook')->name('user.integrations.facebook');
    Route::match(['get', 'post'], '/telegram',     'IntegrationsController@setupTelegram')->name('user.integrations.telegram');

    Route::post('/test',                'IntegrationsController@testConnection')->name('user.integrations.test');
    Route::post('/{channel}/disconnect', 'IntegrationsController@disconnect')->name('user.integrations.disconnect');
    Route::get('/{channel}/statistics',  'IntegrationsController@statistics')->name('user.integrations.statistics');
});


// The link a customer opens to say who is travelling (no login; a long random token
// ties it to one booking). See GuestFormController.
Route::get('guest-form/{token}',  'GuestFormController@show')->name('guest_form.show');
Route::post('guest-form/{token}', 'GuestFormController@store')->middleware('throttle:20,1')->name('guest_form.store');
