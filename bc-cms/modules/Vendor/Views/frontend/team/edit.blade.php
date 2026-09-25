@extends('layouts.user')
@section('content')
<style>.tp-team-btn{display:inline-block;padding:9px 16px;border-radius:7px;font-size:13px;font-weight:600;border:1.5px solid #e4e4e4;background:#fff;color:#333!important;text-decoration:none!important;cursor:pointer}.tp-team-btn--primary{background:#0a0a0a;border-color:#0a0a0a;color:#fff!important}</style>
<div class="container-fluid">
    <h2 class="title-bar">{{ $team->member->display_name ?? '' }} <small class="text-muted">{{ $team->member->email ?? '' }}</small></h2>
    @include('admin.message')
    <form method="post" action="{{ route('vendor.team.store', $team->id) }}">
        @csrf
        <label class="font-weight-bold">{{ __('What they can open') }}</label>
        @foreach($modules as $key => $label)
            <div><label><input type="checkbox" name="permissions[]" value="{{ $key }}" {{ in_array($key, old('permissions', (array) $team->permissions)) ? 'checked' : '' }}> {{ __($label) }}</label></div>
        @endforeach
        <small class="text-muted d-block mb-2">{{ __('The company dashboard and Today are always included.') }}</small>
        <button type="submit" class="tp-team-btn tp-team-btn--primary">{{ __('Save') }}</button>
        <a href="{{ route('vendor.team.index') }}" class="tp-team-btn">{{ __('Cancel') }}</a>
    </form>
</div>
@endsection
