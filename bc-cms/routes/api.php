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
Route::prefix('v')->middleware('auth:sanctum')->group(function () {

    // Tanova Trip Planner API
    Route::prefix('tanova')->group(function () {
        Route::post('generate', [\Modules\Vendor\Controllers\TanovaController::class, 'generate']);
        Route::get('trips', [\Modules\Vendor\Controllers\TanovaController::class, 'listTrips']);
        Route::get('trips/{id}', [\Modules\Vendor\Controllers\TanovaController::class, 'getTrip']);
        Route::get('trips/{id}/replan', [\Modules\Vendor\Controllers\TanovaController::class, 'getReplanSuggestions']);
    });

    // Tsoka AI Concierge (Chatbot) API
    Route::prefix('concierge')->group(function () {
        Route::get('conversations', [\Modules\Vendor\Controllers\ChatbotController::class, 'listConversations']);
        Route::post('message', [\Modules\Vendor\Controllers\ChatbotController::class, 'sendMessage']);
        Route::get('conversations/{id}', [\Modules\Vendor\Controllers\ChatbotController::class, 'getConversation']);
        Route::get('conversations/{id}/messages', [\Modules\Vendor\Controllers\ChatbotController::class, 'getMessages']);
        Route::post('conversations/{id}/close', [\Modules\Vendor\Controllers\ChatbotController::class, 'closeConversation']);
        Route::get('statistics', [\Modules\Vendor\Controllers\ChatbotController::class, 'getStatistics']);
    });

    // Services API
    Route::get('services/{type}', [\Modules\Vendor\Controllers\ServicesController::class, 'index']);

    // Bookings API
    Route::get('bookings', [\Modules\Vendor\Controllers\BookingsController::class, 'index']);
    Route::get('bookings/{id}', [\Modules\Vendor\Controllers\BookingsController::class, 'show']);
    Route::put('bookings/{id}', [\Modules\Vendor\Controllers\BookingsController::class, 'update']);
    Route::post('bookings/{id}/cancel', [\Modules\Vendor\Controllers\BookingsController::class, 'cancel']);

});
