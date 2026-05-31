<?php
namespace Themes\GoTrip\Tour;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

use Modules\Template\Models\Template;
use Modules\Tour\Hook;
use Modules\Tour\Models\TourCategory;
use Themes\GoTrip\Tour\Blocks\CallToAction;
use Themes\GoTrip\Tour\Blocks\FormSearchTour;
use Themes\GoTrip\Tour\Blocks\ListFeaturedItem;
use Themes\GoTrip\Tour\Blocks\ListTours;
use Themes\GoTrip\Tour\Blocks\OurTeam;
use Themes\GoTrip\Tour\Blocks\Testimonial;
use Themes\GoTrip\Tour\Blocks\TourDeals;
use Themes\GoTrip\Tour\Blocks\TourTypes;

class ModuleProvider extends ServiceProvider
{
    public function boot(){
        $this->mergeConfigFrom(__DIR__ . '/Configs/tour.php', 'tour');
        add_action(Hook::FORM_AFTER_CATEGORY,[$this,'category_icon']);
        add_action(Hook::AFTER_SAVING_CATEGORY,[$this,'save_category_icon']);
    }

    public function category_icon(TourCategory $cat){
        echo view('Tour::admin.category.form-icon',['row' => $cat]);
    }

    public function save_category_icon(TourCategory $cat, Request $request){
        $cat->cat_icon = $request->input('cat_icon');
        $cat->save();
    }
}
