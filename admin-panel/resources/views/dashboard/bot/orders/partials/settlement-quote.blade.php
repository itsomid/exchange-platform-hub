{{-- Reconstructed cancel/fill price. $quote comes from BotSettlementQuote. --}}
<div class="row g-2 text-center mb-2">
    <div class="col-6 col-md-3">
        <div class="p-2 rounded border border-primary bg-white">
            <small class="text-muted d-block">قیمت تسویه (USDT)</small>
            <strong class="font-number text-primary">{{ formatNumberTrimZeros($quote['fill_price']) }}</strong>
            <small class="text-muted d-block" style="font-size:.68rem">درآمد ناخالص ÷ مقدار فروش</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-2 rounded border bg-white">
            <small class="text-muted d-block">بازده نسبت به خرید</small>
            @if ($quote['move_pct'] !== null)
                <strong class="font-number {{ (float) $quote['move_pct'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ formatNumberTrimZeros($quote['move_pct'], 2) }}٪
                </strong>
            @else
                <strong>—</strong>
            @endif
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-2 rounded border bg-white">
            <small class="text-muted d-block">فاصله تا هدف</small>
            @if ($quote['distance_amount'] !== null)
                @php $gap = (float) $quote['distance_amount']; @endphp
                <strong class="font-number {{ $gap > 0 ? 'text-warning' : 'text-success' }}">
                    {{ formatNumberTrimZeros(abs($gap)) }}
                </strong>
                <small class="text-muted d-block" style="font-size:.68rem">
                    {{ formatNumberTrimZeros(abs((float) $quote['distance_pct']), 2) }}٪
                    {{ $gap > 0 ? 'پایین‌تر از هدف' : ($gap < 0 ? 'بالاتر از هدف' : 'روی هدف') }}
                </small>
            @else
                <strong>—</strong>
            @endif
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-2 rounded border bg-white">
            <small class="text-muted d-block">پیشرفت مسیر خرید تا هدف</small>
            @if ($quote['progress_pct'] !== null)
                <strong class="font-number">{{ formatNumberTrimZeros($quote['progress_pct'], 2) }}٪</strong>
                <small class="text-muted d-block" style="font-size:.68rem">۱۰۰٪ یعنی رسیدن به قیمت هدف</small>
            @else
                <strong>—</strong>
            @endif
        </div>
    </div>
</div>
