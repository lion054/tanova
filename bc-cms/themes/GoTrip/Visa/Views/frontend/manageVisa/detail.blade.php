@extends('layouts.user')
@section('content')
    <div class="row y-gap-20 justify-between items-end pb-20 lg:pb-40 md:pb-20">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600">{{ $row->id ? __('Edit: ') . $row->title : __('Add new visa service') }}</h1>
            <div class="text-15 text-light-1">{{ __('Configure visa service details, pricing, and availability.') }}</div>
        </div>
    </div>
    @include('admin.message')
    <div class="mb-2">
        @if($row->id)
            @include('Language::admin.navigation')
        @endif
    </div>
    <div class="lang-content-box">
        <form action="{{ route('visa.vendor.store', ['id' => $row->id ?: '-1', 'lang' => request()->query('lang')]) }}" method="post">
            @csrf
            <div class="form-add-service">
                <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                    <a data-bs-toggle="tab" data-bs-target="#nav-content" aria-selected="true" class="active">{{ __('1. Content') }}</a>
                    @if(is_default_lang())
                        <a data-bs-toggle="tab" data-bs-target="#nav-pricing" aria-selected="false">{{ __('2. Pricing') }}</a>
                        <a data-bs-toggle="tab" data-bs-target="#nav-seo" aria-selected="false">{{ __('3. SEO') }}</a>
                    @endif
                </div>

                <div class="tab-content" id="nav-tabContent">
                    {{-- Tab 1: Content --}}
                    <div class="tab-pane fade show active" id="nav-content">
                        <div class="panel">
                            <div class="panel-title"><strong>{{ __('Visa Details') }}</strong></div>
                            <div class="panel-body">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" required
                                        value="{{ old('title', $translation->title ?? '') }}"
                                        placeholder="{{ __('e.g. Zimbabwe Tourist Visa') }}">
                                </div>

                                @if(is_default_lang())
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Destination Country') }}</label>
                                    <select name="to_country" class="form-select">
                                        @foreach(get_country_lists() as $code => $name)
                                            <option value="{{ $code }}" @selected(old('to_country', $row->to_country) == $code)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Visa Type') }}</label>
                                    <select name="type_id" class="form-select">
                                        <option value="">— {{ __('Select type') }} —</option>
                                        @foreach($types as $type)
                                            <option value="{{ $type->id }}" @selected(old('type_id', $row->type_id) == $type->id)>{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Code') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" required
                                        value="{{ old('code', $row->code ?? '') }}"
                                        placeholder="{{ __('Unique alphanumeric code, e.g. ZW-TOURIST') }}">
                                    <small class="text-muted">{{ __('Alphanumeric, dash, and underscore only') }}</small>
                                </div>
                                @endif

                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Description') }}</label>
                                    <textarea name="content" class="form-control has-tinymce" rows="8">{{ old('content', $translation->content ?? '') }}</textarea>
                                </div>

                                @if(is_default_lang())
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Featured Image') }}</label>
                                    {!! \Modules\Media\Helpers\FileHelper::fieldUpload('image_id', $row->image_id) !!}
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Status') }}</label>
                                    <select name="status" class="form-select">
                                        <option value="publish" @selected(old('status', $row->status) == 'publish')>{{ __('Publish') }}</option>
                                        <option value="draft"   @selected(old('status', $row->status) == 'draft')>{{ __('Draft') }}</option>
                                        <option value="pending" @selected(old('status', $row->status) == 'pending')>{{ __('Pending Review') }}</option>
                                    </select>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if(is_default_lang())
                    {{-- Tab 2: Pricing --}}
                    <div class="tab-pane fade" id="nav-pricing">
                        <div class="panel">
                            <div class="panel-title"><strong>{{ __('Pricing') }}</strong></div>
                            <div class="panel-body">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Price') }}</label>
                                    <input type="number" step="0.01" name="price" class="form-control"
                                        value="{{ old('price', $row->price ?? '') }}"
                                        placeholder="0.00">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Original Price (before discount, optional)') }}</label>
                                    <input type="number" step="0.01" name="original_price" class="form-control"
                                        value="{{ old('original_price', $row->original_price ?? '') }}"
                                        placeholder="0.00">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Processing Days') }}</label>
                                    <input type="number" name="processing_days" class="form-control"
                                        value="{{ old('processing_days', $row->processing_days ?? '') }}"
                                        placeholder="{{ __('e.g. 5') }}">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Max Stay Days') }}</label>
                                    <input type="number" name="max_stay_days" class="form-control"
                                        value="{{ old('max_stay_days', $row->max_stay_days ?? '') }}"
                                        placeholder="{{ __('e.g. 30') }}">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600">{{ __('Multiple Entry') }}</label>
                                    <input type="text" name="multiple_entry" class="form-control"
                                        value="{{ old('multiple_entry', $row->multiple_entry ?? '') }}"
                                        placeholder="{{ __('e.g. Single / Multiple') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 3: SEO --}}
                    <div class="tab-pane fade" id="nav-seo">
                        @include('User::frontend.vendor-seo-meta')
                    </div>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <button class="button h-50 px-24 -dark-1 bg-blue-1 text-white" type="submit">
                    <i class="fa fa-save mr-2"></i> {{ __('Save Changes') }}
                </button>
            </div>
        </form>
    </div>
@endsection
@push('js')
    <script type="text/javascript" src="{{ asset('libs/tinymce/js/tinymce/tinymce.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/condition.js?_ver=' . config('app.asset_version')) }}"></script>
@endpush
