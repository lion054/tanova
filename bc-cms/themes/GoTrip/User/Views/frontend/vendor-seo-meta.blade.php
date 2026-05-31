@if($row->id)
@php
    $meta_seo  = $row->getSeoMeta() ?: [];
    $seo_share = $meta_seo['seo_share'] ?? [];
    $seo_desc  = $meta_seo['seo_desc'] ?? $meta_seo['service_desc'] ?? '';
@endphp
<div class="panel">
    <div class="panel-title"><strong>{{ __('Search Engine') }}</strong></div>
    <div class="panel-body">
        {{-- Listing URL --}}
        @php $listingUrl = $row->getDetailUrl(); @endphp
        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded" style="background:#f8f9fa;border:1px solid #e9ecef;">
            <div class="flex-grow-1 text-truncate">
                <div class="text-11 fw-600 text-muted mb-1" style="text-transform:uppercase;letter-spacing:.5px">{{ __('Your listing URL') }}</div>
                <a href="{{ $listingUrl }}" target="_blank" class="text-14" style="word-break:break-all">{{ $listingUrl }}</a>
            </div>
            <a href="{{ $listingUrl }}" target="_blank"
               class="btn btn-sm btn-outline-secondary flex-shrink-0">
                <i class="fa fa-external-link"></i> {{ __('View') }}
            </a>
        </div>

        <div class="form-group mb-3">
            <label class="form-label fw-600">{{ __('Allow search engines to index this listing?') }}</label>
            <select name="seo_index" class="form-select">
                <option value="1" @selected(($meta_seo['seo_index'] ?? 1) == 1)>{{ __('Yes') }}</option>
                <option value="0" @selected(($meta_seo['seo_index'] ?? 1) == 0)>{{ __('No') }}</option>
            </select>
        </div>

        <ul class="nav mb-3" id="vendor-seo-tabs" role="tablist"
            style="border-bottom:2px solid #dee2e6;gap:4px;">
            <li class="nav-item" role="presentation">
                <a data-bs-toggle="tab" data-bs-target="#vseo-general" role="tab"
                   style="display:inline-block;padding:8px 16px;font-size:13px;font-weight:600;color:#495057;text-decoration:none;border:1px solid transparent;border-bottom:none;border-radius:4px 4px 0 0;background:#fff;cursor:pointer;margin-bottom:-2px;border-color:#dee2e6 #dee2e6 #fff;"
                   class="vseo-tab vseo-tab-active">{{ __('General Options') }}</a>
            </li>
            <li class="nav-item" role="presentation">
                <a data-bs-toggle="tab" data-bs-target="#vseo-facebook" role="tab"
                   style="display:inline-block;padding:8px 16px;font-size:13px;font-weight:600;color:#6c757d;text-decoration:none;border:1px solid transparent;border-radius:4px 4px 0 0;cursor:pointer;"
                   class="vseo-tab">{{ __('Share Facebook') }}</a>
            </li>
            <li class="nav-item" role="presentation">
                <a data-bs-toggle="tab" data-bs-target="#vseo-twitter" role="tab"
                   style="display:inline-block;padding:8px 16px;font-size:13px;font-weight:600;color:#6c757d;text-decoration:none;border:1px solid transparent;border-radius:4px 4px 0 0;cursor:pointer;"
                   class="vseo-tab">{{ __('Share X') }}</a>
            </li>
        </ul>

        <div class="tab-content mb-4">
            <div class="tab-pane fade show active" id="vseo-general" role="tabpanel">
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('SEO Title') }}</label>
                    <input type="text" name="seo_title" class="form-control"
                        placeholder="{{ $row->title ?? $row->name ?? __('Leave blank to use listing title') }}"
                        value="{{ $meta_seo['seo_title'] ?? '' }}">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('SEO Description') }}</label>
                    <textarea name="seo_desc" rows="3" class="form-control"
                        placeholder="{{ $seo_desc ?: __('Enter description...') }}"
                    >{{ $meta_seo['seo_desc'] ?? '' }}</textarea>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('Featured Image') }}</label>
                    {!! \Modules\Media\Helpers\FileHelper::fieldUpload('seo_image', $meta_seo['seo_image'] ?? '') !!}
                </div>
            </div>

            <div class="tab-pane fade" id="vseo-facebook" role="tabpanel">
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('Facebook Title') }}</label>
                    <input type="text" name="seo_share[facebook][title]" class="form-control"
                        placeholder="{{ $row->title ?? $row->name ?? __('Enter title...') }}"
                        value="{{ $seo_share['facebook']['title'] ?? '' }}">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('Facebook Description') }}</label>
                    <textarea name="seo_share[facebook][desc]" rows="3" class="form-control"
                        placeholder="{{ $row->short_desc ?? __('Enter description...') }}"
                    >{{ $seo_share['facebook']['desc'] ?? '' }}</textarea>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('Facebook Image') }}</label>
                    {!! \Modules\Media\Helpers\FileHelper::fieldUpload('seo_share[facebook][image]', $seo_share['facebook']['image'] ?? '') !!}
                </div>
            </div>

            <div class="tab-pane fade" id="vseo-twitter" role="tabpanel">
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('X Title') }}</label>
                    <input type="text" name="seo_share[twitter][title]" class="form-control"
                        placeholder="{{ $row->title ?? $row->name ?? __('Enter title...') }}"
                        value="{{ $seo_share['twitter']['title'] ?? '' }}">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('X Description') }}</label>
                    <textarea name="seo_share[twitter][desc]" rows="3" class="form-control"
                        placeholder="{{ $row->short_desc ?? __('Enter description...') }}"
                    >{{ $seo_share['twitter']['desc'] ?? '' }}</textarea>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('X Image') }}</label>
                    {!! \Modules\Media\Helpers\FileHelper::fieldUpload('seo_share[twitter][image]', $seo_share['twitter']['image'] ?? '') !!}
                </div>
            </div>
        </div>

        {{-- Search preview --}}
        <div class="border rounded p-3 bg-white">
            <div class="text-13 text-light-1 mb-2">{{ __('Search Preview') }}</div>
            <div class="d-flex align-items-center gap-2 mb-2">
                @php $favicon = setting_item('site_favicon'); @endphp
                @if($favicon)
                    @php $ffile = (new \Modules\Media\Models\MediaFile())->findById($favicon); @endphp
                    @if(!empty($ffile))
                        <img width="16" height="16" src="{{ asset('uploads/'.$ffile['file_path']) }}" alt="">
                    @endif
                @endif
                <div>
                    <div class="text-13">{{ setting_item_with_lang('site_title', request('lang')) }}</div>
                    <div class="text-12 text-light-1">{{ $meta_seo['full_url'] ?? url('/') }}</div>
                </div>
            </div>
            <div id="vseo-preview-title" style="font-size:18px;color:#1a0dab;line-height:1.3">
                {{ $meta_seo['seo_title'] ?? ($row->title ?? ($row->name ?? '')) }}
            </div>
            <div id="vseo-preview-desc" style="font-size:13px;color:#4d5156;margin-top:4px">
                {{ $seo_desc }}
            </div>
        </div>
    </div>
