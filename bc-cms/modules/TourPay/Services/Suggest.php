<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Facades\DB;
use Modules\TourPay\Models\Invoice;
use Modules\Vendor\Models\VendorCustomer;
use Modules\Vendor\Models\VendorUpsell;

/**
 * Type-ahead for the invoice and quotation form: the vendor's own customers and past clients, and the things they sell
 * (tours, stays, cars, spaces, events, add-ons) plus lines they have billed before. Everything is limited to the current business.
 */
class Suggest
{
    private const LIMIT = 8;

    /** Service tables the vendor can bill from: table => [group label, price column]. Hotel prices are per night, and so on; the vendor edits the line. */
    private const CATALOGUE = ['bc_tours' => 'Tours', 'bc_hotels' => 'Stays', 'bc_cars' => 'Cars', 'bc_spaces' => 'Spaces', 'bc_events' => 'Events'];

    public function customers(string $q): array
    {
        $like = $this->like($q);
        $out = [];

        $customers = VendorCustomer::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like)))
            ->orderByDesc('last_booking_at')->orderBy('first_name')->limit(self::LIMIT)->get();
        foreach ($customers as $c) {
            $this->addClient($out, ['customer_id' => $c->id, 'name' => trim($c->first_name . ' ' . $c->last_name), 'email' => $c->email, 'phone' => $c->phone, 'country' => $c->nationality, 'address' => null, 'source' => __('Customer')]);
        }

        // Clients who were invoiced before but are not in the customer list yet.
        if (count($out) < self::LIMIT) {
            $past = Invoice::query()->whereNotNull('client_name')
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('client_name', 'like', $like)->orWhere('client_email', 'like', $like)->orWhere('client_phone', 'like', $like)))
                ->orderByDesc('id')->limit(40)->get(['customer_id', 'client_name', 'client_email', 'client_phone', 'client_country', 'client_address']);
            foreach ($past as $i) {
                $this->addClient($out, ['customer_id' => $i->customer_id, 'name' => $i->client_name, 'email' => $i->client_email, 'phone' => $i->client_phone, 'country' => $i->client_country, 'address' => $i->client_address, 'source' => __('Past invoice')]);
                if (count($out) >= self::LIMIT) { break; }
            }
        }

        return array_values($out);
    }

    public function services(string $q): array
    {
        $vendorId = (int) resolve_current_vendor_id();
        $like = $this->like($q);
        $out = [];

        foreach (self::CATALOGUE as $table => $group) {
            DB::table($table)->where('author_id', $vendorId)->where('status', 'publish')->whereNull('deleted_at')
                ->when($q !== '', fn ($w) => $w->where('title', 'like', $like))->orderBy('title')->limit(self::LIMIT)->get(['title', 'price', 'sale_price'])
                ->each(function ($t) use (&$out, $group) {
                    $this->addService($out, ['name' => $t->title, 'price' => (float) ($t->sale_price ?: $t->price), 'description' => '', 'group' => __($group)]);
                });
        }
        VendorUpsell::query()->where('status', 'publish')->when($q !== '', fn ($w) => $w->where('name', 'like', $like))->orderBy('name')->limit(self::LIMIT)->get(['name', 'price', 'short_description'])
            ->each(function ($u) use (&$out) {   // by reference: an arrow function would work on a copy
                $this->addService($out, ['name' => $u->name, 'price' => (float) $u->price, 'description' => (string) $u->short_description, 'group' => __('Add-ons')]);
            });

        // Lines billed before (custom lines, negotiated wording); the newest price wins. Catalogue names above are not repeated.
        DB::table('bc_tourpay_invoice_items as it')->join('bc_tourpay_invoices as inv', 'inv.id', '=', 'it.invoice_id')
            ->where('inv.vendor_id', $vendorId)->whereNull('inv.deleted_at')->when($q !== '', fn ($w) => $w->where('it.name', 'like', $like))
            ->orderByDesc('it.id')->limit(60)->get(['it.name', 'it.description', 'it.unit_price'])
            ->each(function ($it) use (&$out) {
                $this->addService($out, ['name' => $it->name, 'price' => (float) $it->unit_price, 'description' => (string) $it->description, 'group' => __('Billed before')]);
            });

        // Names that start with what was typed come first.
        $needle = mb_strtolower($q);
        uasort($out, fn ($a, $b) => [$needle !== '' && str_starts_with(mb_strtolower($a['name']), $needle) ? 0 : 1] <=> [$needle !== '' && str_starts_with(mb_strtolower($b['name']), $needle) ? 0 : 1]);

        return array_slice(array_values($out), 0, self::LIMIT * 2);
    }

    private function addClient(array &$out, array $c): void
    {
        $key = $c['email'] ? mb_strtolower($c['email']) : mb_strtolower($c['name']);
        if ($c['name'] === '' || isset($out[$key])) { return; }
        $out[$key] = $c;
    }

    private function addService(array &$out, array $s): void
    {
        $key = mb_strtolower(trim($s['name']));
        if ($key === '' || isset($out[$key])) { return; }
        $out[$key] = $s;
    }

    private function like(string $q): string
    {
        return '%' . addcslashes($q, '%_\\') . '%';
    }
}
