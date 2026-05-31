@extends('layouts.auth')
@section('title', __('Create Account') . ' — ' . config('app.name', 'Tsoka Travel'))

@section('panel-style')
<style>
    #auth-left-panel {
        background:
            linear-gradient(160deg,
                rgba(0,0,0,.88) 0%,
                rgba(0,0,0,.68) 50%,
                rgba(0,0,0,.88) 100%),
            url('https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=900&q=85')
            center center / cover no-repeat;
    }
    #auth-left-panel::before { display: none; }
</style>
@endsection

@section('panel-deco')
    {{-- Stats row --}}
    <div style="display:flex;gap:16px;margin-bottom:28px;">
        <div style="border:1px solid rgba(255,255,255,.1);border-radius:4px;padding:10px 16px;">
            <div style="font-family:'DM Serif Display',Georgia,serif;font-size:22px;color:#fff;line-height:1;">174</div>
            <div style="font-size:10px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-top:3px;">Properties</div>
        </div>
        <div style="border:1px solid rgba(255,255,255,.1);border-radius:4px;padding:10px 16px;">
            <div style="font-family:'DM Serif Display',Georgia,serif;font-size:22px;color:#fff;line-height:1;">9</div>
            <div style="font-size:10px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-top:3px;">Countries</div>
        </div>
        <div style="border:1px solid rgba(255,255,255,.1);border-radius:4px;padding:10px 16px;">
            <div style="font-family:'DM Serif Display',Georgia,serif;font-size:22px;color:#fff;line-height:1;">2017</div>
            <div style="font-size:10px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-top:3px;">Founded</div>
        </div>
    </div>
@endsection

@section('brand-heading')
    <h1>Join<br><em>Tsoka Travel.</em></h1>
@endsection
@section('brand-sub')
    <p>Create your account and start managing travel across EMEA.</p>
@endsection

