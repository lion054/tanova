<?php
namespace App\Http\Middleware;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

use Closure;
use Illuminate\Support\Facades\Session;

class SetLanguageForApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (class_exists('\Debugbar')) {
            \Debugbar::disable();
        }

        // ?lang=fr, or the client's Accept-Language when it names a language the platform has.
        $locale = $request->get('lang');
        if ($locale === 'all') {
            $locale = null;   // "every language" is handled where services are shaped (see ApiLanguage); the text stays in the default
        } elseif (!$locale) {
            $locale = self::fromHeader($request);
        }
        if($locale)
        {
            $languages = \Modules\Language\Models\Language::getActive();
            $localeCodes = Arr::pluck($languages,'locale');
            if(in_array($locale,$localeCodes)){
                app()->setLocale($locale);
            }else{
                app()->setLocale(setting_item('site_locale'));
            }
        }
        if ($currency = $request->get('_currency')) {
            Session::put('bc_current_currency', $currency);
        }

        // set default header for api
        // TODO: Check if api/booking need it? cuz it will render HTML for now
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }

    /** The best active language named by the Accept-Language header ("fr-CH, fr;q=0.9, en;q=0.8"), by exact code, then by its main part. */
    private static function fromHeader($request): ?string
    {
        $h = (string) $request->header('Accept-Language', '');
        if ($h === '') {
            return null;
        }
        $active = Arr::pluck(\Modules\Language\Models\Language::getActive(), 'locale');
        $norm = fn ($c) => strtolower(str_replace('_', '-', $c));
        $map = [];
        foreach ($active as $c) {
            $map[$norm($c)] = $c;
        }
        $ranges = [];
        foreach (explode(',', $h) as $part) {
            $bits = explode(';', trim($part));
            $q = 1.0;
            foreach (array_slice($bits, 1) as $b) {
                if (preg_match('/^\s*q\s*=\s*([0-9.]+)/', $b, $m)) {
                    $q = (float) $m[1];
                }
            }
            if ($bits[0] !== '' && $bits[0] !== '*' && $q > 0) {
                $ranges[] = [$norm($bits[0]), $q];
            }
        }
        usort($ranges, fn ($a, $b) => $b[1] <=> $a[1]);
        foreach ($ranges as [$tag]) {
            if (isset($map[$tag])) {
                return $map[$tag];
            }
            $main = explode('-', $tag)[0];
            if (isset($map[$main])) {
                return $map[$main];
            }
        }

        return null;
    }
}
