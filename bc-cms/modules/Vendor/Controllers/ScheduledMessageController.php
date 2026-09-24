<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Modules\Vendor\Models\ScheduledMessage;
use Modules\Vendor\Models\ScheduledMessageLog;

/**
 * Phase 3 — Vendor self-service CRUD for lifecycle scheduled messages.
 * ScheduledMessage uses BelongsToVendor (auto-scoped + auto-stamped).
 */
class ScheduledMessageController extends Controller
{
    public function index(Request $request)
    {
        $base = ScheduledMessage::withCount('logs');
        $everything = ScheduledMessage::count();
        ListQuery::search($base, $request->query('s'), ['name', 'subject', 'body']);
        $trig = []; foreach (ScheduledMessage::TRIGGERS as $k => $l) { $trig[$k] = __($l); }
        if (isset($trig[(string) $request->query('trigger')])) { $base->where('trigger', $request->query('trigger')); }
        $chan = []; foreach (ScheduledMessage::CHANNELS as $c) { $chan[$c] = ucfirst($c); }
        if (isset($chan[(string) $request->query('channel')])) { $base->where('channel', $request->query('channel')); }
        $state = (string) $request->query('state', '');
        if ($state === 'on') { $base->where('active', true); } elseif ($state === 'off') { $base->where('active', false); }
        ListQuery::sort($base, $request->query('sort'), ['newest' => ['id', 'desc'], 'name' => ['name', 'asc'], 'sent' => ['id', 'desc']], 'newest');
        $fb = FilterBar::make($request)->search('s', __('Search messages'))->select('trigger', __('When'), $trig, __('Any trigger'))
            ->select('channel', __('Channel'), $chan, __('Any channel'))->select('state', __('State'), ['on' => __('Active'), 'off' => __('Paused')], __('Any state'))
            ->sort(['newest' => __('Newest first'), 'name' => __('Name A to Z')], 'newest')->noun(__('messages'))->total((clone $base)->reorder()->count(), $everything)->toArray();
        $rows = $base->get();

        return view('vendor.scheduled-messages.index', [
            'rows'       => $rows,
            'fb'         => $fb,
            'triggers'   => ScheduledMessage::TRIGGERS,
            'channels'   => ScheduledMessage::CHANNELS,
            'placeholders' => ScheduledMessage::PLACEHOLDERS,
            'log'        => ScheduledMessageLog::orderByRaw('COALESCE(sent_at, updated_at) DESC')->limit(15)->get(),
            'names'      => ScheduledMessage::pluck('name', 'id'),
            'starterLeft' => collect(ScheduledMessage::STARTER)->reject(fn ($m) => ScheduledMessage::where('trigger', $m['trigger'])->where('name', $m['name'])->exists())->count(),
            'page_title' => __('Scheduled Messages'),
        ]);
    }

    public function store(Request $request)
    {
        ScheduledMessage::create($this->validateData($request));

        return back()->with('success', __('Scheduled message created.'));
    }

    /** Adds the recommended messages that are not there yet, all paused. */
    public function starter()
    {
        $added = 0;
        foreach (ScheduledMessage::STARTER as $m) {
            if (ScheduledMessage::where('trigger', $m['trigger'])->where('name', $m['name'])->exists()) {
                continue;
            }
            ScheduledMessage::create($m + ['channel' => 'email', 'active' => false]);
            $added++;
        }

        return back()->with('success', $added
            ? trans_choice(':n recommended message added, paused. Read it, then press Activate.|:n recommended messages added, paused. Read them, then press Activate.', $added, ['n' => $added])
            : __('You already have all the recommended messages.'));
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
