@extends('dashboard.layout.master')
@section('title', 'پیشخوان')
@section('content')

<!-- User Registration statistics-->
<div class="col-sm-12 col-xl-12">
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
            <div id="userRegistrationChart"
                 data-totalRegistration='@json(array_values($totalRegistrationData))'
                 data-totalInActiveRegistration='@json(array_values($totalInActiveRegistrationData))'
            ></div>
        </div>
    </div>
</div>
<!--/ User Registration statistics -->

@endsection
@section('vendor-script')
    @vite([
            'resources/assets/vendor/libs/apex-charts/apexcharts.js',
            'resources/assets/js/config.js',
            'resources/assets/js/user-registration-chart.js',
         ])
@endsection

@section('vendor-style')
    @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
])
@endsection
