<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Vendor\Models\VendorApiKey;
use Modules\Vendor\Models\VendorAllowedOrigin;
use Modules\Vendor\Models\VendorWebhook;

class ApiKeyPortalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $keys    = VendorApiKey::where('vendor_id', Auth::id())->orderByDesc('created_at')->get();
        $origins = VendorAllowedOrigin::where('vendor_id', Auth::id())->get();
        $webhooks = VendorWebhook::where('vendor_id', Auth::id())->withCount('deliveries')->get();

        return view('Vendor::frontend.api-keys.index', compact('keys', 'origins', 'webhooks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'type'       => 'nullable|in:secret,publishable',
            'mode'       => 'nullable|in:live,test',
            'domain'     => 'nullable|string|max:255',
            'rate_limit' => 'nullable|integer|min:0|max:10000000',
        ]);

        $key = VendorApiKey::generate(
            Auth::user(),
            $request->input('name'),
            $request->integer('rate_limit', 10000),
            $request->input('domain'),
            $request->input('type', 'secret'),
            $request->input('mode', 'live'),
        );

        return back()
            ->with('new_key', $key->key)
            ->with('new_key_name', $key->name)
            ->with('success', 'API key created. Copy it now — it will not be shown again.');
    }

    public function revoke(int $id)
    {
        VendorApiKey::where('vendor_id', Auth::id())->findOrFail($id)->update(['active' => false]);
        return back()->with('success', 'API key revoked.');
    }

    public function rotate(int $id)
    {
        $key   = VendorApiKey::where('vendor_id', Auth::id())->findOrFail($id);
        $plain = $key->rotate();

        // Email sent inside VendorApiKeyController::rotate() — not here since that's REST
        // Use mail directly for the web form path
        try {
            \Illuminate\Support\Facades\Mail::to(Auth::user())
                ->send(new \Modules\Vendor\Emails\ApiKeyRotatedEmail($key->name, Auth::user()->name ?? Auth::user()->email));
        } catch (\Throwable) {
            // Mail failure should not break the rotation
        }

        return back()
            ->with('new_key', $plain)
            ->with('new_key_name', $key->name)
            ->with('success', 'API key rotated. Copy the new key — it will not be shown again.');
    }

    public function storeOrigin(Request $request)
    {
        $request->validate([
            'origin' => ['required', 'string', 'max:255', 'regex:/^https:\/\/[a-z0-9\-\.]+(\:\d+)?$/i'],
        ]);

        $count = VendorAllowedOrigin::where('vendor_id', Auth::id())->count();
        if ($count >= 20) {
            return back()->with('error', 'Maximum 20 allowed origins per account.');
        }

        VendorAllowedOrigin::firstOrCreate([
            'vendor_id' => Auth::id(),
            'origin'    => rtrim($request->input('origin'), '/'),
        ]);

        return back()->with('success', 'Origin added.');
    }

    public function destroyOrigin(int $id)
    {
        $record = VendorAllowedOrigin::where('vendor_id', Auth::id())->findOrFail($id);
        \Illuminate\Support\Facades\Cache::forget("vendor_cors:{$record->vendor_id}:" . md5($record->origin));
        $record->delete();
        return back()->with('success', 'Origin removed.');
    }

    public function storeWebhook(Request $request)
    {
        $request->validate([
            'url'      => 'required|url|max:500',
            'events'   => 'required|array|min:1',
            'events.*' => 'in:' . implode(',', VendorWebhook::$supportedEvents),
        ]);

        VendorWebhook::generate(Auth::id(), $request->url, $request->events);

        return back()->with('success', 'Webhook registered. The signing secret has been emailed to you.');
    }

    public function destroyWebhook(int $id)
    {
        VendorWebhook::where('vendor_id', Auth::id())->findOrFail($id)->delete();
        return back()->with('success', 'Webhook removed.');
    }
}
