<?php

namespace App\Pro;

final class Pro
{
    const DEFAULT_ENABLE_THEMES = ['bc', 'gotrip'];
    public static function isPro()
    {
        $theme = env('BC_ACTIVE_THEME', defined('BC_INIT_THEME') ? BC_INIT_THEME : 'BC');

        // Always enable for BC now
        if (in_array(strtolower($theme), self::DEFAULT_ENABLE_THEMES)) return true;
        return defined('BC_IS_PRO') and BC_IS_PRO;
    }

    public static function isEnable()
    {
        return config('pro.enable') && in_array(strtolower(config('bc.active_theme')), self::DEFAULT_ENABLE_THEMES);
    }
}
