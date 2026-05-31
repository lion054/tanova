<?php

namespace Pro\Integrations;

use Modules\Core\Abstracts\BaseSettingsClass;

class SettingClass extends BaseSettingsClass
{
    public static function getSettingPages(): array
    {
        return [
            'integrations' => [
                'id'       => 'integrations',
                'title'    => __('Integrations & API'),
                'position' => 55,
                'view'     => 'Integrations::admin.settings',
                'keys'     => [
                    'anthropic_api_key',
                    'anthropic_model',
                    'tanova_window_hours',
                ],
                'is_pro' => true,
            ],
        ];
    }
}
