<?php

namespace Pro\Concierge;

use Modules\ModuleServiceProvider;
use Pro\Concierge\Services\ConciergeAiService;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Views', 'Concierge');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');

        $this->loadRoutesFrom(__DIR__ . '/Routes/admin.php');

        add_action('API_ROUTES', function () {
            $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');
        });
    }

    public function register(): void
    {
        $this->app->singleton(ConciergeAiService::class);
    }

    public static function getAdminMenu(): array
    {
        return [
            'concierge' => [
                'position' => 63,
                'url'      => route('admin.concierge.index'),
                'title'    => __('Concierge'),
                'icon'     => 'ion ion-ios-chatbubbles',
            ],
        ];
    }
}
