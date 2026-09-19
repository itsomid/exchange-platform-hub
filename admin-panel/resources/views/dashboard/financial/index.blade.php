@extends('dashboard.layout.master')
@section('title', 'داشبورد مالی')
@section('content')

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">داشبورد مالی</h4>
            <p class="text-muted mb-0">نمای کلی از عملکرد مالی صرافی</p>
        </div>
        <div>
            <span class="badge bg-label-secondary" id="last-update-time">
                <i class="fa fa-clock me-1"></i> آخرین بروزرسانی: --
            </span>
        </div>
    </div>

    <!-- Section: Trades -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1"><i class="fa fa-chart-line text-primary me-2"></i>معاملات</h5>
                <small class="text-muted">خلاصه آمار معاملات Spot و OTC</small>
            </div>
            <button class="btn btn-sm btn-outline-primary" id="btn-refresh-trades" onclick="loadTradeStats()">
                <i class="fa fa-refresh me-1"></i> بروزرسانی
            </button>
        </div>
        <div class="card-body">

            <!-- Date Range Filter for Volume Cards -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="form-label mb-0 text-nowrap">بازه زمانی حجم:</label>
                        <input type="date" class="form-control form-control-sm" id="volume-start-date" style="max-width: 160px;">
                        <span class="text-muted">تا</span>
                        <input type="date" class="form-control form-control-sm" id="volume-end-date" style="max-width: 160px;">
                        <button class="btn btn-sm btn-primary" id="btn-apply-volume-filter" onclick="applyVolumeFilter()">
                            <i class="fa fa-filter me-1"></i> اعمال
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="btn-reset-volume-filter" onclick="resetVolumeFilter()">
                            <i class="fa fa-times me-1"></i> ریست
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-md-end">
                        <label class="form-label mb-0 text-nowrap">تاریخ تعداد معاملات:</label>
                        <input type="date" class="form-control form-control-sm" id="trade-count-date" style="max-width: 160px;">
                        <button class="btn btn-sm btn-primary" id="btn-apply-count-filter" onclick="applyCountFilter()">
                            <i class="fa fa-filter me-1"></i> اعمال
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="btn-reset-count-filter" onclick="resetCountFilter()">
                            <i class="fa fa-times me-1"></i> ریست
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI Cards Row -->
            <div class="row g-4">

                <!-- Card 1: Trade Volume Today / Range -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="text-muted mb-0 me-2" id="volume-today-label">حجم معاملات امروز</h6>
                                        <span class="badge bg-label-info rounded-pill" id="volume-range-badge" style="display:none;"></span>
                                    </div>
                                    <h3 class="mb-2 font-number" id="today-trade-volume">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <small class="text-muted">
                                            <span class="text-primary fw-semibold">Spot:</span>
                                            <span class="font-number" id="today-spot-volume-card">--</span>
                                        </small>
                                        <small class="text-muted">
                                            <span class="text-success fw-semibold">OTC:</span>
                                            <span class="font-number" id="today-otc-volume-card">--</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-primary rounded">
                                        <i class="fa fa-chart-bar fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-dollar-sign me-1"></i>معادل USDT</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: All Time Trade Volume -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2">حجم کل معاملات</h6>
                                    <h3 class="mb-2 font-number" id="alltime-trade-volume">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <small class="text-muted">
                                            <span class="text-primary fw-semibold">Spot:</span>
                                            <span class="font-number" id="alltime-spot-volume">--</span>
                                        </small>
                                        <small class="text-muted">
                                            <span class="text-success fw-semibold">OTC:</span>
                                            <span class="font-number" id="alltime-otc-volume">--</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-success rounded">
                                        <i class="fa fa-chart-area fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-infinity me-1"></i>از ابتدا تاکنون (USDT)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Number of Trades Today -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="text-muted mb-0 me-2" id="count-today-label">تعداد معاملات امروز</h6>
                                        <span class="badge bg-label-warning rounded-pill" id="count-date-badge" style="display:none;"></span>
                                    </div>
                                    <h3 class="mb-2 font-number" id="today-trade-count">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-6 rounded"></span>
                                        </div>
                                    </h3>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <small class="text-muted">
                                            <span class="text-primary fw-semibold">Spot:</span>
                                            <span class="font-number" id="today-spot-count">--</span>
                                        </small>
                                        <small class="text-muted">
                                            <span class="text-success fw-semibold">OTC:</span>
                                            <span class="font-number" id="today-otc-count">--</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-warning rounded">
                                        <i class="fa fa-hashtag fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-exchange-alt me-1"></i>Spot + OTC</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: All Time Trade Count -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2">تعداد کل معاملات</h6>
                                    <h3 class="mb-2 font-number" id="alltime-trade-count">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-6 rounded"></span>
                                        </div>
                                    </h3>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <small class="text-muted">
                                            <span class="text-primary fw-semibold">Spot:</span>
                                            <span class="font-number" id="alltime-spot-count">--</span>
                                        </small>
                                        <small class="text-muted">
                                            <span class="text-success fw-semibold">OTC:</span>
                                            <span class="font-number" id="alltime-otc-count">--</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-danger rounded">
                                        <i class="fa fa-list-ol fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-infinity me-1"></i>از ابتدا تاکنون</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Section: Revenue -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1"><i class="fa fa-coins text-success me-2"></i>درآمد (کارمزد)</h5>
                <small class="text-muted">خلاصه کارمزدهای دریافتی از معاملات Spot و OTC</small>
            </div>
            <button class="btn btn-sm btn-outline-success" id="btn-refresh-revenue" onclick="loadRevenueStats()">
                <i class="fa fa-refresh me-1"></i> بروزرسانی
            </button>
        </div>
        <div class="card-body">

            <!-- Date Range Filter for Revenue -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="form-label mb-0 text-nowrap">بازه زمانی کارمزد:</label>
                        <input type="date" class="form-control form-control-sm" id="revenue-start-date" style="max-width: 160px;">
                        <span class="text-muted">تا</span>
                        <input type="date" class="form-control form-control-sm" id="revenue-end-date" style="max-width: 160px;">
                        <button class="btn btn-sm btn-success" id="btn-apply-revenue-filter" onclick="applyRevenueFilter()">
                            <i class="fa fa-filter me-1"></i> اعمال
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="btn-reset-revenue-filter" onclick="resetRevenueFilter()">
                            <i class="fa fa-times me-1"></i> ریست
                        </button>
                    </div>
                </div>
            </div>

            <!-- Revenue KPI Cards -->
            <div class="row g-4">

                <!-- Card 1: Commission Earned Today / Range -->
                <div class="col-xl-6 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="text-muted mb-0 me-2" id="revenue-today-label">کارمزد دریافتی امروز</h6>
                                        <span class="badge bg-label-info rounded-pill" id="revenue-range-badge" style="display:none;"></span>
                                    </div>
                                    <h3 class="mb-2 font-number" id="today-commission">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <small class="text-muted">
                                            <span class="text-primary fw-semibold">Spot:</span>
                                            <span class="font-number" id="today-spot-commission">--</span>
                                        </small>
                                        <small class="text-muted">
                                            <span class="text-success fw-semibold">OTC:</span>
                                            <span class="font-number" id="today-otc-commission">--</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-success rounded">
                                        <i class="fa fa-coins fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-dollar-sign me-1"></i>معادل USDT</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: All Time Commission -->
                <div class="col-xl-6 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2">کل کارمزد دریافتی</h6>
                                    <h3 class="mb-2 font-number" id="alltime-commission">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <small class="text-muted">
                                            <span class="text-primary fw-semibold">Spot:</span>
                                            <span class="font-number" id="alltime-spot-commission">--</span>
                                        </small>
                                        <small class="text-muted">
                                            <span class="text-success fw-semibold">OTC:</span>
                                            <span class="font-number" id="alltime-otc-commission">--</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-warning rounded">
                                        <i class="fa fa-sack-dollar fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-infinity me-1"></i>از ابتدا تاکنون (USDT)</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Section: Assets -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1"><i class="fa fa-wallet text-info me-2"></i>دارایی‌ها</h5>
                <small class="text-muted">وضعیت لحظه‌ای دارایی‌های صرافی</small>
            </div>
            <button class="btn btn-sm btn-outline-info" id="btn-refresh-assets" onclick="loadAssetStats()">
                <i class="fa fa-refresh me-1"></i> بروزرسانی
            </button>
        </div>
        <div class="card-body">
            <div class="row g-4">

                <!-- Card 1: Total Exchange Wallet Balance -->
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2">موجودی کل کیف پول صرافی</h6>
                                    <h3 class="mb-2 font-number" id="total-exchange-balance">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-info rounded">
                                        <i class="fa fa-building-columns fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-dollar-sign me-1"></i>معادل USDT - لحظه‌ای</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Hot Wallet Balance -->
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2">موجودی Hot Wallet</h6>
                                    <h3 class="mb-2 font-number" id="total-hot-wallet-balance">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-warning rounded">
                                        <i class="fa fa-fire fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-link me-1"></i>موجودی آنچین (USDT)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Net Exchange Assets -->
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2">خالص دارایی صرافی</h6>
                                    <h3 class="mb-2 font-number" id="net-exchange-assets">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <small class="text-muted">
                                        بدهی کاربران:
                                        <span class="font-number" id="total-user-liabilities">--</span>
                                        <span>USDT</span>
                                    </small>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-primary rounded" id="net-assets-icon">
                                        <i class="fa fa-scale-balanced fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-calculator me-1"></i>دارایی صرافی منهای بدهی کاربران</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Section: Liabilities -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1"><i class="fa fa-handshake text-danger me-2"></i>بدهی‌ها و تعهدات</h5>
                <small class="text-muted">وضعیت بدهی صرافی به کاربران به تفکیک ارز</small>
            </div>
            <button class="btn btn-sm btn-outline-danger" id="btn-refresh-liabilities" onclick="loadLiabilityStats()">
                <i class="fa fa-refresh me-1"></i> بروزرسانی
            </button>
        </div>
        <div class="card-body">

            <!-- KPI Card: Total Exchange Debt -->
            <div class="row g-4 mb-4">
                <div class="col-xl-6 col-md-8">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2">کل بدهی صرافی به کاربران</h6>
                                    <h3 class="mb-2 font-number" id="total-debt-usdt">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <small class="text-muted" id="liability-currency-count">
                                        <i class="fa fa-coins me-1"></i>--
                                    </small>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-danger rounded">
                                        <i class="fa fa-hand-holding-dollar fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-dollar-sign me-1"></i>مجموع موجودی کاربران (USDT)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Currency Balance Breakdown Table -->
            <div class="card border shadow-none">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0"><i class="fa fa-table me-2 text-muted"></i>تفکیک موجودی به ازای هر ارز</h6>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="liability-search" placeholder="جستجوی ارز..." oninput="filterLiabilityTable()">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="liability-table">
                        <thead class="table-light">
                            <tr>
                                <th class="cursor-pointer" onclick="sortLiabilityTable('symbol')">
                                    ارز <i class="fa fa-sort text-muted ms-1" id="sort-icon-symbol"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortLiabilityTable('userBalanceUsdt')">
                                    موجودی کاربران <i class="fa fa-sort text-muted ms-1" id="sort-icon-userBalanceUsdt"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortLiabilityTable('exchangeBalanceUsdt')">
                                    موجودی صرافی <i class="fa fa-sort text-muted ms-1" id="sort-icon-exchangeBalanceUsdt"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortLiabilityTable('differenceUsdt')">
                                    تفاوت (USDT) <i class="fa fa-sort text-muted ms-1" id="sort-icon-differenceUsdt"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="liability-table-body">
                            <!-- Skeleton rows -->
                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted text-center py-2 d-none" id="liability-no-results">
                    <i class="fa fa-info-circle me-1"></i> نتیجه‌ای یافت نشد
                </div>
            </div>

        </div>
    </div>

    <!-- Section: Cash Flow -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1"><i class="fa fa-money-bill-transfer text-purple me-2"></i>جریان نقدینگی</h5>
                <small class="text-muted">خلاصه واریز و برداشت‌های کاربران</small>
            </div>
            <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-cashflow" onclick="loadCashFlowStats()">
                <i class="fa fa-refresh me-1"></i> بروزرسانی
            </button>
        </div>
        <div class="card-body">

            <!-- Date Range Filter -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="form-label mb-0 text-nowrap">بازه زمانی:</label>
                        <input type="date" class="form-control form-control-sm" id="cashflow-start-date" style="max-width: 160px;">
                        <span class="text-muted">تا</span>
                        <input type="date" class="form-control form-control-sm" id="cashflow-end-date" style="max-width: 160px;">
                        <button class="btn btn-sm btn-primary" onclick="applyCashFlowFilter()">
                            <i class="fa fa-filter me-1"></i> اعمال
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="resetCashFlowFilter()">
                            <i class="fa fa-times me-1"></i> ریست
                        </button>
                        <span class="badge bg-label-info rounded-pill" id="cashflow-range-badge" style="display:none;"></span>
                    </div>
                </div>
            </div>

            <!-- KPI Cards Row -->
            <div class="row g-4 mb-4">

                <!-- Card 1: Total Deposits -->
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2" id="cashflow-deposit-label">کل واریزی‌ها امروز</h6>
                                    <h3 class="mb-2 font-number text-success" id="total-deposit-usdt">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <small class="text-muted" id="cashflow-deposit-count">
                                        <i class="fa fa-hashtag me-1"></i>--
                                    </small>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-success rounded">
                                        <i class="fa fa-arrow-down fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-dollar-sign me-1"></i>معادل USDT</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Total Withdrawals -->
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2" id="cashflow-withdrawal-label">کل برداشت‌ها امروز</h6>
                                    <h3 class="mb-2 font-number text-danger" id="total-withdrawal-usdt">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <small class="text-muted" id="cashflow-withdrawal-count">
                                        <i class="fa fa-hashtag me-1"></i>--
                                    </small>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-danger rounded">
                                        <i class="fa fa-arrow-up fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-dollar-sign me-1"></i>معادل USDT</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Net Cash Flow -->
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2" id="cashflow-net-label">خالص جریان نقدینگی امروز</h6>
                                    <h3 class="mb-2 font-number" id="net-cashflow-usdt">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <small class="text-muted" id="cashflow-currency-count">
                                        <i class="fa fa-coins me-1"></i>--
                                    </small>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-primary rounded" id="net-cashflow-icon">
                                        <i class="fa fa-money-bill-transfer fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-calculator me-1"></i>واریز منهای برداشت (USDT)</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Cash Flow Breakdown Table -->
            <div class="card border shadow-none">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0"><i class="fa fa-table me-2 text-muted"></i>تفکیک جریان نقدینگی به ازای هر ارز</h6>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="cashflow-search" placeholder="جستجوی ارز..." oninput="filterCashFlowTable()">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="cashflow-table">
                        <thead class="table-light">
                            <tr>
                                <th class="cursor-pointer" onclick="sortCashFlowTable('symbol')">
                                    ارز <i class="fa fa-sort text-muted ms-1" id="cf-sort-icon-symbol"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortCashFlowTable('depositUsdt')">
                                    واریز (USDT) <i class="fa fa-sort text-muted ms-1" id="cf-sort-icon-depositUsdt"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortCashFlowTable('withdrawalUsdt')">
                                    برداشت (USDT) <i class="fa fa-sort text-muted ms-1" id="cf-sort-icon-withdrawalUsdt"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortCashFlowTable('netUsdt')">
                                    خالص (USDT) <i class="fa fa-sort text-muted ms-1" id="cf-sort-icon-netUsdt"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="cashflow-table-body">
                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted text-center py-2 d-none" id="cashflow-no-results">
                    <i class="fa fa-info-circle me-1"></i> نتیجه‌ای یافت نشد
                </div>
            </div>

        </div>
    </div>

    <!-- Section: Expenses -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1"><i class="fa fa-file-invoice-dollar text-warning me-2"></i>هزینه‌ها (کارمزدهای پرداختی)</h5>
                <small class="text-muted">کارمزدهای پرداختی صرافی شامل فی انتقال، شبکه و صرافی مرجع</small>
            </div>
            <button class="btn btn-sm btn-outline-warning" id="btn-refresh-expenses" onclick="loadExpenseStats()">
                <i class="fa fa-refresh me-1"></i> بروزرسانی
            </button>
        </div>
        <div class="card-body">

            <!-- Date Range Filter -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="form-label mb-0 text-nowrap">بازه زمانی:</label>
                        <input type="date" class="form-control form-control-sm" id="expense-start-date" style="max-width: 160px;">
                        <span class="text-muted">تا</span>
                        <input type="date" class="form-control form-control-sm" id="expense-end-date" style="max-width: 160px;">
                        <button class="btn btn-sm btn-warning" onclick="applyExpenseFilter()">
                            <i class="fa fa-filter me-1"></i> اعمال
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="resetExpenseFilter()">
                            <i class="fa fa-times me-1"></i> ریست
                        </button>
                        <span class="badge bg-label-info rounded-pill" id="expense-range-badge" style="display:none;"></span>
                    </div>
                </div>
            </div>

            <!-- KPI Card + Pie Chart Row -->
            <div class="row g-4 mb-4">

                <!-- Card: Total Fees Paid -->
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-none h-100 financial-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-2" id="expense-total-label">کل هزینه‌ها امروز</h6>
                                    <h3 class="mb-2 font-number text-warning" id="total-fees-usdt">
                                        <div class="placeholder-glow">
                                            <span class="placeholder col-8 rounded"></span>
                                        </div>
                                    </h3>
                                    <small class="text-muted" id="expense-currency-count">
                                        <i class="fa fa-coins me-1"></i>--
                                    </small>
                                </div>
                                <div class="avatar flex-shrink-0 ms-3">
                                    <div class="avatar-initial bg-label-warning rounded">
                                        <i class="fa fa-file-invoice-dollar fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fa fa-dollar-sign me-1"></i>مجموع کارمزدهای پرداختی (USDT)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fee Type Breakdown Cards -->
                <div class="col-xl-8 col-md-6">
                    <div class="row g-3 h-100">
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 text-center">
                                    <div class="avatar avatar-sm mx-auto mb-2">
                                        <div class="avatar-initial bg-label-info rounded" style="width:36px;height:36px;">
                                            <i class="fa fa-exchange-alt"></i>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mb-1">فی انتقال</small>
                                    <h5 class="mb-0 font-number" id="total-transfer-fees">
                                        <span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span>
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 text-center">
                                    <div class="avatar avatar-sm mx-auto mb-2">
                                        <div class="avatar-initial bg-label-danger rounded" style="width:36px;height:36px;">
                                            <i class="fa fa-network-wired"></i>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mb-1">فی شبکه</small>
                                    <h5 class="mb-0 font-number" id="total-network-fees">
                                        <span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span>
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 text-center">
                                    <div class="avatar avatar-sm mx-auto mb-2">
                                        <div class="avatar-initial bg-label-primary rounded" style="width:36px;height:36px;">
                                            <i class="fa fa-building"></i>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mb-1">فی صرافی مرجع</small>
                                    <h5 class="mb-0 font-number" id="total-ref-exchange-fees">
                                        <span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Pie Chart -->
            <div class="row mb-4">
                <div class="col-xl-6 col-md-8 mx-auto">
                    <div class="card border shadow-none">
                        <div class="card-body">
                            <h6 class="text-center text-muted mb-3"><i class="fa fa-chart-pie me-1"></i>توزیع هزینه‌ها بر اساس نوع</h6>
                            <div id="expense-pie-chart" style="min-height: 280px;">
                                <div class="d-flex justify-content-center align-items-center" style="height: 280px;">
                                    <div class="spinner-border text-warning" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expense Breakdown Table -->
            <div class="card border shadow-none">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0"><i class="fa fa-table me-2 text-muted"></i>تفکیک هزینه‌ها به ازای هر ارز</h6>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="expense-search" placeholder="جستجوی ارز..." oninput="filterExpenseTable()">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="expense-table">
                        <thead class="table-light">
                            <tr>
                                <th class="cursor-pointer" onclick="sortExpenseTable('symbol')">
                                    ارز <i class="fa fa-sort text-muted ms-1" id="exp-sort-icon-symbol"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortExpenseTable('transferFeesUsdt')">
                                    فی انتقال <i class="fa fa-sort text-muted ms-1" id="exp-sort-icon-transferFeesUsdt"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortExpenseTable('networkFeesUsdt')">
                                    فی شبکه <i class="fa fa-sort text-muted ms-1" id="exp-sort-icon-networkFeesUsdt"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortExpenseTable('refExchangeFeesUsdt')">
                                    فی صرافی مرجع <i class="fa fa-sort text-muted ms-1" id="exp-sort-icon-refExchangeFeesUsdt"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortExpenseTable('totalUsdt')">
                                    مجموع (USDT) <i class="fa fa-sort text-muted ms-1" id="exp-sort-icon-totalUsdt"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="expense-table-body">
                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted text-center py-2 d-none" id="expense-no-results">
                    <i class="fa fa-info-circle me-1"></i> نتیجه‌ای یافت نشد
                </div>
            </div>

        </div>
    </div>

    <!-- Section: Profit & Loss -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1"><i class="fa fa-scale-balanced text-info me-2"></i>سود و زیان</h5>
                <small class="text-muted">تحلیل سودآوری صرافی — اسپرد معاملات OTC + کارمزدها − هزینه‌ها</small>
            </div>
            <button class="btn btn-sm btn-outline-info" id="btn-refresh-pnl" onclick="loadProfitLossStats()">
                <i class="fa fa-refresh me-1"></i> بروزرسانی
            </button>
        </div>
        <div class="card-body">

            <!-- Date Filter -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="form-label mb-0 text-nowrap">بازه زمانی:</label>
                        <input type="date" class="form-control form-control-sm" id="pnl-start-date" style="max-width: 160px;">
                        <span class="text-muted">تا</span>
                        <input type="date" class="form-control form-control-sm" id="pnl-end-date" style="max-width: 160px;">
                        <button class="btn btn-sm btn-info" onclick="applyPnlFilter()">
                            <i class="fa fa-filter me-1"></i> اعمال
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="resetPnlFilter()">
                            <i class="fa fa-times me-1"></i> ریست
                        </button>
                        <span class="badge bg-label-info" id="pnl-range-badge" style="display: none;"></span>
                    </div>
                </div>
            </div>

            <!-- KPI Cards Row -->
            <div class="row g-3 mb-4">
                <!-- Trade Spread Card -->
                <div class="col-md-4">
                    <div class="card financial-kpi-card border shadow-none h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded-circle bg-label-info">
                                    <i class="fa fa-arrows-left-right fa-lg"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-1" id="pnl-spread-label">سود اسپرد معاملات امروز</small>
                                <h3 class="mb-0 font-number" id="total-spread-usdt">
                                    <span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span>
                                </h3>
                                <small class="text-muted">USDT</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total P/L Card -->
                <div class="col-md-4">
                    <div class="card financial-kpi-card border shadow-none h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded-circle bg-label-success" id="pnl-icon-bg">
                                    <i class="fa fa-chart-line fa-lg"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-1" id="pnl-total-label">سود/زیان خالص امروز</small>
                                <h3 class="mb-0 font-number" id="total-profit-loss">
                                    <span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span>
                                </h3>
                                <div>
                                    <small class="text-muted">USDT</small>
                                    <span class="badge bg-label-secondary ms-2" id="pnl-revenue-badge">
                                        <span class="placeholder-glow"><span class="placeholder col-6 placeholder-sm rounded"></span></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Profit Margin Card -->
                <div class="col-md-4">
                    <div class="card financial-kpi-card border shadow-none h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary" id="margin-icon-bg">
                                    <i class="fa fa-percent fa-lg"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-1" id="pnl-margin-label">حاشیه سود خالص امروز</small>
                                <h3 class="mb-0 font-number" id="net-profit-margin">
                                    <span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span>
                                </h3>
                                <small class="text-muted">درصد</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue / Expense / Commission Summary -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center p-3 rounded" style="background-color: rgba(40, 199, 111, 0.08);">
                        <i class="fa fa-arrow-up text-success me-2"></i>
                        <div>
                            <small class="text-muted d-block">مجموع درآمد</small>
                            <span class="font-number fw-semibold" id="pnl-total-revenue">
                                <span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span>
                            </span>
                            <small class="text-muted ms-1">USDT</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center p-3 rounded" style="background-color: rgba(234, 84, 85, 0.08);">
                        <i class="fa fa-arrow-down text-danger me-2"></i>
                        <div>
                            <small class="text-muted d-block">مجموع هزینه‌ها</small>
                            <span class="font-number fw-semibold" id="pnl-total-expenses">
                                <span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span>
                            </span>
                            <small class="text-muted ms-1">USDT</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center p-3 rounded" style="background-color: rgba(115, 103, 240, 0.08);">
                        <i class="fa fa-coins text-primary me-2"></i>
                        <div>
                            <small class="text-muted d-block">کارمزد معاملات</small>
                            <span class="font-number fw-semibold" id="pnl-total-commission">
                                <span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span>
                            </span>
                            <small class="text-muted ms-1">USDT</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Spread Breakdown Table -->
            <div class="card border shadow-none">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0">
                        <i class="fa fa-table me-2 text-muted"></i>تفکیک اسپرد به ازای هر ارز
                        <span class="badge bg-label-info ms-2" id="pnl-currency-count"></span>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="pnl-search" placeholder="جستجوی ارز..." oninput="filterPnlTable()">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="pnl-table">
                        <thead class="table-light">
                            <tr>
                                <th class="cursor-pointer" onclick="sortPnlTable('symbol')">
                                    ارز <i class="fa fa-sort text-muted ms-1" id="pnl-sort-icon-symbol"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortPnlTable('tradeCount')">
                                    تعداد معاملات <i class="fa fa-sort text-muted ms-1" id="pnl-sort-icon-tradeCount"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortPnlTable('volumeUsdt')">
                                    حجم (USDT) <i class="fa fa-sort text-muted ms-1" id="pnl-sort-icon-volumeUsdt"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortPnlTable('spreadUsdt')">
                                    سود اسپرد (USDT) <i class="fa fa-sort text-muted ms-1" id="pnl-sort-icon-spreadUsdt"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortPnlTable('avgSpreadPct')">
                                    میانگین اسپرد <i class="fa fa-sort text-muted ms-1" id="pnl-sort-icon-avgSpreadPct"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="pnl-table-body">
                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted text-center py-2 d-none" id="pnl-no-results">
                    <i class="fa fa-info-circle me-1"></i> نتیجه‌ای یافت نشد
                </div>
            </div>

        </div>
    </div>

    <!-- Section: Stock Purchases -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1"><i class="fa fa-building-columns text-dark me-2"></i>گزارش خرید سهام</h5>
                <small class="text-muted">لیست تراکنش‌های خرید سهام با امکان خروجی اکسل</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-success" id="btn-export-stock" onclick="exportStockPurchases()">
                    <i class="fa fa-file-excel me-1"></i> خروجی اکسل
                </button>
                <button class="btn btn-sm btn-outline-dark" id="btn-refresh-stock" onclick="loadStockPurchaseStats()">
                    <i class="fa fa-refresh me-1"></i> بروزرسانی
                </button>
            </div>
        </div>
        <div class="card-body">

            <!-- Date Filter + Search -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="form-label mb-0 text-nowrap">بازه زمانی:</label>
                        <input type="date" class="form-control form-control-sm" id="stock-start-date" style="max-width: 160px;">
                        <span class="text-muted">تا</span>
                        <input type="date" class="form-control form-control-sm" id="stock-end-date" style="max-width: 160px;">
                        <button class="btn btn-sm btn-dark" onclick="applyStockFilter()">
                            <i class="fa fa-filter me-1"></i> اعمال
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="resetStockFilter()">
                            <i class="fa fa-times me-1"></i> ریست
                        </button>
                        <span class="badge bg-label-dark" id="stock-range-badge" style="display: none;"></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-md-end">
                        <div class="input-group input-group-sm" style="max-width: 250px;">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="stock-search" placeholder="جستجوی کاربر یا شماره قرارداد..." oninput="handleStockSearch()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card financial-kpi-card border shadow-none h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded-circle bg-label-dark">
                                    <i class="fa fa-file-contract fa-lg"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-1" id="stock-total-label">تعداد قراردادها</small>
                                <h3 class="mb-0 font-number" id="stock-total-contracts">
                                    <span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card financial-kpi-card border shadow-none h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded-circle bg-label-success">
                                    <i class="fa fa-check-circle fa-lg"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-1">قراردادهای فعال</small>
                                <h3 class="mb-0 font-number" id="stock-active-contracts">
                                    <span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card financial-kpi-card border shadow-none h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded-circle bg-label-info">
                                    <i class="fa fa-cubes fa-lg"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-1">مجموع تعداد سهام</small>
                                <h3 class="mb-0 font-number" id="stock-total-amount">
                                    <span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card financial-kpi-card border shadow-none h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded-circle bg-label-warning">
                                    <i class="fa fa-money-bill-wave fa-lg"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-1">مجموع مبلغ خرید</small>
                                <h3 class="mb-0 font-number" id="stock-total-value">
                                    <span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span>
                                </h3>
                                <small class="text-muted">تومان</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Purchases Table -->
            <div class="card border shadow-none">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="stock-table">
                        <thead class="table-light">
                            <tr>
                                <th class="cursor-pointer" onclick="sortStockTable('created_at')">
                                    تاریخ <i class="fa fa-sort text-muted ms-1" id="stock-sort-icon-created_at"></i>
                                </th>
                                <th>
                                    کاربر
                                </th>
                                <th>
                                    سهم
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortStockTable('amount')">
                                    تعداد <i class="fa fa-sort text-muted ms-1" id="stock-sort-icon-amount"></i>
                                </th>
                                <th class="cursor-pointer text-end" onclick="sortStockTable('total_value')">
                                    مبلغ کل <i class="fa fa-sort text-muted ms-1" id="stock-sort-icon-total_value"></i>
                                </th>
                                <th class="cursor-pointer text-center" onclick="sortStockTable('contract_number')">
                                    شماره قرارداد <i class="fa fa-sort text-muted ms-1" id="stock-sort-icon-contract_number"></i>
                                </th>
                                <th class="text-center">
                                    وضعیت
                                </th>
                            </tr>
                        </thead>
                        <tbody id="stock-table-body">
                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                                    <td><span class="placeholder-glow"><span class="placeholder col-5 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-4 rounded"></span></span></td>
                                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                                    <td class="text-center"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                                    <td class="text-center"><span class="placeholder-glow"><span class="placeholder col-4 rounded"></span></span></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted text-center py-2 d-none" id="stock-no-results">
                    <i class="fa fa-info-circle me-1"></i> نتیجه‌ای یافت نشد
                </div>
                <!-- Pagination -->
                <div class="card-footer d-flex justify-content-between align-items-center py-2" id="stock-pagination-wrapper">
                    <small class="text-muted" id="stock-pagination-info"></small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="stock-pagination"></ul>
                    </nav>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/js/financial-dashboard.js'])
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/dashboard/financial-dashboard.scss'])
    @if (session('theme', 'light') === 'dark')
        @vite(['resources/assets/vendor/libs/dashboard/financial-dashboard-dark.scss'])
    @endif
@endsection
