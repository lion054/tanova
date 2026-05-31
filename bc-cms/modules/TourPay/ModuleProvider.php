<?php
namespace Modules\TourPay;

use Modules\ModuleServiceProvider;
use Modules\User\Helpers\PermissionHelper;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');

        PermissionHelper::add([
            'tourpay_view',
            'tourpay_create',
            'tourpay_update',
            'tourpay_delete',
        ]);
    }

    public function register()
    {
        $this->app->register(RouterServiceProvider::class);
    }

    public static function getAdminMenu()
    {
        return [
            'tourpay' => [
                'position'   => 55,
                'url'        => route('tourpay.admin.index'),
                'title'      => __('TourPay'),
                'icon'       => 'icofont-money',
                'permission' => 'tourpay_view',
            ],
        ];
    }

    public static function getUserMenu()
    {
        return [
            'tourpay' => [
                'url'      => route('tourpay.vendor.index'),
                'title'    => __('TourPay'),
                'icon'     => 'icofont-money',
                'position' => 85,
            ],
        ];
    }
}
