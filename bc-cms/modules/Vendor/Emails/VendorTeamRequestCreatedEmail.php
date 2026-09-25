<?php

namespace Modules\Vendor\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * @property $vendor_team
 */
class VendorTeamRequestCreatedEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $member;
    protected $vendor;

    public function __construct($member, $vendor,$vendor_team)
    {
        $this->member = $member;
        $this->vendor = $vendor;
        $this->vendor_team = $vendor_team;
    }

    public function build()
    {
        $subject = __('Request join team');
        return $this->subject($subject)->view('Vendor::emails.vendor-team-request-create', ['content' => $this->body()]);
    }

    public function body()
    {
        $company = e($this->vendor->business_name ?: $this->vendor->display_name);
        $set = '';
        // A person who has never signed in needs a password: a one-time link to choose it.
        if (empty($this->member->last_login_at)) {
            $token = \Illuminate\Support\Facades\Password::broker()->createToken($this->member);
            $url = route('password.reset', ['token' => $token, 'email' => $this->member->email]);
            $set = '<p>First, choose your password: <a href="' . $url . '">set my password</a>.</p>';
        }

        return '
            <h1>Hello ' . e($this->member->display_name) . '</h1>
            <p><strong>' . $company . '</strong> has added you to its team on ' . e(setting_item('site_title')) . '. You will work inside ' . $company . ' and see only its bookings, customers and records, and only the parts its owner gave you.</p>
            ' . $set . '
            <p style="text-align: center">' . $this->button() . '</p>
            <p>Regards,<br>' . e(setting_item('site_title')) . '</p>';
    }

    public function button()
    {
        $link = URL::temporarySignedRoute('team-accept',now()->addMinutes(60),['vendor_team'=>$this->vendor_team->id]);
        $button = '<a style="border-radius: 3px;
                color: #fff;
                display: inline-block;
                text-decoration: none;
                background-color: #3490dc;
                border-top: 10px solid #3490dc;
                border-right: 18px solid #3490dc;
                border-bottom: 10px solid #3490dc;
                border-left: 18px solid #3490dc;" href="' . $link . '">Accept</a>';
        return $button;
    }

}

