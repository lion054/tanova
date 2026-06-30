<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vendor\Models\ScheduledMessage;

/**
 * Phase 3 — Vendor self-service CRUD for lifecycle scheduled messages.
 * ScheduledMessage uses BelongsToVendor (auto-scoped + auto-stamped).
 */
class ScheduledMessageController extends Controller
{
    public function index(Request $request)
    {
        return view('vendor.scheduled-messages.index', [
            'rows'       => ScheduledMessage::withCount('logs')->orderByDesc('id')->get(),
            'triggers'   => ScheduledMessage::TRIGGERS,
            'channels'   => ScheduledMessage::CHANNELS,
            'page_title' => __('Scheduled Messages'),
        ]);
    }

    public function store(Request $request)
    {
        ScheduledMessage::create($this->validateData($request));

        return back()->with('success', __('Scheduled message created.'));
    }

    public function update(Request $request, ScheduledMessage $scheduledMessage)
    {
        $scheduledMessage->update($this->validateData($request));

        return back()->with('success', __('Scheduled message updated.'));
    }

    public function toggle(ScheduledMessage $scheduledMessage)
    {
        $scheduledMessage->update(['active' => ! $scheduledMessage->active]);

        return back()->with('success', __('Scheduled message updated.'));
    }

    public function destroy(ScheduledMessage $scheduledMessage)
    {
        $scheduledMessage->delete();

        return back()->with('success', __('Scheduled message deleted.'));
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'trigger'     => ['required', 'in:' . implode(',', array_keys(ScheduledMessage::TRIGGERS))],
            'offset_days' => ['nullable', 'integer', 'between:-365,365'],
            'channel'     => ['required', 'in:' . implode(',', ScheduledMessage::CHANNELS)],
            'subject'     => ['nullable', 'string', 'max:191'],
            'body'        => ['required', 'string'],
        ]);

        $data['offset_days'] = (int) ($data['offset_days'] ?? 0);
        $data['active']      = $request->boolean('active', true);

        return $data;
    }
}
