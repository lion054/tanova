<?php

namespace Pro\Integrations;

use Modules\ModuleServiceProvider;
use Pro\Integrations\Services\Fiscal\FiscalizeService;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Views', 'Integrations');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/Routes/admin.php');
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');

        add_filter('SETTINGS_PAGES', function ($pages) {
            return array_merge($pages, SettingClass::getSettingPages());
        });
    }

    /** Vendor portal sidebar entries. Grouped by config/vendor_nav.php. */
    public static function getUserMenu()
    {
        return [
            'operators' => [
                'url'        => route('vendor.operators.index'),
                'title'      => __('Operators & Suppliers'),
                'icon'       => 'icon ion-ios-git-network',
                'position'   => 86,
                'permission' => 'dashboard_vendor_access',
                'is_new'     => true,
            ],
        ];
    }

    public function register(): void
    {
        $this->app->singleton(FiscalizeService::class);
    }

    public static function getAdminMenu(): array
    {
        return [
            'integrations' => [
                'position' => 64,
                'url'      => route('admin.integrations.hub'),
                'title'    => __('Integrations'),
                'icon'     => 'ion ion-ios-apps',
            ],
        ];
    }
}
