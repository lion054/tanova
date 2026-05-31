<?php

use Illuminate\Support\Facades\Route;
use App\Services\Adapters\WhatsAppAdapter;
use App\Services\Adapters\FacebookMessengerAdapter;
use App\Services\Adapters\TelegramAdapter;

/**
 * Webhook routes for multi-channel integrations
 * These are PUBLIC routes (no authentication) that handle incoming messages from external services
 */

Route::prefix('webhooks')->group(function () {

    /**
     * WhatsApp Business API webhook
     * Setup: https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks
     */
    Route::post('whatsapp', function (\Illuminate\Http\Request $request, WhatsAppAdapter $adapter) {
        return response()->json($adapter->handleWebhook($request));
    })->name('webhooks.whatsapp');

    Route::get('whatsapp', function (\Illuminate\Http\Request $request, WhatsAppAdapter $adapter) {
        return $adapter->handleWebhook($request);
    });

    /**
     * Facebook Messenger webhook
     * Setup: https://developers.facebook.com/docs/messenger-platform/webhooks
     */
    Route::post('facebook', function (\Illuminate\Http\Request $request, FacebookMessengerAdapter $adapter) {
        return response()->json($adapter->handleWebhook($request));
    })->name('webhooks.facebook');

    Route::get('facebook', function (\Illuminate\Http\Request $request, FacebookMessengerAdapter $adapter) {
        return $adapter->handleWebhook($request);
    });

    /**
     * Telegram Bot API webhook
     * Setup: https://core.telegram.org/bots/api#setwebhook
     */
    Route::post('telegram', function (\Illuminate\Http\Request $request, TelegramAdapter $adapter) {
        return response()->json($adapter->handleWebhook($request));
    })->name('webhooks.telegram');

});
