<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            'site_title'        => 'Tsoka',
            'email_from_name'   => 'Tsoka',
            'admin_email'       => 'hello@tsokatravel.com',
            'email_from_address'=> 'hello@tsokatravel.com',
            'footer_text_right' => 'Tsoka',
        ];

        foreach ($updates as $name => $val) {
            DB::table('core_settings')->where('name', $name)->update(['val' => $val]);
        }

        // Footer copyright text
        DB::table('core_settings')
            ->where('name', 'footer_text_left')
            ->update(['val' => 'Copyright © ' . date('Y') . ' by Tsoka']);

        // Email header/footer branding
        DB::table('core_settings')
            ->where('name', 'email_header')
            ->update(['val' => '<h1 class="site-title" style="text-align:center">Tsoka</h1>']);

        DB::table('core_settings')
            ->where('name', 'email_footer')
            ->update(['val' => '<p style="text-align:center">&copy; ' . date('Y') . ' Tsoka. All rights reserved</p>']);

        // Welcome email templates — replace "Go Trip" with "Tsoka"
        foreach (['user_content_email_registered', 'vendor_content_email_registered',
                  'admin_content_email_user_registered', 'admin_content_email_vendor_registered',
                  'user_content_email_forget_password', 'booking_enquiry_mail_to_vendor_content',
                  'booking_enquiry_mail_to_admin_content', 'invoice_company_info'] as $key) {
            $row = DB::table('core_settings')->where('name', $key)->first();
            if ($row) {
                $updated = str_replace(['Go Trip', 'GoTrip'], 'Tsoka', $row->val);
                DB::table('core_settings')->where('name', $key)->update(['val' => $updated]);
            }
        }

        // Topbar left text — replace gotrip email
        $topbar = DB::table('core_settings')->where('name', 'topbar_left_text')->first();
        if ($topbar) {
            $updated = str_replace(['hi@gotrip.com', 'Go Trip', 'GoTrip'], ['hello@tsokatravel.com', 'Tsoka', 'Tsoka'], $topbar->val);
            DB::table('core_settings')->where('name', 'topbar_left_text')->update(['val' => $updated]);
        }

        // Contact page description
        $contact = DB::table('core_settings')->where('name', 'page_contact_desc')->first();
        if ($contact) {
            $updated = str_replace(['Go Trip', 'GoTrip', 'gotrip.org'], ['Tsoka', 'Tsoka', 'tsokatravel.com'], $contact->val);
            DB::table('core_settings')->where('name', 'page_contact_desc')->update(['val' => $updated]);
        }

        // Invoice company info
        $invoice = DB::table('core_settings')->where('name', 'invoice_company_info')->first();
        if ($invoice) {
            $updated = str_replace(['Go Trip', 'GoTrip', 'gotrip.org', 'www.gotrip.org'], ['Tsoka', 'Tsoka', 'tsokatravel.com', 'tsokatravel.com'], $invoice->val);
            DB::table('core_settings')->where('name', 'invoice_company_info')->update(['val' => $updated]);
        }
    }

    public function down(): void
    {
        // Intentionally left empty — rebrand is forward-only
    }
};
