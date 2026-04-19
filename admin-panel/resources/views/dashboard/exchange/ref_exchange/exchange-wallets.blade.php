@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول های صرافی - ' . $exchangeName)
@section('content')

    @php
        $supportedSymbols = collect($supportedAssets)
            ->pluck('ccy')
            ->filter()
            ->map(fn($symbol) => strtoupper((string) $symbol))
            ->unique()
            ->values();

        $currenciesBySymbol = \App\Models\Currency::whereIn('symbol', $supportedSymbols)
            ->get()
            ->keyBy(fn($currency) => strtoupper((string) $currency->symbol));

        $currencyIds = $currenciesBySymbol->pluck('id')->values();

        $baseChainsByCurrencyId = \App\Models\CurrencyChain::with('currency')
            ->whereIn('currency_id', $currencyIds)
            ->where('is_base_coin', true)
            ->get()
            ->groupBy('currency_id')
            ->map(fn($group) => $group->first());

        $currencyHasChainsMap = \App\Models\Currency::whereIn('symbol', $supportedSymbols)
            ->withCount('chains')
            ->get()
            ->mapWithKeys(fn($currency) => [strtoupper((string) $currency->symbol) => ($currency->chains_count ?? 0) > 0]);

        $supportedRows = collect($supportedAssets)
            ->map(function ($asset) use ($currenciesBySymbol, $baseChainsByCurrencyId) {
                $symbol = strtoupper((string) ($asset->ccy ?? ''));
                $currency = $currenciesBySymbol->get($symbol);
                $baseChain = $currency ? $baseChainsByCurrencyId->get($currency->id) : null;

                return [
                    'asset' => $asset,
                    'symbol' => $symbol,
                    'chain' => strtoupper((string) ($baseChain?->chain?->value ?? $baseChain?->chain ?? 'OTHER')),
                    'chain_logo' => $baseChain && $baseChain->currency ? $baseChain->currency->coinLogo() : null,
                ];
            })
            ->sortBy(fn($row) => strtolower((string) $row['symbol']))
            ->values();

        $unsupportedSorted = collect($unsupportedAssets)
            ->sortBy(fn($asset) => strtolower((string) ($asset->ccy ?? '')))
            ->values();
    @endphp

    <div class="card">
        <div class="card-header pb-3">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div>
                    <h4 class="mb-1">دارایی در صرافی مرجع ({{ $exchangeName }})</h4>
                    <small class="text-muted">نسخه بهینه برای مدیریت تعداد زیاد دارایی</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-success">{{ collect($supportedAssets)->count() }} پشتیبانی‌شده</span>
                    <span class="badge bg-label-warning">{{ collect($unsupportedAssets)->count() }} پشتیبانی‌نشده</span>
                </div>
            </div>
        </div>

        <div class="card-body pt-2">
            <div class="row g-3 mb-4">
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-regular fa-search"></i></span>
                        <input type="text" class="form-control" id="assetSearch"
                            placeholder="جستجو بر اساس نماد یا نوع دارایی...">
                    </div>
                </div>
                <div class="col-md-5">
                    <select class="form-select" id="assetTypeFilter">
                        <option value="all">همه دارایی‌ها</option>
                        <option value="supported">دارایی‌های پشتیبانی‌شده</option>
                        <option value="UNSUPPORTED">پشتیبانی‌نشده</option>
                    </select>
                </div>
            </div>

            <div class="mb-4" id="supportedSection">
                <h6 class="mb-3 text-success">دارایی‌های پشتیبانی‌شده</h6>
                <div class="card border mb-4 asset-group" data-group-type="supported">
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>کوین</th>
                                    <th>شبکه</th>
                                    <th>موجودی آزاد</th>
                                    <th>موجودی مسدود</th>
                                    <th class="text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($supportedRows as $row)
                                    @php
                                        $asset = $row['asset'];
                                        $symbol = $row['symbol'];
                                        $chain = $row['chain'];
                                        $chainLogo = $row['chain_logo'];
                                        $searchText = strtolower($symbol . ' supported');
                                        $canWithdraw = $currencyHasChainsMap[$symbol] ?? false;
                                    @endphp
                                    <tr data-search="{{ $searchText }}" data-group-type="supported">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative flex-shrink-0">
                                                    <img src="{{ $asset->coinLogo }}" class="rounded-circle" width="38"
                                                        height="38" alt="{{ $symbol }}">
                                                    @if (!empty($chainLogo))
                                                        <img src="{{ $chainLogo }}"
                                                            class="position-absolute rounded-circle border border-white" width="18"
                                                            height="18" style="bottom: -2px; right: -2px; background: #fff;"
                                                            alt="{{ $chain }}" title="{{ $chain }}">
                                                    @endif
                                                </div>
                                                <div class="fw-semibold">{{ $symbol }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $chain }}</small>
                                        </td>
                                        <td>
                                            <span
                                                class="font-number fw-semibold">{{ formatNumberTrimZeros($asset->available) }}</span>
                                            <small class="text-muted ms-1">{{ $symbol }}</small>
                                        </td>
                                        <td>
                                            <span
                                                class="text-danger fw-semibold font-number">{{ formatNumberTrimZeros($asset->frozen) }}</span>
                                            <small class="text-danger ms-1">{{ $symbol }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if ($canWithdraw)
                                                <a href="{{ route('admin.ref-exchange.assets-gathering-to-hd-wallet.create', ['currency_symbol' => $symbol, 'amount' => $asset->available, 'exchange' => $exchange]) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fa-regular fa-arrow-up-right me-1"></i>
                                                    برداشت
                                                </a>
                                            @else
                                                <span class="text-muted">امکان برداشت نیست</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mb-2" id="unsupportedSection">
                <h6 class="mb-3 text-warning">دارایی‌های پشتیبانی‌نشده</h6>
                <div class="card border asset-group" data-group-type="unsupported">
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>کوین</th>
                                    <th>وضعیت</th>
                                    <th>موجودی آزاد</th>
                                    <th>موجودی مسدود</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($unsupportedSorted as $asset)
                                    @php
                                        $symbol = strtoupper((string) ($asset->ccy ?? ''));
                                        $searchText = strtolower($symbol . ' unsupported');
                                    @endphp
                                    <tr data-search="{{ $searchText }}" data-group-type="unsupported">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="{{ $asset->coinLogo }}" class="rounded-circle" width="38" height="38"
                                                    alt="{{ $symbol }}">
                                                <div class="fw-semibold">{{ $symbol }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">پشتیبانی‌نشده</small>
                                        </td>
                                        <td>
                                            <span
                                                class="font-number fw-semibold">{{ formatNumberTrimZeros($asset->available) }}</span>
                                            <small class="text-muted ms-1">{{ $symbol }}</small>
                                        </td>
                                        <td>
                                            <span
                                                class="text-danger fw-semibold font-number">{{ formatNumberTrimZeros($asset->frozen) }}</span>
                                            <small class="text-danger ms-1">{{ $symbol }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="assetEmptyState" class="text-center py-5 d-none">
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
            const assetSearch = document.getElementById('assetSearch');
            const assetTypeFilter = document.getElementById('assetTypeFilter');
            const assetEmptyState = document.getElementById('assetEmptyState');

            function applyAssetFilters() {
                const query = (assetSearch?.value || '').toLowerCase().trim();
                const selectedType = assetTypeFilter?.value || 'all';
                const groups = document.querySelectorAll('.asset-group');
                let hasVisibleGroup = false;

                groups.forEach(function (group) {
                    const groupType = group.dataset.groupType || '';
                    const shouldShowGroup = selectedType === 'all'
                        || selectedType === groupType
                        || (selectedType === 'UNSUPPORTED' && groupType === 'unsupported');
                    const rows = Array.from(group.querySelectorAll('tbody tr[data-search]'));
                    let hasVisibleRow = false;

                    rows.forEach(function (row) {
                        const haystack = row.dataset.search || '';
                        const matchQuery = !query || haystack.includes(query);
                        const isVisible = shouldShowGroup && matchQuery;

                        row.style.display = isVisible ? '' : 'none';
                        if (isVisible) {
                            hasVisibleRow = true;
                        }
                    });

                    group.style.display = hasVisibleRow ? '' : 'none';
                    if (hasVisibleRow) {
                        hasVisibleGroup = true;
                    }
                });

                assetEmptyState.classList.toggle('d-none', hasVisibleGroup);
            }

            assetSearch?.addEventListener('input', applyAssetFilters);
            assetTypeFilter?.addEventListener('change', applyAssetFilters);
        });
    </script>
@endpush

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/js/config.js', 'resources/assets/js/wallet.js'])
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection