@extends('dashboard.layout.master')
@section('title', 'پیشخوان')
@section('content')

    <div class="row g-6">
        <!-- Support Tracker -->
        <div class="col-sm-12 col-xl-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between pb-0">
                    <div class="card-title mb-0">
                        <h5 class="mb-0">آمار پشتیبانی</h5>
                        <small class="text-muted">هفت روز گذشته</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" id="supportTrackerMenu" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            <i class="ti ti-dots-vertical ti-sm text-muted"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="supportTrackerMenu">
                            <a class="dropdown-item" href="javascript:void(0);">View More</a>
                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                            <div class="mt-lg-4 mt-lg-2 mb-lg-4 mb-2 pt-1">
                                <h1 class="mb-0">164</h1>
                                <p class="mb-0">تعداد تیکت ها</p>
                            </div>
                            <ul class="p-0 m-0">
                                <li class="d-flex gap-3 align-items-center mb-lg-3 pt-2 pb-1">
                                    <div class="badge rounded bg-label-primary p-1"><i class="ti ti-ticket ti-sm"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">تیکت های جدید</h6>
                                        <small class="text-muted">142</small>
                                    </div>
                                </li>
                                <li class="d-flex gap-3 align-items-center mb-lg-3 pb-1">
                                    <div class="badge rounded bg-label-info p-1"><i
                                            class="ti ti-circle-check ti-sm"></i></div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">تیکت های باز</h6>
                                        <small class="text-muted">28</small>
                                    </div>
                                </li>
                                <li class="d-flex gap-3 align-items-center pb-1">
                                    <div class="badge rounded bg-label-warning p-1"><i class="ti ti-clock ti-sm"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">میانگین زمان پاسخ گویی</h6>
                                        <small class="text-muted">1 روز</small>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="col-12 col-sm-8 col-md-12 col-lg-8">
                            <div id="supportTracker"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Support Tracker -->
        <!-- Shipment statistics-->
        <div class="col-sm-12 col-xl-9">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="mb-1">آمار ثبت نام کاربران</h5>
                        <p class="card-subtitle">تعداد ثبت نام در این ماه</p>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-label-primary" id="selectedMonth">
                            {{\App\Helpers\DateFormatter::convertToPersianDate(now(),'%B')}}</button>
                        <button type="button" class="btn btn-label-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu" id="monthDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="فروردین">فروردین</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="اردیبهشت">اردیبهشت</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="خرداد">خرداد</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="تیر">تیر</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="مرداد">مرداد</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="شهریور">شهریور</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="مهر">مهر</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="آبان">آبان</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="آذر">آذر</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="دی">دی</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="بهمن">بهمن</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="اسفند">اسفند</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div id="shipmentStatisticsChart"></div>
                </div>
            </div>
        </div>
        <!--/ Shipment statistics -->
        <!-- Orders last week -->
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card h-100">
                <div class="card-header pb-3">
                    <h5 class="card-title mb-1">تعداد سفارش OCT</h5>
                    <p class="card-subtitle">هفته گذشته</p>
                </div>
                <div class="card-body">
                    <div id="ordersLastWeek"></div>
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <h4 class="mb-0">124k</h4>
                        <small class="text-success">+12.6%</small>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss',
            'resources/assets/vendor/libs/apex-charts/apexcharts.js',
            'resources/assets/js/config.js',
            'resources/assets/js/dashboard.js'
         ])

@endsection

@section('scripts')
    <script type="module">

    </script>
@endsection
@section('vendor-style')
    <style type="text/css">

    </style>
@endsection
