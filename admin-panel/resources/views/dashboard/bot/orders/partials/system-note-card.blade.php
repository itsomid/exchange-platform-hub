@php
    $reason = trim((string) ($text ?? ''));
    $symbol = $symbol ?? null;

    $segments = $reason !== '' ? array_map('trim', explode('|', $reason)) : [];
    $primary = $segments[0] ?? '';
    $extras = array_slice($segments, 1);

    $parsed = [
        'title' => 'رویداد سیستمی',
        'market' => null,
        'code' => null,
        'message' => null,
        'notes' => [],
    ];

    if (str_starts_with($primary, 'coinex.sell.place_failed')) {
        $parsed['title'] = 'ثبت پله فروش در صرافی مرجع ناموفق بود';
        if (preg_match('/market=([^\s]+)/', $primary, $m)) $parsed['market'] = $m[1];
        if (preg_match('/code=([^\s]+)/', $primary, $m)) $parsed['code'] = $m[1];
        if (preg_match('/msg=(.+)$/', $primary, $m)) $parsed['message'] = trim($m[1]);
    } elseif (str_starts_with($primary, 'coinex.buy.')) {
        $parsed['title'] = 'خرید در صرافی مرجع ناموفق بود';
        if (preg_match('/market=([^\s]+)/', $primary, $m)) $parsed['market'] = $m[1];
        if (preg_match('/code=([^\s]+)/', $primary, $m)) $parsed['code'] = $m[1];
        if (preg_match('/msg=(.+)$/', $primary, $m)) $parsed['message'] = trim($m[1]);
    } elseif ($primary === 'bot.buy.no_live_price') {
        $parsed['title'] = 'قیمت لحظه‌ای برای خرید در دسترس نبود';
        $parsed['message'] = 'سیستم نتوانست قیمت زنده این ارز را از فید قیمت بخواند.';
    } elseif ($reason !== '') {
        $parsed['message'] = $primary;
    }

    foreach ($extras as $extra) {
        if ($extra === 'auto-liquidated') {
            $parsed['notes'][] = 'موقعیت خریداری‌شده به‌صورت اضطراری در صرافی مرجع نقد شد.';
        } elseif (str_starts_with($extra, 'dispose_failed')) {
            $parsed['notes'][] = 'نقدسازی اضطراری ناموفق بود — نیاز به بررسی اپراتور.';
        } elseif (str_starts_with($extra, 'liquidation_exchange_order_id=')) {
            $parsed['notes'][] = 'شناسه سفارش نقدسازی: ' . substr($extra, strlen('liquidation_exchange_order_id='));
        } else {
            $parsed['notes'][] = $extra;
        }
    }
@endphp

<div class="rounded-3 p-3 mb-2"
    style="background:rgba(234,84,85,.05); border:1px solid rgba(234,84,85,.18);">
    <div class="d-flex align-items-start gap-2">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
            style="width:32px;height:32px;background:#fff;">
            <i class="fas fa-robot text-danger" style="font-size:.85rem;"></i>
        </div>
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                @if ($symbol)
                    <span class="badge bg-dark font-number">{{ $symbol }}</span>
                @endif
                <strong class="text-danger small">{{ $parsed['title'] }}</strong>
            </div>
            @if ($parsed['market'] || $parsed['code'] || $parsed['message'])
                <div class="d-flex flex-wrap gap-2 mb-1">
                    @if ($parsed['market'])
                        <span class="badge rounded-pill text-bg-light border font-number small">{{ $parsed['market'] }}</span>
                    @endif
                    @if ($parsed['code'])
                        <span class="badge rounded-pill text-bg-light border font-number small">کد: {{ $parsed['code'] }}</span>
                    @endif
                </div>
                @if ($parsed['message'])
                    <div class="small mb-1">{{ $parsed['message'] }}</div>
                @endif
            @endif
            @if (! empty($parsed['notes']))
                <ul class="small text-muted mb-1 ps-3">
                    @foreach ($parsed['notes'] as $note)
                        <li>{{ $note }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($reason !== '')
                <code class="d-block small text-muted text-break" style="direction:ltr;text-align:left;">{{ $reason }}</code>
            @endif
        </div>
    </div>
</div>
