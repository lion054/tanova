@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{__("Vendor Plans")}}</h1>
        <div class="title-actions">
            <a href="{{route('vendor.admin.plan.create')}}" class="btn btn-primary"><i class="fa fa-plus"></i> {{__("Add Plan")}}</a>
        </div>
    </div>
    @include('admin.message')
    <div class="panel">
        <div class="panel-body">
            <form action="" method="post" class="bc-form-item">
                @csrf
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th width="60px"><input type="checkbox" class="check-all"></th>
                            <th>{{__('Name')}}</th>
                            <th width="160px">{{__('Monthly Price')}}</th>
                            <th width="160px">{{__('Annual Price')}}</th>
                            <th width="120px">{{__('OS')}}</th>
                            <th width="90px">{{__('Staff')}}</th>
                            <th width="130px">{{__('Commission')}}</th>
                            <th width="100px">{{__('Status')}}</th>
                            <th width="120px">{{__('Actions')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($rows->total() > 0)
                            @foreach($rows as $row)
                            <tr class="status-{{$row->status}}">
                                <td><input type="checkbox" name="ids[]" class="check-item" value="{{$row->id}}"></td>
                                <td>
                                    <a href="{{route('vendor.admin.plan.edit',['id'=>$row->id])}}">
                                        <strong>{{$row->name}}</strong>
                                    </a>
                                </td>
                                <td>{{format_money($row->price)}}</td>
                                <td>{{$row->price_annual ? format_money($row->price_annual) : '—'}}</td>
                                <td>{{$row->coversAllOs() ? __('All') : $row->os_limit}}</td>
                                <td>{{$row->max_staff ?: __('Unlimited')}}</td>
                                <td>{{$row->base_commission}}%</td>
                                <td>
                                    <span class="badge badge-{{$row->status == 'publish' ? 'success' : 'secondary'}}">
                                        {{$row->status == 'publish' ? __('Published') : __('Draft')}}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{route('vendor.admin.plan.edit',['id'=>$row->id])}}" class="btn btn-sm btn-info">
                                        <i class="fa fa-edit"></i> {{__('Edit')}}
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr><td colspan="9">{{__("No plans found. Create your first plan.")}}</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="d-flex">
                        <select name="action" class="form-control mr-2" style="width:auto">
                            <option value="">{{__('-- Bulk Action --')}}</option>
                            <option value="publish">{{__('Publish')}}</option>
                            <option value="draft">{{__('Draft')}}</option>
                            <option value="delete">{{__('Delete')}}</option>
                        </select>
                        <button type="submit" class="btn btn-secondary">{{__('Apply')}}</button>
                    </div>
                    {{$rows->links()}}
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
