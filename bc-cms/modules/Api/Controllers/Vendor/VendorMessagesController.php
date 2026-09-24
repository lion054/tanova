<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Vendor\Jobs\SendCampaignJob;
use Modules\Vendor\Models\ScheduledMessage;
use Modules\Vendor\Models\ScheduledMessageLog;
use Modules\Vendor\Models\VendorCampaign;
use Modules\Vendor\Services\CampaignAudience;

/** Scheduled messages (sent around a trip by themselves) and campaigns (one message to a group). */
class VendorMessagesController extends VendorApiController
{
    // ── Scheduled messages ────────────────────────────────────────────────────

    /** What can be chosen when writing a message: triggers, channels and the {placeholders} you may use. */
    public function options(): JsonResponse
    {
        return $this->success([
            'triggers'     => collect(ScheduledMessage::TRIGGERS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all(),
            'channels'     => ScheduledMessage::CHANNELS,
            'placeholders' => collect(ScheduledMessage::PLACEHOLDERS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $q = ScheduledMessage::withCount('logs');
        ListQuery::search($q, $request->query('q'), ['name', 'subject', 'body']);
        if (array_key_exists((string) $request->query('trigger'), ScheduledMessage::TRIGGERS)) {
            $q->where('trigger', $request->query('trigger'));
        }
        if (in_array($request->query('channel'), ScheduledMessage::CHANNELS, true)) {
            $q->where('channel', $request->query('channel'));
        }
        if (in_array($request->query('state'), ['active', 'paused'], true)) {
            $q->where('active', $request->query('state') === 'active');
        }
        ListQuery::sort($q, $request->query('sort'), ['newest' => ['id', 'desc'], 'name' => ['name', 'asc']], 'newest');

        return $this->page($q, $request, fn ($m) => $this->shape($m));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success($this->shape(ScheduledMessage::withCount('logs')->findOrFail($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $m = ScheduledMessage::create($this->fields($request, true));

        return $this->created($this->shape($m->loadCount('logs')));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $m = ScheduledMessage::findOrFail($id);
        $m->update($this->fields($request, false));

        return $this->success($this->shape($m->loadCount('logs')));
    }

    /** Switch a message on or off without changing it. */
    public function setActive(Request $request, int $id): JsonResponse
    {
        $d = $request->validate(['active' => ['required', 'boolean']]);
        $m = ScheduledMessage::findOrFail($id);
        $m->update(['active' => (bool) $d['active']]);

        return $this->success($this->shape($m->loadCount('logs')));
    }

    public function destroy(int $id): JsonResponse
    {
        ScheduledMessage::findOrFail($id)->delete();

        return $this->noContent();
    }

    /** Add the recommended messages you do not have yet, all paused so nothing goes out until you switch them on. */
    public function starter(): JsonResponse
    {
        $added = [];
        foreach (ScheduledMessage::STARTER as $m) {
            if (ScheduledMessage::where('trigger', $m['trigger'])->where('name', $m['name'])->exists()) {
                continue;
            }
            $added[] = $this->shape(ScheduledMessage::create($m + ['channel' => 'email', 'active' => false])->loadCount('logs'));
        }

        return $this->success($added, 201, ['added' => count($added)]);
    }

    /** What was sent (or tried), newest first. */
    public function log(Request $request): JsonResponse
    {
        $q = ScheduledMessageLog::orderByRaw('COALESCE(sent_at, updated_at) DESC');
        if ($request->filled('message_id') && ctype_digit((string) $request->query('message_id'))) {
            $q->where('scheduled_message_id', (int) $request->query('message_id'));
        }
        if (in_array($request->query('status'), ['sent', 'failed', 'skipped'], true)) {
            $q->where('status', $request->query('status'));
        }
        $names = ScheduledMessage::pluck('name', 'id');

        return $this->page($q, $request, fn ($l) => [
            'id' => $l->id, 'message_id' => $l->scheduled_message_id, 'message' => $names[$l->scheduled_message_id] ?? null, 'booking_id' => $l->booking_id,
            'channel' => $l->channel, 'recipient' => $l->recipient, 'status' => $l->status, 'error' => $l->error, 'sent_at' => optional($l->sent_at)->toIso8601String(),
        ]);
    }

    private function fields(Request $request, bool $create): array
    {
        $req = $create ? 'required' : 'sometimes';
        $d = $request->validate([
            'name'        => [$req, 'string', 'max:191'],
            'trigger'     => [$req, Rule::in(array_keys(ScheduledMessage::TRIGGERS))],
            'offset_days' => ['nullable', 'integer', 'between:-365,365'],
            'channel'     => [$req, Rule::in(ScheduledMessage::CHANNELS)],
            'subject'     => ['nullable', 'string', 'max:191'],
            'body'        => [$req, 'string', 'max:10000'],
            'active'      => ['sometimes', 'boolean'],
        ]);
        if ($create) {
            $d['offset_days'] = (int) ($d['offset_days'] ?? 0);
            $d['active'] = $d['active'] ?? true;
        }

        return $d;
    }

    private function shape(ScheduledMessage $m): array
    {
        return [
            'id' => $m->id, 'name' => $m->name, 'trigger' => $m->trigger, 'offset_days' => (int) $m->offset_days, 'channel' => $m->channel,
            'subject' => $m->subject, 'body' => $m->body, 'active' => (bool) $m->active, 'sent_count' => (int) ($m->logs_count ?? 0), 'created_at' => optional($m->created_at)->toIso8601String(),
        ];
    }

    // ── Campaigns ─────────────────────────────────────────────────────────────

    /** The groups a campaign can go to, and how many people are in each right now. */
    public function audiences(CampaignAudience $a): JsonResponse
    {
        return $this->success(collect(CampaignAudience::AUDIENCES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'recipients' => $a->emails($this->vendorId(), $key)->count()])->values()->all());
    }

    public function campaigns(Request $request): JsonResponse
    {
        $q = VendorCampaign::query();
        ListQuery::search($q, $request->query('q'), ['subject']);
        if (in_array($request->query('status'), ['draft', 'sending', 'sent'], true)) {
            $q->where('status', $request->query('status'));
        }
        ListQuery::sort($q, $request->query('sort'), ['newest' => ['id', 'desc'], 'oldest' => ['id', 'asc'], 'reach' => ['sent_count', 'desc']], 'newest');

        return $this->page($q, $request, fn ($c) => $this->campaignShape($c));
    }

    public function showCampaign(int $id): JsonResponse
    {
        return $this->success($this->campaignShape(VendorCampaign::findOrFail($id)));
    }

    private function campaignShape(VendorCampaign $c): array
    {
        return ['id' => $c->id, 'subject' => $c->subject, 'body' => $c->body, 'audience' => $c->audience, 'status' => $c->status, 'sent_count' => (int) $c->sent_count, 'sent_at' => optional($c->sent_at)->toIso8601String(), 'created_at' => optional($c->created_at)->toIso8601String()];
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $d = $request->validate(['subject' => ['required', 'string', 'max:191'], 'body' => ['required', 'string', 'max:100000'], 'audience' => ['required', Rule::in(array_keys(CampaignAudience::AUDIENCES))]]);

        return $this->created($this->campaignShape(VendorCampaign::create($d + ['status' => VendorCampaign::STATUS_DRAFT])));
    }

    public function updateCampaign(Request $request, int $id): JsonResponse
    {
        $c = VendorCampaign::findOrFail($id);
        if ($c->status !== VendorCampaign::STATUS_DRAFT) {
            return $this->error('not_a_draft', 'Only a draft can be changed.', 409);
        }
        $c->update($request->validate(['subject' => ['sometimes', 'string', 'max:191'], 'body' => ['sometimes', 'string', 'max:100000'], 'audience' => ['sometimes', Rule::in(array_keys(CampaignAudience::AUDIENCES))]]));

        return $this->success($this->campaignShape($c->fresh()));
    }

    public function destroyCampaign(int $id): JsonResponse
    {
        VendorCampaign::findOrFail($id)->delete();

        return $this->noContent();
    }

    /**
     * Sends the campaign to its audience after this response, and marks it sent. A test key sends nothing and leaves
     * the campaign a draft: it only tells you how many people it would reach.
     */
    public function sendCampaign(int $id, CampaignAudience $a): JsonResponse
    {
        $c = VendorCampaign::findOrFail($id);
        if ($c->status === VendorCampaign::STATUS_SENT) {
            return $this->error('already_sent', 'This campaign was already sent.', 409);
        }
        $n = $a->emails($this->vendorId(), (string) $c->audience)->count();
        if ($this->isTest()) {
            return response()->json(['data' => $this->campaignShape($c) + ['recipients' => $n, 'simulated' => true]], 200);
        }
        $c->update(['status' => VendorCampaign::STATUS_SENDING]);
        SendCampaignJob::dispatchAfterResponse($c->id);

        return response()->json(['data' => $this->campaignShape($c->fresh()) + ['recipients' => $n]], 202);
    }
}
