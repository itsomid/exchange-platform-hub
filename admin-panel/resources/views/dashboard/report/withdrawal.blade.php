@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')
    <div class="card">
        <div class="card-header">
            گزارش برداشت
        </div>
        <div class="card-body">
            <form action="{{route('admin.report.withdrawal')}}" method="get" class="row mt-5">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label  class="form-label" for="created_at">از تاریخ</label>
                            <input required
                                   type="text"
                                   name="from_date"
                                   class="form-control"
                                   value="{{old('from_date') ?? request()->input('from_date')}}"
                                   autocomplete="off"
                                   data-jdp
                                   placeholder="جهت درج تاریخ کلیک کنید">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="created_at">تا تاریخ</label>
                            <input required
                                   type="text"
                                   name="to_date"
                                   class="form-control"
                                   value="{{old('to_date') ?? request()->input('to_date')}}"
                                   autocomplete="off"
                                   data-jdp
                                   placeholder="جهت درج تاریخ کلیک کنید">
                        </div>
                    </div>
                    <div class="col-md-3">

                        <div class="form-group">
                            <label class="form-label" for="currency_symbol">کوین پایه:</label>
                            <select id="currency_symbol" class="form-select" name="currency_symbol"
                                    data-placeholder="لطفا کوین پایه بازار را انتخاب کنید.">
                                @foreach($currencies as $currency)
                                    <option value="{{$currency->symbol}}">
                                        <span class='tagify__tag-text'>{{$currency->name}}</span>
                                        <div>
                                            <div class='tagify__tag__avatar-wrap'>
                                                <img src="{{$currency->coinLogo()}}">
                                            </div>

                                        </div>

                                    </option>
                                @endforeach
                            </select>
                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class=" d-flex justify-content-start mt-5">
                        <div class="col-md-1">
                            <button class="btn btn-primary ">
                                <i class="fa-regular fa-chart-area mx-2"></i>
                                دریافت گزارش
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
    @if(isset($withdrawals))
        <div class="row g-6 mt-3">
            <div class="col-lg-12">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0 card-title">نمودار برداشت ها</h5>
                        <div class="dropdown">
                            <button class="btn btn-text-secondary rounded-pill text-muted border-0 p-2 me-n1"
                                    type="button" id="projectStatusId" data-bs-toggle="dropdown" aria-haspopup="true"
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
                                    <h6 class="mb-0">$?????</h6>
                                    <small class="text-body">مجموع برداشت های یک ماه اخیر</small>
                                </div>
                                <h6 class="mb-0 text-success">+?%</h6>
                            </div>
                        </div>
                        <div id="withdrawalChart"></div>
                        <div class="d-flex justify-content-between mb-4">
                            <h6 class="mb-0">تعداد برداشت ها</h6>
                            <div class="d-flex">
                                <p class="mb-0 me-4">$????</p>
                                <p class="mb-0 text-danger">-139.34</p>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-0">مجموع برداشت ها</h6>
                            <div class="d-flex">
                                <p class="mb-0 me-4">$???</p>
                                <p class="mb-0 text-success">+????</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('vendor-script')
    @vite([
            'resources/assets/vendor/libs/apex-charts/apexcharts.js',
            'resources/assets/js/jalalidatepicker.js',
            'resources/assets/js/config.js',
//            'resources/assets/js/withdrawal.js',
         ])
@endsection

@section('vendor-style')
    @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
])
@endsection
@push('scripts')
    @if($chartData)
    <script type="module">
        window.ApexCharts = ApexCharts; // return apex chart

        var options = {
            chart: {
                type: 'line',
                height: 350
            },
            // style:{
            //     direction: 'rtl'
            // },
            series: [{
                name: 'مبلغ',
                data: @json(array_values($chartData))
            }],
            xaxis: {
                categories: @json($dates)
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return value.toLocaleString();
                    }
                }
            },
            stroke: {
                curve: 'smooth'
            },
            markers: {
                size: 5
            },
            colors: ['#36A2EB'],
            dataLabels: {
                enabled: false
            },
            legend: {
                position: 'top'
            }
        };

        var chart = new ApexCharts(document.querySelector("#withdrawalChart"), options);
        chart.render();
    </script>
    @endif
@endpush
