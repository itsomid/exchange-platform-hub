@props([
    'name' => 'market',
    'id' => null,
    'markets',
    'selected' => '',
    'required' => false,
    'emptyLabel' => 'همه بازارها',
    'placeholder' => 'نام یا نماد کوین...',
    'error' => null,
])

@php
    $selectId = $id ?? $name;
    $hasError = $error && $errors->has($error);
@endphp

<select
    name="{{ $name }}"
    id="{{ $selectId }}"
    class="market-select form-select {{ $hasError ? 'is-invalid' : '' }}"
    data-search-placeholder="{{ $placeholder }}"
    {{ $required ? 'required' : '' }}
>
    @unless ($required)
        <option value="" {{ ($selected === '' || $selected === null) ? 'selected' : '' }}>{{ $emptyLabel }}</option>
    @endunless
    @foreach ($markets as $market)
        @php
            $base = $market->baseCurrency;
            $quote = $market->quoteCurrency;
            $search = collect([
                $market->base_currency,
                $market->quote_currency,
                $market->name,
                $base?->name,
                $base?->persian_name,
                $quote?->name,
                $quote?->persian_name,
            ])->filter()->implode(' ');
        @endphp
        <option
            value="{{ $market->id }}"
            data-search="{{ $search }}"
            @if ($base) data-base-logo="{{ $base->coinLogo() }}" @endif
            @if ($quote) data-quote-logo="{{ $quote->coinLogo() }}" @endif
            {{ (string) $selected === (string) $market->id ? 'selected' : '' }}
        >{{ $market->base_currency }}/{{ $market->quote_currency }}</option>
    @endforeach
</select>

@if ($hasError)
    <div class="invalid-feedback">{{ $errors->first($error) }}</div>
@endif

@once
    @section('vendor-style')
        @parent
        @vite(['resources/assets/vendor/libs/select2/select2.scss'])
    @endsection

    @section('vendor-script')
        @parent
        @vite(['resources/assets/vendor/libs/select2/select2.js'])
    @endsection
@endonce

@pushOnce('scripts')
<script>
(function () {
    function marketOption(option) {
        if (!option.element) {
            return option.text;
        }

        var $source = $(option.element);
        var baseLogo = $source.attr('data-base-logo');
        var quoteLogo = $source.attr('data-quote-logo');

        if (!baseLogo && !quoteLogo) {
            return option.text;
        }

        function coinImg(src, overlap) {
            return $('<img>', { src: src, alt: '', width: 22, height: 22 }).css({
                width: '22px',
                height: '22px',
                borderRadius: '50%',
                objectFit: 'contain',
                flexShrink: '0',
                marginInlineStart: overlap ?  '0' : '-12px' 
            });
        }

        var $logos = $('<span></span>').css({
            display: 'inline-flex',
            alignItems: 'center'
        });
   
        if (quoteLogo) {
            $logos.append(coinImg(quoteLogo, !!baseLogo));
        }
        if (baseLogo) {
            $logos.append(coinImg(baseLogo, false));
        }
        return $('<span></span>').css({
            display: 'inline-flex',
            alignItems: 'center',
            gap: '8px',
        }).append($logos).append($('<span></span>').text(option.text));
    }

    function marketMatcher(params, data) {
        var term = $.trim(params.term || '').toLowerCase();
        if (term === '') {
            return data;
        }
        if (!data.element || !data.element.value) {
            return null;
        }

        var haystack = ((data.text || '') + ' ' + ($(data.element).attr('data-search') || '')).toLowerCase();
        return haystack.indexOf(term) > -1 ? data : null;
    }

    $(document).ready(function () {
        $('.market-select').each(function () {
            var $el = $(this);
            if ($el.data('select2')) {
                return;
            }

            $el.wrap('<div class="position-relative"></div>').select2({
                dir: 'rtl',
                width: '100%',
                dropdownParent: $el.parent(),
                templateResult: marketOption,
                templateSelection: marketOption,
                matcher: marketMatcher,
                language: {
                    noResults: function () {
                        return 'بازاری پیدا نشد';
                    }
                }
            });

            $el.on('select2:open', function () {
                var field = document.querySelector('.select2-container--open .select2-search__field');
                if (field) {
                    field.setAttribute('placeholder', $el.attr('data-search-placeholder') || '');
                }
            });
        });
    });
}());
</script>
@endPushOnce
