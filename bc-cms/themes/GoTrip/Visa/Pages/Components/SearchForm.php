<?php

namespace Themes\GoTrip\Visa\Pages\Components;

use Livewire\Attributes\Url;
use Modules\Visa\Pages\Components\SearchForm as SearchFormModule;

class SearchForm extends SearchFormModule
{
    #[Url]
    public $to_country;

    #[Url]
    public $visa_type;

    #[Url]
    public $guests;

    public $shouldRedirect = false;

    public $layout = 'default';
    public $style = 'default';

    public function render()
    {
        if ($this->layout == 'vertical') {
            return view('Visa::frontend.components.search-form.index-vertical');
        }
        return view('Visa::frontend.components.search-form.index', ['style' => $this->style]);
    }

    public function placeholder()
    {
        return <<<'HTML'
        <form>
            <!-- Loading spinner... -->
             <span>...</span>
        </form>
        HTML;
    }

    public function submit()
    {
        $data = [
            'to_country' => $this->to_country,
            'visa_type' => $this->visa_type,
            'guests' => $this->guests,
        ];
        if ($this->shouldRedirect) {
            return $this->redirectRoute('visa.search', $data);
        }
        $this->dispatch('search', $data);
    }
}
