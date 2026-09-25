<?php
namespace Modules\TourPay;

use Modules\ModuleServiceProvider;
use Modules\User\Helpers\PermissionHelper;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        if ($this->app->runningInConsole()) {
            $this->commands([\Modules\TourPay\Commands\ReconcilePayments::class, \Modules\TourPay\Commands\SendReminders::class, \Modules\TourPay\Commands\MoneyBackfillCommand::class, \Modules\TourPay\Commands\MoneyReconcileCommand::class, \Modules\TourPay\Commands\FxRefreshCommand::class, \Modules\TourPay\Commands\CommissionSettleCommand::class, \Modules\TourPay\Commands\MoneyProtectCommand::class]);
        }

        \Modules\TourPay\Services\LedgerHooks::register();

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
            'statement' => [
                'url'        => route('tourpay.vendor.statement'),
                'title'      => __('Statement'),
                'icon'       => 'icofont-file-document',
                'position'   => 86,
            ],
        ];
    }
}
