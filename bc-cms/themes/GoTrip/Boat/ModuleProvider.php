<?php

namespace Themes\GoTrip\Boat;

use Modules\ModuleServiceProvider;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot(){
        $this->mergeConfigFrom(__DIR__ . '/Configs/boat.php', 'boat');
    }
}
