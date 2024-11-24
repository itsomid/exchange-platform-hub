@extends('dashboard.layout.master')
@section('title', 'پیشخوان')
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between my-0">
                        <div class="card-title mb-0">
                            <h5 class="mb-0">فیلتر</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{route('admin.transaction.report.index')}}" method="get">
                            <div class="row justify-content-center">
                                <div class="col-md-3">
                                    <div class="form-group mt-3">
                                        <label for="type">نوع تراکنش:</label>
                                        <select name="type" class="form-control" id="type" onclick="toggleDepositType()" onchange="toggleDepositType()">
                                            <option value="deposit"  {{request()->has('type') && request()->input('type') == 'deposit' ? 'selected' : "" }}>واریز (deposit)</option>
                                            <option value="buy"      {{request()->has('type') && request()->input('type') == 'buy'     ? 'selected' : "" }}>   خرید (buy) </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mt-3">
                                        <label for="deposit_type">نوع واریز</label>
                                        <select name="deposit_type" class="form-control" id="deposit_type">
                                            <option value=""> همه </option>

                                        @foreach(\App\Enums\DepositTypeEnum::TYPE_LABEL as $key=>$case)
                                                <option value="{{$key}}" {{request()->has('deposit_type') && request()->input('deposit_type') == $key ? 'selected' : "" }}>
                                                    {{$case}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mt-3">
                                        <label class="form-label" for="from_date">از تاریخ:</label>
                                        <input name="from_date" type="text" id="from_date" class="form-control" placeholder="از تاریخ" value="{{old('from_date') ?? request()->input('from_date')}}" data-jdp autocomplete="off">
                                        @error('from_date')<small class="text-danger">{{$message}}</small>@enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mt-3">
                                        <label class="form-label" for="to_date">تا تاریخ:</label>
                                        <input name="to_date" type="text" id="to_date" class="form-control" placeholder="تا تاریخ" value="{{old('to_date') ?? request()->input('to_date')}}" data-jdp autocomplete="off">
                                        @error('to_date')<small class="text-danger">{{$message}}</small>@enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="mt-3"> </label>
                                    <button type="submit"  class="btn btn-primary w-100 text-white" >فیلتر</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">

                <div class="col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between mb-5">
                            <div class="card-title mb-0">
                                <h5 class="mb-0">نمودار مجموع {{request()->input('type') == 'deposit' ? 'واریز' : "خرید" }}</h5>
                                <small class="text-muted">{{request()->input('time_span',7)}} روز اخیر</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div id="lineChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/css/apex-charts.css',
           'resources/assets/vendor/js/apexcharts.js',
           'resources/assets/js/jalalidatepicker.js',])
@endsection

@section('scripts')
    <script type="module">
        window.ApexCharts = ApexCharts; // return apex chart

        var options = {
            chart: {
                type: 'line',
                height: 350
            },
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

        var chart = new ApexCharts(document.querySelector("#lineChart"), options);
        chart.render();
    </script>


    <script>
        function toggleDepositType() {
            let transactionType = document.getElementById('type').value;
            let depositTypeSelect = document.getElementById('deposit_type');

            if (transactionType === 'deposit') {
                depositTypeSelect.disabled = false;
            } else {
                depositTypeSelect.disabled = true;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleDepositType();
        });
    </script>
@endsection
