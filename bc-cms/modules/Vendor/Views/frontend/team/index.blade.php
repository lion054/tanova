@extends('layouts.user')
@section('content')
<div class="container-fluid">
    <h2 class="title-bar">{{ __('Team members') }}</h2>
    @include('admin.message')
    <p style="max-width:720px;">{{ __('Your team are the people who work in your company. Each one signs in with their own email and password, sees only your company\'s things, and can open only the parts you tick. They can never open the platform admin area, your subscription, API keys, integrations or team settings.') }}</p>
    <hr>
    <form method="post" action="{{ route('vendor.team.add') }}">
        @csrf
        <div class="row">
            <div class="col-md-3"><label class="font-weight-bold">{{ __('Name') }}</label><input type="text" name="name" value="{{ old('name') }}" required class="form-control" placeholder="{{ __('Full name') }}"></div>
            <div class="col-md-3"><label class="font-weight-bold">{{ __('Email address') }}</label><input type="email" name="email" value="{{ old('email') }}" required class="form-control" placeholder="name@yourcompany.com"></div>
            <div class="col-md-6">
                <label class="font-weight-bold">{{ __('What they can open') }}</label>
                @foreach($modules as $key => $label)
                    <div><label><input type="checkbox" name="permissions[]" value="{{ $key }}" {{ in_array($key, old('permissions', [])) ? 'checked' : '' }}> {{ __($label) }}</label></div>
                @endforeach
                <small class="text-muted">{{ __('The company dashboard and Today are always included.') }}</small>
            </div>
        </div>
        <button class="btn btn-success mt-2"><i class="fa fa-plus"></i> {{ __('Add and send invitation') }}</button>
    </form>
    <hr>
    <h4>{{ __('People on your team') }}</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Can open') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $t)
                <tr>
                    <td><strong>{{ $t->member->display_name ?? '' }}</strong></td>
                    <td>{{ $t->member->email ?? '' }}</td>
                    <td>{{ collect($t->permissions)->map(fn ($k) => __($modules[$k] ?? $k))->implode(', ') }}</td>
                    <td><span class="badge badge-{{ $t->status_badge }}">{{ $t->status_text }}</span></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('vendor.team.edit', $t->id) }}">{{ __('Change access') }}</a>
                        @if($t->status !== Modules\Vendor\Models\VendorTeam::STATUS_PUBLISH)<a class="btn btn-sm btn-outline-secondary" href="{{ route('vendor.team.re-send-request', $t->id) }}">{{ __('Send invitation again') }}</a>@endif
                        <a class="btn btn-sm btn-outline-danger" href="{{ URL::signedRoute('vendor.team.delete', ['vendorTeam' => $t->id]) }}" onclick="return confirm('{{ __('Remove this person from your team?') }}')">{{ __('Remove') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">{{ __('Nobody yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
