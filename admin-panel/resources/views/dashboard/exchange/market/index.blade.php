@extends('dashboard.layout.master')
@section('title', 'مدیریت بازارها')
@section('content')

    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد بازار</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $markets->count() }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-users"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>بازار های فعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $markets->where('is_active')->count() }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-solid fa-user-check"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>بازار های غیر فعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $markets->where('is_active', false)->count() }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="fa-solid fa-user-xmark"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">جست و جو</h5>

            <form class="row mt-3 d-flex align-items-end justify-content-between"
                action="{{ route('admin.currency.index') }}" method="get">

                <div class="col-md-4 user_status ">
                    <label class="form-label" for="status">وضعیت بازار :</label>
                    <select id="status" name="type" class="form-select text-capitalize mb-md-0 ">
                        <option value="" {{ request('is_active') == '' ? 'selected' : '' }}>همه</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>فعال</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                </div>
            </form>
        </div>
    </div>



    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست بازارها ({{ $activeExchange->name }})</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{ route('admin.market.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i>
                        افزودن بازار جدید
                    </a>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>کوین</th>
                            <th>آخرین قیمت (USDT)</th>
                            <th>تغییرات (۲۴ ساعت)</th>
                            <th>قیمت صرافی (فروش به مشتری)</th>
                            <th>قیمت صرافی (خرید از مشتری)</th>
                            <th>صرافی مرجع</th>
                            <th>حداقل مقدار معامله</th>
                            <th>حداکثر مقدار معامله</th>
                            <th>وضعیت</th>
                            <th class="sticky-column">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($markets as $market)
                            <tr data-market-id="{{ $market->id }}">
                                <td class="">{{ $market->id }}</td>
                                <td class="text-heading fw-medium">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="avatar-group d-flex align-items-center">

                                            <div class="avatar avatar-sm">
                                                <img src="{{ asset($market->quoteCurrency->coinLogo()) }}"
                                                    class="rounded-circle">
                                            </div>
                                            <div class="avatar avatar-sm">
                                                <img src="{{ asset($market->baseCurrency->coinLogo()) }}"
                                                    class="rounded-circle  ">
                                            </div>
                                        </div>
                                        <div class="ms-3">{{ $market->base_currency }}/{{ $market->quote_currency }}
                                        </div>
                                    </div>
                                </td>

                                <td class="">
                                    <h4 class="font-number text-heading h5 mb-0">
                                        <span class="ms-1"
                                            data-role="last-price">{{ formatNumberTrimZeros($market->activeExchangePrice->price) }}</span>

                                        <small class="text-muted">USDT</small>
                                    </h4>
                                </td>
                                <td class="font-number">
                                    <div class="badge rounded bg-label-{{ $market->activeExchangePrice->price_change_percentage < 0 ? 'danger' : 'success' }}"
                                        data-role="price-change" dir="ltr"
                                        data-value="{{ $market->activeExchangePrice->price_change_percentage }}">
                                        {{ $market->activeExchangePrice->price_change_percentage > 0 ? '+' : '' }}{{ formatNumber($market->activeExchangePrice->price_change_percentage) }}
                                        %
                                    </div>
                                </td>
                                <td class="font-number text-heading">
                                    <div class="badge rounded bg-label-secondary me-3" data-role="profit-sell" dir="ltr"
                                        data-value="{{ $market->activeExchangePrice->exchange_profit_sell }}">
                                        {{ $market->activeExchangePrice->exchange_profit_sell > 0 ? '+' : '' }}{{ formatNumber($market->activeExchangePrice->exchange_profit_sell) }}
                                        %
                                    </div>
                                    <span class="ms-1 h5"
                                        data-role="sell-price">{{ formatNumberTrimZeros($market->activeExchangePrice->exchange_sell_price) }}</span>
                                    <small class="text-muted">USDT</small>

                                </td>
                                <td class="font-number text-heading ">
                                    <div class="badge rounded bg-label-secondary me-3" data-role="profit-buy" dir="ltr"
                                        data-value="{{ $market->activeExchangePrice->exchange_profit_buy }}">
                                        {{ $market->activeExchangePrice->exchange_profit_buy > 0 ? '+' : '' }}{{ formatNumber($market->activeExchangePrice->exchange_profit_buy) }}
                                        %
                                    </div>
                                    <span class="ms-1 h5"
                                        data-role="buy-price">{{ formatNumberTrimZeros($market->activeExchangePrice->exchange_buy_price) }}</span>
                                    <small class="text-muted">USDT</small>
                                </td>
                                <td class="fw-bold">
                                    {{ $market->activeExchangePrice->exchange->name }}
                                </td>
                                <td class="font-number ">
                                    {{ formatNumberTrimZeros($market->min_otc_amount) }}
                                </td>
                                <td class="font-number">
                                    {{ formatNumberTrimZeros($market->max_otc_amount) }}
                                </td>
                                <td class="">
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <span class="badge bg-label-{{ $market->is_active ? 'success' : 'danger' }} me-1">
                                            {{ $market->is_active ? 'فعال' : 'غیرفعال' }}
                                        </span>
                                        @if (!$market->price_update_enabled)
                                            <span class="badge bg-label-danger me-1">
                                                عدم بروزرسانی قیمت
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="sticky-column">
                                    <div class="">
                                        <a class="text-secondary me-2"
                                            href="{{ route('admin.market.edit', ['market' => $market->id]) }}">
                                            <i class="fa-light fa-pen-to-square fa-lg"></i>
                                        </a>
                                        <a class="p-0 toggle-home-btn" style="cursor: pointer;" data-market-id="{{ $market->id }}"
                                            data-bs-toggle="tooltip"
                                            data-url="{{ route('admin.market.toggle-home', ['market' => $market->id]) }}"
                                            title="{{ $market->show_in_home ? 'حذف از صفحه اصلی' : 'نمایش در صفحه اصلی' }}">
                                            <i
                                                class="{{ $market->show_in_home ? 'fa-solid text-warning' : 'fa-light text-secondary' }} fa-star fa-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('vendor-script')
    <script>
        $(document).ready(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
    <script>

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.Echo === 'undefined') {
                console.warn('Echo is not initialized. Check VITE_REVERB/VITE_PUSHER env vars and frontend build.');
                return;
            }

            const marketRows = document.querySelectorAll('tr[data-market-id]');
            const marketState = new Map();

            const toNumber = (value) => {
                if (value === null || typeof value === 'undefined') {
                    return null;
                }

                const normalized = String(value).replace(/,/g, '').trim();
                if (normalized === '') {
                    return null;
                }

                const numeric = Number(normalized);
                return Number.isFinite(numeric) ? numeric : null;
            };

            const formatPrice = (value) => {
                const numeric = Number(value);
                if (!Number.isFinite(numeric)) {
                    return '-';
                }

                if (numeric === 0) {
                    return '0';
                }

                const absolute = Math.abs(numeric);
                if (absolute < 0.00000001) {
                    const decimals = Math.min(20, Math.max(8, Math.ceil(-Math.log10(absolute)) + 4));
                    return numeric
                        .toFixed(decimals)
                        .replace(/\.0+$/, '')
                        .replace(/(\.\d*?)0+$/, '$1');
                }

                return numeric.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 8,
                });
            };

            const formatPercent = (value) => {
                const numeric = Number(value);
                if (!Number.isFinite(numeric)) {
                    return null;
                }

                return `${numeric > 0 ? '+' : ''}${numeric.toFixed(2)} %`;
            };

            const applyBadgeState = (badgeElement, value) => {
                if (!badgeElement) {
                    return;
                }

                const numeric = Number(value);
                if (!Number.isFinite(numeric)) {
                    return;
                }

                badgeElement.classList.remove('bg-label-danger', 'bg-label-success');
                badgeElement.classList.add(numeric < 0 ? 'bg-label-danger' : 'bg-label-success');
                badgeElement.setAttribute('data-value', String(numeric));
            };

            const applyValueAnimation = (element, direction) => {
                if (!element || direction === 0) {
                    return;
                }

                const classToAdd = direction > 0 ? 'price-flash-up' : 'price-flash-down';

                element.classList.remove('price-flash-up', 'price-flash-down');
                void element.offsetWidth;
                element.classList.add(classToAdd);

                window.setTimeout(() => {
                    element.classList.remove('price-flash-up', 'price-flash-down');
                }, 2000);
            };

            const updateValue = ({
                row,
                key,
                incomingValue,
                element,
                formatFn,
                afterUpdate,
            }) => {
                const numericValue = toNumber(incomingValue);
                if (numericValue === null) {
                    return;
                }

                const previousState = marketState.get(row) ?? {};
                const previousValue = typeof previousState[key] === 'number' ? previousState[key] : null;

                if (element) {
                    element.textContent = formatFn(numericValue);
                }

                if (typeof afterUpdate === 'function') {
                    afterUpdate(numericValue);
                }

                if (previousValue !== null && previousValue !== numericValue) {
                    applyValueAnimation(element, numericValue > previousValue ? 1 : -1);
                }

                previousState[key] = numericValue;
                marketState.set(row, previousState);
            };

            marketRows.forEach((row) => {
                const marketId = row.getAttribute('data-market-id');
                if (!marketId) {
                    return;
                }

                const initialChangeBadge = row.querySelector('[data-role="price-change"]');
                const initialProfitSellBadge = row.querySelector('[data-role="profit-sell"]');
                const initialProfitBuyBadge = row.querySelector('[data-role="profit-buy"]');

                marketState.set(row, {
                    last: toNumber(row.querySelector('[data-role="last-price"]')?.textContent),
                    sell: toNumber(row.querySelector('[data-role="sell-price"]')?.textContent),
                    buy: toNumber(row.querySelector('[data-role="buy-price"]')?.textContent),
                    change: toNumber(initialChangeBadge?.getAttribute('data-value')),
                    profitSell: toNumber(initialProfitSellBadge?.getAttribute('data-value')),
                    profitBuy: toNumber(initialProfitBuyBadge?.getAttribute('data-value')),
                });

                window.Echo.channel(`market.${marketId}`).listen('MarketUpdated', (event) => {
                    const lastPriceElement = row.querySelector('[data-role="last-price"]');
                    const sellPriceElement = row.querySelector('[data-role="sell-price"]');
                    const buyPriceElement = row.querySelector('[data-role="buy-price"]');

                    const changeBadge = row.querySelector('[data-role="price-change"]');


                    if (typeof event.last !== 'undefined' && lastPriceElement) {
                        updateValue({
                            row,
                            key: 'last',
                            incomingValue: event.last,
                            element: lastPriceElement,
                            formatFn: formatPrice,
                        });
                    }

                    if (typeof event.exchange_sell_price !== 'undefined' && sellPriceElement) {
                        updateValue({
                            row,
                            key: 'sell',
                            incomingValue: event.exchange_sell_price,
                            element: sellPriceElement,
                            formatFn: formatPrice,
                        });
                    }

                    if (typeof event.exchange_buy_price !== 'undefined' && buyPriceElement) {
                        updateValue({
                            row,
                            key: 'buy',
                            incomingValue: event.exchange_buy_price,
                            element: buyPriceElement,
                            formatFn: formatPrice,
                        });
                    }

                    if (typeof event.price_change_percentage !== 'undefined' && changeBadge) {
                        updateValue({
                            row,
                            key: 'change',
                            incomingValue: event.price_change_percentage,
                            element: changeBadge,
                            formatFn: formatPercent,
                            afterUpdate: (value) => applyBadgeState(changeBadge, value),
                        });
                    }

                });
            });
            // Star toggle
            document.querySelectorAll('.toggle-home-btn').forEach((btn) => {
                btn.addEventListener('click', function () {
                    const url = this.dataset.url;
                    const icon = this.querySelector('i');

                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    })
                        .then((res) => res.json())
                        .then((data) => {
                            if (data.show_in_home) {
                                icon.classList.remove('fa-light', 'text-secondary');
                                icon.classList.add('fa-solid', 'text-warning');
                                this.title = 'حذف از صفحه اصلی';
                            } else {
                                icon.classList.remove('fa-solid', 'text-warning');
                                icon.classList.add('fa-light', 'text-secondary');
                                this.title = 'نمایش در صفحه اصلی';
                            }
                        });
                });
            });
        });
    </script>
