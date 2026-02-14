@extends('dashboard.layout.master')
@section('title', 'گزارش موجودی ایندکس‌های HD Wallet')
@section('content')

    {{-- Header Card --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 card-title">
                    <i class="fa-regular fa-wallet me-2"></i>
                    گزارش موجودی ایندکس‌های HD Wallet
                </h5>
                <small class="text-muted">نمایش موجودی هر ایندکس بر اساس واریزهای تایید شده</small>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#currencySelectModal">
                <i class="fa-regular fa-filter me-2"></i>
                انتخاب کوین و مشاهده گزارش
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-6 mt-3" id="summaryCards" style="display: none;">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="">موجودی واقعی (پس از برداشت)</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="totalBalance">-</h4>
                                <small id="currencySymbolLabel">-</small>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded">
                            <i class="fa-regular fa-coins fa-lg"></i>
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
                            <span class="">مجموع واریزها</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2 text-success" id="totalDepositsAmount">-</h5>
                                <small class="text-muted" id="totalDepositCountLabel">-</small>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded">
                            <i class="fa-regular fa-arrow-down-left fa-lg"></i>
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
                            <span class="">مجموع برداشت‌ها</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2 text-danger" id="totalOutgoingAmount">-</h5>
                                <small class="text-muted" id="totalOutgoingCountLabel">-</small>
                            </div>
                        </div>
                        <span class="badge bg-label-danger rounded">
                            <i class="fa-regular fa-arrow-up-right fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Second Row Summary Cards --}}
    <div class="row g-6 mt-3" id="summaryCardsRow2" style="display: none;">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="">تعداد ایندکس‌های فعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="totalIndexCount">-</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded">
                            <i class="fa-regular fa-users fa-lg"></i>
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
                            <span class="">تعداد کل واریزها</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="totalDepositCount">-</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded">
                            <i class="fa-regular fa-download fa-lg"></i>
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
                            <span class="">شبکه</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2" id="chainNameLabel">-</h5>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded">
                            <i class="fa-regular fa-network-wired fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Data Table --}}
    <div class="card mt-4" id="dataTableCard" style="display: none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="" alt="" id="currencyLogo" class="me-3" width="40" style="display: none;">
                <div>
                    <h5 class="mb-0" id="reportTitle">لیست موجودی ایندکس‌ها</h5>
                    <small class="text-muted" id="reportSubtitle">به ترتیب بیشترین موجودی</small>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-primary me-2" id="selectedCountBadge" style="display: none;">
                    <span id="selectedCount">0</span> انتخاب شده
                </span>
                <button type="button" class="btn btn-warning btn-sm" id="sweepSelectedBtn" style="display: none;"
                    disabled>
                    <i class="fa-regular fa-paper-plane me-1"></i>
                    ارسال به برداشت
                </button>
                <button type="button" class="btn btn-info btn-sm" id="syncTransactionsBtn" disabled>
                    <i class="fa-regular fa-sync me-1"></i>
                    همگام‌سازی تراکنش‌ها
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" id="exportExcelBtn" disabled>
                    <i class="fa-regular fa-file-excel me-1"></i>
                    خروجی اکسل
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="refreshDataBtn" disabled>
                    <i class="fa-regular fa-refresh me-1"></i>
                    بروزرسانی
                </button>
            </div>
        </div>
        <div class="card-body">
            {{-- Filter Row --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">حداقل موجودی</label>
                    <input type="number" class="form-control" id="minBalanceFilter" value="0" min="0"
                        step="any">
                </div>
                <div class="col-md-3">
                    <label class="form-label">تعداد در هر صفحه</label>
                    <select class="form-select" id="perPageFilter">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">&nbsp;</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="includeWithdrawalsFilter" checked>
                        <label class="form-check-label" for="includeWithdrawalsFilter">
                            محاسبه با کسر برداشت‌ها
                        </label>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-primary" id="applyFilterBtn">
                        <i class="fa-regular fa-filter me-1"></i>
                        اعمال فیلتر
                    </button>
                </div>
            </div>

            {{-- Loading Indicator --}}
            <div class="text-center py-5" id="loadingIndicator" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">در حال بارگذاری...</span>
                </div>
                <p class="mt-2 text-muted">در حال بارگذاری اطلاعات...</p>
            </div>

            {{-- Data Table --}}
            <div class="table-responsive" id="tableContainer" style="display: none;">
                <table class="table table-hover" id="balanceTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <input class="form-check-input" type="checkbox" id="selectAllCheckbox"
                                    title="انتخاب همه">
                            </th>
                            <th>#</th>
                            <th>ایندکس HD Wallet</th>
                            <th>شناسه کاربر</th>
                            <th>واریزها</th>
                            <th>برداشت‌ها</th>
                            <th>موجودی واقعی</th>
                            <th>موجودی شبکه</th>
                            <th>تعداد تراکنش</th>
                            <th>آخرین واریز</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        {{-- Data will be loaded here via JavaScript --}}
                    </tbody>
                </table>
            </div>

            {{-- Empty State --}}
            <div class="text-center py-5" id="emptyState" style="display: none;">
                <i class="fa-regular fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">هیچ داده‌ای یافت نشد</p>
            </div>

            {{-- Pagination --}}
            <nav id="paginationContainer" class="mt-4" style="display: none;">
                <ul class="pagination justify-content-center" id="pagination">
                    {{-- Pagination will be generated here --}}
                </ul>
            </nav>
        </div>
    </div>

    {{-- Currency Selection Modal --}}
    <div class="modal fade" id="currencySelectModal" tabindex="-1" aria-labelledby="currencySelectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="currencySelectModalLabel">
                        <i class="fa-regular fa-coins me-2"></i>
                        انتخاب کوین و شبکه
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- فیلتر و جستجو --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-search"></i></span>
                                <input type="text" class="form-control" id="currencySearch"
                                    placeholder="جستجوی کوین یا شبکه...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" id="chainFilter">
                                <option value="all">همه شبکه‌ها</option>
                                <option value="TRC20">TRC20</option>
                                <option value="ERC20">ERC20</option>
                                <option value="BSC">BSC (BEP20)</option>
                                <option value="BTC">BTC</option>
                                <option value="DOGE">DOGE</option>
                            </select>
                        </div>
                    </div>

                    {{-- جدول کوین‌ها --}}
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover table-sm" id="currencySelectTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>کوین</th>
                                    <th>شبکه</th>
                                    <th style="width: 100px;">انتخاب</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($currencyChainsList as $index => $chain)
                                    @php
                                        // Get chain icon based on chain type
                                        // Don't show chain icon for native/parent coins
$isNativeCoin = match ($chain->chain) {
    \App\Enums\CurrencyChainEnum::TRC20 => $chain->currency->symbol === 'TRX',
    \App\Enums\CurrencyChainEnum::ERC20 => $chain->currency->symbol === 'ETH',
    \App\Enums\CurrencyChainEnum::BSC => $chain->currency->symbol === 'BNB',
    \App\Enums\CurrencyChainEnum::BTC => $chain->currency->symbol === 'BTC',
    \App\Enums\CurrencyChainEnum::DOGE => $chain->currency->symbol === 'DOGE',
    \App\Enums\CurrencyChainEnum::LTC => $chain->currency->symbol === 'LTC',
    default => false,
};

$chainIcon = $isNativeCoin
    ? null
    : match ($chain->chain) {
        \App\Enums\CurrencyChainEnum::TRC20 => asset('images/coins/trx.svg'),
        \App\Enums\CurrencyChainEnum::ERC20 => asset('images/coins/eth.svg'),
        \App\Enums\CurrencyChainEnum::BSC => asset('images/coins/bnb.svg'),
        \App\Enums\CurrencyChainEnum::BTC => asset('images/coins/btc.svg'),
        \App\Enums\CurrencyChainEnum::DOGE => asset('images/coins/doge.svg'),
        \App\Enums\CurrencyChainEnum::LTC => asset('images/coins/ltc.svg'),
                                                default => null,
                                            };
                                    @endphp
                                    <tr class="currency-chain-row"
                                        data-symbol="{{ strtolower($chain->currency->symbol) }}"
                                        data-name="{{ strtolower($chain->currency->name ?? '') }}"
                                        data-chain="{{ strtolower($chain->chain_name) }}"
                                        data-currency-symbol="{{ $chain->currency->symbol }}"
                                        data-chain-id="{{ $chain->id }}" data-chain-name="{{ $chain->chain_name }}"
                                        data-chain-enum="{{ $chain->chain->value }}"
                                        data-logo="{{ $chain->currency->coinLogo() }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="position-relative">
                                                    <img src="{{ $chain->currency->coinLogo() }}" class="rounded-circle"
                                                        width="36" height="36">
                                                    @if ($chainIcon)
                                                        <img src="{{ $chainIcon }}"
                                                            class="position-absolute rounded-circle border border-white"
                                                            width="18" height="18"
                                                            style="bottom: -2px; right: -2px; background: #fff;"
                                                            title="{{ $chain->chain_name }}">
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $chain->currency->symbol }}</span>
                                                    <small
                                                        class="text-muted d-block">{{ $chain->currency->persian_name ?? $chain->currency->name }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-primary">{{ $chain->chain_name }}</span>
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary select-currency-btn">
                                                <i class="fa-regular fa-check me-1"></i>انتخاب
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- راهنما --}}
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fa-regular fa-info-circle me-2"></i>
                        <small>با کلیک روی دکمه انتخاب، گزارش موجودی آن کوین و شبکه نمایش داده می‌شود.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex justify-content-between w-100">
                        <div>
                            <span class="text-muted" id="currencyCountInfo">{{ $currencyChainsList->count() }}
                                کوین/شبکه</span>
                        </div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Wallet Selection Modal --}}
    <div class="modal fade" id="walletSelectModal" tabindex="-1" aria-labelledby="walletSelectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="walletSelectModalLabel">
                        <i class="fa-regular fa-wallet me-2"></i>
                        انتخاب والت برای برداشت
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-4" id="walletListLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">در حال بارگذاری...</span>
                        </div>
                        <p class="mt-2 text-muted">در حال دریافت لیست والت‌ها از سوییپر...</p>
                    </div>
                    <div id="walletListError" style="display: none;">
                        <div class="alert alert-danger mb-0">
                            <i class="fa-regular fa-exclamation-triangle me-2"></i>
                            <span id="walletListErrorText">خطا در دریافت لیست والت‌ها</span>
                        </div>
                    </div>
                    <div id="walletListContainer" style="display: none;">
                        <p class="text-muted mb-3">
                            <strong id="sweepIndicesCount">0</strong> ایندکس انتخاب شده.
                            والت مورد نظر را انتخاب کنید:
                        </p>
                        <div class="list-group" id="walletListBody">
                            {{-- Wallets loaded via AJAX --}}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Sync Progress Modal --}}
    <div class="modal fade" id="syncProgressModal" tabindex="-1" aria-labelledby="syncProgressModalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="syncProgressModalLabel">
                        <i class="fa-regular fa-sync me-2"></i>
                        همگام‌سازی تراکنش‌های خروجی
                    </h5>
                </div>
                <div class="modal-body">
                    {{-- Progress Info Card --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">کوین:</span>
                                <strong id="syncCurrencyInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">شبکه:</span>
                                <strong id="syncChainInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">وضعیت:</span>
                                <span id="syncStatusBadge" class="badge bg-info">در حال پردازش...</span>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">پیشرفت</span>
                            <span class="fw-bold"><span id="syncPercentage">0</span>%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div id="syncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0"
                                aria-valuemax="100">
                                <span id="syncProgressBarText">0%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Current Status Message --}}
                    <div class="alert alert-info" id="syncCurrentMessage">
                        <i class="fa-regular fa-info-circle me-2"></i>
                        <span id="syncMessageText">در حال آماده‌سازی...</span>
                    </div>

                    {{-- Statistics Cards --}}
                    <div class="row g-3 mb-3" id="syncStatsCards" style="display: none;">
                        <div class="col-md-4">
                            <div class="card bg-label-primary">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="syncTotalAddresses">0</h3>
                                    <small class="text-muted">کل آدرس‌ها</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-label-success">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="syncNewTransactions">0</h3>
                                    <small class="text-muted">تراکنش جدید</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-label-danger">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="syncErrorCount">0</h3>
                                    <small class="text-muted">خطا</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Processed Addresses List --}}
                    <div id="syncAddressesList" style="display: none;">
                        <h6 class="mb-3">آدرس‌های پردازش شده:</h6>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th style="width: 100px;">ایندکس</th>
                                        <th>آدرس</th>
                                        <th style="width: 120px;">تراکنش جدید</th>
                                        <th style="width: 80px;">وضعیت</th>
                                    </tr>
                                </thead>
                                <tbody id="syncAddressesTableBody">
                                    {{-- Loaded dynamically --}}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Errors List --}}
                    <div id="syncErrorsList" style="display: none;">
                        <h6 class="mb-3 text-danger">
                            <i class="fa-regular fa-exclamation-triangle me-2"></i>
                            خطاهای رخ داده (<span id="syncErrorsCount">0</span>)
                        </h6>
                        <div class="list-group" style="max-height: 200px; overflow-y: auto;" id="syncErrorsListBody">
                            {{-- Loaded dynamically --}}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="syncCloseBtn" data-bs-dismiss="modal"
                        style="display: none;">
                        بستن
                    </button>
                    <button type="button" class="btn btn-primary" id="syncRefreshDataBtn" style="display: none;">
                        <i class="fa-regular fa-refresh me-1"></i>
                        بروزرسانی داده‌ها
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/spinkit/spinkit.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/block-ui/block-ui.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // State
            let currentCurrency = null;
            let currentChainId = null;
            let currentChainName = null;
            let currentChainEnum = null;
            let currentCurrencyLogo = null;
            let currentPage = 1;

            // Selection state for sweep
            const selectedIndices = new Set();

            // DOM Elements
            const currencySelectModalEl = document.getElementById('currencySelectModal');
            const currencySelectModal = new bootstrap.Modal(currencySelectModalEl);
            const summaryCards = document.getElementById('summaryCards');
            const dataTableCard = document.getElementById('dataTableCard');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const tableContainer = document.getElementById('tableContainer');
            const emptyState = document.getElementById('emptyState');
            const paginationContainer = document.getElementById('paginationContainer');
            const exportExcelBtn = document.getElementById('exportExcelBtn');
            const refreshDataBtn = document.getElementById('refreshDataBtn');
            const applyFilterBtn = document.getElementById('applyFilterBtn');
            const minBalanceFilter = document.getElementById('minBalanceFilter');
            const perPageFilter = document.getElementById('perPageFilter');
            const includeWithdrawalsFilter = document.getElementById('includeWithdrawalsFilter');
            const currencySearch = document.getElementById('currencySearch');
            const chainFilter = document.getElementById('chainFilter');
            const currencyCountInfo = document.getElementById('currencyCountInfo');
            const summaryCardsRow2 = document.getElementById('summaryCardsRow2');
            const sweepSelectedBtn = document.getElementById('sweepSelectedBtn');
            const selectedCountBadge = document.getElementById('selectedCountBadge');
            const selectedCountEl = document.getElementById('selectedCount');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');

            // Sync Elements
            const syncTransactionsBtn = document.getElementById('syncTransactionsBtn');
            const syncProgressModalEl = document.getElementById('syncProgressModal');
            const syncProgressModal = new bootstrap.Modal(syncProgressModalEl);
            let currentSyncId = null;
            let syncPollingInterval = null;

            // Update selection UI
            function updateSelectionUI() {
                const count = selectedIndices.size;
                if (count > 0) {
                    selectedCountBadge.style.display = '';
                    sweepSelectedBtn.style.display = '';
                    sweepSelectedBtn.disabled = false;
                    selectedCountEl.textContent = count;
                } else {
                    selectedCountBadge.style.display = 'none';
                    sweepSelectedBtn.style.display = 'none';
                    sweepSelectedBtn.disabled = true;
                }

                // Update select-all checkbox state
                const checkboxes = document.querySelectorAll('.row-select-checkbox');
                if (checkboxes.length > 0 && selectAllCheckbox) {
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    const someChecked = Array.from(checkboxes).some(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = someChecked && !allChecked;
                }
            }

            // Row checkbox change - event delegation
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('row-select-checkbox')) {
                    const index = parseInt(e.target.dataset.index);
                    if (e.target.checked) {
                        selectedIndices.add(index);
                    } else {
                        selectedIndices.delete(index);
                    }
                    updateSelectionUI();
                }
            });

            // Select All checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.row-select-checkbox');
                    checkboxes.forEach(cb => {
                        cb.checked = selectAllCheckbox.checked;
                        const index = parseInt(cb.dataset.index);
                        if (selectAllCheckbox.checked) {
                            selectedIndices.add(index);
                        } else {
                            selectedIndices.delete(index);
                        }
                    });
                    updateSelectionUI();
                });
            }

            // Wallet Selection Modal Elements
            const walletSelectModalEl = document.getElementById('walletSelectModal');
            const walletSelectModal = new bootstrap.Modal(walletSelectModalEl);
            const walletListLoading = document.getElementById('walletListLoading');
            const walletListError = document.getElementById('walletListError');
            const walletListErrorText = document.getElementById('walletListErrorText');
            const walletListContainer = document.getElementById('walletListContainer');
            const walletListBody = document.getElementById('walletListBody');
            const sweepIndicesCountEl = document.getElementById('sweepIndicesCount');

            // Sweep Selected Button Click → Open wallet selection modal
            if (sweepSelectedBtn) {
                sweepSelectedBtn.addEventListener('click', function() {
                    if (selectedIndices.size === 0) {
                        showToast('لطفاً حداقل یک ایندکس انتخاب کنید', 'warning');
                        return;
                    }

                    if (!currentCurrency || !currentChainId) {
                        showToast('لطفاً ابتدا یک کوین و شبکه انتخاب کنید', 'warning');
                        return;
                    }

                    // Update indices count in modal
                    sweepIndicesCountEl.textContent = selectedIndices.size;

                    // Show loading, hide others
                    walletListLoading.style.display = '';
                    walletListError.style.display = 'none';
                    walletListContainer.style.display = 'none';

                    // Open modal
                    walletSelectModal.show();

                    // Fetch wallets from sweeper
                    loadSweeperWallets();
                });
            }

            // Load wallets from sweeper API
            async function loadSweeperWallets() {
                try {
                    const response = await fetch(
                    '{{ route('admin.report.hd-wallet-index.sweeper-wallets') }}', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    const result = await response.json();

                    if (result.success && result.data?.wallets?.length > 0) {
                        renderWalletList(result.data.wallets);
                        walletListLoading.style.display = 'none';
                        walletListContainer.style.display = '';
                    } else if (result.success && (!result.data?.wallets || result.data.wallets.length === 0)) {
                        walletListLoading.style.display = 'none';
                        walletListErrorText.textContent = 'هیچ والتی یافت نشد';
                        walletListError.style.display = '';
                    } else {
                        walletListLoading.style.display = 'none';
                        walletListErrorText.textContent = result.error || 'خطا در دریافت لیست والت‌ها';
                        walletListError.style.display = '';
                    }
                } catch (error) {
                    console.error('Error loading wallets:', error);
                    walletListLoading.style.display = 'none';
                    walletListErrorText.textContent = 'خطا در ارتباط با سرور: ' + error.message;
                    walletListError.style.display = '';
                }
            }

            // Render wallet list in modal
            function renderWalletList(wallets) {
                const statusLabels = {
                    'active': {
                        text: 'فعال',
                        class: 'bg-success'
                    },
                    'inactive': {
                        text: 'غیرفعال',
                        class: 'bg-warning'
                    },
                    'archived': {
                        text: 'آرشیو',
                        class: 'bg-secondary'
                    }
                };

                // Map CurrencyChainEnum to sweeper network
                const chainToNetwork = {
                    'BTC': 'bitcoin',
                    'ERC20': 'ethereum',
                    'TRC20': 'tron',
                    'BSC': 'bnb',
                    'DOGE': 'dogecoin'
                };

                const requiredNetwork = chainToNetwork[currentChainEnum];

                // Filter wallets that support the required network
                const filteredWallets = wallets.filter(wallet => {
                    if (!requiredNetwork) return true; // Show all if network mapping not found
                    return wallet.supportedNetworks && wallet.supportedNetworks.includes(requiredNetwork);
                });

                if (filteredWallets.length === 0) {
                    walletListLoading.style.display = 'none';
                    walletListErrorText.textContent = `هیچ والتی برای شبکه ${currentChainName} یافت نشد`;
                    walletListError.style.display = '';
                    return;
                }

                walletListBody.innerHTML = filteredWallets.map(wallet => {
                    const statusInfo = statusLabels[wallet.status] || {
                        text: wallet.status,
                        class: 'bg-secondary'
                    };
                    const networks = (wallet.supportedNetworks || []).join(', ') || '-';
                    const lastUsed = wallet.lastUsed ? new Date(wallet.lastUsed).toLocaleDateString(
                        'fa-IR') : '-';

                    return `
                        <a href="#" class="list-group-item list-group-item-action wallet-select-item ${wallet.status !== 'active' ? 'opacity-50' : ''}"
                           data-wallet-id="${wallet.walletId}"
                           data-wallet-name="${wallet.name}"
                           data-wallet-status="${wallet.status}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="fa-regular fa-wallet me-2 text-primary"></i>
                                        ${wallet.name}
                                        <small class="text-muted ms-1">(${wallet.walletId})</small>
                                    </h6>
                                    <small class="text-muted d-block">
                                        <i class="fa-regular fa-network-wired me-1"></i>
                                        شبکه‌ها: ${networks}
                                    </small>
                                    ${wallet.description ? `<small class="text-muted d-block"><i class="fa-regular fa-info-circle me-1"></i>${wallet.description}</small>` : ''}
                                    <small class="text-muted d-block">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        آخرین استفاده: ${lastUsed}
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge ${statusInfo.class}">${statusInfo.text}</span>
                                </div>
                            </div>
                        </a>
                    `;
                }).join('');
            }

            // Handle wallet selection click → execute sweep
            document.addEventListener('click', async function(e) {
                const walletItem = e.target.closest('.wallet-select-item');
                if (!walletItem) return;

                e.preventDefault();

                const walletId = walletItem.dataset.walletId;
                const walletName = walletItem.dataset.walletName;
                const walletStatus = walletItem.dataset.walletStatus;

                if (walletStatus !== 'active') {
                    showToast('این والت غیرفعال است و قابل استفاده نیست', 'warning');
                    return;
                }

                const indicesArray = Array.from(selectedIndices).sort((a, b) => a - b);

                const sweepConfirmText =
                    `آیا از ارسال ${indicesArray.length} ایندکس به پروسه برداشت اطمینان دارید?\n\n` +
                    `والت: ${walletName} (${walletId})\n` +
                    `کوین: ${currentCurrency}\n` +
                    `شبکه: ${currentChainName}\n` +
                    `ایندکس‌ها: ${indicesArray.join(', ')}`;

                const sweepConfirmResult = await Swal.fire({
                    title: 'تایید ارسال ایندکس‌ها',
                    html: `<div style="white-space: pre-line; text-align: right;">${escapeHtml(sweepConfirmText)}</div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'بله، ارسال کن',
                    cancelButtonText: 'انصراف',
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    didOpen: (popup) => popup.setAttribute('dir', 'rtl')
                });

                if (!sweepConfirmResult.isConfirmed) {
                    return;
                }

                // Close wallet modal
                walletSelectModal.hide();

                // Disable sweep button and show loading
                sweepSelectedBtn.disabled = true;
                const originalHTML = sweepSelectedBtn.innerHTML;
                sweepSelectedBtn.innerHTML =
                    '<i class="fa-regular fa-spinner fa-spin me-1"></i> در حال ارسال...';

                try {
                    const response = await fetch(
                        '{{ route('admin.report.hd-wallet-index.sweep-selected') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content
                            },
                            body: JSON.stringify({
                                indices: indicesArray,
                                currency_symbol: currentCurrency,
                                currency_chain_id: parseInt(currentChainId),
                                wallet_id: walletId,
                                force: false
                            })
                        });

                    const result = await response.json();

                    if (result.success) {
                        const summary = result.data?.summary || {};
                        showToast(
                            result.message ||
                            `${summary.successful || 0} ایندکس با موفقیت به برداشت ارسال شد`,
                            'success'
                        );

                        // Show detailed results if there are errors
                        if (result.data?.errors?.length > 0) {
                            const errors = result.data.errors || [];
                            const errorItemsHtml = errors
                                .map(e =>
                                    `<li>ایندکس <strong>${escapeHtml(String(e.addressIndex))}</strong>: ${escapeHtml(String(e.error || ''))}</li>`
                                )
                                .join('');

                            await Swal.fire({
                                title: 'خطا در برخی ایندکس‌ها',
                                html: `
                                    <div style="text-align: right;">
                                        <div class="mb-2">در برخی ایندکس‌ها خطا رخ داده است:</div>
                                        <div style="max-height: 320px; overflow: auto;">
                                            <ul class="mb-0 ps-3">${errorItemsHtml}</ul>
                                        </div>
                                    </div>
                                `,
                                icon: 'warning',
                                confirmButtonText: 'باشه',
                                confirmButtonColor: '#696cff',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                },
                                buttonsStyling: false,
                                didOpen: (popup) => popup.setAttribute('dir', 'rtl')
                            });
                        }

                        // Clear selection and refresh
                        selectedIndices.clear();
                        updateSelectionUI();
                        loadData();
                    } else {
                        // Show error with wallet info if available
                        let errorMsg = result.error || 'خطا در ارسال درخواست برداشت';
                        if (result.walletInfo) {
                            errorMsg +=
                                `\n\nوالت: ${result.walletInfo.name} (${result.walletInfo.walletId})\nشبکه‌های پشتیبانی شده: ${(result.walletInfo.supportedNetworks || []).join(', ')}`;
                        }
                        showToast(errorMsg, 'error');
                    }
                } catch (error) {
                    console.error('Error sweeping indices:', error);
                    showToast('خطا در ارتباط با سرور: ' + error.message, 'error');
                } finally {
                    sweepSelectedBtn.disabled = false;
                    sweepSelectedBtn.innerHTML = originalHTML;
                    updateSelectionUI();
                }
            });

            // Filter currency/chain rows in modal
            function filterRows() {
                const searchTerm = (currencySearch.value || '').toLowerCase();
                const selectedChain = (chainFilter.value || 'all').toLowerCase();
                const rows = document.querySelectorAll('.currency-chain-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const symbol = row.dataset.symbol || '';
                    const name = row.dataset.name || '';
                    const chain = row.dataset.chain || '';

                    const matchesSearch = symbol.includes(searchTerm) || name.includes(searchTerm) || chain
                        .includes(searchTerm);
                    const matchesChain = selectedChain === 'all' || chain.includes(selectedChain);

                    if (matchesSearch && matchesChain) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (currencyCountInfo) {
                    currencyCountInfo.textContent = `${visibleCount} کوین/شبکه`;
                }
            }

            // Search input event
            if (currencySearch) {
                currencySearch.addEventListener('input', filterRows);
                currencySearch.addEventListener('keyup', filterRows);
            }

            // Chain filter change event
            if (chainFilter) {
                chainFilter.addEventListener('change', filterRows);
            }

            // Select currency button click - using event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.select-currency-btn')) {
                    e.preventDefault();
                    const btn = e.target.closest('.select-currency-btn');
                    const row = btn.closest('.currency-chain-row');

                    if (row) {
                        currentCurrency = row.dataset.currencySymbol;
                        currentChainId = row.dataset.chainId;
                        currentChainName = row.dataset.chainName;
                        currentChainEnum = row.dataset.chainEnum;
                        currentCurrencyLogo = row.dataset.logo;
                        currentPage = 1;

                        currencySelectModal.hide();
                        loadData();
                    }
                }
                console.log(currentCurrency, currentChainId);
            });

            // Apply Filter Button Click
            if (applyFilterBtn) {
                applyFilterBtn.addEventListener('click', function() {
                    currentPage = 1;
                    loadData();
                });
            }

            // Refresh Data Button Click
            if (refreshDataBtn) {
                refreshDataBtn.addEventListener('click', function() {
                    loadData();
                });
            }

            // Export Excel Button Click
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', function() {
                    if (!currentCurrency) return;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('admin.report.hd-wallet-index.export-excel') }}';

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                    form.appendChild(csrfInput);

                    const currencyInput = document.createElement('input');
                    currencyInput.type = 'hidden';
                    currencyInput.name = 'currency_symbol';
                    currencyInput.value = currentCurrency;
                    form.appendChild(currencyInput);

                    if (currentChainId) {
                        const chainInput = document.createElement('input');
                        chainInput.type = 'hidden';
                        chainInput.name = 'currency_chain_id';
                        chainInput.value = currentChainId;
                        form.appendChild(chainInput);
                    }

                    const minBalanceInput = document.createElement('input');
                    minBalanceInput.type = 'hidden';
                    minBalanceInput.name = 'min_balance';
                    minBalanceInput.value = minBalanceFilter.value || 0;
                    form.appendChild(minBalanceInput);

                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                });
            }

            // Load Data Function
            async function loadData() {
                if (!currentCurrency) return;

                // Show loading state
                summaryCards.style.display = 'flex';
                summaryCardsRow2.style.display = 'flex';
                dataTableCard.style.display = 'block';
                loadingIndicator.style.display = 'block';
                tableContainer.style.display = 'none';
                emptyState.style.display = 'none';
                paginationContainer.style.display = 'none';

                try {
                    console.log(currentCurrency, currentChainId);
                    const response = await fetch('{{ route('admin.report.hd-wallet-index.balance-data') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            currency_symbol: currentCurrency,
                            currency_chain_id: currentChainId,
                            min_balance: minBalanceFilter.value || 0,
                            per_page: perPageFilter.value,
                            page: currentPage,
                            include_withdrawals: includeWithdrawalsFilter.checked
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        updateSummary(result.data.summary);
                        updateTable(result.data.items, result.data.pagination);
                        updatePagination(result.data.pagination);

                        exportExcelBtn.disabled = false;
                        refreshDataBtn.disabled = false;
                        syncTransactionsBtn.disabled = false;
                    } else {
                        showEmptyState();
                    }
                } catch (error) {
                    console.error('Error loading data:', error);
                    showEmptyState();
                } finally {
                    loadingIndicator.style.display = 'none';
                }
            }

            // Update Summary Cards
            function updateSummary(summary) {
                document.getElementById('totalBalance').textContent = summary.total_balance;
                document.getElementById('currencySymbolLabel').textContent = summary.currency_symbol;
                document.getElementById('totalIndexCount').textContent = summary.total_index_count.toLocaleString(
                    'fa-IR');
                document.getElementById('totalDepositCount').textContent = summary.total_deposit_count
                    .toLocaleString('fa-IR');
                document.getElementById('chainNameLabel').textContent = currentChainName || summary.chain_name;

                // New summary fields for deposits and outgoing
                document.getElementById('totalDepositsAmount').textContent = summary.total_deposits + ' ' + summary
                    .currency_symbol;
                document.getElementById('totalDepositCountLabel').textContent =
                    `(${summary.total_deposit_count.toLocaleString('fa-IR')} تراکنش)`;
                document.getElementById('totalOutgoingAmount').textContent = summary.total_outgoing + ' ' + summary
                    .currency_symbol;
                document.getElementById('totalOutgoingCountLabel').textContent = summary.total_outgoing_count ?
                    `(${summary.total_outgoing_count.toLocaleString('fa-IR')} تراکنش)` :
                    '(0 تراکنش)';

                document.getElementById('reportTitle').textContent =
                    `لیست موجودی ایندکس‌های ${summary.currency_name} (${summary.currency_symbol})`;
                document.getElementById('reportSubtitle').textContent =
                    `شبکه: ${currentChainName || summary.chain_name} - به ترتیب بیشترین موجودی`;

                const logoEl = document.getElementById('currencyLogo');
                if (currentCurrencyLogo) {
                    logoEl.src = currentCurrencyLogo;
                    logoEl.style.display = 'block';
                } else {
                    logoEl.style.display = 'none';
                }
            }

            // Update Table
            function updateTable(items, pagination) {
                const tbody = document.getElementById('tableBody');
                const includeWithdrawals = includeWithdrawalsFilter.checked;

                if (items.length === 0) {
                    showEmptyState();
                    return;
                }

                tableContainer.style.display = 'block';
                emptyState.style.display = 'none';

                // Reset selection state
                selectedIndices.clear();
                updateSelectionUI();

                tbody.innerHTML = items.map((item, index) => {
                    const rowNumber = (pagination.current_page - 1) * pagination.per_page + index + 1;
                    const depositsCol = includeWithdrawals && item.total_deposits ?
                        `<strong class="text-success font-number">${item.total_deposits}</strong>` :
                        `<strong class="text-success font-number">${item.total_balance}</strong>`;
                    const outgoingCol = includeWithdrawals ?
                        `<strong class="text-danger font-number">${item.total_outgoing || '0'}</strong>` :
                        '<span class="text-muted">-</span>';
                    const balanceClass = parseFloat(item.total_balance_raw) < 0 ? 'text-danger' :
                        'text-primary';

                    return `
                <tr>
                    <td>
                        <input class="form-check-input row-select-checkbox" type="checkbox"
                            data-index="${item.hd_wallet_index}" data-balance="${item.total_balance_raw}">
                    </td>
                    <td>${rowNumber}</td>
                    <td>
                        <span class="badge bg-label-primary">${item.hd_wallet_index}</span>
                    </td>
                    <td>
                        <a href="/admin/users/${item.hd_wallet_index}/inquiry" target="_blank" class="text-primary">
                            ${item.hd_wallet_index}
                            <i class="fa-regular fa-external-link fa-xs ms-1"></i>
                        </a>
                    </td>
                    <td>
                        ${depositsCol}
                        <small class="text-muted d-block">${item.deposit_count} تراکنش</small>
                    </td>
                    <td>
                        ${outgoingCol}
                        <small class="text-muted d-block">${item.outgoing_count || 0} تراکنش</small>
                    </td>
                    <td>
                        <strong class="font-number ${balanceClass}">${item.total_balance}</strong>
                        <small class="text-muted ms-1">${item.currency_symbol}</small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span id="blockchainBalance_${item.hd_wallet_index}" class="text-muted">-</span>
                            <button type="button"
                                class="btn btn-sm btn-outline-info query-blockchain-btn"
                                data-user-id="${item.hd_wallet_index}"
                                data-currency="${item.currency_symbol}"
                                title="استعلام از شبکه">
                                <i class="fa-regular fa-globe"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <small class="text-muted">${item.deposit_count} واریز</small><br>
                        <small class="text-muted">${item.outgoing_count || 0} برداشت</small>
                    </td>
                    <td><small class="font-number">${item.last_deposit_at}</small></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fa-regular fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="/admin/users/${item.hd_wallet_index}/edit" target="_blank">
                                        <i class="fa-regular fa-user me-2"></i>
                                        مشاهده کاربر
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/admin/users/${item.hd_wallet_index}/wallets" target="_blank">
                                        <i class="fa-regular fa-wallet me-2"></i>
                                        کیف پول کاربر
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/admin/deposits?user=${item.hd_wallet_index}&currency=${item.currency_symbol}" target="_blank">
                                        <i class="fa-regular fa-arrow-down-left me-2"></i>
                                        واریزهای کاربر
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
                }).join('');
            }

            // Update Pagination
            function updatePagination(pagination) {
                if (pagination.last_page <= 1) {
                    paginationContainer.style.display = 'none';
                    return;
                }

                paginationContainer.style.display = 'block';
                const paginationEl = document.getElementById('pagination');

                let html = '';

                // Previous button
                html += `
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
                    <i class="fa-regular fa-chevron-right"></i>
                </a>
            </li>
        `;

                // Page numbers
                const startPage = Math.max(1, pagination.current_page - 2);
                const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

                if (startPage > 1) {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                    if (startPage > 2) {
                        html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    html += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
                }

                if (endPage < pagination.last_page) {
                    if (endPage < pagination.last_page - 1) {
                        html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a></li>`;
                }

                // Next button
                html += `
            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
                    <i class="fa-regular fa-chevron-left"></i>
                </a>
            </li>
        `;

                paginationEl.innerHTML = html;

                // Add click handlers
                paginationEl.querySelectorAll('a[data-page]').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = parseInt(this.dataset.page);
                        if (page >= 1 && page <= pagination.last_page && page !== currentPage) {
                            currentPage = page;
                            loadData();
                        }
                    });
                });
            }

            // Show Empty State
            function showEmptyState() {
                tableContainer.style.display = 'none';
                emptyState.style.display = 'block';
                paginationContainer.style.display = 'none';
            }

            // Show Toast Notification
            function showToast(message, type = 'error') {
                const iconMap = {
                    error: 'error',
                    success: 'success',
                    warning: 'warning',
                    info: 'info'
                };

                const titleMap = {
                    error: 'خطا!',
                    success: 'موفق!',
                    warning: 'هشدار',
                    info: 'اطلاع'
                };

                const icon = iconMap[type] || 'error';
                const title = titleMap[type] || 'خطا!';
                const text = String(message ?? '');
                const shouldUseModal = text.includes('\n') || text.length > 160;

                if (shouldUseModal) {
                    return Swal.fire({
                        title,
                        icon,
                        html: `<div style="white-space: pre-line; text-align: right;">${escapeHtml(text)}</div>`,
                        confirmButtonText: 'باشه',
                        confirmButtonColor: '#696cff',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false,
                        didOpen: (popup) => popup.setAttribute('dir', 'rtl')
                    });
                }

                return Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon,
                    title: text,
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: (popup) => popup.setAttribute('dir', 'rtl')
                });
            }

            // Query Blockchain Balance - Event Delegation
            document.addEventListener('click', async function(e) {
                if (e.target.closest('.query-blockchain-btn')) {

                    e.preventDefault();
                    const btn = e.target.closest('.query-blockchain-btn');
                    const userId = btn.dataset.userId;
                    const currency = btn.dataset.currency;

                    if (!currentChainId) {
                        showToast('لطفاً ابتدا یک کوین و شبکه انتخاب کنید', 'warning');
                        return;
                    }

                    const balanceEl = document.getElementById(`blockchainBalance_${userId}`);
                    const originalIcon = btn.innerHTML;

                    // Show loading
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-regular fa-spinner fa-spin"></i>';
                    balanceEl.innerHTML = '<span class="text-muted">در حال استعلام...</span>';

                    try {
                        const response = await fetch(
                            '{{ route('admin.report.hd-wallet-index.query-blockchain') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    user_id: userId,
                                    currency_symbol: currency,
                                    currency_chain_id: currentChainId
                                })
                            });

                        const result = await response.json();

                        if (result.success) {
                            const explorerLink = result.data.explorer_url ?
                                `<a href="${result.data.explorer_url}" target="_blank" class="text-decoration-none" title="مشاهده در Explorer">
                             <i class="fa-regular fa-external-link fa-xs"></i>
                           </a>` :
                                '';
                            balanceEl.innerHTML = `
                        <strong class="text-info font-number">${result.data.balance}</strong>
                        <small class="text-muted ms-1">${result.data.currency_symbol}</small>
                        ${explorerLink}
                    `;
                            showToast(
                                `موجودی آدرس ${result.data.address} دریافت شد`,
                                'success');
                        } else {
                            balanceEl.innerHTML = `<span class="text-danger">
                        <i class="fa-regular fa-exclamation-circle"></i> خطا
                    </span>`;
                            showToast(`خطا در استعلام کاربر ${userId}: ${result.error}`, 'error');
                        }
                    } catch (error) {
                        console.error('Error querying blockchain:', error);
                        balanceEl.innerHTML =
                            '<span class="text-danger"><i class="fa-regular fa-exclamation-circle"></i> خطا</span>';
                        showToast(`خطا در ارتباط با سرور: ${error.message}`, 'error');
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = originalIcon;
                    }
                }
            });

            // ========== SYNC TRANSACTIONS FUNCTIONALITY ==========

            // Sync Transactions Button Click
            if (syncTransactionsBtn) {
                syncTransactionsBtn.addEventListener('click', async function() {
                    if (!currentCurrency || !currentChainId) {
                        showToast('لطفاً ابتدا یک کوین و شبکه انتخاب کنید', 'warning');
                        return;
                    }

                    const syncConfirmText =
                        `آیا از شروع همگام‌سازی تراکنش‌های خروجی برای ${currentCurrency} (${currentChainName}) اطمینان دارید؟\n\n` +
                        `این پروسه ممکن است چند دقیقه طول بکشد.`;

                    const syncConfirmResult = await Swal.fire({
                        title: 'شروع همگام‌سازی',
                        html: `<div style="white-space: pre-line; text-align: right;">${escapeHtml(syncConfirmText)}</div>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#696cff',
                        cancelButtonColor: '#8592a3',
                        confirmButtonText: 'بله، شروع کن',
                        cancelButtonText: 'انصراف',
                        customClass: {
                            confirmButton: 'btn btn-primary me-2',
                            cancelButton: 'btn btn-label-secondary'
                        },
                        buttonsStyling: false,
                        didOpen: (popup) => popup.setAttribute('dir', 'rtl')
                    });

                    if (!syncConfirmResult.isConfirmed) return;

                    // Start sync
                    await startSync();
                });
            }

            // Start Sync Process
            async function startSync() {
                try {
                    const response = await fetch('{{ route('admin.report.hd-wallet-index.start-sync') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            currency_symbol: currentCurrency,
                            currency_chain_id: currentChainId,
                            delay: 500 // milliseconds between API calls
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        currentSyncId = result.sync_id;

                        // Show modal and start polling
                        initializeSyncModal();
                        syncProgressModal.show();
                        startSyncPolling();

                        showToast('همگام‌سازی شروع شد', 'success');
                    } else {
                        showToast('خطا در شروع همگام‌سازی: ' + (result.error || 'خطای نامشخص'), 'error');
                    }
                } catch (error) {
                    console.error('Error starting sync:', error);
                    showToast('خطا در ارتباط با سرور: ' + error.message, 'error');
                }
            }

            // Initialize Sync Modal
            function initializeSyncModal() {
                // Set currency info
                document.getElementById('syncCurrencyInfo').textContent = currentCurrency;
                document.getElementById('syncChainInfo').textContent = currentChainName;

                // Reset progress
                updateSyncProgress({
                    percentage: 0,
                    message: 'در حال شروع...',
                    status: 'starting',
                    data: {}
                });

                // Hide/show elements
                document.getElementById('syncStatsCards').style.display = 'none';
                document.getElementById('syncAddressesList').style.display = 'none';
                document.getElementById('syncErrorsList').style.display = 'none';
                document.getElementById('syncCloseBtn').style.display = 'none';
                document.getElementById('syncRefreshDataBtn').style.display = 'none';
            }

            // Start Polling Sync Progress
            function startSyncPolling() {
                // Clear any existing interval
                if (syncPollingInterval) {
                    clearInterval(syncPollingInterval);
                }

                // Poll every 2 seconds
                syncPollingInterval = setInterval(async () => {
                    await fetchSyncProgress();
                }, 2000);

                // Initial fetch
                fetchSyncProgress();
            }

            // Fetch Sync Progress
            async function fetchSyncProgress() {
                if (!currentSyncId) {
                    return;
                }

                try {
                    const response = await fetch(
                        `{{ url('admin/report/hd-wallet-index/sync-progress') }}/${currentSyncId}`, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                    const result = await response.json();

                    if (result.success) {
                        updateSyncProgress(result.progress);

                        // Stop polling if completed or error
                        if (result.progress.status === 'completed' || result.progress.status === 'error') {
                            stopSyncPolling();
                        }
                    } else {
                        console.error('Error fetching sync progress:', result.error);
                    }
                } catch (error) {
                    console.error('Error fetching sync progress:', error);
                }
            }

            // Update Sync Progress UI
            function updateSyncProgress(progress) {
                const {
                    percentage,
                    message,
                    status,
                    data
                } = progress;

                // Update progress bar
                const progressBar = document.getElementById('syncProgressBar');
                const progressText = document.getElementById('syncProgressBarText');
                const percentageEl = document.getElementById('syncPercentage');

                progressBar.style.width = percentage + '%';
                progressBar.setAttribute('aria-valuenow', percentage);
                progressText.textContent = percentage + '%';
                percentageEl.textContent = percentage;

                // Update message
                document.getElementById('syncMessageText').textContent = message;

                // Update status badge
                const statusBadge = document.getElementById('syncStatusBadge');
                const messageAlert = document.getElementById('syncCurrentMessage');

                if (status === 'completed') {
                    statusBadge.className = 'badge bg-success';
                    statusBadge.textContent = 'تکمیل شد ✓';
                    messageAlert.className = 'alert alert-success';
                    progressBar.className = 'progress-bar bg-success';

                    // Show close and refresh buttons
                    document.getElementById('syncCloseBtn').style.display = '';
                    document.getElementById('syncRefreshDataBtn').style.display = '';
                } else if (status === 'error') {
                    statusBadge.className = 'badge bg-danger';
                    statusBadge.textContent = 'خطا ✗';
                    messageAlert.className = 'alert alert-danger';
                    progressBar.className = 'progress-bar bg-danger';

                    // Show close button
                    document.getElementById('syncCloseBtn').style.display = '';
                } else if (status === 'processing') {
                    statusBadge.className = 'badge bg-info';
                    statusBadge.textContent = 'در حال پردازش...';
                    messageAlert.className = 'alert alert-info';
                    progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
                }

                // Update statistics if available
                if (data.total_addresses !== undefined) {
                    document.getElementById('syncStatsCards').style.display = 'flex';
                    document.getElementById('syncTotalAddresses').textContent = data.total_addresses;
                    document.getElementById('syncNewTransactions').textContent = data.total_new_transactions || 0;
                    document.getElementById('syncErrorCount').textContent = (data.errors || []).length;
                }

                // Update processed addresses list
                if (data.processed_addresses && data.processed_addresses.length > 0) {
                    document.getElementById('syncAddressesList').style.display = 'block';
                    updateProcessedAddressList(data.processed_addresses);
                }

                // Update errors list
                if (data.errors && data.errors.length > 0) {
                    document.getElementById('syncErrorsList').style.display = 'block';
                    document.getElementById('syncErrorsCount').textContent = data.errors.length;
                    updateErrorsList(data.errors);
                }
            }

            // Update Processed Addresses List
            function updateProcessedAddressList(addresses) {
                const tbody = document.getElementById('syncAddressesTableBody');

                tbody.innerHTML = addresses.map((addr, index) => {
                    const statusIcon = addr.status === 'success' ?
                        '<i class="fa-regular fa-check-circle text-success"></i>' :
                        '<i class="fa-regular fa-times-circle text-danger"></i>';

                    const statusText = addr.status === 'success' ?
                        '<span class="badge bg-label-success">موفق</span>' :
                        '<span class="badge bg-label-danger">خطا</span>';

                    const newTxCount = addr.new_transactions !== undefined ?
                        `<strong class="text-success">${addr.new_transactions}</strong>` :
                        '-';

                    return `
                    <tr>
                        <td>${index + 1}</td>
                        <td><span class="badge bg-label-primary">${addr.index}</span></td>
                        <td><small class="font-number">${addr.address}</small></td>
                        <td class="text-center">${newTxCount}</td>
                        <td class="text-center">${statusText}</td>
                    </tr>
                `;
                }).join('');
            }

            // Update Errors List
            function updateErrorsList(errors) {
                const listBody = document.getElementById('syncErrorsListBody');

                listBody.innerHTML = errors.map(err => {
                    return `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>ایندکس ${err.index}:</strong>
                                <small class="d-block text-muted font-number">${err.address}</small>
                            </div>
                        </div>
                        <p class="mb-0 mt-2 text-danger small">
                            <i class="fa-regular fa-exclamation-triangle me-1"></i>
                            ${err.error}
                        </p>
                    </div>
                `;
                }).join('');
            }

            // Stop Sync Polling
            function stopSyncPolling() {
                if (syncPollingInterval) {
                    clearInterval(syncPollingInterval);
                    syncPollingInterval = null;
                }
            }

            // Sync Refresh Data Button
            document.getElementById('syncRefreshDataBtn')?.addEventListener('click', function() {
                syncProgressModal.hide();
                loadData(); // Reload main data
                showToast('داده‌ها بروزرسانی شد', 'success');
            });

            // Clean up polling when modal is closed
            syncProgressModalEl?.addEventListener('hidden.bs.modal', function() {
                stopSyncPolling();
            });

            // ========== END SYNC TRANSACTIONS FUNCTIONALITY ==========

            console.log('HD Wallet Index Report script loaded successfully');
        });

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }
    </script>
@endpush
