@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول های صرافی')
@section('content')

    <div class="card mt-6">
        <div class="card-header pb-3">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div>
                    <h4 class="mb-1">دارایی کیف پول های صرافی</h4>
                    <small class="text-muted">نمایش نرمال و مرتب‌شده بر اساس موجودی</small>
                </div>
                <span class="badge bg-label-primary">{{ collect($exchangeWallets)->count() }} کیف پول</span>
            </div>
        </div>
        <div class="card-body pt-2">
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-regular fa-search"></i></span>
                        <input type="text" class="form-control" id="walletSearch"
                            placeholder="جستجو بر اساس نماد، شبکه یا نام ارز...">
                    </div>
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>کوین</th>
                            <th>موجودی</th>
                            <th>ارزش تقریبی (USDT)</th>
                            <th class="text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($walletRows as $row)
                            @php
                                $wallet = $row['wallet'];
                                $symbol = $row['symbol'];

                                $currencyName = $wallet->currency->name ?? $symbol;
                                $searchText = strtolower($symbol . '  ' . $currencyName);
                            @endphp
                            <tr data-search="{{ $searchText }}">
                                <td class="w-20">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="position-relative flex-shrink-0">
                                            <img src="{{ $wallet->currency->coinLogo() }}" class="rounded-circle" width="38"
                                                height="38" alt="{{ $symbol }}">

                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $symbol }}</div>
                                            <small class="text-muted">{{ $currencyName }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="w-20">
                                    <span class="font-number fw-semibold">{{ formatNumberTrimZeros($wallet->balance) }}</span>
                                    <small class="text-muted me-1">{{ $symbol }}</small>
                                </td>
                                <td>
                                    <span class="text-primary font-number">{{ formatNumber($wallet->assetValue, 2) }}</span>
                                    <small class="text-muted me-1">USDT</small>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                        <a href="{{ route('admin.wallet.detail', ['user' => config('bitexroom.user_id'), 'wallet' => $wallet->id, 'type' => 'deposit']) }}"
                                            class="btn btn-sm btn-label-primary">جزئیات</a>
                                        <a class="btn btn-sm btn-primary"
                                            href="{{ route('admin.wallet.increase-credit.form', ['currency' => $wallet->currency_symbol, 'user' => config('bitexroom.user_id')]) }}">
                                            <i class="fa-regular fa-plus"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="walletEmptyState" class="text-center py-5 d-none">
                <div class="mb-2 text-muted">
                    <i class="fa-regular fa-folder-open fa-2x"></i>
                </div>
                <h6 class="mb-1">نتیجه‌ای یافت نشد</h6>
                <small class="text-muted">فیلتر یا عبارت جستجو را تغییر دهید.</small>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const walletSearch = document.getElementById('walletSearch');
            const walletEmptyState = document.getElementById('walletEmptyState');

            function applyWalletFilters() {
                const query = (walletSearch?.value || '').toLowerCase().trim();
                const rows = Array.from(document.querySelectorAll('tbody tr[data-search]'));
                let hasVisibleRow = false;

                rows.forEach(function (row) {
                    const haystack = row.dataset.search || '';
                    const isVisible = !query || haystack.includes(query);
                    row.style.display = isVisible ? '' : 'none';

                    if (isVisible) {
                        hasVisibleRow = true;
                    }
                });

                walletEmptyState.classList.toggle('d-none', hasVisibleRow);
            }

            walletSearch?.addEventListener('input', applyWalletFilters);
        });
    </script>
@endpush

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
        'resources/assets/js/config.js',
        'resources/assets/js/wallet.js'
    ])
@endsection

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    ])
@endsection