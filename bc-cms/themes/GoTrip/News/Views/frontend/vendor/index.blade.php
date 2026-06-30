@extends('Layout::user')
@section('content')
<div class="tnv-page">

    <div class="tnv-ph">
        <div>
            <div class="tnv-ph__crumb">{{ __('Marketing') }}</div>
            <h1 class="tnv-ph__title">{{ !empty($recovery) ? __('Recovery News') : __('Manage News') }}</h1>
            <div class="tnv-ph__sub">{{ __('Your operator content hub. AI-powered publishing for the modern travel brand.') }}</div>
        </div>
        @if(Auth::user()->hasPermission('news_create') && empty($recovery))
        <div class="tnv-ph__actions">
            <a href="{{ route('news.vendor.create') }}" class="tnv-btn tnv-btn--gold"><i class="icofont-plus"></i> {{ __('Add News') }}</a>
        </div>
        @endif
    </div>

    @include('admin.message')

    <div class="tnv-c">
        <div class="tnv-c__h" style="flex-wrap:wrap;gap:10px">
            {{-- Bulk actions --}}
            @if(!empty($rows))
            <form method="post" action="{{ route('news.vendor.bulkEdit') }}" class="filter-form" style="display:flex;gap:8px">
                {{ csrf_field() }}
                <select name="action" class="tnv-in" style="width:auto;min-height:36px;padding:6px 10px">
                    <option value="">{{ __('Bulk actions') }}</option>
                    @if(!setting_item('news_vendor_need_approve'))<option value="publish">{{ __('Publish') }}</option>@endif
                    <option value="pending">{{ __('Move to Pending') }}</option>
                    <option value="draft">{{ __('Move to Draft') }}</option>
                    <option value="delete">{{ __('Delete') }}</option>
                </select>
                <button data-confirm="{{ __('Do you want to delete?') }}" class="tnv-btn tnv-btn--ghost tnv-btn--sm dungdt-apply-form-btn" type="button">{{ __('Apply') }}</button>
            </form>
            @endif
            {{-- Search --}}
            <form method="get" action="{{ route('news.vendor.index') }}" class="filter-form" role="search" style="display:flex;gap:8px">
                <input type="text" name="s" value="{{ Request()->s }}" placeholder="{{ __('Search by name') }}" class="tnv-in" style="width:170px;min-height:36px;padding:6px 10px">
                <select name="cate_id" class="tnv-in" style="width:auto;min-height:36px;padding:6px 10px">
                    <option value="">{{ __('All categories') }}</option>
                    @if(!empty($categories))@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach @endif
                </select>
                <button class="tnv-btn tnv-btn--gold tnv-btn--sm" type="submit">{{ __('Search') }}</button>
            </form>
        </div>
        <div class="tnv-c__b tnv-c__b--flush">
            <form action="" class="bc-form-item">
                <div style="overflow-x:auto">
                <table class="tnv-tbl">
                    <thead><tr><th style="width:40px"><input type="checkbox" class="check-all"></th><th>{{ __('Name') }}</th><th>{{ __('Category') }}</th><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th><th class="right"></th></tr></thead>
                    <tbody>
                        @if($rows->total() > 0)
                            @foreach($rows as $row)
                                <tr>
                                    <td><input type="checkbox" class="check-item" name="ids[]" value="{{ $row->id }}"></td>
                                    <td><a class="tnv-link-gold" href="{{ route('news.vendor.edit',['id'=>$row->id]) }}">{{ $row->title }}</a></td>
                                    <td class="tnv-muted">{{ $row->cat->name ?? '' }}</td>
                                    <td class="tnv-muted">{{ display_date($row->updated_at) }}</td>
                                    <td><span class="tnv-b {{ $row->status==='publish'?'tnv-b--pos':($row->status==='pending'?'tnv-b--gold':'tnv-b--neutral') }}">{{ ucfirst($row->status) }}</span></td>
                                    <td class="right"><a href="{{ route('news.vendor.edit',['id'=>$row->id]) }}" class="tnv-btn tnv-btn--ghost tnv-btn--sm">{{ __('Edit') }}</a></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                </div>
            </form>
            @if($rows->total() === 0)
                <div class="tnv-empty">
                    <div class="tnv-empty__ic"><i class="icofont-newspaper"></i></div>
                    <div class="tnv-empty__t">{{ __('No news yet') }}</div>
                    <div class="tnv-empty__s">{{ __('Publish your first article.') }}</div>
                </div>
            @else
                <div style="padding:14px 20px">{{ $rows->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>

</div>
@endsection
