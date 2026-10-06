@php
    $canceledSells = $execution->sellOrders->where('cancel_reason', \App\Models\Bot\BotSellOrder::CANCEL_ADMIN_CURRENCY);
    $runs = $canceledSells->pluck('currencyCancellation')->filter()->unique('id');
@endphp

@foreach ($runs as $run)
    @php
        $runSells = $canceledSells->where('bot_currency_cancellation_id', $run->id);
        $settlements = $runSells->pluck('settlement')->filter();
        $netPnl = $settlements->sum(fn ($s) => (float) $s->net_pnl);
        $perfFee = $settlements->sum(fn ($s) => (float) $s->performance_fee);
    @endphp
    <div class="mt-3 rounded-3 p-3" style="background:rgba(255,159,67,.07); border:1px solid rgba(255,159,67,.35);">
        <div class="d-flex align-items-start gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                style="width:36px;height:36px;background:rgba(255,255,255,.85);">
                <i class="fas fa-ban text-warning"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <strong class="text-warning">لغو ارز {{ strtoupper((string) $execution->currency?->symbol) }} توسط ادمین</strong>
                    <span class="badge bg-label-secondary font-number">#{{ $run->id }}</span>
                    <span class="badge rounded-pill text-bg-light border small">{{ $run->exchangeModeLabel() }}</span>
                </div>

                <div class="small px-2 py-1 rounded mb-2"
                    style="background:rgba(255,255,255,.75); border:1px dashed rgba(0,0,0,.08);">
                    <span class="text-muted">دلیل لغو:</span>
                    <strong class="ms-1 text-break">{{ $run->reason }}</strong>
                </div>

                <ul class="small text-muted mb-0 ps-3">
                    <li>
                        {{ $runSells->count() }} پله فروش باز این ارز لغو و موجودی به کیف پول ربات کاربر برگشت.
                    </li>
                    @if ($run->settlement_price !== null)
                        <li>
                            قیمت تسویه: <span class="font-number">{{ formatNumberTrimZeros($run->settlement_price, 8) }}</span> USDT
                            ({{ $run->price_source === \App\Models\Bot\BotCurrencyCancellation::PRICE_MARKET_SELL ? 'میانگین فروش واقعی در صرافی مرجع' : 'قیمت لحظه‌ای' }})
                        </li>
                    @endif
                    <li>
                        @if ($netPnl > 0)
                            سود خالص برگشتی: <span class="font-number text-success">{{ formatNumberTrimZeros($netPnl, 8) }}</span> USDT
                            — کارمزد عملکرد: <span class="font-number">{{ formatNumberTrimZeros($perfFee, 8) }}</span> USDT
                        @else
                            ارز در سود نبود؛ دقیقاً اصل سرمایه به کاربر برگشت.
                        @endif
                    </li>
                    @if ($run->sell_error)
                        <li class="text-danger">فروش در صرافی مرجع ناموفق بود و تسویه با قیمت لحظه‌ای انجام شد.</li>
                    @endif
                    <li>
                        {{ $run->admin_label ?: 'ادمین' }} —
                        {{ \App\Helpers\DateFormatter::convertToPersianDate($run->created_at, '%Y/%m/%d H:i') }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endforeach