@section('content')
<style>
    /* Step progress */
    .step-progress { display:flex; align-items:center; gap:0; margin-bottom:32px; }
    .step-dot {
        display:flex; align-items:center; justify-content:center;
        width:26px; height:26px; border-radius:50%;
        font-size:11px; font-weight:600;
        background:var(--g100); color:var(--g400);
        flex-shrink:0; transition:all .2s;
    }
    .step-dot.active { background:var(--black); color:#fff; }
    .step-dot.done   { background:var(--g600); color:#fff; }
    .step-line { flex:1; height:1px; background:var(--g200); margin:0 6px; }
    .step-line.done { background:var(--g600); }

    /* Steps */
    .reg-step { display:none; }
    .reg-step.active { display:block; }

    /* Step label */
    .step-label { font-size:10px; font-weight:500; letter-spacing:.1em; text-transform:uppercase; color:var(--g400); margin-bottom:4px; }
    .step-title { font-family:'DM Serif Display',Georgia,serif; font-size:22px; font-weight:400; color:var(--black); margin-bottom:24px; letter-spacing:-.02em; }

    /* Nav buttons */
    .step-nav { display:flex; gap:10px; margin-top:24px; }
    .btn-back {
        flex:0 0 auto; padding:11px 18px; background:transparent;
        border:1px solid var(--g200); border-radius:4px;
        font-family:'Inter',sans-serif; font-size:13px; font-weight:500;
        color:var(--g600); cursor:pointer; transition:all .15s;
    }
    .btn-back:hover { border-color:var(--black); color:var(--black); }
    .btn-next {
        flex:1; padding:12px; background:var(--black);
        border:none; border-radius:4px;
        font-family:'Inter',sans-serif; font-size:13px; font-weight:500;
        letter-spacing:.03em; color:#fff; cursor:pointer; transition:background .15s;
    }
    .btn-next:hover { background:var(--g800); }
</style>

<div class="form-heading" style="margin-bottom:8px;">
    <h2>{{ __('Create Account') }}</h2>
    <p>{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
</div>

@if($errors->any())
    <div class="alert-danger" style="margin-top:16px;">{{ $errors->first() }}</div>
@endif

{{-- Step progress indicator --}}
<div class="step-progress" style="margin-top:20px;">
    <div class="step-dot active" id="dot-1">1</div>
    <div class="step-line" id="line-1"></div>
    <div class="step-dot" id="dot-2">2</div>
    <div class="step-line" id="line-2"></div>
    <div class="step-dot" id="dot-3">3</div>
</div>

<form method="POST" action="{{ route('auth.register.store') }}" class="bc-form-register" id="reg-form">
    @csrf
    <input type="hidden" name="redirect" value="{{ request()->get('redirect') }}">

    {{-- ── Step 1: Personal ─────────────────────────────── --}}
    <div class="reg-step active" id="step-1">
        <p class="step-label">Step 1 of 3</p>
        <p class="step-title">{{ __('Personal Info') }}</p>

        <div class="field-row">
            <div class="field">
                <label>{{ __('First Name') }} *</label>
                <input type="text" id="f-first_name" name="first_name" value="{{ old('first_name') }}" placeholder="e.g. Amara">
                <span class="error error-first_name" style="color:#c0392b;font-size:12px;display:block;margin-top:4px;"></span>
            </div>
            <div class="field">
                <label>{{ __('Last Name') }} *</label>
                <input type="text" id="f-last_name" name="last_name" value="{{ old('last_name') }}" placeholder="e.g. Osei">
                <span class="error error-last_name" style="color:#c0392b;font-size:12px;display:block;margin-top:4px;"></span>
            </div>
        </div>

        <div class="field">
            <label>{{ __('Phone') }} *</label>
            <input type="tel" id="f-phone" name="phone" value="{{ old('phone') }}" placeholder="+263 77 000 0000">
            <span class="error error-phone" style="color:#c0392b;font-size:12px;display:block;margin-top:4px;"></span>
        </div>

        <div class="field">
            <label>{{ __('Email Address') }} *</label>
            <input type="email" id="f-email" name="email" value="{{ old('email') }}" placeholder="you@example.com">
            <span class="error error-email" style="color:#c0392b;font-size:12px;display:block;margin-top:4px;"></span>
        </div>

        <div class="step-nav">
            <button type="button" class="btn-next" onclick="goStep(2)">{{ __('Continue') }} →</button>
        </div>
    </div>

    {{-- ── Step 2: Property / Business ──────────────────── --}}
    <div class="reg-step" id="step-2">
        <p class="step-label">Step 2 of 3</p>
        <p class="step-title">{{ __('Your Property') }}</p>

        <div class="field">
            <label>{{ __('Property / Business Name') }}</label>
            <input type="text" id="f-business_name" name="business_name" value="{{ old('business_name') }}" placeholder="e.g. Sunset Safari Lodge">
            <p class="field-hint">The name guests and partners will see on your listings.</p>
        </div>

        <div class="field-row">
            <div class="field">
                <label>{{ __('Country') }}</label>
                <select id="f-country" name="country">
                    <option value="">— {{ __('Select country') }} —</option>
                    @foreach(get_country_lists() as $code => $name)
                        <option value="{{ $code }}" {{ old('country') == $code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>{{ __('City') }}</label>
                <input type="text" id="f-city" name="city" value="{{ old('city') }}" placeholder="e.g. Harare">
            </div>
        </div>

        <div class="field">
            <label>{{ __('Address') }}</label>
            <input type="text" id="f-address" name="address" value="{{ old('address') }}" placeholder="Street address">
        </div>

        <div class="step-nav">
            <button type="button" class="btn-back" onclick="goStep(1)">← {{ __('Back') }}</button>
            <button type="button" class="btn-next" onclick="goStep(3)">{{ __('Continue') }} →</button>
        </div>
    </div>

    {{-- ── Step 3: Security ─────────────────────────────── --}}
    <div class="reg-step" id="step-3">
        <p class="step-label">Step 3 of 3</p>
        <p class="step-title">{{ __('Set Password') }}</p>

        <div class="field">
            <label>{{ __('Password') }} *</label>
            <input type="password" id="f-password" name="password" placeholder="{{ __('Min. 8 characters') }}">
            <p class="field-hint">Must include uppercase, lowercase, number and symbol.</p>
            <span class="error error-password" style="color:#c0392b;font-size:12px;display:block;margin-top:4px;"></span>
        </div>

        <div class="check-row" style="margin-top:8px;">
            <input type="checkbox" id="register-term" name="term">
            <label for="register-term">
                {{ __('I accept the') }}
                <a href="#">{{ __('Terms') }}</a>
                {{ __('and') }}
                <a href="#">{{ __('Privacy Policy') }}</a>
            </label>
        </div>
        <span class="error error-term" style="color:#c0392b;font-size:12px;display:block;margin-bottom:10px;"></span>
        <div class="error message-error" style="color:#c0392b;font-size:13px;margin-bottom:14px;"></div>

        <div class="step-nav">
            <button type="button" class="btn-back" onclick="goStep(2)">← {{ __('Back') }}</button>
            <button type="submit" class="btn-next">{{ __('Create Account') }}</button>
        </div>
    </div>

</form>

<script>
function goStep(n) {
    var current = document.querySelector('.reg-step.active');
    if (!current) return;
    var currentN = parseInt(current.id.replace('step-',''));

    // Basic validation before moving forward
    if (n > currentN) {
        if (currentN === 1) {
            var ok = true;
            ['f-first_name','f-last_name','f-phone','f-email'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el.value.trim()) { el.style.borderColor='#c0392b'; ok = false; }
                else el.style.borderColor='';
            });
            if (!ok) return;
        }
    }

    // Hide current, show next
    current.classList.remove('active');
    document.getElementById('step-'+n).classList.add('active');

    // Update dots
    for (var i = 1; i <= 3; i++) {
        var dot = document.getElementById('dot-'+i);
        dot.classList.remove('active','done');
        if (i < n)      dot.classList.add('done');
        else if (i === n) dot.classList.add('active');
    }
    // Update lines
    for (var j = 1; j <= 2; j++) {
        var line = document.getElementById('line-'+j);
        line.classList.toggle('done', j < n);
    }
}

// AJAX submit handler
document.getElementById('reg-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn  = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = '{{ __("Creating account…") }}';

    // Clear previous errors
    form.querySelectorAll('.error').forEach(function(el){ el.textContent=''; });

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (data.error) {
            // Show field errors
            var msgs = data.messages;
            Object.keys(msgs).forEach(function(field) {
                var el = form.querySelector('.error-' + field);
                if (el) el.textContent = Array.isArray(msgs[field]) ? msgs[field][0] : msgs[field];
                // Highlight the input
                var input = form.querySelector('[name="'+field+'"]');
                if (input) input.style.borderColor = '#c0392b';
            });
            // Generic fallback
            var generic = form.querySelector('.message-error');
            if (generic && msgs.message_error) generic.textContent = msgs.message_error;
            btn.disabled = false;
            btn.textContent = '{{ __("Create Account") }}';
        } else {
            // Success — redirect
            window.location.href = data.redirect || '{{ url("/") }}';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = '{{ __("Create Account") }}';
    });
});
</script>
@endsection
