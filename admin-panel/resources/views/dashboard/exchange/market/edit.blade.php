@extends('dashboard.layout.master')
@section('title', 'ویرایش بازار')
@section('content')

    <div class="row g-6">

        <div class="col-xl-4 col-sm-6">
            <div class="card h-100">
                <div class="card-header pb-0">

                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3 card-title">قیمت مرجع بازار ({{ $market->activeExchange->name }})</h5>
                        <div class="avatar-group d-flex align-items-center assigned-avatar">
                            <div class="me-8">{{ $market->base_currency }}/{{ $market->quote_currency }}</div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->quoteCurrency->coinLogo()) }}" class="rounded-circle ">
                            </div>
                            <div class="avatar avatar-md ">
                                <img src="{{ asset($market->baseCurrency->coinLogo()) }}" class="rounded-circle  ">
                            </div>

                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center my-3 justify-content-end  font-number">
                        <div class="badge rounded bg-label-{{ $market->activeExchangePrice->price_change_percentage < 0 ? 'danger' : 'success' }}"
                            dir="ltr">
                            {{ $market->activeExchangePrice->price_change_percentage > 0 ? '+' : '' }}{{ formatNumber($market->activeExchangePrice->price_change_percentage) }}
                            %
                        </div>
                        <h3 class="mb-0">
                            ${{ formatNumberTrimZeros($market->activeExchangePrice->price) }}
                        </h3>
                    </div>
                </div>
                <div class="card-body px-0">
                    <div id="marketWeeklyChart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card h-100">
                <div class="card-header pb-0">

                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3 card-title">قیمت ارايه شده صرافی (فروش به مشتری)
                            <div class="badge rounded bg-label-{{ $market->activeExchangePrice->exchange_profit_sell < 0 ? 'danger' : 'success' }}"
                                dir="ltr">
                                {{ $market->activeExchangePrice->exchange_profit_sell > 0 ? '+' : '' }}{{ formatNumberTrimZeros($market->activeExchangePrice->exchange_profit_sell) }}
                                %
                            </div>
                        </h5>
                        <div class="avatar-group d-flex align-items-center assigned-avatar">
                            <div class="me-8">{{ $market->base_currency }}/{{ $market->quote_currency }}</div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->quoteCurrency->coinLogo()) }}" class="rounded-circle ">
                            </div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->baseCurrency->coinLogo()) }}" class="rounded-circle  ">
                            </div>

                        </div>
                    </div>


                    <div class="d-flex gap-2 align-items-center my-3 justify-content-end font-number">
                        <div class="badge rounded bg-label-{{ $market->activeExchangePrice->price_change_percentage < 0 ? 'danger' : 'success' }}"
                            dir="ltr">
                            {{ $market->activeExchangePrice->price_change_percentage > 0 ? '+' : '' }}{{ formatNumber($market->activeExchangePrice->price_change_percentage) }}
                            %
                        </div>

                        <h3 class="mb-0">
                            ${{ formatNumberTrimZeros($market->activeExchangePrice->exchange_sell_price) }}
                        </h3>

                    </div>
                </div>
                <div class="card-body px-0">
                    {{-- <div id="exchangePrice"></div> --}}
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card h-100">
                <div class="card-header pb-0">

                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3 card-title">قیمت ارايه شده صرافی (خرید از مشتری)
                            <div class="badge rounded bg-label-{{ $market->activeExchangePrice->exchange_profit_buy < 0 ? 'danger' : 'success' }}"
                                dir="ltr">
                                {{ $market->activeExchangePrice->exchange_profit_buy > 0 ? '+' : '' }}{{ formatNumberTrimZeros($market->activeExchangePrice->exchange_profit_buy) }}
                                %
                            </div>
                        </h5>
                        <div class="avatar-group d-flex align-items-center assigned-avatar">
                            <div class="me-8">{{ $market->base_currency }}/{{ $market->quote_currency }}</div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->quoteCurrency->coinLogo()) }}" class="rounded-circle ">
                            </div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->baseCurrency->coinLogo()) }}" class="rounded-circle  ">
                            </div>

                        </div>
                    </div>


                    <div class="d-flex gap-2 align-items-center my-3 justify-content-end font-number">
                        <div class="badge rounded bg-label-{{ $market->activeExchangePrice->price_change_percentage < 0 ? 'danger' : 'success' }}"
                            dir="ltr">
                            {{ $market->activeExchangePrice->price_change_percentage > 0 ? '+' : '' }}{{ formatNumber($market->activeExchangePrice->price_change_percentage) }}
                            %
                        </div>
                        <h3 class="mb-0">
                            ${{ formatNumberTrimZeros($market->activeExchangePrice->exchange_buy_price) }}
                        </h3>

                    </div>
                </div>
                <div class="card-body px-0">
                    {{-- <div id="exchangePrice"></div> --}}
                </div>
            </div>
        </div>
        <div class="col-12" id="reference-support">
            <div class="card ref-support-card is-pending mb-0" role="status">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="flex-grow-1">
                        <div class="h6 mb-1">{{ $market->base_currency }}/{{ $market->quote_currency }}</div>
                        <div class="text-muted small mb-0">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            در حال بررسی پشتیبانی این بازار روی صرافی مرجع...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    بازار {{ $market->baseCurrency->symbol }}/{{ $market->quoteCurrency->symbol }}
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.market.update', ['market' => $market]) }}" method="post">
                        @method('PATCH')
                        @csrf
                        <h6>اطلاعات بازار</h6>
                        <div class="row">
                            <div class="col-md-8">

                                <div class="form-group">
                                    <label class="form-label" for="exchange">صرافی مرجع</label>
                                    <select name="exchange_id" id="exchange" class="select2 form-control">
                                        @foreach ($exchanges as $exchange)
                                            <option @if ($exchange->id === $market->activeExchangePrice->exchange_id) selected @endif
                                                value="{{ $exchange->id }}">{{ $exchange->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('exchange_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="w-100 mb-4"></div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="symbol">کوین پایه</label>
                                    <input name="symbol" id="symbol" class="form-control"
                                        value="{{ $market->baseCurrency->symbol }}" required disabled>
                                    @error('Symbol')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="symbol">ارز متقابل </label>
                                    <input name="symbol" id="symbol" class="form-control"
                                        value="{{ $market->quoteCurrency->symbol }}" required disabled>
                                    @error('Symbol')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="min_otc_amount">حداقل مقدار معامله OTC در این
                                        بازار</label>
                                    <input name="min_otc_amount" id="min_otc_amount" class="form-control font-number"
                                        dir="ltr" placeholder="حداقل مقدار معامله OTC در این بازار"
                                        value="{{ formatNumberTrimZeros($market->min_otc_amount) }}" required>
                                    @error('min_otc_amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="max_otc_amount">حداکثر مقدار معامله OTC در این
                                        بازار</label>
                                    <input name="max_otc_amount" id="max_otc_amount" class="form-control font-number"
                                        dir="ltr" placeholder="حداکثر مقدار معامله  OTC در این بازار"
                                        value="{{ formatNumberTrimZeros($market->max_otc_amount) }}" required>
                                    @error('max_otc_amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="min_trade_amount">حداقل مقدار معامله SPOT در این
                                        بازار</label>
                                    <input name="min_trade_amount" id="min_trade_amount" class="form-control font-number"
                                        dir="ltr" placeholder="حداقل مقدار معامله اسپات در این بازار"
                                        value="{{ formatNumberTrimZeros($market->min_trade_amount) }}" required>
                                    @error('min_trade_amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="max_trade_amount">حداکثر مقدار معامله SPOT در این
                                        بازار</label>
                                    <input name="max_trade_amount" id="max_trade_amount" class="form-control font-number"
                                        dir="ltr" placeholder="حداکثر مقدار معامله اسپات در این بازار"
                                        value="{{ formatNumberTrimZeros($market->max_trade_amount) }}" required>
                                    @error('max_trade_amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_profit_sell">سود صرافی از محل خرید از
                                        صرافی
                                        مرجع (فروش به مشتری) (درصد)</label>
                                    <input name="exchange_profit_sell" id="exchange_profit_sell"
                                        class=" form-control font-number " dir="ltr"
                                        placeholder="سود صرافی از محل خرید از صرافی مرجع( فروش به مشتری)"
                                        value="{{ formatNumber($market->activeExchangePrice->exchange_profit_sell, 2) }}"
                                        required>
                                    @error('exchange_profit_sell')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_profit_buy">سود صرافی از محل فروش به صرافی
                                        مرجع (خرید از مشتری)</label>
                                    <input name="exchange_profit_buy" id="exchange_profit_buy"
                                        class=" form-control font-number " dir="ltr"
                                        placeholder="سود صرافی از محل فروش به صرافی مرجع (خرید از مشتری)"
                                        value="{{ formatNumber($market->activeExchangePrice->exchange_profit_buy, 2) }}"
                                        required>
                                    @error('exchange_profit_buy')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input" name="is_active" value="1"
                                        {{ $market->is_active ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">وضعیت بازار
                                        @include('dashboard.exchange.market.partials.switch-state', [
                                            'on' => $market->is_active,
                                        ])
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input" name="ref_exchange_sell_enabled"
                                        value="1" {{ $market->ref_exchange_sell_enabled ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">وضعیت فروش در صرافی مرجع در هر معامله OTC/SPOT
                                        @include('dashboard.exchange.market.partials.switch-state', [
                                            'on' => $market->ref_exchange_sell_enabled,
                                        ])
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mt-5">
                                <label class="switch switch-lg">
                                    <input type="checkbox" class="switch-input" name="price_update_enabled"
                                        value="1" {{ $market->price_update_enabled ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">بارگیری قیمت از صرافی مرجع
                                        @include('dashboard.exchange.market.partials.switch-state', [
                                            'on' => $market->price_update_enabled,
                                        ])

                                        <small class="text-muted d-block">
                                            با فعال کردن این گزینه، قیمت بازار بعد از یک دقیقه شروع به بارگیری از صرافی
                                            مرجع
                                            می کند.
                                        </small>
                                    </span>

                                </label>
                            </div>
                        </div>

                        <div class=" d-flex justify-content-start mt-5">

                            <button class="btn btn-primary ">
                                <i class="fa fa-save mx-2"></i>
                                ذخیره
                            </button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/js/config.js', 'resources/assets/js/market.js', 'resources/assets/vendor/js/forms-selects.js', 'resources/assets/vendor/libs/select2/select2.js'])

@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
    <style>
        .ref-support-card {
            border: 1px solid rgba(67, 89, 113, .12);
            border-inline-start: 3px solid #c5c9d4;
            box-shadow: none;
        }

        .ref-support-card.is-ok {
            border-inline-start-color: #28c76f;
            background: linear-gradient(to left, rgba(40, 199, 111, .08), #fff 46%);
        }

        .ref-support-card.is-bad {
            border-inline-start-color: #ea5455;
            background: linear-gradient(to left, rgba(234, 84, 85, .08), #fff 46%);
        }

        .ref-support-card.is-warn {
            border-inline-start-color: #ff9f43;
            background: linear-gradient(to left, rgba(255, 159, 67, .1), #fff 46%);
        }

        .switch-state {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin-inline-start: .5rem;
            padding: .1rem .55rem;
            border-radius: 50rem;
            font-size: .8125rem;
            font-weight: 500;
            vertical-align: middle;
        }

        .switch-state-dot {
            width: .5rem;
            height: .5rem;
            border-radius: 50%;
            background: currentColor;
        }

        .switch-state.is-on {
            color: #28c76f;
            background: rgba(40, 199, 111, .12);
        }

        .switch-state.is-off {
            color: #ea5455;
            background: rgba(234, 84, 85, .12);
        }

        .ref-support-card .ref-support-refresh {
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
    </style>
@endsection

@push('scripts')
    <script>
        // Make marketHistoryData global so market.js can access it
        window.marketHistoryData = @json(
            $marketHistory->map(function ($item) {
                return [
                    'timestamp' => $item->timestamp->format('Y-m-d H:i:s'),
                    'close' => (float) $item->close,
                ];
            }));

        // Pass price change percentage for chart color
        window.priceChangePercentage = {{ $market->activeExchangePrice->price_change_percentage ?? 0 }};

        $(document).ready(function() {
            const supportUrl = @json(route('admin.market.min-otc', ['market' => $market->id]));
            const supportBox = $('#reference-support');
            const pairLabel = @json($market->base_currency . '/' . $market->quote_currency);
            const baseSymbol = @json($market->base_currency);
            const quoteSymbol = @json($market->quote_currency);

            function refreshButton(tone) {
                return $('<button>', {
                    type: 'button',
                    class: 'btn btn-sm btn-outline-' + tone + ' ref-support-refresh flex-shrink-0',
                    id: 'refresh-exchange-min',
                    title: 'بررسی دوباره',
                    html: '<i class="fa fa-refresh"></i>',
                });
            }

            function supportCard(tone, role) {
                return $('<div>', {
                    class: 'card ref-support-card ' + tone + ' mb-0',
                    role: role,
                });
            }

            function renderLoading() {
                const card = supportCard('is-pending', 'status');
                const body = $('<div>', {
                    class: 'card-body d-flex align-items-center gap-3 py-3'
                });
                const meta = $('<div>', {
                    class: 'flex-grow-1'
                });
                meta.append($('<div>', {
                    class: 'h6 mb-1',
                    text: pairLabel
                }));
                meta.append(
                    $('<div>', {
                        class: 'text-muted small mb-0'
                    }).append(
                        '<span class="spinner-border spinner-border-sm" role="status"></span> ',
                        document.createTextNode('در حال بررسی پشتیبانی این بازار روی صرافی مرجع...')
                    )
                );
                body.append(meta);
                card.append(body);
                supportBox.empty().append(card);
            }

            function renderSupported(response) {
                const card = supportCard('is-ok', 'status');
                const body = $('<div>', {
                    class: 'card-body d-flex align-items-center gap-3 py-3'
                });
                const meta = $('<div>', {
                    class: 'flex-grow-1'
                });
                const title = $('<div>', {
                    class: 'd-flex align-items-center flex-wrap gap-2 mb-1'
                });
                title.append(
                    $('<span>', {
                        class: 'h6 mb-0',
                        text: pairLabel
                    }),
                    $('<span>', {
                        class: 'badge bg-label-primary',
                        text: response.exchange || 'صرافی مرجع'
                    }),
                    $('<span>', {
                        class: 'badge bg-label-success',
                        text: 'پشتیبانی می‌شود'
                    })
                );
                meta.append(
                    title,
                    $('<div>', {
                        class: 'text-muted small mb-0',
                        text: 'حداقل مقدار معامله این بازار را برابر یا بزرگ‌تر از عدد صرافی مرجع بگذارید.',
                    })
                );

                const details = $('<div>', {
                    class: 'd-flex flex-wrap gap-4 small mt-2'
                });
                [
                    ['حداقل مقدار هر سفارش', response.formatted_min_qty, baseSymbol],
                    ['حداقل ارزش هر سفارش', response.formatted_min_notional, quoteSymbol],
                    ['قیمت فعلی', response.formatted_price, quoteSymbol],
                ].forEach(function([label, value, unit]) {
                    if (!value) {
                        return;
                    }
                    details.append($('<div>').append(
                        $('<span>', {
                            class: 'text-muted',
                            text: label + ': '
                        }),
                        $('<span>', {
                            class: 'fw-semibold font-number',
                            dir: 'ltr',
                            text: value + ' ' + unit
                        })
                    ));
                });
                if (details.children().length) {
                    meta.append(details);
                }

                if (response.limited_by === 'min_notional') {
                    meta.append($('<div>', {
                        class: 'small text-warning mt-2',
                        text: 'صرافی مرجع سفارش با ارزش کمتر از ' + response.formatted_min_notional + ' ' + quoteSymbol +
                            ' را رد می‌کند، پس ' + response.formatted_min_qty + ' ' + baseSymbol + ' کافی نیست. عدد پیشنهادی = (' +
                            response.formatted_min_notional + ' ' + quoteSymbol + ' + ' + response.min_notional_margin_percent +
                            '٪ حاشیه برای نوسان قیمت) ÷ ' + response.formatted_price + ' که به گام مقدار صرافی به بالا گرد شده است.',
                    }));
                }

                const amount = $('<div>', {
                    class: 'text-end flex-shrink-0'
                });
                amount.append(
                    $('<div>', {
                        class: 'text-muted small',
                        text: response.limited_by === 'min_notional' ? 'حداقل معامله پیشنهادی' : 'حداقل معامله'
                    }),
                    $('<div>', {
                        class: 'h4 mb-0 font-number',
                        dir: 'ltr'
                    }).append(
                        document.createTextNode((response.formatted_amount || '') + ' '),
                        $('<small>', {
                            class: 'text-muted fs-6',
                            text: baseSymbol
                        })
                    )
                );
                body.append(meta, amount, refreshButton('success'));
                card.append(body);
                supportBox.empty().append(card);
            }

            function renderUnsupported(response) {
                const card = supportCard('is-bad', 'alert');
                const body = $('<div>', {
                    class: 'card-body d-flex align-items-center gap-3 py-3'
                });
                const meta = $('<div>', {
                    class: 'flex-grow-1'
                });
                const title = $('<div>', {
                    class: 'd-flex align-items-center flex-wrap gap-2 mb-1'
                });
                title.append(
                    $('<span>', {
                        class: 'h6 mb-0',
                        text: pairLabel
                    }),
                    $('<span>', {
                        class: 'badge bg-label-primary',
                        text: response.exchange || 'صرافی مرجع'
                    }),
                    $('<span>', {
                        class: 'badge bg-label-danger',
                        text: 'پشتیبانی نمی‌شود'
                    })
                );
                meta.append(
                    title,
                    $('<div>', {
                        class: 'text-muted small mb-0',
                        text: 'این جفت‌ارز در صرافی مرجع وجود ندارد. قیمت، حداقل مقدار معامله و سایر اطلاعات از این صرافی دریافت نمی‌شود. صرافی مرجع را عوض کنید، یا بارگیری قیمت را خاموش کنید.',
                    })
                );
                body.append(meta, refreshButton('danger'));
                card.append(body);
                supportBox.empty().append(card);
            }

            function renderWarning(message) {
                const card = supportCard('is-warn', 'alert');
                const body = $('<div>', {
                    class: 'card-body d-flex align-items-center gap-3 py-3'
                });
                const meta = $('<div>', {
                    class: 'flex-grow-1'
                });
                const title = $('<div>', {
                    class: 'd-flex align-items-center flex-wrap gap-2 mb-1'
                });
                title.append(
                    $('<span>', {
                        class: 'h6 mb-0',
                        text: pairLabel
                    }),
                    $('<span>', {
                        class: 'badge bg-label-warning',
                        text: 'بررسی نشد'
                    })
                );
                meta.append(
                    title,
                    $('<div>', {
                        class: 'text-muted small mb-0',
                        text: message || 'خطا در دریافت اطلاعات از صرافی مرجع',
                    })
                );
                body.append(meta, refreshButton('warning'));
                card.append(body);
                supportBox.empty().append(card);
            }

            function fetchExchangeSupport() {
                renderLoading();

                $.ajax({
                    url: supportUrl,
                    method: 'GET',
                    data: {
                        exchange_id: $('#exchange').val()
                    },
                    success: function(response) {
                        if (response.supported === true) {
                            renderSupported(response);
                            return;
                        }
                        if (response.supported === false) {
                            renderUnsupported(response);
                            return;
                        }
                        renderWarning(response.message);
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message ?
                            xhr.responseJSON.message :
                            'خطا در دریافت اطلاعات از صرافی مرجع';
                        renderWarning(message);
                    },
                });
            }

            fetchExchangeSupport();

            $(document).on('click', '#refresh-exchange-min', function() {
                fetchExchangeSupport();
            });

            $('#exchange').on('change', function() {
                fetchExchangeSupport();
            });

            $('.switch-input').on('change', function() {
                const state = $(this).closest('.switch').find('.switch-state');
                state.toggleClass('is-on', this.checked).toggleClass('is-off', !this.checked);
                state.find('.switch-state-text').text(this.checked ? 'فعال' : 'غیرفعال');
            });
        });
    </script>
@endpush
