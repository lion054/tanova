<?php
namespace Themes\GoTrip\Car\Blocks;

use Modules\Car\Blocks\FormSearchCar as BlocksFormSearchCar;

class FormSearchCar extends BlocksFormSearchCar
{
    public $bg_image;
    public function getOptions(){
        return [
            'settings' => [
                [
                    'id'        => 'title',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Title')
                ],
                [
                    'id'        => 'sub_title',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Sub Title')
                ],
                [
                    'id'            => 'style',
                    'type'          => 'radios',
                    'label'         => __('Style Background'),
                    'values'        => [
                        [
                            'value'   => 'carousel',
                            'name' => __("Slider Carousel")
                        ]
                    ]
                ],
                [
                    'id'    => 'bg_image',
                    'type'  => 'uploader',
                    'label' => __('- Layout Normal: Background Image Uploader')
                ],
                [
                    'id'          => 'list_slider',
                    'type'        => 'listItem',
                    'label'       => __('- Layout Slider: List Item(s)'),
                    'title_field' => 'title',
                    'settings'    => [
                        [
                            'id'    => 'bg_image',
                            'type'  => 'uploader',
                            'label' => __('Background Image Uploader')
                        ]
                    ]
                ]
            ],
            'category'=>__("Service Car")
        ];
    }
}
