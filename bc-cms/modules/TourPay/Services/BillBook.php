<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Facades\DB;
use Modules\TourPay\Models\Bill;
use Modules\TourPay\Models\BillPayment;

/** The one place a payment to a supplier is recorded (portal and API). The ledger row is written by the payment's own hook. */
class BillBook
{
    /** @param array{amount:float|int|string,method:string,paid_at?:?string,reference?:?string,notes?:?string} $d */
    public function pay(Bill $bill, array $d): BillPayment
    {
        return DB::transaction(function () use ($bill, $d) {
            $bill = Bill::lockForUpdate()->findOrFail($bill->id);   // two payments at once must not both pass the balance check
            if ($bill->status === 'void') {
                throw new InvoiceRuleException('bill_void', __('This bill is void.'));
            }
            if ((float) $d['amount'] > $bill->balance() + 0.001) {
                throw new InvoiceRuleException('exceeds_balance', __('That is more than the :bal still owed on this bill.', ['bal' => number_format($bill->balance(), 2)]));
            }
            $payment = BillPayment::create($d + ['vendor_id' => $bill->vendor_id, 'bill_id' => $bill->id, 'paid_at' => $d['paid_at'] ?? now()->toDateString()]);
            $bill->recalculate();

            return $payment;
        });
    }
}
