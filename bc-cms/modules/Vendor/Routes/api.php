<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor API Routes  (auth via Sanctum session token — for the portal UI)
| Prefix: /api/vendor/...
|--------------------------------------------------------------------------
*/

Route::prefix('vendor')->middleware('auth:sanctum')->group(function () {

    // API Key management
    Route::get('api-keys',              'VendorApiKeyController@index');
    Route::post('api-keys',             'VendorApiKeyController@store');
    Route::delete('api-keys/{id}',      'VendorApiKeyController@destroy');
    Route::post('api-keys/{id}/rotate', 'VendorApiKeyController@rotate');
    Route::get('usage',                 'VendorApiKeyController@usage');

    // Allowed origin management (CORS whitelist for vendor websites)
    Route::get('allowed-origins',          'VendorAllowedOriginController@index');
    Route::post('allowed-origins',         'VendorAllowedOriginController@store');
    Route::delete('allowed-origins/{id}',  'VendorAllowedOriginController@destroy');

    // Outbound webhook management
    Route::get('webhooks',                    'VendorWebhookController@index');
    Route::post('webhooks',                   'VendorWebhookController@store');
    Route::put('webhooks/{id}',               'VendorWebhookController@update');
    Route::delete('webhooks/{id}',            'VendorWebhookController@destroy');
    Route::get('webhooks/{id}/deliveries',    'VendorWebhookController@deliveries');

});
