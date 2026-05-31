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
