<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Vendor\Models\VendorCustomer;
use Modules\Vendor\Models\VendorHoliday;
use Modules\Vendor\Models\VendorHolidaySend;

/**
 * Sends a holiday greeting to a vendor's customers.
 *
 * Idempotency is enforced by the (holiday_id, customer_id, year) unique index on
 * the send log rather than by checking-then-writing, so two concurrent runs cannot
 * double-send. A duplicate insert is caught and counted as "skipped".
 *
 * Only the email channel actually delivers today. WhatsApp and SMS are recorded as
 * skipped rather than silently reported as sent — wiring them means routing through
 * the existing Sms module, which is deliberately out of scope for this port.
 */
class HolidayGreetingService
{
    /**
     * @return array{sent:int, skipped:int, failed:int}
     */
    public function sendFor(VendorHoliday $holiday, int $year): array
    {
        $sent = $skipped = $failed = 0;

        $recipients = VendorCustomer::whereNotNull('email')->get();

        foreach ($recipients as $customer) {
            // Claim the slot first — the unique index is the lock.
            try {
                $log = VendorHolidaySend::create([
                    'vendor_id'   => $holiday->vendor_id,
                    'holiday_id'  => $holiday->id,
                    'customer_id' => $customer->id,
                    'year'        => $year,
                    'recipient'   => $customer->email,
                    'channel'     => $holiday->channel,
                    'status'      => 'sent',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // 23000 = integrity constraint violation — already greeted this year.
                if ($e->getCode() === '23000') {
                    $skipped++;
                    continue;
                }
                throw $e;
            }

            if ($holiday->channel !== 'email') {
                $log->update(['status' => 'skipped', 'error' => __('Channel not yet supported.')]);
                $skipped++;
                continue;
            }

            try {
                $body = $this->renderBody($holiday, $customer);

                Mail::html($body, function ($message) use ($customer, $holiday) {
                    $message->to($customer->email)->subject($holiday->subjectLine());
                });

                $log->update(['sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Holiday greeting failed', [
                    'holiday_id'  => $holiday->id,
                    'customer_id' => $customer->id,
                    'error'       => $e->getMessage(),
                ]);
                $log->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
                $failed++;
            }
        }

        return compact('sent', 'skipped', 'failed');
    }

    private function renderBody(VendorHoliday $holiday, VendorCustomer $customer): string
    {
        $template = $holiday->custom_body ?: __('Wishing you a wonderful :name from all of us.', ['name' => $holiday->name]);

        return strtr($template, [
            '{name}'       => e($customer->full_name),
            '{first_name}' => e($customer->first_name ?: ''),
            '{holiday}'    => e($holiday->name),
        ]);
    }
}
