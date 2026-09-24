<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FrontendGuard
{
    // Routes allowed for guests (unauthenticated)
    protected array $guestAllowed = [
        'login',
        'register',
        'forgot-password',
        'reset-password',
        'reset-password/*',
        'password/reset',
        'password/email',
        'email/verify',
        'email/verify/*',
        'confirm-password',
        'two-factor-challenge',
        'two-factor-challenge/*',
        'social-login/*',
        'social-callback/*',
        'install',
        'install/*',
        'up',
        'health',
        'tourpay/pay/*',
        'tourpay/notify/*',

        // Guest booking payment journey.
        //
        // Vendor-API bookings (POST /api/v/bookings) belong to an end customer
        // who has no account here, and the endpoint hands back a checkout_url.
        // Without these entries that URL bounced to /login and the booking
        // could never be paid. Gated by the booking_guest_checkout setting —
        // BookingController::validateCheckout() still refuses guests when it
        // is off. Deliberately path-specific: `booking/*` wholesale would also
        // expose storeNoteBooking, modal and the enquiry endpoints.
        'booking/*/checkout',
        'booking/*/check-status',
        'booking/doCheckout',
        'booking/confirm/*',
        'booking/cancel/*',
        // Where PayPal sends someone who paid for a booking made in a vendor's
        // app (see BookingController::appReturn); only shows the booking's status.
        'booking/return/*',
        // The link a customer opens to tell the vendor who is travelling.
        'guest-form/*',
        'gateway/*',
    ];

    // Routes allowed for authenticated users (portal + service browsing)
    protected array $authAllowed = [
        // Portal management
        'admin',
        'admin/*',
        'user/*',
        'vendor/*',
        'logout',
        'api/*',
        // Auth flows
        'email/verify',
        'email/verify/*',
        'confirm-password',
        'two-factor-challenge',
        // Service listing & detail pages
        'tour',
        'tour/*',
        'hotel',
        'hotel/*',
        'car',
        'car/*',
        'boat',
        'boat/*',
        'flight',
        'flight/*',
        'space',
        'space/*',
        'event',
        'event/*',
        'visa',
        'visa/*',
        // Booking & payment flow
        'booking',
        'booking/*',
        'gateway/*',
        // Public pages
        '/',
        'home',
        'contact',
        'profile',
        'profile/*',
        'news',
        'news/*',
        'support',
        'support/*',
        'tourpay/pay/*',
        'tourpay/notify/*',
        // Assets & utilities
        'custom-css',
        'check-cookie',
        'notify/*',
        'sitemap.xml',
        'sitemap-*.xml',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            foreach ($this->guestAllowed as $pattern) {
                if ($request->is($pattern)) {
                    return $next($request);
                }
            }
            return redirect('/login');
        }

        // Authenticated — only allow portal routes
        foreach ($this->authAllowed as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        return redirect('/admin');
    }
}
