<?php

namespace Themes\GoTrip;

use Illuminate\Contracts\Http\Kernel;
use Themes\GoTrip\Database\Seeders\DatabaseSeeder;
use Themes\GoTrip\Database\Seeders\DatabaseSeederForReImport;
use Themes\GoTrip\Boat\Blocks\ListBoat;
use Themes\GoTrip\Boat\Blocks\FormSearchBoat;
use Themes\GoTrip\News\Blocks\ListNews;
use Themes\GoTrip\Car\Blocks\FormSearchCar;
use Themes\GoTrip\Car\Blocks\ListCar;
use Themes\GoTrip\Event\Blocks\FormSearchEvent;
use Themes\GoTrip\Event\Blocks\ListEvent;
use Themes\GoTrip\Event\Blocks\EventTermFeatureBox;
use Themes\GoTrip\Flight\Blocks\FormSearchFlight;
use Themes\GoTrip\Hotel\Blocks\FormSearchHotel;
use Themes\GoTrip\Hotel\Blocks\ListHotel;
use Themes\GoTrip\Location\Blocks\ListLocations;
use Themes\GoTrip\Space\Blocks\FormSearchSpace;
use Themes\GoTrip\Space\Blocks\ListSpace;
use Themes\GoTrip\Tour\Blocks\Testimonial;
use Themes\GoTrip\Tour\Blocks\CallToAction;
use Themes\GoTrip\Tour\Blocks\ListFeaturedItem;
use Themes\GoTrip\Tour\Blocks\OurTeam;
use Themes\GoTrip\Tour\Blocks\FormSearchTour;
use Themes\GoTrip\Tour\Blocks\ListTours;
use Themes\GoTrip\Tour\Blocks\TourTypes;
use Themes\GoTrip\Tour\Blocks\TourDeals;
use Themes\GoTrip\Template\Blocks\FormSearchAllService;
use Illuminate\Pagination\Paginator;



class ThemeProvider extends \Themes\Base\ThemeProvider
{
    public static $version = '2.0';
    public static $name = 'Tsoka';
    public static $parent = 'base';
    public static $asset_version = 'gt2.0-01';

    public static $seeder = DatabaseSeeder::class;

    public static $seederForReImport = DatabaseSeederForReImport::class;




    public static function info()
    {
        // TODO: Implement info() method.
    }

    public function boot(Kernel $kernel)
    {
        parent::boot($kernel);
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        Paginator::defaultView('Layout::global.livewire.pagination');

        config([
            'app.asset_version' => static::$asset_version,
        ]);
    }

    public function register()
    {
        static::$modules = array_merge(static::$modules, [
            'user'      => \Modules\User\ModuleProvider::class,
            'visa'      => \Modules\Visa\ModuleProvider::class,
            'tanova'    => \Pro\Tanova\ModuleProvider::class,
            // In this list so the vendor sidebar and the admin menu pick up its menu entries (Finance > TourPay, Statement).
            'tourpay'   => \Modules\TourPay\ModuleProvider::class,
        ]);
        parent::register();
        $this->app->register(\Pro\Tanova\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\User\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Boat\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Page\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Location\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Hotel\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\News\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Template\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Tour\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Car\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Contact\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Space\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Core\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Vendor\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Event\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Flight\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Form\ModuleProvider::class);
        $this->app->register(\Themes\GoTrip\Visa\ModuleProvider::class);
        $this->app->register(\Modules\TourPay\ModuleProvider::class);
        $this->app->register(UpdaterProvider::class);
    }


    public static function getTemplateBlocks()
    {
        return [
            'list_news' => ListNews::class,
            'list_boat' => ListBoat::class,
            'form_search_boat' => FormSearchBoat::class,
            'form_search_car' => FormSearchCar::class,
            'list_car' => ListCar::class,
            'form_search_event' => FormSearchEvent::class,
            'list_event' => ListEvent::class,
            'event_term_feature_box' => EventTermFeatureBox::class,
            'form_search_flight' => FormSearchFlight::class,
            'form_search_hotel' => FormSearchHotel::class,
            'list_hotel' => ListHotel::class,
            'list_locations' => ListLocations::class,
            'form_search_space' => FormSearchSpace::class,
            'list_space' => ListSpace::class,
            'testimonial' => Testimonial::class,
            'call_to_action' => CallToAction::class,
            'list_featured_item' => ListFeaturedItem::class,
            'our_team' => OurTeam::class,
            'form_search_tour' => FormSearchTour::class,
            'list_tours' => ListTours::class,
            'tour_types' => TourTypes::class,
            'tour_deals' => TourDeals::class,
            'form_search_all_service' => FormSearchAllService::class,

        ];
    }
}