</div>
@push('js')
<script>
(function(){
    var ACTIVE_STYLE = 'display:inline-block;padding:8px 16px;font-size:13px;font-weight:600;color:#495057;text-decoration:none;border:1px solid #dee2e6;border-bottom:2px solid #fff;border-radius:4px 4px 0 0;background:#fff;cursor:pointer;margin-bottom:-2px;';
    var IDLE_STYLE   = 'display:inline-block;padding:8px 16px;font-size:13px;font-weight:600;color:#6c757d;text-decoration:none;border:1px solid transparent;border-radius:4px 4px 0 0;cursor:pointer;';

    document.querySelectorAll('#vendor-seo-tabs .vseo-tab').forEach(function(tab){
        tab.addEventListener('shown.bs.tab', function(){
            document.querySelectorAll('#vendor-seo-tabs .vseo-tab').forEach(function(t){ t.style.cssText = IDLE_STYLE; });
            this.style.cssText = ACTIVE_STYLE;
        });
    });

    var titleInput = document.querySelector('[name="seo_title"]');
    var descInput  = document.querySelector('[name="seo_desc"]');
    var prevTitle  = document.getElementById('vseo-preview-title');
    var prevDesc   = document.getElementById('vseo-preview-desc');
    var fallbackTitle = '{{ addslashes($row->title ?? $row->name ?? '') }}';
    if(titleInput && prevTitle){
        titleInput.addEventListener('input', function(){
            prevTitle.textContent = this.value || fallbackTitle;
        });
    }
    if(descInput && prevDesc){
        descInput.addEventListener('input', function(){
            prevDesc.textContent = this.value;
        });
    }
})();
</script>
@endpush
@endif
