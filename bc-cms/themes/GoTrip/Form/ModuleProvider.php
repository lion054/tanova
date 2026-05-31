<?php


namespace Themes\GoTrip\Form;

use Livewire\Livewire;

class ModuleProvider extends \Modules\Form\ModuleProvider
{

    // override visa livewire component
    public function boot()
    {
        parent::boot();
        app('view')->prependNamespace('Form', __DIR__ . '/Views');

        Livewire::component('form::simple-form', SimpleForm::class);
    }
}
