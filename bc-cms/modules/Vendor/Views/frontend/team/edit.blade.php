@extends('layouts.user')
@section('content')
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
        <button class="btn btn-success">{{ __('Save') }}</button>
        <a href="{{ route('vendor.team.index') }}" class="btn btn-default">{{ __('Cancel') }}</a>
    </form>
</div>
@endsection
