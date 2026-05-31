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

        add_filter('SETTINGS_PAGES', function ($pages) {
            return array_merge($pages, SettingClass::getSettingPages());
        });
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
