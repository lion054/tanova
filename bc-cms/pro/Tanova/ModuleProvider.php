<?php

namespace Pro\Tanova;

use Modules\ModuleServiceProvider;
use Pro\Tanova\Commands\ImportFromTsokanew;
use Pro\Tanova\Models\TanovaTrip;
use Pro\Tanova\Services\TanovaEngine;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Views', 'Tanova');
        $this->loadViewsFrom(__DIR__ . '/Views', 'Tanova_trip');
        $this->mergeConfigFrom(__DIR__ . '/Configs/config.php', 'tanova');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');

        $this->loadRoutesFrom(__DIR__ . '/Routes/admin.php');
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');

        add_action('API_ROUTES', function () {
            $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');
        });

        if ($this->app->runningInConsole()) {
            $this->commands([ImportFromTsokanew::class]);
        }
    }

    public function register(): void
    {
        $this->app->singleton(TanovaEngine::class);
    }

    public static function getBookableServices(): array
    {
        return ['tanova_trip' => TanovaTrip::class];
    }

    /** Vendor portal sidebar entries. Grouped by config/vendor_nav.php. */
    public static function getUserMenu()
    {
        return [
            'catalogs' => [
                'url'        => route('vendor.catalogs.index'),
                'title'      => __('Catalogs'),
                'icon'       => 'icon ion-ios-albums',
                'position'   => 73,
                'permission' => 'dashboard_vendor_access',
                'is_new'     => true,
            ],
            'meals' => [
                'url'        => route('vendor.meals.index'),
                'title'      => __('Meals & Dining'),
                'icon'       => 'icon ion-ios-restaurant',
                'position'   => 74,
                'permission' => 'dashboard_vendor_access',
                'is_new'     => true,
            ],
            'restaurants' => [
                'url'        => route('vendor.restaurants.index'),
                'title'      => __('Restaurants'),
                'icon'       => 'icon ion-ios-wine',
                'position'   => 75,
                'permission' => 'dashboard_vendor_access',
                'is_new'     => true,
            ],
            'itineraries' => [
                'url'        => route('vendor.itineraries.index'),
                'title'      => __('Itinerary Builder'),
                'icon'       => 'icon ion-ios-map',
                'position'   => 68,
                'permission' => 'dashboard_vendor_access',
                'is_new'     => true,
            ],
            'ai_plan' => [
                'url'        => route('vendor.ai_plan.edit'),
                'title'      => __('AI Planning'),
                'icon'       => 'icon ion-ios-color-wand',
                'position'   => 87,
                'permission' => 'dashboard_vendor_access',
                'is_new'     => true,
            ],
        ];
    }

    public static function getAdminMenu(): array
    {
        return [
            'tanova' => [
                'position' => 62,
                'url'      => route('admin.tanova.index'),
                'title'    => __('Tanova'),
                'icon'     => 'ion ion-ios-map',
            ],
        ];
    }
}
