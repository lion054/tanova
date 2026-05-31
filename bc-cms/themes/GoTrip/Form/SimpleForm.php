<?php

namespace Themes\GoTrip\Form;

use Modules\Form\SimpleForm as SimpleFormModule;

class SimpleForm extends SimpleFormModule
{
    public function render()
    {
        return view('Form::frontend.simple-form');
    }
}
