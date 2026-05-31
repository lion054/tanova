<?php
namespace Themes\GoTrip\Template\Blocks;

use Modules\Template\Blocks\BaseBlock;


class ListAllService extends BaseBlock
{
    public $title = "";
    public $sub_title = "";
    public $bg_image = "";
    public $list_slider = [];
    public $hide_form_search = false;
    public $single_form_search = false;
    public $style = "";
    public $bg_opacity = "";
    public $service_types = [];

    // TODO: find a way to dynamic title per module
    public $title_for_hotel = '';
    public $title_for_space = '';
    public $title_for_car   = '';
    public $title_for_event = '';
    public $title_for_tour  = '';
    public $title_for_flight = '';
    public $title_for_boat = '';
    public $title_for_visa = '';

    function getOptions()
    {
        $list_service = [];
        foreach (get_bookable_services() as $key => $service) {
            if($key == "flight"){
                continue;
            }
            $list_service[] = ['value'   => $key,
                               'name' => ucwords($key)
            ];
            $arg[] = [
                'id'        => 'title_for_'.$key,
                'type'      => 'input',
                'inputType' => 'text',
                'label'     => __('Title for :service',['service'=>ucwords($key)])
            ];
        }
        $arg[] = [
            'id'            => 'service_types',
            'type'          => 'checklist',
            'listBox'          => 'true',
            'label'         => "<strong>".__('Service Type')."</strong>",
            'values'        => $list_service,
            'std'=>[]
        ];

        $arg[] = [
            'id'        => 'title',
            'type'      => 'input',
            'inputType' => 'text',
            'label'     => __('Title')
        ];

        $arg[] = [
            'id'        => 'sub_title',
            'type'      => 'input',
            'inputType' => 'text',
            'label'     => __('Sub Title')
        ];

        $arg[] =  [
            'id'            => 'style',
            'type'          => 'radios',
            'label'         => __('Style Background'),
            'values'        => [
                [
                    'value'   => '',
                    'name' => __("Style 1")
                ],
            ]
        ];

        $arg[] =  [
            'id'        => 'number',
            'type'      => 'input',
            'inputType' => 'number',
            'label'     => __('Number Item')
        ];

        $arg[] =  [
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
        ];

        $arg[] =  [
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
        ];

        return ([
            'settings' => $arg,
            'category'=>__("Other Block")
        ]);
    }

    public function getTitle()
    {
        return __('List All Service');
    }

    public function render()
    {
        $model = [
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'bg_image' => $this->bg_image,
            'list_slider' => $this->list_slider,
            'hide_form_search' => $this->hide_form_search,
            'single_form_search' => $this->single_form_search,
            'style' => $this->style,
            'bg_opacity' => $this->bg_opacity,
            'service_types' => $this->service_types,
            
            'title_for_hotel' => $this->title_for_hotel,
            'title_for_space' => $this->title_for_space,
            'title_for_car'   => $this->title_for_car,
            'title_for_event' => $this->title_for_event,
            'title_for_tour'  => $this->title_for_tour,
            'title_for_flight' => $this->title_for_flight,
            'title_for_boat' => $this->title_for_boat,
            'title_for_visa' => $this->title_for_visa,
        ];

        $model['style'] = !empty($model['style']) ? $model['style'] :  "style_1";
        $model['order'] = !empty($model['order']) ? $model['order'] :  "id";
        $model['order_by'] = !empty($model['order_by']) ? $model['order_by'] :  "desc";
        $model['number'] = !empty($model['number']) ? $model['number'] :  8;
        $model['modelBlock'] = $model;
        $model['rows'] = [];
        if(!empty($model['service_types'])){
            foreach ($model['service_types'] as $service_type){
                $allServices = get_bookable_services();
                if(empty($allServices[$service_type])) continue;
                $module = new $allServices[$service_type];
                $table_name = $module->getTableName();
                $module = $module->with(['location','translation','hasWishList']);
                if(!empty($model['is_featured']))
                {
                    $module->where('is_featured',1);
                }
                $module = $module->orderBy($table_name.".".$model['order'], $model['order_by']);
                $module = $module->where($table_name.".status", "publish");
                $module = $module->with(['location']);
                if($service_type == 'tour'){
                    $module->with(['category_tour','category_tour.translation']);
                }

                $module = $module->groupBy($table_name.".id");
                $model['rows'][$service_type] = $module->limit($model['number'])->get();
            }
        }
        return $this->view('Template::frontend.blocks.list-all-service.'.$model['style'], $model);
    }

    public function contentAPI($model = []){
        return $model;
    }
}
