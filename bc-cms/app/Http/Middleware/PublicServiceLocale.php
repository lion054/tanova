<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * The language of a service's public page. The platform picks a language from a URL prefix (/fr/hotel/...), which the language switch on
 * a plain page link never provided, so choosing Chinese changed nothing. On the service pages only, a `?lang=zh` in the link (or the
 * choice remembered from the last one) now sets the language. Portal and admin screens are deliberately untouched: a vendor editing a
 * service must always see and save the default language.
 */
class PublicServiceLocale
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && $request->is('hotel/*', 'tour/*', 'space/*', 'car/*', 'boat/*', 'event/*') && file_exists(storage_path() . '/installed')) {
            $codes = Arr::pluck(\Modules\Language\Models\Language::getActive(), 'locale');
            if (!in_array($request->segment(1), $codes, true)) {   // a URL prefix language, when the platform uses those, wins
                $asked = (string) $request->query('lang', '');
                $want = in_array($asked, $codes, true) ? $asked : (string) $request->session()->get('website_locale', '');
                if ($want !== '' && in_array($want, $codes, true)) {
                    app()->setLocale($want);
                    if ($asked !== '') {
                        $request->session()->put('website_locale', $want);
                    }
                }
            }
        }

        return $next($request);
    }
}
