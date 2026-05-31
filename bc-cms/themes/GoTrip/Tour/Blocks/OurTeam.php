<?php
namespace Themes\GoTrip\Tour\Blocks;

use Modules\Media\Helpers\FileHelper;
use Modules\Template\Blocks\BaseBlock;

class OurTeam extends BaseBlock
{
    public $title;
    public $subtitle;
    public $style;
    public $list_item;

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
                    'id'        => 'subtitle',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Subtitle')
                ],

                [
                    'id'            => 'style',
                    'type'          => 'radios',
                    'label'         => __('Style Background'),
                    'values'        => [
                        [
                            'value'   => '',
                            'name' => __("Style 1")
                        ],
                    ]
                ],
                [
                    'id'          => 'list_item',
                    'type'        => 'listItem',
                    'label'       => __('List Item(s)'),
                    'title_field' => 'title',
                    'settings'    => [
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
                            'id'    => 'avatar',
                            'type'  => 'uploader',
                            'label' => __('Avatar Image')
                        ],
                    ]
                ],
            ],
            'category'=>__("Other Block")
        ];
    }

    public function getTitle()
    {
        return __('Our Team');
    }

    public function render()
    {
        $model = [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'style' => $this->style,
            'list_item' => $this->list_item,
        ];
        if (empty($model['style'])) {
            $model['style'] = 'style_1';
        }
        return $this->view('Template::frontend.blocks.our-team.index', $model);
    }
}
