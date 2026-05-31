@extends('layouts.user')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="portal-eyebrow">Stay OS</p>
            <h1 class="portal-h1">Channel Catalog</h1>
        </div>
        <a href="{{ route('admin.integrations.category', 'stay_os') }}" class="tp-ab-btn tp-ab-btn--ghost"
           style="text-decoration:none;display:inline-flex;align-items:center;gap:5px;padding:9px 16px;border-radius:7px;border:1.5px solid #e4e4e4;font-size:12px;font-weight:600;color:#333">
            <i class="ion ion-ios-arrow-back"></i> Stay OS
        </a>
    </div>

    @include('admin.message')

    <div style="text-align:center;padding:72px 24px;background:#fff;border:1px solid #ebebeb;border-radius:10px">
        <i class="ion ion-ios-globe" style="font-size:2.5rem;color:#e4e4e4;display:block;margin-bottom:16px"></i>
        <div style="font-size:16px;font-weight:700;color:#0a0a0a;margin-bottom:6px">
            OTA Channel Sync — Coming Soon
        </div>
        <div style="font-size:13px;color:#aaa;max-width:420px;margin:0 auto;line-height:1.7">
            Connect directly to Airbnb, Booking.com and Expedia from your Stay OS integrations page.
            Direct channel sync without a third-party channel manager is coming in a future update.
        </div>
        <a href="{{ route('admin.integrations.category', 'stay_os') }}"
           style="display:inline-flex;align-items:center;gap:6px;margin-top:24px;padding:9px 18px;
                  border-radius:7px;border:1.5px solid #e0e0e0;font-size:12px;font-weight:600;
                  color:#0a0a0a;text-decoration:none;background:#fff">
            <i class="ion ion-ios-apps"></i> Set Up Stay OS Integrations
        </a>
    </div>

</div>
@endsection
