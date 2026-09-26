<?php

namespace Modules\Vendor\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\FrontendController;
use Modules\Vendor\Services\ServiceTranslations;

/**
 * A small language dashboard: every service the company has, its words in the default language on the left, and a box to write them in
 * another language on the right. Progress per language and per kind of service; save a page of them at a time.
 */
class LanguagesController extends FrontendController
{
    private const PER_PAGE = 10;

    public function index(Request $request)
    {
        $this->checkPermission('dashboard_vendor_access');
        $vendorId = resolve_current_vendor_id();
        $user = Auth::user();
        $types = ServiceTranslations::typesFor($user);
        $languages = ServiceTranslations::languages();
        $locale = (string) $request->query('lang', optional($languages->first())->locale);
        $language = $languages->firstWhere('locale', $locale);
        $enabled = ServiceTranslations::enabled();

        $data = ['page_title' => __('Languages'), 'enabled' => $enabled, 'languages' => $languages, 'types' => $types, 'language' => $language, 'defaultName' => optional(\Modules\Language\Models\Language::where('locale', setting_item('site_locale'))->first())->name ?: __('the default language'),
            'breadcrumbs' => [['name' => __('Languages'), 'class' => 'active']]];
        if (!$enabled || !$language || !$types) {
            return view('Vendor::frontend.languages.index', $data + ['summary' => [], 'items' => null, 'type' => '', 'q' => '', 'only' => false, 'progress' => [], 'rows' => [], 'marks' => []]);
        }

        $type = (string) $request->query('type', '');
        $type = isset($types[$type]) ? $type : (array_key_first($types));
        $q = trim((string) $request->query('q', ''));
        $only = $request->boolean('missing');
        $summary = ServiceTranslations::summary($types, $vendorId, $locale);

        // The page: the services of one kind, newest first, optionally only the ones not finished in this language.
        $models = ServiceTranslations::query($type, $vendorId)->when($q !== '', fn ($b) => $b->where('title', 'like', '%' . $q . '%'))->orderByDesc('id')->get();
        $rows = ServiceTranslations::rows($type, $locale, $models->pluck('id'));
        $marks = ServiceTranslations::marksFor($type, $locale, $models->pluck('id'));
        $progress = [];
        foreach ($models as $m) {
            $progress[$m->id] = ServiceTranslations::progress($m, $rows->get($m->id), $types[$type]['fields'], $marks[$m->id] ?? []);
        }
        if ($only) {
            $models = $models->filter(fn ($m) => $progress[$m->id]['state'] !== 'done')->values();
        }
        $page = max(1, (int) $request->query('page', 1));
        $items = new \Illuminate\Pagination\LengthAwarePaginator($models->forPage($page, self::PER_PAGE), $models->count(), self::PER_PAGE, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('Vendor::frontend.languages.index', $data + compact('summary', 'items', 'type', 'q', 'only', 'progress', 'rows', 'marks'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('dashboard_vendor_access');
        $vendorId = resolve_current_vendor_id();
        $types = ServiceTranslations::typesFor(Auth::user());
        $locale = (string) $request->input('lang');
        $type = (string) $request->input('type');
        if (!ServiceTranslations::enabled() || !ServiceTranslations::languages()->contains('locale', $locale) || !isset($types[$type])) {
            return back()->with('danger', __('That language or kind of service is not available.'));
        }
        $fields = $types[$type]['fields'];
        $sent = (array) $request->input('t', []);
        // Only this company's services, whatever ids were sent.
        $mine = ServiceTranslations::query($type, $vendorId)->whereIn('id', array_map('intval', array_keys($sent)))->get();
        $saved = 0;
        foreach ($mine as $model) {
            $saved += ServiceTranslations::save($model, $type, $locale, (array) ($sent[$model->id] ?? []), $fields) ? 1 : 0;
        }

        return redirect($request->input('back') && str_starts_with((string) $request->input('back'), url('/vendor/languages')) ? $request->input('back') : route('vendor.languages.index', ['lang' => $locale, 'type' => $type]))
            ->with('success', $saved ? trans_choice(':n service saved.|:n services saved.', $saved, ['n' => $saved]) : __('Nothing changed.'));
    }

    /** Adds a language from the catalogue to the platform (or switches an existing one back on). */
    public function addLanguage(Request $request)
    {
        $this->checkPermission('dashboard_vendor_access');
        $locale = (string) $request->input('locale');
        $catalogue = config('languages_catalogue', []);
        if (!isset($catalogue[$locale])) {
            return back()->with('danger', __('Choose a language from the list.'));
        }
        $row = \Modules\Language\Models\Language::withTrashed()->where('locale', $locale)->first();
        if ($row) {
            $row->deleted_at = null;
            $row->status = 'publish';
            $row->save();
        } else {
            $row = new \Modules\Language\Models\Language();
            $row->locale = $locale;
            $row->name = $catalogue[$locale];
            $row->status = 'publish';
            $row->save();
        }
        \Illuminate\Support\Facades\Cache::forget('locale_active_0');
        \Illuminate\Support\Facades\Cache::forget('locale_active_1');
        // The words around a service page (buttons, headings) are written in the new language once the page has been sent back.
        if (\Modules\Vendor\Services\AiTranslator::configured()) {
            $name = $catalogue[$locale];
            dispatch(function () use ($locale, $name) {
                try {
                    \Modules\Vendor\Services\UiStrings::fill($locale, $name);
                } catch (\Throwable $e) {
                    \Log::warning('UiStrings: ' . $e->getMessage());
                }
            })->afterResponse();
        }

        return redirect()->route('vendor.languages.index', ['lang' => $locale])->with('success', __(':l added. You can write it here now, or let AI translate it.', ['l' => $catalogue[$locale]]));
    }

    /** The services still to translate into a language, so the page can work through them with a progress bar. */
    public function aiQueue(Request $request)
    {
        $this->checkPermission('dashboard_vendor_access');
        [$language, $types, $err] = $this->aiContext($request);
        if ($err) {
            return response()->json(['error' => $err], 422);
        }
        $vendorId = resolve_current_vendor_id();
        $scope = (string) $request->input('scope', 'all');
        $queue = [];
        foreach ($types as $key => $t) {
            if ($scope !== 'all' && $scope !== $key) {
                continue;
            }
            $models = ServiceTranslations::query($key, $vendorId)->orderBy('id')->get();
            $rows = ServiceTranslations::rows($key, $language->locale, $models->pluck('id'));
            $marks = ServiceTranslations::marksFor($key, $language->locale, $models->pluck('id'));
            foreach ($models as $m) {
                if (ServiceTranslations::progress($m, $rows->get($m->id), $t['fields'], $marks[$m->id] ?? [])['state'] !== 'done') {
                    $queue[] = ['type' => $key, 'id' => $m->id, 'title' => (string) $m->title];
                }
            }
        }

        return response()->json(['total' => count($queue), 'items' => array_slice($queue, 0, 1500)]);
    }

    /** Translates a few services (one call from the page's progress loop). */
    public function aiRun(Request $request)
    {
        $this->checkPermission('dashboard_vendor_access');
        [$language, $types, $err] = $this->aiContext($request);
        if ($err) {
            return response()->json(['error' => $err], 422);
        }
        $vendorId = resolve_current_vendor_id();
        $items = collect((array) $request->input('items', []))->take(5);
        $default = optional(\Modules\Language\Models\Language::where('locale', setting_item('site_locale'))->first())->name ?: 'English';
        $written = 0;
        $done = 0;
        try {
            foreach ($items->groupBy('type') as $type => $group) {
                if (!isset($types[$type])) {
                    continue;
                }
                $models = ServiceTranslations::query($type, $vendorId)->whereIn('id', $group->pluck('id')->map(fn ($i) => (int) $i)->all())->get();   // only this company's
                $written += ServiceTranslations::autoTranslate($models, $type, $language->locale, $language->name, $default, $types[$type]['fields']);
                $done += $models->count();
            }
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage(), 'done' => $done, 'written' => $written], 502);
        }

        return response()->json(['done' => $done, 'written' => $written]);
    }

    /** @return array{0:?object,1:array,2:?string} */
    private function aiContext(Request $request): array
    {
        if (!ServiceTranslations::enabled()) {
            return [null, [], __('Languages are switched off for this platform.')];
        }
        if (!\Modules\Vendor\Services\AiTranslator::configured()) {
            return [null, [], __('Automatic translation is not set up on this platform yet.')];
        }
        $language = ServiceTranslations::languages()->firstWhere('locale', (string) $request->input('lang'));
        if (!$language) {
            return [null, [], __('That language is not available.')];
        }

        return [$language, ServiceTranslations::typesFor(Auth::user()), null];
    }
}
