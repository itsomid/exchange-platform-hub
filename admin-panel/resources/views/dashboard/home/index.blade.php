@extends('dashboard.layout.master')
@section('title', 'پیشخوان')
@section('content')

    @if (!$is2FAEnabled)
        <div class="alert alert-warning mb-4">
            <div class="d-flex">
                <i class="fas fa-shield-alt me-2 mt-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading">احراز هویت دو مرحله‌ای فعال نیست!</h5>
                    <p>برای افزایش امنیت پنل مدیریت، لطفا احراز هویت دو مرحله‌ای را فعال کنید. بدون فعال‌سازی احراز هویت دو
                        مرحله‌ای، دسترسی شما به بخش‌های مختلف پنل محدود خواهد شد.</p>
                    <a href="{{ route('admin.profile.2fa.edit') }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-lock me-1"></i> فعال‌سازی احراز هویت دو مرحله‌ای
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- KPI Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">کل کاربران</h6>
                            <h3 class="mb-0" id="total-users">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </h3>
                            <small class="text-success" id="today-registrations"></small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded">
                                <i class="fa fa-users fa-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">کاربران آنلاین</h6>
                            <h3 class="mb-0" id="active-users">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </h3>
                            <small class="text-muted">فعال در ۱۰ دقیقه اخیر</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded">
                                <i class="fa fa-user-check fa-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-pills trading-volume-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="spot-today-tab" data-bs-toggle="pill"
                                data-bs-target="#spot-today" type="button" role="tab">
                                <i class="fa fa-chart-line me-1"></i>
                                Spot امروز
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="otc-today-tab" data-bs-toggle="pill" data-bs-target="#otc-today"
                                type="button" role="tab">
                                <i class="fa fa-exchange-alt me-1"></i>
                                OTC امروز
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="weekly-volume-tab" data-bs-toggle="pill"
                                data-bs-target="#weekly-volume" type="button" role="tab" data-volume-type="weekly">
                                <i class="fa fa-chart-area me-1"></i>
                                هفته گذشته
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="spot-today" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">حجم معاملات Spot
                                        <small class="text-muted">(امروز)</small>
                                    </h6>
                                    <h3 class="mb-0 font-number" id="today-spot-volume">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                    </h3>

                                </div>
                                <div class="avatar">
                                    <div class="avatar-initial bg-label-primary rounded">
                                        <i class="fa fa-chart-line fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="otc-today" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">حجم معاملات OTC
                                        <small class="text-muted">(امروز)</small>
                                    </h6>
                                    <h3 class="mb-0 font-number" id="today-otc-volume">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                    </h3>

                                </div>
                                <div class="avatar">
                                    <div class="avatar-initial bg-label-success rounded">
                                        <i class="fa fa-exchange-alt fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="weekly-volume" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">کل حجم معاملات
                                        <small class="text-muted">(7 روز گذشته - Spot + OTC)</small>
                                    </h6>
                                    <h3 class="mb-0 font-number" id="weekly-volume-display">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                    </h3>

                                </div>
                                <div class="avatar">
                                    <div class="avatar-initial bg-label-warning rounded">
                                        <i class="fa fa-chart-area fa-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Pending Actions and Trading Pairs -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">اقدامات در انتظار</h5>
                        <i class="fa fa-clock text-warning fa-lg"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h6 class="mb-0">برداشت‌های در انتظار</h6>
                            <small class="text-muted">نیاز به تایید</small>
                        </div>
                        <span class="badge bg-warning" id="pending-withdrawals">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fa fa-chart-line text-primary me-2"></i>
                        جفت ارزهای پر معامله Spot
                    </h5>
                    <ul class="list-unstyled mb-0" id="top-spot-pairs">
                        <li class="text-center py-3">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            <span class="me-2">در حال بارگذاری...</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fa fa-exchange-alt text-success me-2"></i>
                        جفت ارزهای پر معامله OTC
                    </h5>
                    <ul class="list-unstyled mb-0" id="top-otc-pairs">
                        <li class="text-center py-3">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            <span class="me-2">در حال بارگذاری...</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Summary with Tabs -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="card-title mb-0">خلاصه مالی صرافی</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills nav-fill financial-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="withdrawals-tab" data-bs-toggle="tab"
                                data-bs-target="#withdrawals" type="button" role="tab" data-type="withdrawals">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fa fa-arrow-up fa-lg mb-2"></i>
                                    <span>برداشت کاربران</span>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="otc-fees-tab" data-bs-toggle="tab" data-bs-target="#otc-fees"
                                type="button" role="tab" data-type="otc_fees">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fa fa-coins fa-lg mb-2"></i>
                                    <span>کارمزد OTC</span>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="withdrawal-fees-tab" data-bs-toggle="tab"
                                data-bs-target="#withdrawal-fees" type="button" role="tab"
                                data-type="withdrawal_fees">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fa fa-percent fa-lg mb-2"></i>
                                    <span>کارمزد برداشت</span>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ref-purchases-tab" data-bs-toggle="tab"
                                data-bs-target="#ref-purchases" type="button" role="tab" data-type="ref_purchases">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fa fa-shopping-cart fa-lg mb-2"></i>
                                    <span>خرید از صرافی مرجع</span>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ref-withdrawals-tab" data-bs-toggle="tab"
                                data-bs-target="#ref-withdrawals" type="button" role="tab"
                                data-type="ref_withdrawals">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fa fa-exchange-alt fa-lg mb-2"></i>
                                    <span>برداشت از صرافی مرجع</span>
                                </div>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- همه تب‌ها از همین container استفاده می‌کنند -->
                        <div id="financial-summary-content" class="py-3">
                            <div class="text-center py-5">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                <span class="me-2">در حال بارگذاری...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">واریز (هفته اخیر)</h5>
                    <div class="badge bg-label-success">+0%</div>
                </div>
                <div class="card-body">
                    <h4 class="mb-3"><span id="total-deposits-value">0</span> <small class="text-muted">USDT</small>
                    </h4>
                    <div id="deposit-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">برداشت (هفته اخیر)</h5>
                    <div class="badge bg-label-danger">+0%</div>
                </div>
                <div class="card-body">
                    <h4 class="mb-3"><span id="total-withdrawals-value">0</span> <small class="text-muted">USDT</small>
                    </h4>
                    <div id="withdrawal-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">آمار معاملات OTC</h5>
                    <small class="text-muted">هفته گذشته</small>
                </div>
                <div class="card-body">
                    <div id="otc-trading-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">آخرین فعالیت‌ها</h5>
                    <button class="btn btn-sm btn-primary" onclick="loadRecentActivities()">
                        <i class="fa fa-refresh"></i> بروزرسانی
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>نوع</th>
                                    <th>کاربر</th>
                                    <th>مقدار</th>
                                    <th>ارز</th>
                                    <th>زمان</th>
                                    <th>وضعیت</th>
                                </tr>
                            </thead>
                            <tbody id="recent-activities-tbody">
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                        <span class="me-2">در حال بارگذاری...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/js/dashboard-modern.js'])
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
    @if (session('theme', 'light') === 'dark')
        @vite(['resources/assets/vendor/libs/dashboard/dashboard-modern-dark.scss'])
    @else
        @vite(['resources/assets/vendor/libs/dashboard/dashboard-modern.scss'])
    @endif
@endsection
