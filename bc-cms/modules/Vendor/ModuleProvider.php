<?php
namespace Modules\Vendor;

use Illuminate\Support\ServiceProvider;
use Modules\ModuleServiceProvider;
use Modules\Vendor\Models\VendorPayout;

class ModuleProvider extends ModuleServiceProvider
{

    public function boot(){
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Views', 'vendor');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Vendor\Commands\DispatchScheduledMessages::class,
                \Modules\Vendor\Commands\RetryWebhooks::class,
                \App\Console\Commands\TenantExport::class,
                \Modules\Vendor\Commands\SyncSubscriptions::class,
            ]);
        }
    }
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouterServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }

    public static function getAdminMenu()
    {
        $count = VendorPayout::countInitial();
        return [
            'vendor_plans' => [
                'position'   => 68,
                'url'        => route('vendor.admin.plan.index'),
                'title'      => __('Vendor Plans'),
                'icon'       => 'icon ion-ios-pricetags',
                'permission' => 'user_create',
                'group'      => 'system',
            ],
            'vendor_subscriptions' => [
                'position'   => 69,
                'url'        => route('vendor.admin.subscription.index'),
                'title'      => __('Subscriptions'),
                'icon'       => 'icon ion-ios-card',
                'permission' => 'vendor_payout_view',
                'group'      => 'system',
            ],
            'payout' => [
                'position'   => 70,
                'url'        => route('vendor.admin.payout.index'),
                'title'      => __('Payouts :count', ['count' => $count ? sprintf('<span class="badge badge-warning">%d</span>', $count) : '']),
                'icon'       => 'icon ion-md-card',
                'permission' => 'user_create',
                'group'      => 'system',
            ],
        ];
    }


    public static function getTemplateBlocks(){
        return [
            'vendor_register_form'=>"\\Modules\\Vendor\\Blocks\\VendorRegisterForm",
            'vendor_list'=>"\\Modules\\Vendor\\Blocks\\ListVendor",
        ];
    }
    public static function getUserMenu()
    {
        $res = [];

        // New modules carry is_new => true so the sidebar shows a small "New" pill
        // (emphasis via a pill, not bold-everything). Icons use one ion family.

        // ── Bookings / daily ops (Phase 1 + 2) ────────────────────────────
        $res['today'] = [
            'url' => route('vendor.today'), 'title' => __('Today'),
            'icon' => 'icon ion-ios-sunny', 'position' => 70,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['checkin'] = [
            'url' => route('vendor.checkin.index'), 'title' => __('Check-In'),
            'icon' => 'icon ion-ios-checkmark-circle', 'position' => 71,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['departures'] = [
            'url' => route('vendor.departures.index'), 'title' => __('Departures & seats'),
            'icon' => 'icon ion-ios-calendar', 'position' => 70,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['shelves'] = [
            'url' => route('vendor.shelves'), 'title' => __('Trending & Bestsellers'),
            'icon' => 'icon ion-ios-flame', 'position' => 73,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['waitlist'] = [
            'url' => route('vendor.waitlist.index'), 'title' => __('Waitlist'),
            'icon' => 'icon ion-ios-people', 'position' => 72,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];

        // ── Catalog (Phase 1) ─────────────────────────────────────────────
        $res['pricing_tiers'] = [
            'url' => route('vendor.pricing_tiers.index'), 'title' => __('Pricing Tiers'),
            'icon' => 'icon ion-ios-pricetags', 'position' => 73,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['upsells'] = [
            'url' => route('vendor.upsells.index'), 'title' => __('Upsells & Add-ons'),
            'icon' => 'icon ion-ios-add-circle', 'position' => 74,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];

        // ── Insights (Phase 4) ────────────────────────────────────────────
        $res['analytics'] = [
            'url' => route('vendor.analytics'), 'title' => __('Analytics'),
            'icon' => 'icon ion-ios-stats', 'position' => 75,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];

        // ── Engage (Phase 3) ──────────────────────────────────────────────
        $res['loyalty'] = [
            'url' => route('vendor.loyalty.index'), 'title' => __('Loyalty'),
            'icon' => 'icon ion-ios-ribbon', 'position' => 76,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['scheduled_messages'] = [
            'url' => route('vendor.scheduled_messages.index'), 'title' => __('Scheduled Messages'),
            'icon' => 'icon ion-ios-paper-plane', 'position' => 77,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['occasions'] = [
            'url' => route('vendor.occasions.index'), 'title' => __('Occasions'),
            'icon' => 'icon ion-ios-gift', 'position' => 78,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        // Shared-calendar greetings — distinct from Occasions, which are per-customer.
        $res['holidays'] = [
            'url' => route('vendor.holidays.index'), 'title' => __('Holiday Greetings'),
            'icon' => 'icon ion-ios-calendar', 'position' => 78,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['customers'] = [
            'url' => route('vendor.customers.index'), 'title' => __('Customers'),
            'icon' => 'icon ion-ios-contacts', 'position' => 69,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['campaigns'] = [
            'url' => route('vendor.campaigns.index'), 'title' => __('Email Campaigns'),
            'icon' => 'icon ion-ios-mail', 'position' => 79,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];

        // ── Tanova marketplace (Phase 5) ──────────────────────────────────
        $res['marketplace'] = [
            'url' => route('vendor.marketplace.index'), 'title' => __('Marketplace'),
            'icon' => 'icon ion-ios-globe', 'position' => 80,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['inbox'] = [
            'url' => route('vendor.inbox.index'), 'title' => __('Inbox'),
            'icon' => 'icon ion-ios-chatbubbles', 'position' => 81,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];

        // ── Settings (Phase 6) ────────────────────────────────────────────
        $res['go_live'] = [
            'url' => route('vendor.go_live'), 'title' => __('Go Live'),
            'icon' => 'icon ion-ios-rocket', 'position' => 110,
            'permission' => 'dashboard_vendor_access', 'is_new' => true,
        ];
        $res['help'] = [
            'url' => route('vendor.help'), 'title' => __('Help'),
            'icon' => 'icon ion-ios-help-circle', 'position' => 120,
            'permission' => 'dashboard_vendor_access',
        ];

        $res['subscription'] = [
            'url'        => route('vendor.subscription.index'),
            'title'      => __('My Subscription'),
            'icon'       => 'icon ion-ios-pricetag',
            'position'   => 79,
            'permission' => 'dashboard_vendor_access',
        ];

        $res['booking_report'] = [
            'url'        => route('vendor.bookingReport'),
            'title'      => __('Booking Report'),
            'icon'       => 'icon ion-ios-pie',
            'position'   => 81,
            'permission' => 'dashboard_vendor_access',
        ];


        $res['enquiry']= [
            'position'   => 82,
            'icon'       => 'icofont-ebook',
            'url'        => route('vendor.enquiry_report'),
            'title'      => __("Enquiry Report"),
            'permission' => 'enquiry_view',
        ];

        if(!setting_item('disable_payout'))
        {
            $res['payout']= [
                'url'        => route('vendor.payout.index'),
                'title'      => __("Payouts"),
                'icon'       => 'icon ion-md-card',
                'position'   => 90,
                'permission' => 'dashboard_vendor_access',
            ];
        }

        $res['api_keys'] = [
            'url'        => route('vendor.api_keys.index'),
            'title'      => __('API Keys'),
            'icon'       => 'icon ion-ios-key',
            'position'   => 95,
            'permission' => 'dashboard_vendor_access',
        ];
        $res['api_docs'] = [
            'url'        => route('vendor.api_docs'),
            'title'      => __('API & Website Docs'),
            'icon'       => 'icon ion-ios-book',
            'position'   => 96,
            'permission' => 'dashboard_vendor_access',
            'is_new'     => true,
        ];
        if(is_enable_vendor_team()){

            $res['team']= [
                'url'        => route('vendor.team.index'),
                'title'      => __("Teams"),
                'icon'       => 'icon ion-ios-contacts',
                'position'   => 100,
                'permission' => 'dashboard_vendor_access',
            ];
        }
        return $res;
    }
}
