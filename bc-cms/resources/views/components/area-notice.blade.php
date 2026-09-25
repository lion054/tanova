{{-- Why someone was moved to another part of the portal (set by App\Http\Middleware\AreaGuard). --}}
@if($notice = session('area_notice'))
<div role="status" style="margin:14px 18px 0;padding:12px 16px;border:1px solid #0a0a0a;border-radius:10px;background:#fff;color:#0a0a0a;font-size:13px;line-height:1.5;display:flex;gap:10px;align-items:flex-start;justify-content:space-between;">
    <span><strong style="letter-spacing:.02em;">{{ __('Heads up') }}</strong>&nbsp; {{ $notice }}</span>
    <button type="button" onclick="this.parentNode.remove()" aria-label="{{ __('Dismiss') }}" style="border:0;background:transparent;font-size:18px;line-height:1;cursor:pointer;color:#666;">&times;</button>
</div>
@endif
