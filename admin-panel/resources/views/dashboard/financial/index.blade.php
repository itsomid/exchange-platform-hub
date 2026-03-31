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

@endsection

@section('vendor-script')
    @vite(['resources/assets/js/financial-dashboard.js'])
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/dashboard/financial-dashboard.scss'])
    @if (session('theme', 'light') === 'dark')
        @vite(['resources/assets/vendor/libs/dashboard/financial-dashboard-dark.scss'])
    @endif
@endsection
