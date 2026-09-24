<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TourPay becomes the one invoicing module.
 *
 *  - Every invoice belongs to a vendor (vendor_id, backfilled from author_id) and numbers are unique per vendor,
 *    not across the whole platform.
 *  - Payments are a ledger (bc_tourpay_payments), so an invoice can be part paid and its status follows the money.
 *  - Per-vendor settings (numbering, defaults, banking, look) replace the platform-wide ones.
 *  - The older, separate Vendor "Invoices" (bc_vendor_invoices) are copied across, once. Their tables are left in
 *    place, untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_tourpay_invoices', function (Blueprint $t) {
            if (!Schema::hasColumn('bc_tourpay_invoices', 'vendor_id'))    { $t->unsignedBigInteger('vendor_id')->nullable()->after('id')->index(); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'customer_id'))  { $t->unsignedBigInteger('customer_id')->nullable()->after('client_user_id'); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'booking_id'))   { $t->unsignedBigInteger('booking_id')->nullable()->after('customer_id')->index(); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'parent_id'))    { $t->unsignedBigInteger('parent_id')->nullable()->after('booking_id'); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'discount'))     { $t->decimal('discount', 14, 2)->default(0)->after('subtotal'); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'tax_mode'))     { $t->string('tax_mode', 12)->default('inclusive')->after('tax_rate'); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'amount_paid'))  { $t->decimal('amount_paid', 14, 2)->default(0)->after('total'); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'sent_at'))      { $t->timestamp('sent_at')->nullable(); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'viewed_at'))    { $t->timestamp('viewed_at')->nullable(); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'voided_at'))    { $t->timestamp('voided_at')->nullable(); }
        });

        // Whose is it: the author was always the vendor (or the vendor's team member).
        DB::statement('UPDATE bc_tourpay_invoices SET vendor_id = author_id WHERE vendor_id IS NULL AND author_id IS NOT NULL');
        DB::statement("UPDATE bc_tourpay_invoices SET status = 'void' WHERE status = 'cancelled'");

        // Numbers are unique per vendor, so two businesses can both have "INV-2026-001".
        $indexes = collect(DB::select("SHOW INDEX FROM bc_tourpay_invoices WHERE Key_name = 'bc_tourpay_invoices_invoice_number_unique'"));
        if ($indexes->isNotEmpty()) {
            Schema::table('bc_tourpay_invoices', fn (Blueprint $t) => $t->dropUnique('bc_tourpay_invoices_invoice_number_unique'));
        }
        if (collect(DB::select("SHOW INDEX FROM bc_tourpay_invoices WHERE Key_name = 'tp_invoices_vendor_number_unique'"))->isEmpty()) {
            Schema::table('bc_tourpay_invoices', fn (Blueprint $t) => $t->unique(['vendor_id', 'invoice_number'], 'tp_invoices_vendor_number_unique'));
        }

        Schema::table('bc_tourpay_invoice_items', function (Blueprint $t) {
            if (!Schema::hasColumn('bc_tourpay_invoice_items', 'vendor_id')) { $t->unsignedBigInteger('vendor_id')->nullable()->after('id')->index(); }
        });
        DB::statement('UPDATE bc_tourpay_invoice_items i JOIN bc_tourpay_invoices v ON v.id = i.invoice_id SET i.vendor_id = v.vendor_id WHERE i.vendor_id IS NULL');

        if (!Schema::hasTable('bc_tourpay_payments')) {
            Schema::create('bc_tourpay_payments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('vendor_id')->index();
                $t->unsignedBigInteger('invoice_id')->index();
                $t->decimal('amount', 14, 2);
                $t->string('method', 24)->default('other');        // cash|bank|card|mobile_money|paypal|stripe|paystack|other
                $t->string('reference', 191)->nullable();
                $t->date('paid_at');
                $t->text('notes')->nullable();
                $t->string('source', 16)->default('manual');        // manual|gateway|booking|api|migrated
                $t->string('proof_path', 255)->nullable();          // a bank slip a guest uploaded
                $t->unsignedBigInteger('recorded_by')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('bc_tourpay_settings')) {
            Schema::create('bc_tourpay_settings', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('vendor_id')->unique();
                $t->string('invoice_prefix', 12)->nullable();
                $t->string('quote_prefix', 12)->nullable();
                $t->string('default_currency', 8)->nullable();
                $t->decimal('default_tax_rate', 6, 2)->nullable();
                $t->string('default_tax_mode', 12)->nullable();
                $t->unsignedSmallInteger('default_due_days')->nullable();
                $t->unsignedSmallInteger('default_valid_days')->nullable();
                $t->text('default_terms')->nullable();
                $t->text('default_notes')->nullable();
                $t->json('banking_details')->nullable();
                $t->unsignedTinyInteger('template')->nullable();
                $t->string('accent_color', 9)->nullable();
                $t->string('footer_note', 500)->nullable();
                $t->timestamps();
            });
        }

        // Invoices that were marked paid before payments were tracked get one payment, so the ledger agrees with the status.
        $paid = DB::table('bc_tourpay_invoices')->where('status', 'paid')->where('amount_paid', 0)->where('total', '>', 0)->get(['id', 'vendor_id', 'total', 'updated_at']);
        foreach ($paid as $i) {
            DB::table('bc_tourpay_payments')->insert([
                'vendor_id' => $i->vendor_id ?: 0, 'invoice_id' => $i->id, 'amount' => $i->total, 'method' => 'other', 'paid_at' => substr((string) $i->updated_at, 0, 10) ?: now()->toDateString(),
                'notes' => 'Marked paid before payments were recorded', 'source' => 'migrated', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('bc_tourpay_invoices')->where('id', $i->id)->update(['amount_paid' => $i->total]);
        }

        $this->copyVendorInvoices();
    }

    /** The separate Vendor "Invoices" module: copy each invoice, its lines and payments into TourPay, once. */
    private function copyVendorInvoices(): void
    {
        if (!Schema::hasTable('bc_vendor_invoices')) {
            return;
        }
        foreach (DB::table('bc_vendor_invoices')->orderBy('id')->get() as $v) {
            if (DB::table('bc_tourpay_invoices')->where('vendor_id', $v->vendor_id)->where('invoice_number', $v->number)->exists()) {
                continue;
            }
            $id = DB::table('bc_tourpay_invoices')->insertGetId([
                'vendor_id' => $v->vendor_id, 'author_id' => $v->vendor_id, 'invoice_number' => $v->number, 'type' => 'invoice', 'status' => $v->status,
                'customer_id' => $v->customer_id, 'booking_id' => $v->booking_id,
                'client_name' => $v->bill_to_name, 'client_email' => $v->bill_to_email, 'client_address' => $v->bill_to_address,
                'currency' => $v->currency, 'subtotal' => $v->subtotal, 'discount' => $v->discount, 'tax_rate' => $v->tax_rate, 'tax_mode' => 'exclusive',
                'tax_amount' => $v->tax_amount, 'total' => $v->total, 'amount_paid' => $v->amount_paid,
                'issue_date' => $v->issue_date, 'due_date' => $v->due_date, 'notes' => $v->notes, 'payment_terms' => $v->terms,
                'template' => 1, 'pay_token' => (string) \Illuminate\Support\Str::uuid(), 'created_at' => $v->created_at, 'updated_at' => $v->updated_at,
            ]);
            foreach (DB::table('bc_vendor_invoice_lines')->where('invoice_id', $v->id)->orderBy('sort_order')->get() as $l) {
                DB::table('bc_tourpay_invoice_items')->insert([
                    'vendor_id' => $v->vendor_id, 'invoice_id' => $id, 'sort_order' => $l->sort_order, 'name' => $l->description, 'description' => '',
                    'quantity' => $l->quantity, 'unit_price' => $l->unit_price, 'total' => $l->line_total, 'created_at' => $l->created_at, 'updated_at' => $l->updated_at,
                ]);
            }
            foreach (DB::table('bc_vendor_invoice_payments')->where('invoice_id', $v->id)->get() as $p) {
                DB::table('bc_tourpay_payments')->insert([
                    'vendor_id' => $v->vendor_id, 'invoice_id' => $id, 'amount' => $p->amount, 'method' => $p->method, 'reference' => $p->reference,
                    'paid_at' => substr((string) $p->paid_at, 0, 10), 'notes' => $p->notes, 'source' => 'migrated', 'created_at' => $p->created_at, 'updated_at' => $p->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Deliberately not reversible: it would drop money records.
    }
};
