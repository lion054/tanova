<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Vendor\Models\VendorAllowedOrigin;

class VendorAllowedOriginController extends Controller
{
    public function index(): JsonResponse
    {
        $origins = VendorAllowedOrigin::where('vendor_id', Auth::id())->get();
        return response()->json(['data' => $origins]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'origin' => [
                'required',
                'string',
                'max:255',
                'regex:/^https:\/\/[a-z0-9\-\.]+(\:\d+)?$/i', // https only in production
            ],
        ]);

        // Prevent unbounded origin lists per vendor
        $count = VendorAllowedOrigin::where('vendor_id', Auth::id())->count();
        if ($count >= 20) {
            return response()->json([
                'error' => ['code' => 'origin_limit', 'message' => 'Maximum 20 allowed origins per vendor.'],
            ], 422);
        }

        $origin = rtrim($request->input('origin'), '/');

        $record = VendorAllowedOrigin::firstOrCreate([
            'vendor_id' => Auth::id(),
            'origin'    => $origin,
        ]);

        if ($record->wasRecentlyCreated) {
            Cache::forget("vendor_cors:{$record->vendor_id}:" . md5($origin));
        }

        return response()->json([
            'message' => $record->wasRecentlyCreated ? 'Origin added.' : 'Origin already registered.',
            'data'    => $record,
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = VendorAllowedOrigin::where('vendor_id', Auth::id())->findOrFail($id);
        Cache::forget("vendor_cors:{$record->vendor_id}:" . md5($record->origin));
        $record->delete();
        return response()->json(['message' => 'Origin removed.']);
    }
}
