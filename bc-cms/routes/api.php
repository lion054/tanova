<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// SEO & Sitemap Routes
include base_path('routes/api-seo.php');

// Geolocation API
Route::group(['prefix' => 'geo'], function () {
    Route::get('nearby', [\App\Http\Controllers\GeoController::class, 'nearby']);
    Route::get('detect', [\App\Http\Controllers\GeoController::class, 'detect']);
});

// ── Vendor API Routes (Prefix: /api/v) ───────────────────────────────────
// Authenticated via Bearer token (API key)
// SaaS model: All data filtered by vendor_id
// NOTE: The canonical vendor API (services, bookings, tanova, analytics, concierge)
// lives in modules/Api/Routes/api-vendor.php — properly secured by the
// ResolveVendorApiKey middleware and backed by Modules\Api\Controllers\Vendor\*.
// The only routes kept here are the chatbot endpoints whose controller exists.
// (Previously this block also referenced TanovaController/ServicesController/
//  BookingsController which never existed — those broke `php artisan route:list`
//  and were dead duplicates of api-vendor.php, so they have been removed.)
Route::prefix('v')->middleware('auth:sanctum')->group(function () {

    // Tsoka AI Concierge (Chatbot) API
    Route::prefix('concierge')->group(function () {
        Route::get('conversations', [\Modules\Vendor\Controllers\ChatbotController::class, 'listConversations']);
        Route::post('message', [\Modules\Vendor\Controllers\ChatbotController::class, 'sendMessage']);
        Route::get('conversations/{id}', [\Modules\Vendor\Controllers\ChatbotController::class, 'getConversation']);
        Route::get('conversations/{id}/messages', [\Modules\Vendor\Controllers\ChatbotController::class, 'getMessages']);
        Route::post('conversations/{id}/close', [\Modules\Vendor\Controllers\ChatbotController::class, 'closeConversation']);
        Route::get('statistics', [\Modules\Vendor\Controllers\ChatbotController::class, 'getStatistics']);
    });

});
