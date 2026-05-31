<?php


namespace Themes\GoTrip\Visa;


use Themes\GoTrip\Visa\Pages\Components\SearchForm;
use Livewire\Livewire;

class ModuleProvider extends \Modules\Visa\ModuleProvider
{

    // override visa livewire component
    public function boot()
    {
        parent::boot();
        Livewire::component('visa::search-form', SearchForm::class);
    }
}
