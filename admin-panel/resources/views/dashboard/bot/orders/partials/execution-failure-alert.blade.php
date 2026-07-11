@php
    $reason = trim((string) ($execution->failure_reason ?? ''));
    $showFailure = $reason !== '' || in_array($execution->status, ['FAILED', 'SKIPPED'], true);
@endphp

@if ($showFailure)
    @php
        $segments = $reason !== '' ? array_map('trim', explode('|', $reason)) : [];
        $primary = $segments[0] ?? '';
        $extras = array_slice($segments, 1);

        $parsed = [
            'kind' => 'generic',
            'title' => 'علت مشکل در اجرای خرید',
            'market' => null,
            'code' => null,
            'message' => null,
            'notes' => [],
        ];

        if (str_starts_with($primary, 'coinex.sell.place_failed')) {
            $parsed['kind'] = 'sell_place_failed';
            $parsed['title'] = 'ثبت پله فروش در صرافی مرجع ناموفق بود';
            if (preg_match('/market=([^\s]+)/', $primary, $m)) {
                $parsed['market'] = $m[1];
            }
            if (preg_match('/code=([^\s]+)/', $primary, $m)) {
                $parsed['code'] = $m[1];
            }
            if (preg_match('/msg=(.+)$/', $primary, $m)) {
                $parsed['message'] = trim($m[1]);
            }
        } elseif (str_starts_with($primary, 'coinex.buy.')) {
            $parsed['kind'] = 'buy_failed';
            $parsed['title'] = 'خرید در صرافی مرجع ناموفق بود';
            if (preg_match('/market=([^\s]+)/', $primary, $m)) {
                $parsed['market'] = $m[1];
            }
            if (preg_match('/code=([^\s]+)/', $primary, $m)) {
                $parsed['code'] = $m[1];
            }
            if (preg_match('/msg=(.+)$/', $primary, $m)) {
                $parsed['message'] = trim($m[1]);
            } elseif ($primary === 'bot.buy.no_live_price') {
                $parsed['title'] = 'قیمت لحظه‌ای برای خرید در دسترس نبود';
                $parsed['message'] = 'سیستم نتوانست قیمت زنده این ارز را از فید قیمت بخواند.';
            }
        } elseif ($primary === 'bot.buy.no_live_price') {
            $parsed['kind'] = 'no_price';
            $parsed['title'] = 'قیمت لحظه‌ای برای خرید در دسترس نبود';
            $parsed['message'] = 'سیستم نتوانست قیمت زنده این ارز را از فید قیمت بخواند.';
        } elseif ($reason === '') {
            $parsed['title'] = $execution->status === 'SKIPPED' ? 'این ارز از خرید کنار گذاشته شد' : 'اجرای خرید ناموفق بود';
            $parsed['message'] = 'دلیل دقیق در سیستم ثبت نشده است.';
        } else {
            $parsed['message'] = $primary;
        }

        foreach ($extras as $extra) {
            if ($extra === 'auto-liquidated') {
                $parsed['notes'][] = 'موقعیت خریداری‌شده به‌صورت اضطراری در صرافی مرجع نقد شد تا کوین روی حساب omnibus گیر نکند.';
            } elseif (str_starts_with($extra, 'dispose_failed')) {
                $parsed['notes'][] = 'نقدسازی اضطراری هم ناموفق بود — نیاز به بررسی اپراتور.';
            } elseif (str_starts_with($extra, 'liquidation_exchange_order_id=')) {
                $parsed['notes'][] = 'شناسه سفارش نقدسازی: ' . substr($extra, strlen('liquidation_exchange_order_id='));
            } else {
                $parsed['notes'][] = $extra;
            }
        }

        $alertStyle = match ($execution->status) {
            'SKIPPED' => ['bg' => 'rgba(108,117,125,.08)', 'border' => 'rgba(108,117,125,.28)', 'icon' => 'fa-forward', 'color' => 'text-secondary'],
            default => ['bg' => 'rgba(234,84,85,.06)', 'border' => 'rgba(234,84,85,.28)', 'icon' => 'fa-triangle-exclamation', 'color' => 'text-danger'],
        };
    @endphp

    <div class="mt-3 rounded-3 p-3"
        style="background:{{ $alertStyle['bg'] }}; border:1px solid {{ $alertStyle['border'] }};">
        <div class="d-flex align-items-start gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                style="width:36px;height:36px;background:rgba(255,255,255,.85);">
                <i class="fas {{ $alertStyle['icon'] }} {{ $alertStyle['color'] }}"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <strong class="{{ $alertStyle['color'] }}">{{ $parsed['title'] }}</strong>
                    <span class="badge {{ $execution->status === 'SKIPPED' ? 'bg-secondary' : 'bg-danger' }}">
                        {{ $execution->status }}
                    </span>
                </div>

                @if ($parsed['market'] || $parsed['code'] || $parsed['message'])
                    <div class="row g-2 mb-2">
                        @if ($parsed['market'])
                            <div class="col-auto">
                                <span class="badge rounded-pill text-bg-light border font-number">
                                    <i class="fas fa-chart-line fa-xs me-1 text-muted"></i>{{ $parsed['market'] }}
                                </span>
                            </div>
                        @endif
                        @if ($parsed['code'])
                            <div class="col-auto">
                                <span class="badge rounded-pill text-bg-light border font-number">
                                    <i class="fas fa-hashtag fa-xs me-1 text-muted"></i>کد: {{ $parsed['code'] }}
                                </span>
                            </div>
                        @endif
                        @if ($parsed['message'])
                            <div class="col-12">
                                <div class="small px-2 py-1 rounded"
                                    style="background:rgba(255,255,255,.75); border:1px dashed rgba(0,0,0,.08);">
                                    <span class="text-muted">پیام صرافی / سیستم:</span>
                                    <strong class="ms-1">{{ $parsed['message'] }}</strong>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if (! empty($parsed['notes']))
                    <ul class="small text-muted mb-2 ps-3">
                        @foreach ($parsed['notes'] as $note)
                            <li>{{ $note }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($reason !== '')
                    <details class="small">
                        <summary class="text-muted" style="cursor:pointer;">متن خام خطا</summary>
                        <code class="d-block mt-2 p-2 rounded bg-white border small text-break"
                            style="white-space:pre-wrap; direction:ltr; text-align:left;">{{ $reason }}</code>
                    </details>
                @endif
            </div>
        </div>
    </div>
@endif
