<div class="item-list">
    <div class="row">
        <div class="col-md-3">
            <div class="thumb-image">
                <a href="{{ $row->getDetailUrl() }}" target="_blank">
                    @if($row->image_url)
                        <img src="{{ $row->image_url }}" class="img-responsive" alt="">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height:120px">
                            <i class="icofont-id" style="font-size:48px;color:#ccc"></i>
                        </div>
                    @endif
                </a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="item-title">
                <a href="{{ $row->getDetailUrl() }}" target="_blank">{{ $row->title }}</a>
            </div>
            <div class="location">
                <i class="icofont-globe"></i>
                {{ __('Country') }}: {{ $row->country ?? $row->to_country }}
            </div>
            <div class="location">
                <i class="icofont-money"></i>
                {{ __('Price') }}: <span class="sale-price">{{ format_money($row->price) }}</span>
                @if($row->original_price > $row->price)
                    <span class="price" style="text-decoration:line-through">{{ format_money($row->original_price) }}</span>
                @endif
            </div>
            <div class="location">
                <i class="icofont-clock-time"></i>
                {{ __('Processing') }}: {{ $row->processing_days }} {{ __('day(s)') }}
            </div>
            <div class="location">
                <i class="icofont-ui-settings"></i>
                {{ __('Status') }}: <span class="badge badge-{{ $row->status }}">{{ $row->status_text }}</span>
            </div>
            <div class="location">
                <i class="icofont-wall-clock"></i>
                {{ __('Last Updated') }}: {{ display_datetime($row->updated_at ?? $row->created_at) }}
            </div>
            <div class="control-action">
                @if(!empty($recovery))
                    <a href="{{ route('visa.vendor.restore', [$row->id]) }}" class="btn btn-primary" data-confirm="{{ __('"Do you want to restore?"') }}">{{ __('Restore') }}</a>
                    @if(Auth::user()->hasPermission('visa_delete'))
                        <a href="{{ route('visa.vendor.delete', ['id' => $row->id, 'permanently_delete' => 1]) }}" class="btn btn-danger" data-confirm="{{ __('"Permanently delete? This cannot be undone."') }}">{{ __('Delete Forever') }}</a>
                    @endif
                @else
                    @if(Auth::user()->hasPermission('visa_update'))
                        <a href="{{ route('visa.vendor.edit', [$row->id]) }}" class="btn btn-warning">{{ __('Edit') }}</a>
                    @endif
                    @if(Auth::user()->hasPermission('visa_delete'))
                        <a href="{{ route('visa.vendor.delete', [$row->id]) }}" class="btn btn-danger" data-confirm="{{ __('"Do you want to delete?"') }}">{{ __('Delete') }}</a>
                    @endif
                    @if($row->status == 'publish')
                        <a href="{{ route('visa.vendor.bulk_edit', [$row->id, 'action' => 'make-hide']) }}" class="btn btn-secondary">{{ __('Make Hidden') }}</a>
                    @endif
                    @if($row->status == 'draft')
                        <a href="{{ route('visa.vendor.bulk_edit', [$row->id, 'action' => 'make-publish']) }}" class="btn btn-success">{{ __('Publish') }}</a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
