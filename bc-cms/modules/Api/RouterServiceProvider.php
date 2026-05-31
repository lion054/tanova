<?php
namespace Modules\Api;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouterServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Api\Controllers';

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapVendorApiRoutes();
    }

    /**
     * Consumer-facing REST API (mobile app, public search, user auth).
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['api'])
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/Routes/api.php');
    }

    /**
     * Vendor-facing REST API (/api/v/*).
     * Auth: sk_live_xxx API key resolved by ResolveVendorApiKey middleware.
     * All responses are scoped to the authenticated vendor.
     */
    protected function mapVendorApiRoutes()
    {
        Route::prefix('api')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/Routes/api-vendor.php');
    }
}