@endsection

@section('vendor-style')
    <style>
        .table-responsive {
            overflow-x: auto;
            position: relative;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .sticky-column {
            position: sticky;
            left: 0;
            background-color: #fff !important;
            z-index: 2 !important;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1) !important;
        }

        .table thead .sticky-column {
            z-index: 3 !important;
            background-color: #fff !important;
        }

        .table tbody tr:hover .sticky-column {
            background-color: #f8f9fa !important;
        }

        [data-role="last-price"],
        [data-role="sell-price"],
        [data-role="buy-price"],
        [data-role="price-change"],
        [data-role="profit-sell"],
        [data-role="profit-buy"] {
            transition: transform 0.25s ease, filter 0.25s ease;
            will-change: transform, filter;
        }

        @keyframes priceFlashUp {
            0% {
                color: #16c784;
                filter: drop-shadow(0 0 0 rgba(25, 135, 84, 0));
                transform: scale(1);
            }

            35% {
                color: #16c784;
                filter: drop-shadow(0 0 0.15rem rgba(25, 135, 84, 0.35));
                transform: scale(1.18);
            }

            100% {
                color: inherit;
                filter: drop-shadow(0 0 0 rgba(25, 135, 84, 0));
                transform: scale(1);
            }
        }

        @keyframes priceFlashDown {
            0% {
                color: #dc3545;
                filter: drop-shadow(0 0 0 rgba(220, 53, 69, 0));
                transform: scale(1);
            }

            35% {
                color: #dc3545;
                filter: drop-shadow(0 0 0.15rem rgba(220, 53, 69, 0.35));
                transform: scale(1.18);
            }

            100% {
                color: inherit;
                filter: drop-shadow(0 0 0 rgba(220, 53, 69, 0));
                transform: scale(1);
            }
        }

        .price-flash-up {
            animation: priceFlashUp 20s ease;
        }

        .price-flash-down {
            animation: priceFlashDown 20s ease;
        }
    </style>
@endsection
