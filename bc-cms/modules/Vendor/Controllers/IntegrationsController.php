<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;

class IntegrationsController extends Controller
{
    /**
     * Show integrations dashboard
     */
    public function index()
    {
        $vendor = Auth::user();

        $integrations = [
            'whatsapp' => [
                'name' => 'WhatsApp Business',
                'icon' => 'whatsapp',
                'color' => '#25D366',
                'connected' => !empty($vendor->whatsapp_phone),
                'enabled' => $vendor->whatsapp_enabled ?? false,
                'phone' => $vendor->whatsapp_phone ?? null,
                'description' => 'Chat with customers on WhatsApp',
            ],
            'facebook' => [
                'name' => 'Facebook Messenger',
                'icon' => 'facebook',
                'color' => '#1877F2',
                'connected' => !empty($vendor->facebook_page_id),
                'enabled' => $vendor->facebook_enabled ?? false,
                'page_id' => $vendor->facebook_page_id ?? null,
                'description' => 'Chat with customers on Facebook Messenger',
            ],
            'telegram' => [
                'name' => 'Telegram Bot',
                'icon' => 'telegram',
                'color' => '#0088cc',
                'connected' => !empty($vendor->telegram_bot_token),
                'enabled' => $vendor->telegram_enabled ?? false,
                'bot_token' => $vendor->telegram_bot_token ? '●●●●●●●●' : null,
                'description' => 'Chat with customers on Telegram',
            ],
        ];

        return view('vendor::frontend.integrations.index', [
            'vendor' => $vendor,
            'integrations' => $integrations,
        ]);
    }

    /**
     * Setup WhatsApp Business
     */
    public function setupWhatsApp(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('vendor::frontend.integrations.whatsapp', [
                'vendor' => Auth::user(),
            ]);
        }

        $request->validate([
            'whatsapp_phone' => 'required|regex:/^\+?[1-9]\d{1,14}$/',
            'whatsapp_access_token' => 'required|string|min:20',
            'whatsapp_phone_number_id' => 'required|string',
            'whatsapp_business_account_id' => 'required|string',
        ], [
            'whatsapp_phone.regex' => 'Phone must be in format: +1234567890',
        ]);

        $vendor = Auth::user();

        // Test connection before saving
        if ($request->has('test_connection')) {
            try {
                $response = Http::withToken($request->whatsapp_access_token)
                    ->get("https://graph.instagram.com/v18.0/{$request->whatsapp_phone_number_id}");

                if (!$response->successful()) {
                    return back()->withInput()
                        ->with('error', 'Invalid WhatsApp credentials. Please verify and try again.');
                }
            } catch (\Exception $e) {
                return back()->withInput()
                    ->with('error', 'Failed to connect to WhatsApp. Please check your credentials.');
            }
        }

        // Save credentials (encrypted)
        $vendor->update([
            'whatsapp_phone' => $request->whatsapp_phone,
            'whatsapp_access_token' => Crypt::encryptString($request->whatsapp_access_token),
            'whatsapp_phone_number_id' => $request->whatsapp_phone_number_id,
            'whatsapp_business_account_id' => $request->whatsapp_business_account_id,
            'whatsapp_enabled' => true,
        ]);

