<?php
namespace Themes\GoTrip\Space;

use Illuminate\Support\ServiceProvider;
use Modules\Template\Models\Template;
use Themes\GoTrip\Space\Blocks\FormSearchSpace;
use Themes\GoTrip\Space\Blocks\ListSpace;

class ModuleProvider extends ServiceProvider
{
    public function boot(){
        $this->mergeConfigFrom(__DIR__ . '/Configs/space.php', 'space');
        add_filter(\Modules\Space\Hook::SPACE_SETTING_CONFIG,[$this,'alterSettings']);
        add_action(\Modules\Space\Hook::SPACE_SETTING_AFTER_MAP,[$this,'showCustomFieldsAfterMap']);
    }



    public function alterSettings($settings){
        if(!empty($settings['space'])){
            $settings['space']['keys'][] = 'space_map_image';
        }
        return $settings;
    }

    public function showCustomFieldsAfterMap(){
        echo view('Space::admin.setting-after-map');
    }
}
