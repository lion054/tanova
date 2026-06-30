<?php

namespace Modules\Vendor\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\VendorCampaign;

/**
 * Phase 3 — sends a vendor email campaign off the request cycle.
 *
 * Runs in a queue worker (no auth), so recipients are resolved strictly from the
 * campaign's own vendor_id — a campaign can never reach another vendor's customers.
 */
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId)
    {
    }

    public function handle(): void
    {
        // No auth in the worker → bypass the vendor scope and load explicitly.
        $campaign = VendorCampaign::withoutVendorScope()->find($this->campaignId);

        if (! $campaign || $campaign->status === VendorCampaign::STATUS_SENT) {
            return;
        }

        $count = 0;
        foreach ($this->recipients($campaign) as $email) {
            try {
                Mail::html($campaign->body, fn ($m) => $m->to($email)->subject($campaign->subject));
                $count++;
            } catch (\Throwable $e) {
                // Skip a bad address; continue the run.
            }
        }

        $campaign->update([
            'status'     => VendorCampaign::STATUS_SENT,
            'sent_count' => $count,
            'sent_at'    => now(),
        ]);
    }

    private function recipients(VendorCampaign $campaign)
    {
        $query = Booking::where('vendor_id', $campaign->vendor_id)
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if ($campaign->audience === 'completed') {
            $query->where('status', Booking::COMPLETED);
        } elseif ($campaign->audience === 'upcoming') {
            $query->whereDate('start_date', '>=', now()->toDateString());
        }

        return $query->pluck('email')->unique()->values();
    }
}
