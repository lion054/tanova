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
