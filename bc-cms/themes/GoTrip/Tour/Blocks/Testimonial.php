<?php
namespace Themes\GoTrip\Tour\Blocks;

use Modules\Media\Helpers\FileHelper;
use Modules\Template\Blocks\BaseBlock;

class Testimonial extends BaseBlock
{
    public $style;
    public $title;
    public $subtitle;
    public $happy_people_number;
    public $happy_people_text;
    public $overall_rating_number;
    public $overall_rating_text;
    public $overall_rating_star;
    public $list_item;
    public $title_trusted;
    public $list_trusted;
    public $testimonial_bg;

    public function getOptions(){
        return [
            'settings' => [
                [
                    'id'            => 'style',
                    'type'          => 'radios',
                    'label'         => __('Style Background'),
                    'values'        => [
                        [
                            'value'   => '',
                            'name' => __("Style 1")
                        ],
                        [
                            'value'   => 'style_2',
                            'name' => __("Style 2")
                        ],
                        [
                            'value'   => 'style_3',
                            'name' => __("Style 3")
                        ],
                        [
                            'value'   => 'style_4',
                            'name' => __("Style 4")
                        ],
                        [
                            'value'   => 'style_5',
                            'name' => __("Style 5")
                        ],
                        [
                            'value'   => 'style_6',
                            'name' => __("Style 6")
                        ],
                        [
                            'value'   => 'style_7',
                            'name' => __("Style 7")
                        ],
                        [
                            'value'   => 'style_8',
                            'name' => __("Style 8")
                        ]
                    ]
                ],
                [
                    'id'        => 'title',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Title'),
                    'conditions' => ['style' => ['','style_2','style_3','style_4','style_5','style_6','style_8']]
                ],
                [
                    'id'        => 'subtitle',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Subtitle'),
                    'conditions' => ['style' => ['','style_2','style_3','style_4','style_5','style_6','style_8']]
                ],
                [
                    'id'        => 'happy_people_number',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Happy people number'),
                    'conditions' => ['style' => ['','style_2','style_3','style_4','style_5']]
                ],
                [
                    'id'        => 'happy_people_text',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Happy people text'),
                    'conditions' => ['style' => ['','style_2','style_3','style_4','style_5']]
                ],
                [
                    'id'        => 'overall_rating_number',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Overall rating number'),
                    'conditions' => ['style' => ['','style_2','style_3','style_4','style_5']]
                ],
                [
                    'id'        => 'overall_rating_text',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Overall rating text'),
                    'conditions' => ['style' => ['','style_2','style_3','style_4','style_5']]
                ],
                [
                    'id'        => 'overall_rating_star',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Overall rating star'),
                    'conditions' => ['style' => ['','style_2','style_3','style_4','style_5']]
                ],
                [
                    'id'          => 'list_item',
                    'type'        => 'listItem',
                    'label'       => __('List Item(s)'),
                    'title_field' => 'title',
                    'settings'    => [
                        [
                            'id'        => 'title',
                            'type'      => 'input',
                            'inputType' => 'text',
                            'label'     => __('Title')
                        ],
                        [
                            'id'        => 'name',
                            'type'      => 'input',
                            'inputType' => 'text',
                            'label'     => __('Name')
                        ],
                        [
                            'id'        => 'job',
                            'type'      => 'input',
                            'inputType' => 'text',
                            'label'     => __('Job')
                        ],
                        [
                            'id'    => 'desc',
                            'type'  => 'textArea',
                            'label' => __('Desc')
                        ],
                        [
                            'id'        => 'number_star',
                            'type'      => 'input',
                            'inputType' => 'number',
                            'label'     => __('Number star')
                        ],
                        [
                            'id'    => 'avatar',
                            'type'  => 'uploader',
                            'label' => __('Avatar Image')
                        ]
                    ]
                ],
                [
                    'id'        => 'title_trusted',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Title Trusted'),
                    'conditions' => ['style' => ['','style_2','style_3','style_4','style_5','style_6']]
                ],
                [
                    'id'          => 'list_trusted',
                    'type'        => 'listItem',
                    'label'       => __('List Trusted(s)'),
                    'title_field' => 'title',
                    'settings'    => [
                        [
                            'id'    => 'avatar',
                            'type'  => 'uploader',
                            'label' => __('Logo Image')
                        ],
                    ],
                    'conditions' => ['style' => ['','style_2','style_3','style_4','style_5','style_6']]
                ],
                [
                    'id'    => 'testimonial_bg',
                    'type'  => 'uploader',
                    'label' => __('Testimonial Background (For Style 4, Style 6, Style 7)'),
                    'conditions' => ['style' => ['style_4','style_6','style_7']]
                ],
            ],
            'category'=>__("Other Block")
        ];
    }

    public function getTitle()
    {
        return __('List Testimonial');
    }

    public function render()
    {
        $model = [
            'style' => $this->style,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'happy_people_number' => $this->happy_people_number,
            'happy_people_text' => $this->happy_people_text,
            'overall_rating_number' => $this->overall_rating_number,
            'overall_rating_text' => $this->overall_rating_text,
            'overall_rating_star' => $this->overall_rating_star,
            'list_item' => $this->list_item,
            'title_trusted' => $this->title_trusted,
            'list_trusted' => $this->list_trusted,
            'testimonial_bg' => $this->testimonial_bg,
        ];
        if (empty($model['style'])) {
            $model['style'] = 'style_1';
        }
        return $this->view('Tour::frontend.blocks.testimonial.index', $model);
    }

    public function contentAPI($model = []){
        if(!empty($model['list_item'])){
            foreach (  $model['list_item'] as &$item ){
                $item['avatar_url'] = FileHelper::url($item['avatar'], 'full');
            }
        }
        return $model;
    }
}
