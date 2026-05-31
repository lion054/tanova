<?php
namespace Themes\GoTrip\Hotel\Blocks;

use Modules\Template\Blocks\BaseBlock;
use Modules\Hotel\Models\Hotel;
use Modules\Location\Models\Location;

class ListHotel extends \Modules\Hotel\Blocks\ListHotel
{
    public $title;
    public $desc;
    public $number;
    public $style;
    public $location_ids;
    public $order;
    public $order_by;
    public $is_featured;
    public $custom_ids;



    public function getOptions()
    {
        return [
            'settings' => [
                [
                    'id'        => 'title',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Title')
                ],
                [
                    'id'        => 'desc',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Desc')
                ],
                [
                    'id'        => 'number',
                    'type'      => 'input',
                    'inputType' => 'number',
                    'label'     => __('Number Item'),
                    "default" => "5",
                ],
                [
                    'id'            => 'style',
                    'type'          => 'radios',
                    'label'         => __('Style'),
                    'values'        => [
                        [
                            'value'   => 'normal',
                            'name' => __("Normal")
                        ],
                        [
                            'value'   => 'normal2',
                            'name' => __("Normal 2")
                        ],
                        [
                            'value'   => 'carousel',
                            'name' => __("Slider Carousel")
                        ],
                        [
                            'value'   => 'carousel_v2',
                            'name' => __("Slider Carousel V2")
                        ]
                    ]
                ],
                [
                    'id'      => 'location_ids',
                    'type'    => 'select2',
                    'label'   => __('Filter by Location'),
                    'select2' => [
                        'ajax'  => [
                            'url'      => route('location.admin.getForSelect2'),
                            'dataType' => 'json'
                        ],
                        'width' => '100%',
                        'multiple'    => "true",
                        'placeholder' => __('-- Select --')
                    ],
                    'pre_selected'=>route('location.admin.getForSelect2',['pre_selected'=>1])
                ],
                [
                    'id'            => 'order',
                    'type'          => 'radios',
                    'label'         => __('Order'),
                    'values'        => [
                        [
                            'value'   => 'id',
                            'name' => __("Date Create")
                        ],
                        [
                            'value'   => 'title',
                            'name' => __("Title")
                        ],
                    ],
                ],
                [
                    'id'            => 'order_by',
                    'type'          => 'radios',
                    'label'         => __('Order By'),
                    'values'        => [
                        [
                            'value'   => 'asc',
                            'name' => __("ASC")
                        ],
                        [
                            'value'   => 'desc',
                            'name' => __("DESC")
                        ],
                    ],
                    "selectOptions"=> [
                        'hideNoneSelectedText' => "true"
                    ]
                ],
                [
                    'type'=> "checkbox",
                    'label'=>__("Only featured items?"),
                    'id'=> "is_featured",
                    'default'=>true
                ],
                [
                    'id'           => 'custom_ids',
                    'type'         => 'select2',
                    'label'        => __('List by IDs'),
                    'select2'      => [
                        'ajax'        => [
                            'url'      => route('hotel.admin.getForSelect2'),
                            'dataType' => 'json'
                        ],
                        'width'       => '100%',
                        'multiple'    => "true",
                        'placeholder' => __('-- Select --')
                    ],
                    'pre_selected' => route('hotel.admin.getForSelect2', [
                        'pre_selected' => 1
                    ])
                ],
            ],
            'category'=>__("Service Hotel")
        ];
    }

    public function render()
    {
        $model = [
            'title' => $this->title,
            'desc' => $this->desc,
            'number' => $this->number,
            'style' => $this->style,
            'location_ids' => $this->location_ids,
            'order' => $this->order,
            'order_by' => $this->order_by,
            'is_featured' => $this->is_featured,
            'custom_ids' => $this->custom_ids,
        ];

        $list = $this->query($model);
        $locations = !empty($model['location_ids']) ? $this->list_locations($model['location_ids']) : null;
        $data = [
            'rows'       => $list,
            'style_list' => $model['style'],
            'title'      => $model['title'],
            'desc'       => $model['desc'],
            'locations'  => $locations
        ];
        return $this->view('Hotel::frontend.blocks.list-hotel.index', $data);
    }

    public function list_locations($location_ids){
        $locations = Location::whereIn('id',$location_ids)->where('status','publish');
        return $locations->get();
    }

    public function query($model){
        $listHotel = app(Hotel::class)->search($model);
        $limit = $model['number'] ?? 5;
        return $listHotel->limit($limit)->get();
    }
}
