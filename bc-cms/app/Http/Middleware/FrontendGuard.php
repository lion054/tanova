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
        'tourpay/pay/*',
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