        return redirect()->route('user.integrations.index')
            ->with('success', 'WhatsApp Business connected successfully!');
    }

    /**
     * Setup Facebook Messenger
     */
    public function setupFacebook(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('vendor::frontend.integrations.facebook', [
                'vendor' => Auth::user(),
            ]);
        }

        $request->validate([
            'facebook_page_id' => 'required|regex:/^\d+$/',
            'facebook_access_token' => 'required|string|min:20',
            'facebook_app_id' => 'nullable|string',
            'facebook_app_secret' => 'nullable|string',
        ]);

        $vendor = Auth::user();

        // Test connection
        if ($request->has('test_connection')) {
            try {
                $response = Http::get(
                    "https://graph.facebook.com/{$request->facebook_page_id}",
                    ['access_token' => $request->facebook_access_token]
                );

                if (!$response->successful()) {
                    return back()->withInput()
                        ->with('error', 'Invalid Facebook credentials.');
                }
            } catch (\Exception $e) {
                return back()->withInput()
                    ->with('error', 'Failed to connect to Facebook.');
            }
        }

        // Save credentials (encrypted)
        $vendor->update([
            'facebook_page_id' => $request->facebook_page_id,
            'facebook_access_token' => Crypt::encryptString($request->facebook_access_token),
            'facebook_app_id' => $request->facebook_app_id,
            'facebook_app_secret' => $request->facebook_app_secret ? Crypt::encryptString($request->facebook_app_secret) : null,
            'facebook_enabled' => true,
        ]);

        return redirect()->route('user.integrations.index')
            ->with('success', 'Facebook Messenger connected successfully!');
    }

    /**
     * Setup Telegram Bot
     */
    public function setupTelegram(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('vendor::frontend.integrations.telegram', [
                'vendor' => Auth::user(),
            ]);
        }

        $request->validate([
            'telegram_bot_token' => 'required|regex:/^\d+:[A-Za-z0-9_-]{25,}$/',
        ], [
            'telegram_bot_token.regex' => 'Invalid Telegram bot token format.',
        ]);

        $vendor = Auth::user();

        // Test connection
        if ($request->has('test_connection')) {
            try {
                $response = Http::get(
                    "https://api.telegram.org/bot{$request->telegram_bot_token}/getMe"
                );

                if (!$response->successful()) {
                    return back()->withInput()
                        ->with('error', 'Invalid Telegram bot token.');
                }

                $botInfo = $response->json();
                $botUsername = $botInfo['result']['username'] ?? null;
            } catch (\Exception $e) {
                return back()->withInput()
                    ->with('error', 'Failed to connect to Telegram bot.');
            }
        }

        // Save credentials (encrypted)
        $vendor->update([
            'telegram_bot_token' => Crypt::encryptString($request->telegram_bot_token),
            'telegram_enabled' => true,
        ]);

        return redirect()->route('user.integrations.index')
            ->with('success', 'Telegram Bot connected successfully!');
    }

    /**
     * Disable/disconnect integration
     */
    public function disconnect(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:whatsapp,facebook,telegram',
        ]);

        $vendor = Auth::user();
        $channel = $request->channel;

        $updates = [];
        switch ($channel) {
            case 'whatsapp':
                $updates = [
                    'whatsapp_enabled' => false,
                    'whatsapp_phone' => null,
                    'whatsapp_access_token' => null,
                ];
                break;
            case 'facebook':
                $updates = [
                    'facebook_enabled' => false,
                    'facebook_page_id' => null,
                    'facebook_access_token' => null,
                ];
                break;
            case 'telegram':
                $updates = [
                    'telegram_enabled' => false,
                    'telegram_bot_token' => null,
                ];
                break;
        }

        $vendor->update($updates);

        return redirect()->route('user.integrations.index')
            ->with('success', ucfirst($channel) . ' disconnected successfully.');
    }

    /**
     * Test connection to a channel
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:whatsapp,facebook,telegram',
        ]);

        $vendor = Auth::user();
        $channel = $request->channel;
        $success = false;
        $message = '';

        try {
            switch ($channel) {
                case 'whatsapp':
                    if (!$vendor->whatsapp_access_token) {
                        $message = 'WhatsApp not configured.';
                        break;
                    }
                    $token = Crypt::decryptString($vendor->whatsapp_access_token);
                    $response = Http::withToken($token)
                        ->get("https://graph.instagram.com/v18.0/{$vendor->whatsapp_phone_number_id}");
                    $success = $response->successful();
                    $message = $success ? 'WhatsApp connection successful!' : 'WhatsApp connection failed.';
                    break;

                case 'facebook':
                    if (!$vendor->facebook_access_token) {
                        $message = 'Facebook not configured.';
                        break;
                    }
                    $token = Crypt::decryptString($vendor->facebook_access_token);
                    $response = Http::get(
                        "https://graph.facebook.com/{$vendor->facebook_page_id}",
                        ['access_token' => $token]
                    );
                    $success = $response->successful();
                    $message = $success ? 'Facebook connection successful!' : 'Facebook connection failed.';
                    break;

                case 'telegram':
                    if (!$vendor->telegram_bot_token) {
                        $message = 'Telegram not configured.';
                        break;
                    }
                    $token = Crypt::decryptString($vendor->telegram_bot_token);
                    $response = Http::get("https://api.telegram.org/bot{$token}/getMe");
                    $success = $response->successful();
                    $message = $success ? 'Telegram connection successful!' : 'Telegram connection failed.';
                    break;
            }
        } catch (\Exception $e) {
            $message = 'Connection test failed: ' . $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * Get channel statistics
     */
    public function statistics(Request $request)
    {
        $vendor = Auth::user();
        $channel = $request->get('channel');

        try {
            // Fetch stats from API
            $response = \Illuminate\Support\Facades\Http::withToken($vendor->api_key)
                ->get(url("/api/v/concierge/statistics"), [
                    'channel' => $channel,
                    'period' => '7days',
                ]);

            if ($response->successful()) {
                $stats = $response->json('data', []);
                return response()->json([
                    'success' => true,
                    'data' => $stats,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to fetch channel statistics', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to load statistics',
        ]);
    }
}
