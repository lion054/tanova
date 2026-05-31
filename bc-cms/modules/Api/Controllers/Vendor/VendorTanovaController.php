<?php

namespace Modules\Api\Controllers\Vendor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pro\Tanova\Controllers\Api\TanovaApiController;
use Pro\Tanova\Services\TanovaEngine;

/**
 * Thin wrapper — delegates to the core Tanova controller.
 * VendorContext is already set by ResolveVendorApiKey middleware,
 * so all scoping inside TanovaApiController is automatic.
 */
class VendorTanovaController extends TanovaApiController
{
}
