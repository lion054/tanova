<?php

namespace Modules\Api\Controllers\Vendor;

use Pro\Concierge\Controllers\Api\ConciergeApiController;
use Pro\Concierge\Services\ConciergeAiService;

/**
 * Thin wrapper — delegates to the core Concierge controller.
 * VendorContext is already set by ResolveVendorApiKey middleware,
 * so all scoping inside ConciergeApiController is automatic.
 */
class VendorConciergeController extends ConciergeApiController
{
    public function __construct(ConciergeAiService $ai)
    {
        parent::__construct($ai);
    }
}
