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
                <small class="text-secondary">نمایش موجودی هر ایندکس بر اساس واریزهای تایید شده</small>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#currencySelectModal">
                <i class="fa-regular fa-filter me-2"></i>
                انتخاب کوین و مشاهده گزارش
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-2 mt-3" id="summaryCards" style="display: none;">
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
                                <small class="text-secondary" id="totalDepositCountLabel">-</small>
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
                                <small class="text-secondary" id="totalOutgoingCountLabel">-</small>
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
    <div class="row g-2 mt-3" id="summaryCardsRow2" style="display: none;">
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
                    <small class="text-secondary" id="reportSubtitle">به ترتیب بیشترین موجودی</small>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-primary me-2" id="selectedCountBadge" style="display: none;">
                    <span id="selectedCount">0</span> انتخاب شده
                </span>
                <button type="button" class="btn btn-warning btn-sm" id="sweepSelectedBtn" style="display: none;" disabled>
                    <i class="fa-regular fa-paper-plane me-1"></i>
                    ارسال به برداشت
                </button>
                <button type="button" class="btn btn-success btn-sm" id="fundSelectedBtn" style="display: none;" disabled>
                    <i class="fa-regular fa-gas-pump me-1"></i>
                    واریز گس
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
                    <input type="number" class="form-control" id="minBalanceFilter" value="0" min="0" step="any">
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
                <p class="mt-2 text-secondary">در حال بارگذاری اطلاعات...</p>
            </div>

            {{-- Data Table --}}
            <div class="table-responsive" id="tableContainer" style="display: none;">
                <table class="table table-hover" id="balanceTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <input class="form-check-input" type="checkbox" id="selectAllCheckbox" title="انتخاب همه">
                            </th>
                            <th>#</th>
                            <th>ایندکس</th>
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
                <i class="fa-regular fa-inbox fa-3x text-secondary mb-3"></i>
                <p class="text-secondary">هیچ داده‌ای یافت نشد</p>
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
                                @foreach ($currencyChainsList->unique('chain')->sortBy('chain_name') as $chainFilterItem)
                                    <option value="{{ $chainFilterItem->chain->value }}">{{ $chainFilterItem->chain_name }}
                                    </option>
                                @endforeach
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
                                @php
                                    $chainIconMap = $currencyChainsList
                                        ->filter(fn($item) => (bool) $item->is_base_coin)
                                        ->mapWithKeys(function ($item) {
                                            $chainKey = $item->chain?->value ?? null;

                                            return $chainKey ? [$chainKey => $item->currency->coinLogo()] : [];
                                        })
                                        ->all();
                                @endphp
                                @foreach ($currencyChainsList as $index => $chain)
                                    @php
                                        $chainKey = $chain->chain?->value;
                                        $isNativeCoin = (bool) $chain->is_base_coin;
                                        $chainIcon = !$isNativeCoin && $chainKey
                                            ? ($chainIconMap[$chainKey] ?? null)
                                            : null;
                                    @endphp
                                    <tr class="currency-chain-row" data-symbol="{{ strtolower($chain->currency->symbol) }}"
                                        data-name="{{ strtolower($chain->currency->name ?? '') }}"
                                        data-chain="{{ strtolower($chain->chain_name) }}"
                                        data-currency-symbol="{{ $chain->currency->symbol }}" data-chain-id="{{ $chain->id }}"
                                        data-chain-name="{{ $chain->chain_name }}" data-chain-enum="{{ $chain->chain->value }}"
                                        data-logo="{{ $chain->currency->coinLogo() }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="position-relative">
                                                    <img src="{{ $chain->currency->coinLogo() }}" class="rounded-circle"
                                                        width="36" height="36">
                                                    @if ($chainIcon)
                                                        <img src="{{ $chainIcon }}"
                                                            class="position-absolute rounded-circle border border-white" width="18"
                                                            height="18" style="bottom: -2px; right: -2px; background: #fff;"
                                                            title="{{ $chain->chain_name }}">
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $chain->currency->symbol }}</span>
                                                    <small
                                                        class="text-secondary d-block">{{ $chain->currency->persian_name ?? $chain->currency->name }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-primary">{{ $chain->chain_name }}</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary select-currency-btn">
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
                            <span class="text-secondary" id="currencyCountInfo">{{ $currencyChainsList->count() }}
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
                        <p class="mt-2 text-secondary">در حال دریافت لیست والت‌ها از سوییپر...</p>
                    </div>
                    <div id="walletListError" style="display: none;">
                        <div class="alert alert-danger mb-0">
                            <i class="fa-regular fa-exclamation-triangle me-2"></i>
                            <span id="walletListErrorText">خطا در دریافت لیست والت‌ها</span>
                        </div>
                    </div>
                    <div id="walletListContainer" style="display: none;">
                        <p class="text-secondary mb-3">
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

    {{-- Sweep Progress Modal --}}
    <div class="modal fade" id="sweepProgressModal" tabindex="-1" aria-labelledby="sweepProgressModalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sweepProgressModalLabel">
                        <i class="fa-regular fa-money-bill-transfer me-2 text-primary"></i>
                        برداشت از ایندکس‌ها
                    </h5>
                </div>
                <div class="modal-body">
                    {{-- Sweep Info Card --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">شبکه:</span>
                                <strong id="sweepNetworkInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">والت:</span>
                                <strong id="sweepWalletInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">کوین:</span>
                                <strong id="sweepCoinInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">تعداد ایندکس:</span>
                                <strong id="sweepCountInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary">وضعیت:</span>
                                <span id="sweepStatusBadge" class="badge bg-info">در حال پردازش...</span>
                            </div>
                        </div>
                    </div>

                    {{-- Sweep Progress Bar --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">پیشرفت</span>
                            <span class="fw-bold"><span id="sweepPercentage">0</span>%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div id="sweepProgressBar"
                                class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0"
                                aria-valuemax="100">
                                <span id="sweepProgressBarText">0%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Current Status Message --}}
                    <div class="alert alert-info" id="sweepCurrentMessage">
                        <i class="fa-regular fa-spinner fa-spin me-2"></i>
                        <span id="sweepMessageText">در حال ارسال درخواست برداشت...</span>
                    </div>

                    {{-- Sweep Statistics Cards --}}
                    <div class="row g-3 mb-3" id="sweepStatsCards" style="display: none;">
                        <div class="col-md-3">
                            <div class="card bg-label-primary">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="sweepTotalCount">0</h3>
                                    <small class="text-secondary">کل درخواست</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-label-warning">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="sweepApprovalCount">0</h3>
                                    <small class="text-secondary">در انتظار تایید</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-label-success">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="sweepSuccessCount">0</h3>
                                    <small class="text-secondary">موفق</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-label-danger">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="sweepFailCount">0</h3>
                                    <small class="text-secondary">ناموفق</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sweep Results List --}}
                    <div id="sweepResultsList" style="display: none;">
                        <h6 class="mb-3">
                            <i class="fa-regular fa-list me-1 text-primary"></i>
                            نتایج برداشت:
                        </h6>
                        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th style="width: 80px;">ایندکس</th>
                                        <th>آدرس مبدا</th>
                                        <th style="width: 110px;">مقدار</th>
                                        <th style="width: 80px;">کوین</th>
                                        <th style="width: 130px;">وضعیت</th>
                                        <th>شناسه تراکنش</th>
                                    </tr>
                                </thead>
                                <tbody id="sweepResultsTableBody">
                                    {{-- Loaded dynamically --}}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Sweep Errors List --}}
                    <div id="sweepErrorsList" style="display: none;">
                        <h6 class="mb-3 text-danger">
                            <i class="fa-regular fa-exclamation-triangle me-2"></i>
                            خطاها (<span id="sweepErrorsCount">0</span>)
                        </h6>
                        <div class="list-group" style="max-height: 200px; overflow-y: auto;" id="sweepErrorsListBody">
                            {{-- Loaded dynamically --}}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="sweepCloseBtn" data-bs-dismiss="modal"
                        style="display: none;">
                        بستن
                    </button>
                    <button type="button" class="btn btn-primary" id="sweepRefreshDataBtn" style="display: none;">
                        <i class="fa-regular fa-refresh me-1"></i>
                        بروزرسانی داده‌ها
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Sync Progress Modal --}}
    <div class="modal fade" id="syncProgressModal" tabindex="-1" aria-labelledby="syncProgressModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
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
                                <span class="text-secondary">کوین:</span>
                                <strong id="syncCurrencyInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">شبکه:</span>
                                <strong id="syncChainInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary">وضعیت:</span>
                                <span id="syncStatusBadge" class="badge bg-info">در حال پردازش...</span>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">پیشرفت</span>
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
                                    <small class="text-secondary">کل آدرس‌ها</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-label-success">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="syncNewTransactions">0</h3>
                                    <small class="text-secondary">تراکنش جدید</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-label-danger">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="syncErrorCount">0</h3>
                                    <small class="text-secondary">خطا</small>
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

    {{-- Fund Gas Modal - Wallet & Amount Selection --}}
    <div class="modal modal-lg fade" id="fundWalletSelectModal" tabindex="-1" aria-labelledby="fundWalletSelectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fundWalletSelectModalLabel">
                        <i class="fa-regular fa-gas-pump me-2 text-success"></i>
                        واریز گس به ایندکس‌های انتخابی
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Loading --}}
                    <div class="text-center py-4" id="fundWalletListLoading">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">در حال بارگذاری...</span>
                        </div>
                        <p class="mt-2 text-secondary">در حال دریافت لیست والت‌ها...</p>
                    </div>
                    {{-- Error --}}
                    <div id="fundWalletListError" style="display: none;">
                        <div class="alert alert-danger mb-0">
                            <i class="fa-regular fa-exclamation-triangle me-2"></i>
                            <span id="fundWalletListErrorText">خطا در دریافت لیست والت‌ها</span>
                        </div>
                    </div>
                    {{-- Wallet List & Amount Input --}}
                    <div id="fundWalletListContainer" style="display: none;">
                        <div class="alert alert-info mb-3">
                            <i class="fa-regular fa-info-circle me-2"></i>
                            <small>
                                این عملیات از <strong>ایندکس شماره ۱</strong> مقداری کوین اصلی شبکه
                                (<span id="fundNativeCoinLabel">-</span>)
                                به <strong id="fundIndicesCount">0</strong> آدرس انتخابی واریز می‌کند
                                تا بتوانند فی تراکنش توکن را پرداخت کنند.
                            </small>
                        </div>

                        {{-- Gas Estimation Card --}}
                        <div class="card bg-label-success mb-3" id="fundGasEstimateCard" style="display: none;">
                            <div class="card-body">
                                <h6 class="mb-3">
                                    <i class="fa-regular fa-calculator me-2"></i>
                                    تخمین هزینه گس (برای <span id="fundGasEstimateCount">0</span> آدرس)
                                </h6>

                                {{-- Price Info Row --}}
                                <div class="row g-2 mb-3 pb-3 border-bottom">
                                    <div class="col-md-6 d-flex align-items-center">
                                        <small class="text-secondary me-2">قیمت <span
                                                id="fundNativeCoinLabel4">-</span>:</small>
                                        <strong id="fundNativeCoinPriceUSD" class="text-primary fs-6">-</strong>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-center">
                                        <small class="text-secondary me-2">Gas Limit:</small>
                                        <strong id="fundGasLimit" class="text-primary">-</strong>
                                    </div>
                                </div>

                                {{-- Gas Price Levels --}}
                                <div id="fundGasLevelsContainer" style="display: none;">
                                    <p class="text-secondary mb-2 fw-bold"><small>سطوح قیمت گس:</small></p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 30%;"><small>سطح</small></th>
                                                    <th style="width: 25%;"><small>Gwei</small></th>
                                                    <th style="width: 30%; text-align: right;"><small>هزینه</small></th>
                                                    <th style="width: 15%; text-align: right;"><small>USD</small></th>
                                                </tr>
                                            </thead>
                                            <tbody id="fundGasLevelsBody">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {{-- Single Level Display (for TRC20) --}}
                                <div id="fundGasSingleLevel" style="display: none;">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <small class="text-secondary d-block">هزینه هر آدرس:</small>
                                            <strong id="fundGasCostPerAddress">-</strong>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-secondary d-block">مجموع هزینه:</small>
                                            <strong id="fundTotalGasCostNative">-</strong>
                                        </div>
                                        <div class="col-12" id="fundTotalUSDContainer" style="display: none;">
                                            <small class="text-secondary d-block">معادل دلاری:</small>
                                            <strong id="fundTotalGasCostUSD" class="text-success fs-5">-</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 pt-2 border-top">
                                    <small class="text-secondary" id="fundGasNote"></small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="fundAmountInput" class="form-label fw-bold">
                                مقدار واریز به هر آدرس (<span id="fundNativeCoinLabel2">-</span>)
                            </label>
                            <input type="number" class="form-control" id="fundAmountInput" placeholder="مثلاً 0.001 یا 2"
                                step="any" min="0">
                            <div class="form-text mt-2">
                                مجموع مورد نیاز: <strong id="fundTotalRequired" class="ms-1 fs-6">0</strong>
                                <span id="fundNativeCoinLabel3">-</span>
                                <span class="ms-2 text-secondary">
                                    (معادل: <strong id="fundTotalRequiredUSDT" class="ms-1">-</strong> USDT)
                                </span>
                                (برای <span id="fundIndicesCount2">0</span> آدرس)
                            </div>
                        </div>
                        <hr>
                        <p class="text-secondary mb-2 fw-bold">والت مورد نظر را انتخاب کنید:</p>
                        <div class="list-group" id="fundWalletListBody">
                            {{-- Wallets loaded via AJAX --}}
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-start">
                    <button type="button" class="btn btn-success" id="fundConfirmBtn" disabled>
                        <i class="fa-regular fa-check me-1"></i>
                        تایید و شروع واریز
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>

                </div>
            </div>
        </div>
    </div>

    {{-- Fund Progress Modal --}}
    <div class="modal fade" id="fundProgressModal" tabindex="-1" aria-labelledby="fundProgressModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fundProgressModalLabel">
                        <i class="fa-regular fa-gas-pump me-2 text-success"></i>
                        واریز گس به ایندکس‌ها
                    </h5>
                </div>
                <div class="modal-body">
                    {{-- Fund Info Card --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">شبکه:</span>
                                <strong id="fundNetworkInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">والت:</span>
                                <strong id="fundWalletInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">مقدار به هر آدرس:</span>
                                <strong id="fundAmountInfo">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">منبع واریز:</span>
                                <strong>ایندکس ۱</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary">وضعیت:</span>
                                <span id="fundStatusBadge" class="badge bg-info">در حال پردازش...</span>
                            </div>
                        </div>
                    </div>

                    {{-- Fund Progress Bar --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">پیشرفت</span>
                            <span class="fw-bold"><span id="fundPercentage">0</span>%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div id="fundProgressBar"
                                class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0"
                                aria-valuemax="100">
                                <span id="fundProgressBarText">0%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Current Status Message --}}
                    <div class="alert alert-info" id="fundCurrentMessage">
                        <i class="fa-regular fa-spinner fa-spin me-2"></i>
                        <span id="fundMessageText">در حال ارسال درخواست به سرور...</span>
                    </div>

                    {{-- Fund Statistics Cards --}}
                    <div class="row g-3 mb-3" id="fundStatsCards" style="display: none;">
                        <div class="col-md-3">
                            <div class="card bg-label-primary">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="fundTotalCount">0</h3>
                                    <small class="text-secondary">کل درخواست</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-label-success">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="fundSuccessCount">0</h3>
                                    <small class="text-secondary">موفق</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-label-danger">
                                <div class="card-body text-center">
                                    <h3 class="mb-0" id="fundFailCount">0</h3>
                                    <small class="text-secondary">ناموفق</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-label-warning">
                                <div class="card-body text-center">
                                    <h6 class="mb-0" id="fundTotalSent">0</h6>
                                    <small class="text-secondary">مجموع ارسالی</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Fund Results List --}}
                    <div id="fundResultsList" style="display: none;">
                        <h6 class="mb-3">نتایج واریز:</h6>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th style="width: 80px;">ایندکس</th>
                                        <th>آدرس مقصد</th>
                                        <th style="width: 100px;">مقدار</th>
                                        <th style="width: 100px;">فی</th>
                                        <th style="width: 80px;">وضعیت</th>
                                        <th>هش تراکنش</th>
                                    </tr>
                                </thead>
                                <tbody id="fundResultsTableBody">
                                    {{-- Loaded dynamically --}}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Fund Errors List --}}
                    <div id="fundErrorsList" style="display: none;">
                        <h6 class="mb-3 text-danger">
                            <i class="fa-regular fa-exclamation-triangle me-2"></i>
                            خطاها (<span id="fundErrorsCount">0</span>)
                        </h6>
                        <div class="list-group" style="max-height: 200px; overflow-y: auto;" id="fundErrorsListBody">
                            {{-- Loaded dynamically --}}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="fundCloseBtn" data-bs-dismiss="modal"
                        style="display: none;">
                        بستن
                    </button>
                    <button type="button" class="btn btn-primary" id="fundRefreshDataBtn" style="display: none;">
                        <i class="fa-regular fa-refresh me-1"></i>
                        بروزرسانی داده‌ها
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/spinkit/spinkit.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/block-ui/block-ui.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    <script>
        $(document).ready(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection

@push('scripts')
    <style>
        .toastify.toastify-rtl {
            direction: rtl;
            text-align: right;
        }

        /* Fund wallet selection highlight */
        .fund-wallet-select-item.active.bg-label-success {
            background-color: rgba(40, 167, 69, 0.12) !important;
            border-color: #28a745 !important;
            border-width: 2px;
        }

        .fund-wallet-select-item:hover {
            cursor: pointer;
            border-color: #696cff;
        }
    </style>
    <script>
        // Explorer URL maps
        const chainExplorerTx = {
            'ERC20': 'https://etherscan.io/tx/',
            'BSC': 'https://bscscan.com/tx/',
            'TRC20': 'https://tronscan.org/#/transaction/',
            'BTC': 'https://blockchair.com/bitcoin/transaction/',
            'DOGE': 'https://blockchair.com/dogecoin/transaction/',
            'LTC': 'https://blockchair.com/litecoin/transaction/',
            'POLYGON': 'https://polygonscan.com/tx/',
            'OPTIMISM': 'https://optimistic.etherscan.io/tx/',
            'AVALANCHE': 'https://snowtrace.io/tx/',
            'ARBITRUM': 'https://arbiscan.io/tx/',
            'SONIC': 'https://sonicscan.org/tx/',
        };
        const chainExplorerAddr = {
            'ERC20': 'https://etherscan.io/address/',
            'BSC': 'https://bscscan.com/address/',
            'TRC20': 'https://tronscan.org/#/address/',
            'BTC': 'https://blockchair.com/bitcoin/address/',
            'DOGE': 'https://blockchair.com/dogecoin/address/',
            'LTC': 'https://blockchair.com/litecoin/address/',
            'POLYGON': 'https://polygonscan.com/address/',
            'OPTIMISM': 'https://optimistic.etherscan.io/address/',
            'AVALANCHE': 'https://snowtrace.io/address/',
            'ARBITRUM': 'https://arbiscan.io/address/',
            'SONIC': 'https://sonicscan.org/address/',
        };

        document.addEventListener('DOMContentLoaded', function () {
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
            const fundSelectedBtn = document.getElementById('fundSelectedBtn');
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
                    fundSelectedBtn.style.display = '';
                    fundSelectedBtn.disabled = false;
                    selectedCountEl.textContent = count;
                } else {
                    selectedCountBadge.style.display = 'none';
                    sweepSelectedBtn.style.display = 'none';
                    sweepSelectedBtn.disabled = true;
                    fundSelectedBtn.style.display = 'none';
                    fundSelectedBtn.disabled = true;
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
            document.addEventListener('change', function (e) {
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

            // Fund results copy button - event delegation
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.fund-copy-btn');
                if (!btn) return;
                const text = btn.dataset.copyText;
                if (!text) return;
                navigator.clipboard.writeText(text).then(() => {
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.classList.replace('fa-clone', 'fa-check');
                        setTimeout(() => icon.classList.replace('fa-check', 'fa-clone'), 1500);
                    }
                }).catch(() => { });
            });

            // Sweep results copy button - event delegation
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.sweep-copy-btn');
                if (!btn) return;
                const text = btn.dataset.copyText;
                if (!text) return;
                navigator.clipboard.writeText(text).then(() => {
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.classList.replace('fa-clone', 'fa-check');
                        setTimeout(() => icon.classList.replace('fa-check', 'fa-clone'), 1500);
                    }
                }).catch(() => { });
            });

            // Select All checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function () {
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
            const sweepProgressModalEl = document.getElementById('sweepProgressModal');
            const sweepProgressModal = new bootstrap.Modal(sweepProgressModalEl);
            const walletListLoading = document.getElementById('walletListLoading');
            const walletListError = document.getElementById('walletListError');
            const walletListErrorText = document.getElementById('walletListErrorText');
            const walletListContainer = document.getElementById('walletListContainer');
            const walletListBody = document.getElementById('walletListBody');
            const sweepIndicesCountEl = document.getElementById('sweepIndicesCount');

            // Sweep Selected Button Click → Open wallet selection modal
            if (sweepSelectedBtn) {
                sweepSelectedBtn.addEventListener('click', function () {
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
                        '{{ route('admin.hd-wallet.sweeper-wallets') }}', {
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
                    'DOGE': 'dogecoin',
                    'LTC': 'litecoin',
                    'OPTIMISM': 'optimism',
                    'AVALANCHE': 'avalanche',
                    'ARBITRUM': 'arbitrum',
                    'SONIC': 'sonic'
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
                                                                <small class="text-secondary ms-1">(${wallet.walletId})</small>
                                                            </h6>
                                                            <small class="text-secondary d-block">
                                                                <i class="fa-regular fa-network-wired me-1"></i>
                                                                شبکه‌ها: ${networks}
                                                            </small>
                                                            ${wallet.description ? `<small class="text-secondary d-block"><i class="fa-regular fa-info-circle me-1"></i>${wallet.description}</small>` : ''}
                                                            <small class="text-secondary d-block">
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
            document.addEventListener('click', async function (e) {
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

                // Close wallet modal and show sweep progress modal
                walletSelectModal.hide();

                initializeSweepProgressModal(walletName, currentCurrency, indicesArray.length);
                sweepProgressModal.show();

                try {
                    const total = indicesArray.length;
                    const allResults = [];
                    const allErrors = [];
                    let lastSummary = {};
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                    for (let i = 0; i < total; i++) {
                        const idx = indicesArray[i];
                        const processed = i + 1;
                        const pct = Math.round((processed / total) * 100);

                        document.getElementById('sweepMessageText').textContent =
                            `در حال برداشت از ایندکس ${idx}... (${processed} از ${total})`;

                        try {
                            const response = await fetch(
                                '{{ route('admin.hd-wallet.sweep-selected') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({
                                    indices: [idx],
                                    currency_symbol: currentCurrency,
                                    currency_chain_id: parseInt(currentChainId),
                                    wallet_id: walletId,
                                    force: false
                                })
                            });

                            const result = await response.json();

                            if (result.success && result.data) {
                                allResults.push(...(result.data.results || []));
                                allErrors.push(...(result.data.errors || []));
                                if (result.data.summary) {
                                    lastSummary = result.data.summary;
                                }
                            } else {
                                let errMsg = result.error || 'خطای ناشناخته';
                                if (result.walletInfo) {
                                    errMsg += ` (والت: ${result.walletInfo.name})`;
                                }
                                allErrors.push({ addressIndex: idx, error: errMsg });
                            }
                        } catch (fetchError) {
                            allErrors.push({
                                addressIndex: idx,
                                error: fetchError.message || 'خطا در ارتباط با سرور'
                            });
                        }

                        // Update progress bar after each index
                        document.getElementById('sweepPercentage').textContent = pct;
                        document.getElementById('sweepProgressBar').style.width = pct + '%';
                        document.getElementById('sweepProgressBar').setAttribute('aria-valuenow', pct);
                        document.getElementById('sweepProgressBarText').textContent = pct + '%';
                    }

                    // Build aggregated data and render final results
                    const finalData = {
                        results: allResults,
                        errors: allErrors,
                        summary: {
                            totalRequested: total,
                            successful: allResults.length,
                            failed: allErrors.length,
                            network: lastSummary.network || '',
                            walletId: walletId,
                            coinType: lastSummary.coinType || currentCurrency,
                        }
                    };

                    updateSweepProgressComplete(finalData);
                } catch (error) {
                    console.error('Error sweeping indices:', error);
                    updateSweepProgressError('خطا در ارتباط با سرور: ' + error.message);
                }
            });

            // ========== SWEEP PROGRESS FUNCTIONS ==========

            // Initialize sweep progress modal display
            function initializeSweepProgressModal(walletName, coinSymbol, count) {
                document.getElementById('sweepNetworkInfo').textContent = currentChainName;
                document.getElementById('sweepWalletInfo').textContent = walletName;
                document.getElementById('sweepCoinInfo').textContent = coinSymbol;
                document.getElementById('sweepCountInfo').textContent = count;
                document.getElementById('sweepStatusBadge').textContent = 'در حال پردازش...';
                document.getElementById('sweepStatusBadge').className = 'badge bg-info';
                document.getElementById('sweepPercentage').textContent = '0';
                document.getElementById('sweepProgressBar').style.width = '0%';
                document.getElementById('sweepProgressBar').className =
                    'progress-bar bg-primary progress-bar-striped progress-bar-animated';
                document.getElementById('sweepProgressBarText').textContent = '0%';
                document.getElementById('sweepCurrentMessage').style.display = '';
                document.getElementById('sweepCurrentMessage').className = 'alert alert-info';
                document.getElementById('sweepMessageText').textContent =
                    `در حال ارسال درخواست برداشت برای ${count} ایندکس...`;
                document.getElementById('sweepStatsCards').style.display = 'none';
                document.getElementById('sweepResultsList').style.display = 'none';
                document.getElementById('sweepErrorsList').style.display = 'none';
                document.getElementById('sweepCloseBtn').style.display = 'none';
                document.getElementById('sweepRefreshDataBtn').style.display = 'none';
            }

            // Status label map for sweep results
            const sweepStatusLabels = {
                'admin_approval': { text: 'در انتظار تایید مدیر', class: 'bg-warning' },
                'broadcasted': { text: 'ارسال شده', class: 'bg-info' },
                'confirmed': { text: 'تایید شده', class: 'bg-success' },
                'failed': { text: 'ناموفق', class: 'bg-danger' },
                'pending': { text: 'در انتظار', class: 'bg-secondary' },
            };

            // Render sweep results on completion
            function updateSweepProgressComplete(data) {
                const summary = data?.summary || {};
                const results = data?.results || [];
                const errors = data?.errors || [];
                const total = summary.totalRequested || (results.length + errors.length);
                const successful = summary.successful ?? results.length;
                const failed = summary.failed ?? errors.length;
                const approvalCount = results.filter(r => r.status === 'admin_approval').length;

                // Progress bar → 100%
                document.getElementById('sweepPercentage').textContent = '100';
                document.getElementById('sweepProgressBar').style.width = '100%';
                document.getElementById('sweepProgressBarText').textContent = '100%';
                document.getElementById('sweepProgressBar').classList.remove('progress-bar-animated');

                // Status badge
                if (failed === 0) {
                    document.getElementById('sweepStatusBadge').textContent = 'تکمیل شد';
                    document.getElementById('sweepStatusBadge').className = 'badge bg-success';
                    document.getElementById('sweepProgressBar').className = 'progress-bar bg-success';
                } else if (successful > 0) {
                    document.getElementById('sweepStatusBadge').textContent = 'تکمیل با خطا';
                    document.getElementById('sweepStatusBadge').className = 'badge bg-warning';
                    document.getElementById('sweepProgressBar').className = 'progress-bar bg-warning';
                } else {
                    document.getElementById('sweepStatusBadge').textContent = 'ناموفق';
                    document.getElementById('sweepStatusBadge').className = 'badge bg-danger';
                    document.getElementById('sweepProgressBar').className = 'progress-bar bg-danger';
                }

                // Hide spinner message
                document.getElementById('sweepCurrentMessage').style.display = 'none';

                // Stats cards
                document.getElementById('sweepStatsCards').style.display = 'flex';
                document.getElementById('sweepTotalCount').textContent = total;
                document.getElementById('sweepApprovalCount').textContent = approvalCount;
                document.getElementById('sweepSuccessCount').textContent = successful;
                document.getElementById('sweepFailCount').textContent = failed;

                // Results table — merge successful results + error rows, sorted by addressIndex
                const allRows = [
                    ...results.map(r => ({ ...r, _isError: false })),
                    ...errors.map(e => ({ addressIndex: e.addressIndex, _isError: true, _errorMsg: e.error || 'خطای ناشناخته', ...e }))
                ].sort((a, b) => (a.addressIndex ?? 0) - (b.addressIndex ?? 0));

                if (allRows.length > 0) {
                    document.getElementById('sweepResultsList').style.display = '';
                    const explorerTxBase = chainExplorerTx[currentChainEnum] || null;
                    const explorerAddrBase = chainExplorerAddr[currentChainEnum] || null;

                    document.getElementById('sweepResultsTableBody').innerHTML = allRows.map((r, idx) => {
                        if (r._isError) {
                            const errText = escapeHtml(r._errorMsg);
                            return `
                                                        <tr class="table-danger">
                                                            <td>${idx + 1}</td>
                                                            <td><strong>${r.addressIndex}</strong></td>
                                                            <td><span class="text-danger">-</span></td>
                                                            <td>-</td>
                                                            <td>-</td>
                                                            <td><span class="badge bg-danger">خطا</span></td>
                                                            <td>
                                                                <span class="d-flex align-items-center gap-1">
                                                                    <i class="fa-regular fa-exclamation-circle text-danger flex-shrink-0"></i>
                                                                    <small class="text-danger">${errText}</small>
                                                                </span>
                                                            </td>
                                                        </tr>`;
                        }

                        const statusInfo = sweepStatusLabels[r.status] || { text: r.status, class: 'bg-secondary' };
                        const isApproval = r.status === 'admin_approval';

                        const shortAddr = r.fromAddress
                            ? (r.fromAddress.substring(0, 10) + '…' + r.fromAddress.substring(r.fromAddress.length - 8))
                            : '-';
                        const shortTxId = r.transactionId
                            ? (r.transactionId.substring(0, 10) + '…' + r.transactionId.substring(r.transactionId.length - 8))
                            : '-';

                        const addrCell = r.fromAddress
                            ? `<div class="d-flex align-items-center gap-1">
                                                        <button class="btn btn-sm btn-icon btn-text-secondary p-0 sweep-copy-btn"
                                                            data-copy-text="${r.fromAddress}" title="کپی آدرس">
                                                            <i class="fa-regular fa-clone fa-sm"></i>
                                                        </button>
                                                        ${explorerAddrBase
                                ? `<a href="${explorerAddrBase}${r.fromAddress}" target="_blank" class="font-number"><small>${shortAddr}</small></a>`
                                : `<small class="font-number">${shortAddr}</small>`}
                                                       </div>`
                            : '-';

                        const txCell = r.transactionId
                            ? `<div class="d-flex align-items-center gap-1">
                                                        <button class="btn btn-sm btn-icon btn-text-secondary p-0 sweep-copy-btn"
                                                            data-copy-text="${r.transactionId}" title="کپی شناسه">
                                                            <i class="fa-regular fa-clone fa-sm"></i>
                                                        </button>
                                                        ${explorerTxBase
                                ? `<a href="${explorerTxBase}${r.transactionId}" target="_blank" class="font-number text-primary"><small>${shortTxId}</small></a>`
                                : `<small class="font-number text-primary">${shortTxId}</small>`}
                                                       </div>`
                            : '-';

                        return `
                                                    <tr class="${isApproval ? 'table-warning' : ''}">
                                                        <td>${idx + 1}</td>
                                                        <td><strong>${r.addressIndex}</strong></td>
                                                        <td>${addrCell}</td>
                                                        <td><span class="fw-semibold text-primary">${r.amount || '-'}</span></td>
                                                        <td><span class="badge bg-label-secondary">${r.assetSymbol || r.coinType || '-'}</span></td>
                                                        <td><span class="badge ${statusInfo.class}">${statusInfo.text}</span></td>
                                                        <td>${txCell}</td>
                                                    </tr>`;
                    }).join('');
                }

                // Separate errors summary (kept for quick reference)
                if (errors.length > 0) {
                    document.getElementById('sweepErrorsList').style.display = '';
                    document.getElementById('sweepErrorsCount').textContent = errors.length;
                    document.getElementById('sweepErrorsListBody').innerHTML = errors.map(e => `
                                                <div class="list-group-item list-group-item-danger">
                                                    <div class="d-flex justify-content-between">
                                                        <strong>ایندکس ${e.addressIndex}</strong>
                                                        <span class="badge bg-danger">خطا</span>
                                                    </div>
                                                    <small>${escapeHtml(e.error || '')}</small>
                                                </div>
                                            `).join('');
                }

                // Show action buttons
                document.getElementById('sweepCloseBtn').style.display = '';
                document.getElementById('sweepRefreshDataBtn').style.display = '';

                // Clear index selection
                selectedIndices.clear();
                updateSelectionUI();
            }

            // Show error state in sweep progress modal
            function updateSweepProgressError(errorMessage) {
                document.getElementById('sweepPercentage').textContent = '0';
                document.getElementById('sweepProgressBar').style.width = '100%';
                document.getElementById('sweepProgressBar').className = 'progress-bar bg-danger';
                document.getElementById('sweepProgressBar').classList.remove('progress-bar-animated');
                document.getElementById('sweepProgressBarText').textContent = 'خطا';
                document.getElementById('sweepStatusBadge').textContent = 'خطا';
                document.getElementById('sweepStatusBadge').className = 'badge bg-danger';
                document.getElementById('sweepMessageText').innerHTML =
                    `<i class="fa-regular fa-exclamation-triangle me-2"></i>${escapeHtml(errorMessage)}`;
                document.getElementById('sweepCurrentMessage').className = 'alert alert-danger';
                document.getElementById('sweepCurrentMessage').style.display = '';
                document.getElementById('sweepCloseBtn').style.display = '';
            }

            // Sweep refresh button
            document.getElementById('sweepRefreshDataBtn')?.addEventListener('click', function () {
                sweepProgressModal.hide();
                loadData();
                showToast('داده‌ها بروزرسانی شد', 'success');
            });

            // ========== END SWEEP PROGRESS FUNCTIONS ==========

            // ========== FUND GAS FUNCTIONALITY ==========

            // Fund Modal Elements
            const fundWalletSelectModalEl = document.getElementById('fundWalletSelectModal');
            const fundWalletSelectModal = new bootstrap.Modal(fundWalletSelectModalEl);
            const fundProgressModalEl = document.getElementById('fundProgressModal');
            const fundProgressModal = new bootstrap.Modal(fundProgressModalEl);
            const fundWalletListLoading = document.getElementById('fundWalletListLoading');
            const fundWalletListError = document.getElementById('fundWalletListError');
            const fundWalletListErrorText = document.getElementById('fundWalletListErrorText');
            const fundWalletListContainer = document.getElementById('fundWalletListContainer');
            const fundWalletListBody = document.getElementById('fundWalletListBody');
            const fundAmountInput = document.getElementById('fundAmountInput');
            const fundConfirmBtn = document.getElementById('fundConfirmBtn');

            console.log('Fund confirm button element:', fundConfirmBtn);

            // Selected wallet state
            let selectedFundWallet = null;
            const fundTotalRequiredUSDT = document.getElementById('fundTotalRequiredUSDT');
            let fundNativeCoinPriceUSD = null;

            // Native coin symbol map
            const chainToNativeCoin = {
                'ERC20': 'ETH',
                'TRC20': 'TRX',
                'BSC': 'BNB'
            };

            // Fund Selected Button Click → Open fund wallet selection modal
            if (fundSelectedBtn) {
                fundSelectedBtn.addEventListener('click', function () {
                    if (selectedIndices.size === 0) {
                        showToast('لطفاً حداقل یک ایندکس انتخاب کنید', 'warning');
                        return;
                    }

                    if (!currentCurrency || !currentChainId) {
                        showToast('لطفاً ابتدا یک کوین و شبکه انتخاب کنید', 'warning');
                        return;
                    }

                    // Check this is a token chain (gas funding only makes sense for tokens)
                    const nativeCoin = chainToNativeCoin[currentChainEnum];
                    if (!nativeCoin) {
                        showToast('واریز گس فقط برای شبکه‌های ERC20، TRC20 و BSC امکان‌پذیر است',
                            'warning');
                        return;
                    }

                    // Update modal info
                    document.getElementById('fundNativeCoinLabel').textContent = nativeCoin;
                    document.getElementById('fundNativeCoinLabel2').textContent = nativeCoin;
                    document.getElementById('fundNativeCoinLabel3').textContent = nativeCoin;
                    document.getElementById('fundNativeCoinLabel4').textContent = nativeCoin;
                    document.getElementById('fundIndicesCount').textContent = selectedIndices.size;
                    document.getElementById('fundIndicesCount2').textContent = selectedIndices.size;

                    // Reset amount input, gas estimate card, and selected wallet
                    fundAmountInput.value = '';
                    document.getElementById('fundTotalRequired').textContent = '0';
                    if (fundTotalRequiredUSDT) fundTotalRequiredUSDT.textContent = '-';
                    fundNativeCoinPriceUSD = null;
                    document.getElementById('fundGasEstimateCard').style.display = 'none';
                    selectedFundWallet = null;
                    if (fundConfirmBtn) fundConfirmBtn.disabled = true;

                    console.log('Fund modal opened. Confirm button:', fundConfirmBtn);

                    // Show loading, hide others
                    fundWalletListLoading.style.display = '';
                    fundWalletListError.style.display = 'none';
                    fundWalletListContainer.style.display = 'none';

                    // Open modal
                    fundWalletSelectModal.show();

                    // Fetch gas estimation and wallets in parallel
                    Promise.all([
                        fetchGasEstimation(),
                        loadFundSweeperWallets()
                    ]);
                });
            }

            // Update total required when amount changes
            if (fundAmountInput) {
                fundAmountInput.addEventListener('input', function () {
                    const amount = parseFloat(this.value) || 0;
                    const total = amount * selectedIndices.size;
                    document.getElementById('fundTotalRequired').textContent = total.toFixed(6);
                    if (fundTotalRequiredUSDT && fundNativeCoinPriceUSD) {
                        const usdtValue = total * fundNativeCoinPriceUSD;
                        fundTotalRequiredUSDT.textContent = usdtValue.toLocaleString({
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    } else if (fundTotalRequiredUSDT) {
                        fundTotalRequiredUSDT.textContent = '-';
                    }

                    // Enable/disable confirm button based on amount and wallet selection
                    if (fundConfirmBtn) {
                        fundConfirmBtn.disabled = !(amount > 0 && selectedFundWallet);
                        console.log('Amount changed:', amount, 'Wallet:', selectedFundWallet,
                            'Button enabled:', !fundConfirmBtn.disabled);
                    }
                });
            }

            // Fetch gas estimation
            async function fetchGasEstimation() {
                try {
                    const response = await fetch(
                        '{{ route('admin.hd-wallet.estimate-gas-funding') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            currency_chain_id: parseInt(currentChainId),
                            indices_count: selectedIndices.size
                        })
                    });

                    const result = await response.json();

                    if (result.success && result.data) {
                        const data = result.data;

                        // Helper function for smart USD formatting
                        const formatUSD = (value) => {
                            if (!value || value == 0) return '-';
                            const num = Number(value);
                            if (num >= 0.01) {
                                return '$' + num.toFixed(2);
                            } else if (num >= 0.0001) {
                                return '$' + num.toFixed(4);
                            } else if (num >= 0.000001) {
                                return '$' + num.toFixed(6);
                            } else {
                                return '$' + num.toFixed(8);
                            }
                        };

                        // Show gas estimate card
                        document.getElementById('fundGasEstimateCard').style.display = '';
                        document.getElementById('fundGasEstimateCount').textContent = data.indicesCount ||
                            selectedIndices.size;

                        fundNativeCoinPriceUSD = data.nativeCoinPriceUSD ? Number(data.nativeCoinPriceUSD) :
                            null;

                        // Update price info
                        document.getElementById('fundNativeCoinPriceUSD').textContent =
                            data.nativeCoinPriceUSD ? '$' + Number(data.nativeCoinPriceUSD).toFixed(2) :
                                'نامشخص';
                        document.getElementById('fundGasLimit').textContent =
                            data.gasLimit ? Number(data.gasLimit).toLocaleString() : '-';

                        // Check if we have multiple levels (ERC20/BSC)
                        if (data.allLevels && Object.keys(data.allLevels).length > 0) {
                            // Show levels table
                            document.getElementById('fundGasLevelsContainer').style.display = '';
                            document.getElementById('fundGasSingleLevel').style.display = 'none';

                            const levelsBody = document.getElementById('fundGasLevelsBody');
                            levelsBody.innerHTML = '';

                            const levelNames = {
                                'SafeGasPrice': '🟢 کم',
                                'ProposeGasPrice': '🟡 متوسط',
                                'FastGasPrice': '🔴 سریع'
                            };

                            Object.entries(data.allLevels).forEach(([level, levelData]) => {
                                const usdText = levelData.totalCost && data.nativeCoinPriceUSD ?
                                    formatUSD(levelData.totalCost) :
                                    '-';

                                const row = document.createElement('tr');

                                row.innerHTML = `
                                                            <td><small><strong>${levelNames[level] || level}</strong></small></td>
                                                            <td><small class="badge bg-label-secondary">${levelData.gwei}</small></td>
                                                            <td style="text-align: right;"><small><strong>${levelData.costPerAddress} </strong>${data.nativeSymbol}</small></td>
                                                            <td style="text-align: right;"><small class="text-success">${usdText}</small></td>
                                                        `;
                                levelsBody.appendChild(row);
                            });

                            // Set suggested amount (use ProposeGasPrice level)
                            if (data.allLevels.ProposeGasPrice) {

                                fundAmountInput.value = data.allLevels.SafeGasPrice.costPerAddress;
                                fundAmountInput.dispatchEvent(new Event('input'));
                            }
                        } else {
                            // Show single level (TRC20 or fallback)
                            document.getElementById('fundGasLevelsContainer').style.display = 'none';
                            document.getElementById('fundGasSingleLevel').style.display = '';

                            document.getElementById('fundGasCostPerAddress').textContent =
                                data.costPerAddressFormatted || '-';
                            document.getElementById('fundTotalGasCostNative').textContent =
                                data.totalCostNativeFormatted || '-';

                            if (data.totalCostUSD && data.nativeCoinPriceUSD) {
                                document.getElementById('fundTotalUSDContainer').style.display = '';
                                const usdValue = data.totalCostUSD ? formatUSD(data.totalCostUSD) : (data
                                    .totalCostUSDFormatted || '-');
                                document.getElementById('fundTotalGasCostUSD').textContent = usdValue;
                            } else {
                                document.getElementById('fundTotalUSDContainer').style.display = 'none';
                            }

                            // Set suggested amount in input
                            if (data.costPerAddress) {
                                fundAmountInput.value = data.costPerAddress;
                                fundAmountInput.dispatchEvent(new Event('input'));
                            }
                        }

                        document.getElementById('fundGasNote').textContent =
                            data.recommendation || '';
                    } else {
                        // Don't show error for gas estimation, it's optional
                        console.warn('Gas estimation failed:', result.error);
                    }
                } catch (error) {
                    console.error('Error fetching gas estimation:', error);
                }
            }

            // Load wallets for fund modal
            async function loadFundSweeperWallets() {
                try {
                    const response = await fetch(
                        '{{ route('admin.hd-wallet.sweeper-wallets') }}', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    const result = await response.json();

                    if (result.success && result.data?.wallets?.length > 0) {
                        renderFundWalletList(result.data.wallets);
                        fundWalletListLoading.style.display = 'none';
                        fundWalletListContainer.style.display = '';
                    } else if (result.success && (!result.data?.wallets || result.data.wallets.length === 0)) {
                        fundWalletListLoading.style.display = 'none';
                        fundWalletListErrorText.textContent = 'هیچ والتی یافت نشد';
                        fundWalletListError.style.display = '';
                    } else {
                        fundWalletListLoading.style.display = 'none';
                        fundWalletListErrorText.textContent = result.error || 'خطا در دریافت لیست والت‌ها';
                        fundWalletListError.style.display = '';
                    }
                } catch (error) {
                    console.error('Error loading wallets for fund:', error);
                    fundWalletListLoading.style.display = 'none';
                    fundWalletListErrorText.textContent = 'خطا در ارتباط با سرور: ' + error.message;
                    fundWalletListError.style.display = '';
                }
            }

            // Render wallet list in fund modal
            function renderFundWalletList(wallets) {
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

                const chainToNetwork = {
                    'BTC': 'bitcoin',
                    'ERC20': 'ethereum',
                    'TRC20': 'tron',
                    'BSC': 'bnb',
                    'DOGE': 'dogecoin',
                    'LTC': 'litecoin',
                    'OPTIMISM': 'optimism',
                    'AVALANCHE': 'avalanche',
                    'ARBITRUM': 'arbitrum',
                    'SONIC': 'sonic'
                };

                const requiredNetwork = chainToNetwork[currentChainEnum];
                const filteredWallets = wallets.filter(wallet => {
                    if (!requiredNetwork) return true;
                    return wallet.supportedNetworks && wallet.supportedNetworks.includes(requiredNetwork);
                });

                if (filteredWallets.length === 0) {
                    fundWalletListLoading.style.display = 'none';
                    fundWalletListErrorText.textContent = `هیچ والتی برای شبکه ${currentChainName} یافت نشد`;
                    fundWalletListError.style.display = '';
                    return;
                }

                fundWalletListBody.innerHTML = filteredWallets.map((wallet, index) => {
                    const statusInfo = statusLabels[wallet.status] || {
                        text: wallet.status,
                        class: 'bg-secondary'
                    };
                    const lastUsed = wallet.lastUsed ? new Date(wallet.lastUsed).toLocaleDateString(
                        'fa-IR') : '-';

                    return `
                                                <a href="#" class="list-group-item list-group-item-action fund-wallet-select-item ${wallet.status !== 'active' ? 'opacity-50' : ''}"
                                                   data-wallet-id="${wallet.walletId}"
                                                   data-wallet-name="${wallet.name}"
                                                   data-wallet-status="${wallet.status}"
                                                   data-wallet-index="${index}">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                <i class="fa-regular fa-wallet me-2 text-success"></i>
                                                                ${wallet.name}
                                                                <small class="text-secondary ms-1">(${wallet.walletId})</small>
                                                            </h6>
                                                            <small class="text-secondary d-block">
                                                                <i class="fa-regular fa-clock me-1"></i>
                                                                آخرین استفاده: ${lastUsed}
                                                            </small>
                                                        </div>
                                                        <span class="badge ${statusInfo.class}">${statusInfo.text}</span>
                                                    </div>
                                                </a>
                                            `;
                }).join('');
            }

            // Handle fund wallet selection click → select wallet
            document.addEventListener('click', function (e) {
                const walletItem = e.target.closest('.fund-wallet-select-item');
                if (!walletItem) return;

                e.preventDefault();

                const walletId = walletItem.dataset.walletId;
                const walletName = walletItem.dataset.walletName;
                const walletStatus = walletItem.dataset.walletStatus;

                if (walletStatus !== 'active') {
                    showToast('این والت غیرفعال است', 'warning');
                    return;
                }

                // Highlight selected wallet
                document.querySelectorAll('.fund-wallet-select-item').forEach(item => {
                    item.classList.remove('active', 'bg-label-success');
                });
                walletItem.classList.add('active', 'bg-label-success');

                // Save selected wallet
                selectedFundWallet = {
                    walletId,
                    walletName,
                    walletStatus
                };

                console.log('Wallet selected:', selectedFundWallet);

                // Enable confirm button if amount is valid
                const fundAmount = parseFloat(fundAmountInput.value);
                if (fundConfirmBtn) {
                    fundConfirmBtn.disabled = !(fundAmount > 0);
                    console.log('Confirm button state:', !fundConfirmBtn.disabled, 'Amount:', fundAmount);
                }
            });

            // Handle fund confirm button click
            if (fundConfirmBtn) {
                console.log('Fund confirm button found, attaching event listener');
                fundConfirmBtn.addEventListener('click', async function (e) {
                    console.log('Fund confirm button clicked!');
                    e.preventDefault(); // Prevent any default behavior

                    if (!selectedFundWallet) {
                        console.log('No wallet selected');
                        showToast('لطفاً یک والت انتخاب کنید', 'warning');
                        return;
                    }

                    // Validate amount
                    const fundAmount = parseFloat(fundAmountInput.value);
                    if (!fundAmount || fundAmount <= 0) {
                        showToast('لطفاً مقدار واریز را وارد کنید', 'warning');
                        return;
                    }

                    const nativeCoin = chainToNativeCoin[currentChainEnum];
                    const indicesArray = Array.from(selectedIndices).sort((a, b) => a - b);
                    const totalAmount = (fundAmount * indicesArray.length).toFixed(6);

                    const confirmText =
                        `آیا از واریز گس اطمینان دارید?\n\n` +
                        `والت: ${selectedFundWallet.walletName} (${selectedFundWallet.walletId})\n` +
                        `شبکه: ${currentChainName}\n` +
                        `کوین: ${nativeCoin}\n` +
                        `مقدار به هر آدرس: ${fundAmount} ${nativeCoin}\n` +
                        `تعداد آدرس: ${indicesArray.length}\n` +
                        `مجموع: ${totalAmount} ${nativeCoin}\n` +
                        `منبع: ایندکس ۱\n` +
                        `ایندکس‌ها: ${indicesArray.join(', ')}`;

                    const confirmResult = await Swal.fire({
                        title: 'تایید واریز گس',
                        html: `<div style="white-space: pre-line; text-align: right;">${escapeHtml(confirmText)}</div>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#8592a3',
                        confirmButtonText: 'بله، واریز کن',
                        cancelButtonText: 'انصراف',
                        customClass: {
                            container: 'swal2-fund-zindex',
                            confirmButton: 'btn btn-success me-2',
                            cancelButton: 'btn btn-label-secondary'
                        },
                        buttonsStyling: false,
                        didOpen: (popup) => popup.setAttribute('dir', 'rtl')
                    });

                    if (!confirmResult.isConfirmed) return;

                    // Close wallet modal
                    fundWalletSelectModal.hide();

                    // Show progress modal
                    initializeFundProgressModal(selectedFundWallet.walletName, nativeCoin, fundAmount,
                        indicesArray.length);
                    fundProgressModal.show();

                    // Execute fund request - one index at a time for real-time progress updates
                    try {
                        // Convert amount to decimal string to avoid scientific notation (e.g., 3.25E-6)
                        const amountStr = parseFloat(fundAmount).toFixed(18).replace(/\.?0+$/, '');

                        const total = indicesArray.length;
                        const allResults = [];
                        const allErrors = [];
                        let lastSummary = {};
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                        for (let i = 0; i < total; i++) {
                            const idx = indicesArray[i];
                            const processed = i + 1;
                            const pct = Math.round((processed / total) * 100);

                            // Update in-progress message
                            document.getElementById('fundMessageText').textContent =
                                `در حال واریز به ایندکس ${idx}... (${processed} از ${total})`;

                            try {
                                const response = await fetch(
                                    '{{ route('admin.hd-wallet.fund-selected') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken
                                    },
                                    body: JSON.stringify({
                                        indices: [idx],
                                        currency_chain_id: parseInt(currentChainId),
                                        wallet_id: selectedFundWallet.walletId,
                                        amount: amountStr
                                    })
                                });

                                const result = await response.json();

                                if (result.success && result.data) {
                                    allResults.push(...(result.data.results || []));
                                    allErrors.push(...(result.data.errors || []));
                                    if (result.data.summary) {
                                        lastSummary = result.data.summary;
                                    }
                                } else {
                                    allErrors.push({
                                        addressIndex: idx,
                                        error: result.error || 'خطای ناشناخته'
                                    });
                                }
                            } catch (fetchError) {
                                allErrors.push({
                                    addressIndex: idx,
                                    error: fetchError.message || 'خطا در ارتباط با سرور'
                                });
                            }

                            // Update progress bar after each index completes
                            document.getElementById('fundPercentage').textContent = pct;
                            document.getElementById('fundProgressBar').style.width = pct + '%';
                            document.getElementById('fundProgressBar').setAttribute('aria-valuenow',
                                pct);
                            document.getElementById('fundProgressBarText').textContent = pct + '%';
                        }

                        // Build final aggregated data and render summary
                        const finalData = {
                            results: allResults,
                            errors: allErrors,
                            summary: {
                                totalRequested: total,
                                successful: allResults.length,
                                failed: allErrors.length,
                                totalAmountSent: (parseFloat(amountStr) * allResults.length)
                                    .toFixed(18).replace(/\.?0+$/, ''),
                                nativeSymbol: lastSummary.nativeSymbol || nativeCoin,
                                network: lastSummary.network || '',
                                walletId: selectedFundWallet.walletId,
                                fundingSourceIndex: 1,
                                fundingSourceAddress: lastSummary.fundingSourceAddress || '',
                                amountPerAddress: amountStr,
                            }
                        };

                        updateFundProgressComplete(finalData);
                    } catch (error) {
                        console.error('Error funding indices:', error);
                        updateFundProgressError('خطا در ارتباط با سرور: ' + error.message);
                    }
                });
            } else {
                console.error('Fund confirm button NOT FOUND!');
            }

            // Initialize fund progress modal
            function initializeFundProgressModal(walletName, nativeCoin, amount, count) {
                document.getElementById('fundNetworkInfo').textContent = currentChainName;
                document.getElementById('fundWalletInfo').textContent = walletName;
                document.getElementById('fundAmountInfo').textContent = `${amount} ${nativeCoin}`;
                document.getElementById('fundStatusBadge').textContent = 'در حال پردازش...';
                document.getElementById('fundStatusBadge').className = 'badge bg-info';
                document.getElementById('fundPercentage').textContent = '0';
                document.getElementById('fundProgressBar').style.width = '0%';
                document.getElementById('fundProgressBarText').textContent = '0%';
                document.getElementById('fundCurrentMessage').style.display = '';
                document.getElementById('fundMessageText').textContent =
                    `در حال واریز ${nativeCoin} به ${count} آدرس...`;
                document.getElementById('fundStatsCards').style.display = 'none';
                document.getElementById('fundResultsList').style.display = 'none';
                document.getElementById('fundErrorsList').style.display = 'none';
                document.getElementById('fundCloseBtn').style.display = 'none';
                document.getElementById('fundRefreshDataBtn').style.display = 'none';
            }

            // Update fund progress on completion
            function updateFundProgressComplete(data) {
                const summary = data.summary || {};
                const results = data.results || [];
                const errors = data.errors || [];
                const total = summary.totalRequested || 0;
                const successful = summary.successful || 0;
                const failed = summary.failed || 0;

                // Update progress bar to 100%
                document.getElementById('fundPercentage').textContent = '100';
                document.getElementById('fundProgressBar').style.width = '100%';
                document.getElementById('fundProgressBarText').textContent = '100%';
                document.getElementById('fundProgressBar').classList.remove('progress-bar-animated');

                // Update status
                if (failed === 0) {
                    document.getElementById('fundStatusBadge').textContent = 'تکمیل شد';
                    document.getElementById('fundStatusBadge').className = 'badge bg-success';
                    document.getElementById('fundProgressBar').className = 'progress-bar bg-success';
                } else if (successful > 0) {
                    document.getElementById('fundStatusBadge').textContent = 'تکمیل با خطا';
                    document.getElementById('fundStatusBadge').className = 'badge bg-warning';
                    document.getElementById('fundProgressBar').className = 'progress-bar bg-warning';
                } else {
                    document.getElementById('fundStatusBadge').textContent = 'ناموفق';
                    document.getElementById('fundStatusBadge').className = 'badge bg-danger';
                    document.getElementById('fundProgressBar').className = 'progress-bar bg-danger';
                }

                // Update message
                const nativeSymbol = summary.nativeSymbol || '';
                document.getElementById('fundCurrentMessage').style.display = 'none';

                // Show stats cards
                document.getElementById('fundStatsCards').style.display = 'flex';
                document.getElementById('fundTotalCount').textContent = total;
                document.getElementById('fundSuccessCount').textContent = successful;
                document.getElementById('fundFailCount').textContent = failed;
                document.getElementById('fundTotalSent').textContent =
                    (summary.totalAmountSent || '0') + ' ' + nativeSymbol;

                // Show results table
                if (results.length > 0) {
                    document.getElementById('fundResultsList').style.display = '';
                    document.getElementById('fundResultsTableBody').innerHTML = results.map((r, idx) => {
                        const shortHash = r.txHash ? (r.txHash.substring(0, 10) + '...' + r.txHash
                            .substring(r.txHash.length - 8)) : '-';
                        const explorerTxBase = chainExplorerTx[currentChainEnum] || null;
                        const explorerAddrBase = chainExplorerAddr[currentChainEnum] || null;
                        const shortAddr = r.toAddress ?
                            (r.toAddress.substring(0, 12) + '...' + r.toAddress.substring(r.toAddress
                                .length - 8)) :
                            '-';

                        const addrCell = r.toAddress ?
                            `<div class="d-flex align-items-center gap-1">
                                                        <button class="btn btn-sm btn-icon btn-text-secondary p-0 fund-copy-btn" data-copy-text="${r.toAddress}" title="کپی آدرس">
                                                            <i class="fa-regular fa-clone fa-sm"></i>
                                                        </button>
                                                        ${explorerAddrBase
                                ? `<a href="${explorerAddrBase}${r.toAddress}" target="_blank" class="font-number"><small>${shortAddr}</small></a>`
                                : `<small class="font-number">${shortAddr}</small>`
                            }
                                                       </div>` :
                            '-';

                        const txCell = r.txHash ?
                            `<div class="d-flex align-items-center gap-1">
                                                        <button class="btn btn-sm btn-icon btn-text-secondary p-0 fund-copy-btn" data-copy-text="${r.txHash}" title="کپی هش">
                                                            <i class="fa-regular fa-clone fa-sm"></i>
                                                        </button>
                                                        ${explorerTxBase
                                ? `<a href="${explorerTxBase}${r.txHash}" target="_blank" class="font-number text-primary"><small>${shortHash}</small></a>`
                                : `<small class="font-number text-primary">${shortHash}</small>`
                            }
                                                       </div>` :
                            '-';

                        return `
                                                    <tr>
                                                        <td>${idx + 1}</td>
                                                        <td><strong>${r.addressIndex}</strong></td>
                                                        <td>${addrCell}</td>
                                                        <td><span class="text-success">${r.amount}</span></td>
                                                        <td>
                                                            <span class="d-inline-flex align-items-center gap-1">
                                                                <i class="fa-regular fa-exclamation-triangle text-warning"
                                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                                    data-bs-custom-class="tooltip-dark"
                                                                    title="ممکن است این fee اشتباه باشد؛ برای مشاهده fee واقعی باید status تراکنش گرفته شود"></i>
                                                                <small class="text-secondary">${r.fee || '0'}</small>
                                                            </span>
                                                        </td>
                                                        <td><span class="badge bg-success">موفق</span></td>
                                                        <td>${txCell}</td>
                                                    </tr>
                                                `;
                    }).join('');
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }

                // Show errors
                if (errors.length > 0) {
                    document.getElementById('fundErrorsList').style.display = '';
                    document.getElementById('fundErrorsCount').textContent = errors.length;
                    document.getElementById('fundErrorsListBody').innerHTML = errors.map(e => `
                                                <div class="list-group-item list-group-item-danger">
                                                    <div class="d-flex justify-content-between">
                                                        <strong>ایندکس ${e.addressIndex}</strong>
                                                        <span class="badge bg-danger">خطا</span>
                                                    </div>
                                                    <small>${escapeHtml(e.error || '')}</small>
                                                </div>
                                            `).join('');
                }

                // Show buttons
                document.getElementById('fundCloseBtn').style.display = '';
                document.getElementById('fundRefreshDataBtn').style.display = '';

                // Clear selection
                selectedIndices.clear();
                updateSelectionUI();
            }

            // Update fund progress on error
            function updateFundProgressError(errorMessage, data) {
                document.getElementById('fundPercentage').textContent = '0';
                document.getElementById('fundProgressBar').style.width = '100%';
                document.getElementById('fundProgressBar').className = 'progress-bar bg-danger';
                document.getElementById('fundProgressBar').classList.remove('progress-bar-animated');
                document.getElementById('fundProgressBarText').textContent = 'خطا';
                document.getElementById('fundStatusBadge').textContent = 'خطا';
                document.getElementById('fundStatusBadge').className = 'badge bg-danger';

                document.getElementById('fundMessageText').innerHTML =
                    `<i class="fa-regular fa-exclamation-triangle me-2"></i>${escapeHtml(errorMessage)}`;
                document.getElementById('fundCurrentMessage').className = 'alert alert-danger';

                // Show insufficient balance details if available
                if (data?.sourceAddress) {
                    document.getElementById('fundStatsCards').style.display = 'flex';
                    document.getElementById('fundTotalCount').textContent = data.targetCount || '0';
                    document.getElementById('fundSuccessCount').textContent = '0';
                    document.getElementById('fundFailCount').textContent = data.targetCount || '0';
                    document.getElementById('fundTotalSent').textContent = '0';

                    document.getElementById('fundErrorsList').style.display = '';
                    document.getElementById('fundErrorsCount').textContent = '1';
                    document.getElementById('fundErrorsListBody').innerHTML = `
                                                <div class="list-group-item list-group-item-danger">
                                                    <strong>موجودی ناکافی در ایندکس ۱</strong><br>
                                                    <small>آدرس: ${escapeHtml(data.sourceAddress || '')}</small><br>
                                                    <small>موجودی فعلی: ${escapeHtml(data.currentBalance || '')}</small><br>
                                                    <small>مقدار مورد نیاز: ${escapeHtml(data.totalRequired || '')}</small>
                                                </div>
                                            `;
                }

                document.getElementById('fundCloseBtn').style.display = '';
            }

            // Fund Refresh Data Button
            document.getElementById('fundRefreshDataBtn')?.addEventListener('click', function () {
                fundProgressModal.hide();
                loadData();
                showToast('داده‌ها بروزرسانی شد', 'success');
            });

            // ========== END FUND GAS FUNCTIONALITY ==========

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
            document.addEventListener('click', function (e) {
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

            });

            // Apply Filter Button Click
            if (applyFilterBtn) {
                applyFilterBtn.addEventListener('click', function () {
                    currentPage = 1;
                    loadData();
                });
            }

            // Refresh Data Button Click
            if (refreshDataBtn) {
                refreshDataBtn.addEventListener('click', function () {
                    loadData();
                });
            }

            // Export Excel Button Click
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', function () {
                    if (!currentCurrency) return;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('admin.hd-wallet.export-excel') }}';

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

                    const response = await fetch('{{ route('admin.hd-wallet.balance-data') }}', {
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
                        '<span class="text-secondary">-</span>';
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
                                                <small class="text-secondary d-block">${item.deposit_count} تراکنش</small>
                                            </td>
                                            <td>
                                                ${outgoingCol}
                                                <small class="text-secondary d-block">${item.outgoing_count || 0} تراکنش</small>
                                            </td>
                                            <td>
                                                <strong class="font-number ${balanceClass}">${item.total_balance}</strong>
                                                <small class="text-secondary ms-1">${item.currency_symbol}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span id="blockchainBalance_${item.hd_wallet_index}" class="text-secondary">-</span>
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
                                                <small class="text-secondary">${item.deposit_count} واریز</small><br>
                                                <small class="text-secondary">${item.outgoing_count || 0} برداشت</small>
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
                    link.addEventListener('click', function (e) {
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
                const text = String(message ?? '');
                const normalizedType = ['error', 'success', 'warning', 'info'].includes(type) ? type : 'error';
                const bgMap = {
                    success: 'linear-gradient(to right, #96c93d, #96c93d)',
                    warning: 'linear-gradient(to right, #ffa514, #ffa514)',
                    error: 'linear-gradient(to right, #ff4e08, #ff4e08)',
                    info: 'linear-gradient(to right, #3498db, #3498db)'
                };

                const duration = text.includes('\n') || text.length > 160 ? 10000 : 5000;
                const safeHtml = escapeHtml(text).replace(/\n/g, '<br>');

                if (window.Toastify) {
                    Toastify({
                        text: safeHtml,
                        duration,
                        close: true,
                        gravity: 'top',
                        position: 'right',
                        stopOnFocus: true,
                        className: 'toastify-rtl',
                        style: {
                            background: bgMap[normalizedType] || bgMap.error
                        },
                        onClick: function () { }
                    }).showToast();

                    return;
                }

                return Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: normalizedType === 'error' ? 'error' : normalizedType,
                    title: text,
                    showConfirmButton: false,
                    timer: duration,
                    timerProgressBar: true,
                    didOpen: (popup) => popup.setAttribute('dir', 'rtl')
                });
            }

            // Query Blockchain Balance - Event Delegation
            document.addEventListener('click', async function (e) {
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
                    balanceEl.innerHTML = '<span class="text-secondary">در حال استعلام...</span>';

                    try {
                        const response = await fetch(
                            '{{ route('admin.hd-wallet.query-blockchain') }}', {
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
                                                <small class="text-secondary ms-1">${result.data.currency_symbol}</small>
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
                syncTransactionsBtn.addEventListener('click', async function () {
                    if (!currentCurrency || !currentChainId) {
                        showToast('لطفاً ابتدا یک کوین و شبکه انتخاب کنید', 'warning');
                        return;
                    }

                    const syncingAll = selectedIndices.size === 0;
                    const syncConfirmText = syncingAll
                        ? `آیا از شروع همگام‌سازی تراکنش‌های خروجی برای ${currentCurrency} (${currentChainName}) اطمینان دارید؟\n\n` +
                          `این پروسه ممکن است چند دقیقه طول بکشد.`
                        : `آیا از شروع همگام‌سازی تراکنش‌های خروجی برای ${selectedIndices.size} ایندکس انتخاب‌شده از ${currentCurrency} (${currentChainName}) اطمینان دارید؟\n\n` +
                          `ایندکس‌های انتخاب‌شده: ${Array.from(selectedIndices).sort((a,b)=>a-b).join('، ')}`;

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

                    // Start sync (pass selected indices if any, otherwise empty = all)
                    await startSync(syncingAll ? [] : Array.from(selectedIndices));
                });
            }

            // Start Sync Process
            async function startSync(indices = []) {
                try {
                    const response = await fetch('{{ route('admin.hd-wallet.start-sync') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            currency_symbol: currentCurrency,
                            currency_chain_id: currentChainId,
                            delay: 500, // milliseconds between API calls
                            indices: indices  // empty = all, non-empty = selected only
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
                        `{{ url('admin/hd-wallet/sync-progress') }}/${currentSyncId}`, {
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
                                                        <small class="d-block text-secondary font-number">${err.address}</small>
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
            document.getElementById('syncRefreshDataBtn')?.addEventListener('click', function () {
                syncProgressModal.hide();
                loadData(); // Reload main data
                showToast('داده‌ها بروزرسانی شد', 'success');
            });

            // Clean up polling when modal is closed
            syncProgressModalEl?.addEventListener('hidden.bs.modal', function () {
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