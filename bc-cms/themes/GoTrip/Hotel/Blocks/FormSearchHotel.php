<?php
namespace Themes\GoTrip\Hotel\Blocks;

class FormSearchHotel extends \Modules\Hotel\Blocks\FormSearchHotel
{
    public $list_item;

    public function getOptions()
    {
        return [
            'settings' => [
                [
                    'id'          => 'list_item',
                    'type'        => 'listItem',
                    'label'       => __('- List Item(s)'),
                    'title_field' => 'title',
                    'settings'    => [
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
                            'id'        => 'discover_text',
                            'type'      => 'input',
                            'inputType' => 'text',
                            'label'     => __('Discover Title')
                        ],
                        [
                            'id'        => 'discover_link',
                            'type'      => 'input',
                            'inputType' => 'text',
                            'label'     => __('Discover Link')
                        ],
                        [
                            'id'    => 'bg_image',
                            'type'  => 'uploader',
                            'label' => __('Background Image Uploader')
                        ]
                    ]
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
            ],
            'category'=>__("Service Hotel")
        ];
    }
}
