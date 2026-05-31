@php
    $inputGroupClass = 'form-input';
    switch ($field['type']) {
        case 'checkbox':
        case 'radio':
            $inputGroupClass = 'form-check';
            break;
        default:
            $inputGroupClass = 'form-input';
            break;
    }

@endphp

<div class=" mb-3  col-{{ $field['col'] ?? 12 }}" wire:key="{{ $field['id'] }}">
    <?php $inputClass = isset($errors) && $errors->has($field['id']) ? 'is-invalid' : ''; ?>
    @if ($field['type'] == 'text')
        <div class="{{ $inputGroupClass }}">
            <input @if (strpos($field['rules'], 'required') !== false) required @endif class="form-control has-value {{ $inputClass }}"
                id="data_{{ $field['id'] }}" type="text" wire:model="data.{{ $field['id'] }}">
            @include('Form::frontend.field.label', ['field' => $field])
        </div>
    @elseif($field['type'] == 'email')
        <div class="{{ $inputGroupClass }}">
            <input @if (strpos($field['rules'], 'required') !== false) required @endif class="form-control has-value {{ $inputClass }}"
                id="data_{{ $field['id'] }}" type="email" wire:model="data.{{ $field['id'] }}">
            @include('Form::frontend.field.label', ['field' => $field])
        </div>
    @elseif($field['type'] == 'number')
        <div class="{{ $inputGroupClass }}">
            <input @if (strpos($field['rules'], 'required') !== false) required @endif class="form-control has-value {{ $inputClass }}"
                id="data_{{ $field['id'] }}" type="number" wire:model="data.{{ $field['id'] }}">
            @include('Form::frontend.field.label', ['field' => $field])
        </div>
    @elseif($field['type'] == 'textarea')
        <div class="{{ $inputGroupClass }}">
            <textarea @if (strpos($field['rules'], 'required') !== false) required @endif class="form-control has-value {{ $inputClass }}"
                id="data_{{ $field['id'] }}" wire:model="data.{{ $field['id'] }}"></textarea>
            @include('Form::frontend.field.label', ['field' => $field])
        </div>
    @elseif($field['type'] == 'select')
        <div class="{{ $inputGroupClass }}">
            <select @if (strpos($field['rules'], 'required') !== false) required @endif
                class="form-control has-value {{ $inputClass }}" id="data_{{ $field['id'] }}"
                wire:model="data.{{ $field['id'] }}">
                <?php
                if (!empty($field['data_source'])) {
                    $field['options'] = $this->getDataSource($field);
                }
                ?>
                @if (!empty($field['options']))
                    <option value="">{{ __('--Select--') }}</option>
                    @foreach ($field['options'] as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                @endif
            </select>
            @include('Form::frontend.field.label', ['field' => $field])
        </div>
    @elseif($field['type'] == 'radio')
        <div class="{{ $inputGroupClass }}">
            @include('Form::frontend.field.label', ['field' => $field])
            <div class="@error($field['id']) is-invalid @enderror">
                @foreach ($field['options'] as $option)
                    <label class="d-flex gap-3">
                        <div>
                            <input type="radio" name="{{ $field['id'] }}" value="{{ $option['value'] }}"
                                wire:model="data.{{ $field['id'] }}">
                        </div>
                        <div class="text-14 lh-10 text-light-1 ml-10">{{ $option['label'] }}</div>
                    </label>
                @endforeach
            </div>
        </div>
    @elseif($field['type'] == 'checkbox')
        <div class="{{ $inputGroupClass }}">
            @include('Form::frontend.field.label', ['field' => $field])
            <div class="@error($field['id']) is-invalid @enderror">
                @foreach ($field['options'] as $option)
                    <input type="checkbox" wire:model="data.{{ $field['id'] }}" value="{{ $option['value'] }}">
                    <label for="{{ $option['value'] }}">{{ $option['label'] }}</label>
                @endforeach
            </div>
        </div>
    @endif
    @if ($field['type'] == 'date')
        <div class="{{ $inputGroupClass }}">
            <input @if (strpos($field['rules'], 'required') !== false) required @endif
                class="form-control has-value {{ $inputClass }}" id="data_{{ $field['id'] }}" type="date"
                wire:model="data.{{ $field['id'] }}">
            @include('Form::frontend.field.label', ['field' => $field])
        </div>
    @endif

    @if ($field['type'] == 'file_picker')
        <div class="">
            @include('Form::frontend.field.label', ['field' => $field])
            @include('Form::frontend.field.file_picker', ['field' => $field])
        </div>
    @endif

    @error($field['id'])
        <div class="invalid-feedback d-block"> {{ $message }} </div>
    @enderror
</div>
