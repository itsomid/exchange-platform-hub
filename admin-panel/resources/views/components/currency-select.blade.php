

@props([
    'name'        => 'currency_id',
    'id'          => null,
    'currencies',
    'selected'    => '',
    'required'    => false,
    'placeholder' => 'انتخاب کنید...',
    'error'       => null,
])

@php
    $selectId  = $id ?? $name;
    $hasError  = $error && $errors->has($error);
@endphp

<select
    name="{{ $name }}"
    id="{{ $selectId }}"
    class="currency-select form-select {{ $hasError ? 'is-invalid' : '' }}"
    data-placeholder="{{ $placeholder }}"
    {{ $required ? 'required' : '' }}
>
    <option value=""></option>
    @foreach ($currencies as $currency)
        <option
            value="{{ $currency->id }}"
            data-logo="{{ $currency->coinLogo() }}"
            {{ (string) $selected === (string) $currency->id ? 'selected' : '' }}
        >{{ $currency->symbol }} — {{ $currency->name }}</option>
    @endforeach
</select>

@if ($hasError)
    <div class="invalid-feedback">{{ $errors->first($error) }}</div>
@endif

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @parent
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@pushOnce('scripts')
<script>
(function () {
    function currencyOption(option) {
        if (!option.id) return option.text;
        var logo = $(option.element).data('logo');
        if (!logo) return option.text;
        return $('<span style="display:inline-flex;align-items:center;gap:8px;">' +
            '<img src="' + $('<div/>').text(logo).html() + '" style="width:28px;height:28px;border-radius:50%;object-fit:contain;flex-shrink:0;" />' +
            $('<div/>').text(option.text).html() +
            '</span>');
    }

    $(document).ready(function () {
        $('.currency-select').each(function () {
            var $el = $(this);
            $el.wrap('<div class="position-relative"></div>').select2({
                dir: 'rtl',
                dropdownParent: $el.parent(),
                templateResult: currencyOption,
                templateSelection: currencyOption,
                placeholder: $el.data('placeholder'),
            });
        });
    });
}());
</script>
@endPushOnce
