<?php
namespace Themes\GoTrip\Boat\Blocks;

use Modules\Template\Blocks\BaseBlock;
use Modules\Boat\Models\Boat;

class ListBoat extends BaseBlock
{
    public $style;
    public $columns;
    public $title;
    public $desc;
    public $number;
    public $location_id;
    public $order;
    public $order_by;
    public $is_featured;
    public $custom_ids;

    public function getOptions(){
        return [
            'settings' => [
                [
                    'id'            => 'style',
                    'type'          => 'radios',
                    'label'         => __('Style'),
                    'values'        => [
                        [
                            'value'   => 'style_1',
                            'name' => __("Style 1")
                        ],
                        [
                            'value'   => 'style_2',
                            'name' => __("Style 2")
                        ],
                        [
                            'value'   => 'style_3',
                            'name' => __("Style 3")
                        ]
                    ]
                ],
                [
                    'id'            => 'columns',
                    'type'          => 'radios',
                    'label'         => __('Columns'),
                    'values'        => [
                        [
                            'value'   => '3',
                            'name' => __("3 columns")
                        ],
                        [
                            'value'   => '4',
                            'name' => __("4 columns")
                        ],
                    ]
                ],
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
                    'label'     => __('Number Item')
                ],
                [
                    'id'      => 'location_id',
                    'type'    => 'select2',
                    'label'   => __('Filter by Location'),
                    'select2' => [
                        'ajax'  => [
                            'url'      => route('location.admin.getForSelect2'),
                            'dataType' => 'json'
                        ],
                        'width' => '100%',
                        'allowClear' => 'true',
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
                    ]
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
                            'url'      => route('boat.admin.getForSelect2'),
                            'dataType' => 'json'
                        ],
                        'width'       => '100%',
                        'multiple'    => "true",
                        'placeholder' => __('-- Select --')
                    ],
                    'pre_selected' => route('boat.admin.getForSelect2', [
                        'pre_selected' => 1
                    ])
                ],
            ],
            'category'=>__("Service Boat")
        ];
    }

    public function getTitle()
    {
        return __('Boat: List Items');
    }

    public function render()
    {
        $model = [
            'title' => $this->title,
            'desc' => $this->desc,
            'style' => $this->style,
            'columns' => $this->columns,
            'location_id' => $this->location_id,
            'number' => $this->number,
            'is_featured' => $this->is_featured,
            'custom_ids' => $this->custom_ids,
            'order' => $this->order,
            'order_by' => $this->order_by,
        ];
        $list = $this->query($model);
        $data = [
            'rows'       => $list,
            'style' => $model['style'] ?? 'style_1',
            'columns' => $model['columns'] ?? '4',
            'title'      => $model['title'] ?? '',
            'desc'       => $model['desc'] ?? '',
        ];
        return $this->view('Boat::frontend.blocks.list-boat.' . $data['style'], $data);
    }

    public function contentAPI($model = []){
        $rows = $this->query($model);
        $model['data']= $rows->map(function($row){
            return $row->dataForApi();
        });
        return $model;
    }

    public function query($model){
        $listCar = app(Boat::class)->search($model);
        $limit = $model['number'] ?? 5;
        return $listCar->limit($limit)->get();
    }
}
