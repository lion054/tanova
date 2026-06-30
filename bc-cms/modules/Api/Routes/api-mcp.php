<?php

use Illuminate\Support\Facades\Route;
use Modules\Api\Controllers\McpController;

/*
|--------------------------------------------------------------------------
| Tanova Marketplace MCP API — /api/mcp/...
|--------------------------------------------------------------------------
| PUBLIC, Tanova-branded marketplace for AI platforms (ChatGPT/Gemini/…).
| Discovery is cross-vendor (visible listings only). Transactions are bound to
| one vendor via the draft's resolved owner; session_token is the per-traveller
| secret. Rate-limited; no per-vendor API key (this is the central surface).
|--------------------------------------------------------------------------
*/

Route::prefix('mcp')->middleware(['api', 'throttle:60,1'])->group(function () {

    // Manifest / OpenAPI (Tanova-branded)
    Route::get('manifest',     [McpController::class, 'manifest']);
    Route::get('openapi.json', [McpController::class, 'openapi']);

    // Discovery (public, cross-vendor, visible listings only)
    Route::get('destinations',                  [McpController::class, 'listDestinations']);
    Route::get('experiences',                   [McpController::class, 'searchExperiences']);
    Route::get('experiences/{id}',              [McpController::class, 'getExperience'])->whereNumber('id');
    Route::get('experiences/{id}/availability', [McpController::class, 'checkAvailability'])->whereNumber('id');

    // Itinerary generation (inspiration)
    Route::post('itinerary', [McpController::class, 'generateItinerary']);

    // Booking draft → quote → submit → pay (session_token addressed)
    Route::post('drafts',                  [McpController::class, 'createDraft']);
    Route::patch('drafts/{token}',         [McpController::class, 'updateDraft']);
    Route::get('drafts/{token}/quote',     [McpController::class, 'getQuote']);
    Route::post('drafts/{token}/submit',   [McpController::class, 'submitBooking']);
    Route::post('drafts/{token}/pay-deposit', [McpController::class, 'payDeposit']);

    // Post-booking
    Route::get('bookings/{token}/status',    [McpController::class, 'getBookingStatus']);
    Route::post('bookings/{token}/messages', [McpController::class, 'sendMessage']);
});
