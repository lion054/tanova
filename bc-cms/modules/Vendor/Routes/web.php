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
    Route::post('/pricing-tiers',                'PricingTierController@store')->name('vendor.pricing_tiers.store');
    Route::put('/pricing-tiers/{pricingTier}',   'PricingTierController@update')->name('vendor.pricing_tiers.update');
    Route::delete('/pricing-tiers/{pricingTier}', 'PricingTierController@destroy')->name('vendor.pricing_tiers.destroy');

    // Upsell catalog (Phase 1)
    Route::get('/upsells',           'UpsellController@index')->name('vendor.upsells.index');
    Route::post('/upsells',          'UpsellController@store')->name('vendor.upsells.store');
    Route::put('/upsells/{upsell}',  'UpsellController@update')->name('vendor.upsells.update');
    Route::delete('/upsells/{upsell}', 'UpsellController@destroy')->name('vendor.upsells.destroy');

    // Per-booking operations page (Phase 1 + 2)
    Route::get('/bookings/{booking}/ops', 'BookingOpsController@show')->name('vendor.bookings.ops');

    // Booking add-ons (Phase 1)
    Route::post('/bookings/{booking}/upsells', 'UpsellController@attach')->name('vendor.upsells.attach');
    Route::delete('/booking-upsells/{item}',   'UpsellController@detach')->name('vendor.upsells.detach');

    // Quotes / counter-offers (Phase 1)
    Route::post('/bookings/{booking}/quotes', 'QuoteController@send')->name('vendor.quotes.send');
    Route::post('/quotes/{quote}/counter',    'QuoteController@counter')->name('vendor.quotes.counter');
    Route::post('/quotes/{quote}/accept',     'QuoteController@accept')->name('vendor.quotes.accept');
    Route::post('/quotes/{quote}/decline',    'QuoteController@decline')->name('vendor.quotes.decline');

    // Waitlist (Phase 2)
    Route::get('/waitlist',              'WaitlistController@index')->name('vendor.waitlist.index');
    Route::post('/waitlist',             'WaitlistController@store')->name('vendor.waitlist.store');
    Route::put('/waitlist/{waitlist}',   'WaitlistController@update')->name('vendor.waitlist.update');
    Route::delete('/waitlist/{waitlist}', 'WaitlistController@destroy')->name('vendor.waitlist.destroy');

    // Check-in (Phase 2)
    Route::get('/checkin',                       'CheckinController@index')->name('vendor.checkin.index');
    Route::post('/bookings/{booking}/check-in',  'CheckinController@checkIn')->name('vendor.checkin.in');
    Route::post('/bookings/{booking}/check-out', 'CheckinController@checkOut')->name('vendor.checkin.out');
    Route::post('/bookings/{booking}/no-show',   'CheckinController@noShow')->name('vendor.checkin.noshow');

    // Today snapshot (Phase 2)
    Route::get('/today', 'TodayController@index')->name('vendor.today');

    // Loyalty (Phase 3)
    Route::get('/loyalty',                'LoyaltyController@index')->name('vendor.loyalty.index');
    Route::post('/loyalty/tiers',         'LoyaltyController@storeTier')->name('vendor.loyalty.tiers.store');
    Route::delete('/loyalty/tiers/{tier}', 'LoyaltyController@destroyTier')->name('vendor.loyalty.tiers.destroy');
    Route::post('/loyalty/adjust',        'LoyaltyController@adjust')->name('vendor.loyalty.adjust');

    // Scheduled messages (Phase 3)
    Route::get('/scheduled-messages',                       'ScheduledMessageController@index')->name('vendor.scheduled_messages.index');
    Route::post('/scheduled-messages',                      'ScheduledMessageController@store')->name('vendor.scheduled_messages.store');
    Route::put('/scheduled-messages/{scheduledMessage}',    'ScheduledMessageController@update')->name('vendor.scheduled_messages.update');
    Route::post('/scheduled-messages/{scheduledMessage}/toggle', 'ScheduledMessageController@toggle')->name('vendor.scheduled_messages.toggle');
    Route::delete('/scheduled-messages/{scheduledMessage}', 'ScheduledMessageController@destroy')->name('vendor.scheduled_messages.destroy');

    // Occasions (Phase 3)
    Route::get('/occasions',              'OccasionController@index')->name('vendor.occasions.index');
    Route::post('/occasions',             'OccasionController@store')->name('vendor.occasions.store');
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
    Route::get('/',                     'IntegrationsController@index')->name('user.integrations.index');

    Route::match(['get', 'post'], '/whatsapp',     'IntegrationsController@setupWhatsApp')->name('user.integrations.whatsapp');
    Route::match(['get', 'post'], '/facebook',     'IntegrationsController@setupFacebook')->name('user.integrations.facebook');
    Route::match(['get', 'post'], '/telegram',     'IntegrationsController@setupTelegram')->name('user.integrations.telegram');

    Route::post('/test',                'IntegrationsController@testConnection')->name('user.integrations.test');
    Route::post('/{channel}/disconnect', 'IntegrationsController@disconnect')->name('user.integrations.disconnect');
    Route::get('/{channel}/statistics',  'IntegrationsController@statistics')->name('user.integrations.statistics');
});
