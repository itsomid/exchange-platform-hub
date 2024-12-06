@extends('dashboard.layout.master')
@section('title', 'پیشخوان')
@section('content')

    <div class="row g-6">
        <div class="col-xl-3 col-12">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-0 text-body">واریز (هفته اخیر)</h6>
                        <div class="badge bg-label-success">+15%</div>
                    </div>
                    <h4 class="card-title mb-1">${{number_format(23234,2)}}</h4>
                </div>
                <div class="card-body px-0">
                    <div id="deposit"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-12">
            <div class="card h-100">


                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-0 text-body">برداشت (هفته اخیر)</h6>
                        <div class="badge bg-label-danger">+15%</div>
                    </div>
                    <h4 class="card-title mb-1">${{number_format(23234,2)}}</h4>
                </div>


                <div class="card-body px-0">
                    <div id="withdraw"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-12">
            <div class="card h-100">


                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-0 text-body">درآمد از سود صرافی برحسب تتر (هفته اخیر)</h6>
                        <div class="badge bg-label-success">+15%</div>
                    </div>
                    <h4 class="card-title mb-1">${{number_format(23234,2)}}</h4>
                </div>


                <div class="card-body px-0">
                    <div id="exchangeProfitIncome"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-12">
            <div class="card h-100">


                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-0 text-body">درآمد از کارمزد معاملات بر حسب تتر  (هفته اخیر)</h6>
                        <div class="badge bg-label-success">+15%</div>
                    </div>
                    <h4 class="card-title mb-1">${{number_format(23234,2)}}</h4>
                </div>


                <div class="card-body px-0">
                    <div id="exchangeFeeIncome"></div>
                </div>
            </div>
        </div>
        <!-- Project Status -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0 card-title">نمودار واریزی ها (یک ماه اخیر)</h5>
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill text-muted border-0 p-2 me-n1" type="button"
                                id="projectStatusId" data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                            <i class="fa-regular fa-grip-dots-vertical text-muted"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="projectStatusId">
                            <a class="dropdown-item" href="javascript:void(0);">مشاهده با تفکیک تاریخ</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="badge rounded bg-label-primary p-2 me-3 rounded">
                            <i class="fa-solid fa-money-from-bracket"></i>
                        </div>
                        <div class="d-flex justify-content-between w-100 gap-2 align-items-center">
                            <div class="me-2">
                                <h6 class="mb-0">$4,3742</h6>
                                <small class="text-body">مجموع واریزی های یک ماه اخیر</small>
                            </div>
                            <h6 class="mb-0 text-success">+10.2%</h6>
                        </div>
                    </div>
                    <div id="projectStatusChart"></div>
                    <div class="d-flex justify-content-between mb-4">
                        <h6 class="mb-0">تعداد واریزی ها</h6>
                        <div class="d-flex">
                            <p class="mb-0 me-4">$756.26</p>
                            <p class="mb-0 text-danger">-139.34</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-0">مجموع واریزی ها</h6>
                        <div class="d-flex">
                            <p class="mb-0 me-4">$2,207.03</p>
                            <p class="mb-0 text-success">+576.24</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Support Tracker -->
        <div class="col-sm-12 col-xl-4">
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
                        <button type="button" class="btn btn-label-primary dropdown-toggle dropdown-toggle-split"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu" id="monthDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="فروردین">فروردین</a>
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);" data-month="اردیبهشت">اردیبهشت</a>
                            </li>
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
        <div class="col-xl-4 col-md-4 col-6">
            <div class="card h-100">
                <div class="card-header pb-3">
                    <h5 class="card-title mb-1">نمودار خرید OTC</h5>
                    <p class="card-subtitle">هفته گذشته</p>
                </div>
                <div class="card-body">
                    <div id="OTCBuyLastWeek"></div>
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <h4 class="mb-0">1,245</h4>
                        <small class="text-success">+12.6%</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4 col-6">
            <div class="card h-100">
                <div class="card-header pb-3">
                    <h5 class="card-title mb-1">نمودار فروش OTC</h5>
                    <p class="card-subtitle">هفته گذشته</p>
                </div>
                <div class="card-body">
                    <div id="OTCSellLastWeek"></div>
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <h4 class="mb-0">1,230</h4>
                        <small class="text-success">+12.6%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('vendor-script')
    @vite([
            'resources/assets/vendor/libs/apex-charts/apexcharts.js',
            'resources/assets/js/config.js',
            'resources/assets/js/dashboard.js',
            'resources/assets/js/dashboard-simple-deposit.js',
            'resources/assets/js/dashboard-simple-withdraw.js',
            'resources/assets/js/dashboard-simple-fee-income.js',
            'resources/assets/js/dashboard-simple-profit-income.js',
            'resources/assets/js/deposit.js',
         ])
@endsection

@section('vendor-style')
    @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
])
@endsection
