<?php
namespace Themes\GoTrip\Tour\Blocks;

use Modules\Location\Models\Location;
use Modules\Media\Helpers\FileHelper;

class  FormSearchTour extends \Modules\Tour\Blocks\FormSearchTour
{
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
                            'value'   => '',
                            'name' => __("Normal")
                        ]
                    ]
                ],
                [
                    'id'    => 'bg_image',
                    'type'  => 'uploader',
                    'label' => __('- Layout Normal: Background Image Uploader')
                ]
            ],
            'category'=>__("Service Tour")
        ];
    }

    public function getTitle()
    {
        return __('Tour: Form Search');
    }

    public function render()
    {
        $model = [
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'style' => $this->style,
            'bg_image' => $this->bg_image,
            'list_slider' => $this->list_slider,
        ];
        $limit_location = 15;
        if( empty(setting_item("tour_location_search_style")) or setting_item("tour_location_search_style") == "normal" ){
            $limit_location = 1000;
        }
        $data = [
            'tour_location' => Location::where("status","publish")->limit($limit_location)->with(['translation'])->get()->toTree(),
            'bg_image_url'  => '',
        ];
        $data = array_merge($model, $data);
        if (!empty($model['bg_image'])) {
            $data['bg_image_url'] = FileHelper::url($model['bg_image'], 'full');
        }
        $data['style'] = $model['style'] ?? "";
        $data['list_slider'] = $model['list_slider'] ?? "";
        return $this->view('Tour::frontend.blocks.form-search-tour.index', $data);
    }

    public function contentAPI($model = []){
        if (!empty($model['bg_image'])) {
            $model['bg_image_url'] = FileHelper::url($model['bg_image'], 'full');
        }
        return $model;
    }
}
