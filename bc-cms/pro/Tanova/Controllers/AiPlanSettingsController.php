<?php

namespace Pro\Tanova\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Pro\Tanova\Models\TanovaAiSetting;

/**
 * Tanova port, phase 7 — AI planning preferences.
 */
class AiPlanSettingsController extends Controller
{
    public function edit()
    {
        return view('vendor.ai-plan.index', [
            'settings'   => TanovaAiSetting::forCurrentVendor(),
            'tones'      => TanovaAiSetting::TONES,
            'paces'      => TanovaAiSetting::PACES,
            'page_title' => __('AI Planning'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'tone'               => ['required', 'in:' . implode(',', TanovaAiSetting::TONES)],
            'pace'               => ['required', 'in:' . implode(',', TanovaAiSetting::PACES)],
            'max_days'           => ['required', 'integer', 'min:1', 'max:90'],
            'activities_per_day' => ['required', 'integer', 'min:1', 'max:10'],
            'house_rules'        => ['nullable', 'string', 'max:2000'],
            'avoid'              => ['nullable', 'string', 'max:2000'],
        ]);

        $data['include_meals']       = $request->boolean('include_meals');
        $data['include_restaurants'] = $request->boolean('include_restaurants');
        $data['prefer_own_catalog']  = $request->boolean('prefer_own_catalog');
        $data['auto_publish']        = $request->boolean('auto_publish');

        $settings = TanovaAiSetting::first();

        if ($settings) {
            $settings->update($data);
        } else {
            TanovaAiSetting::create($data);   // vendor_id stamped by BelongsToVendor
        }

        return redirect()->route('vendor.ai_plan.edit')
            ->with('success', __('AI planning preferences saved.'));
    }
}
