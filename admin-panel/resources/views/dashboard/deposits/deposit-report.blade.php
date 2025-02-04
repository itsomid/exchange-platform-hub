@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')
    <div class="card">

        <div class="card-header d-flex justify-content-between">

            <h5 class="mb-0 card-title">گزارش واریز {{request()->input('currency_symbol')}}</h5>
            @if(isset($completeWithdrawals) && $deposits->isNotEmpty())
                <img src="{{$deposits[0]->currency->coinLogo()}}" width="60">
            @endif
        </div>
        <div class="card-body">
            <form action="{{route('admin.report.deposit')}}" method="get" class="row mt-5">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="from_date">از تاریخ</label>
                            <input required
                                   type="text"
                                   id="from_date"
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
                            <label class="form-label" for="to_date">تا تاریخ</label>
                            <input required
                                   type="text"
                                   id="to_date"
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
                                    <option
                                        value="{{$currency->symbol}}" {{request()->input('currency_symbol') === $currency->symbol ? 'selected':''}}>
                                        <span class='tagify__tag-text'>{{$currency->name}}</span>
                                    </option>
                                @endforeach
                            </select>
                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class=" d-flex justify-content-start mt-5">

                        <button class="btn btn-primary ">
                            <i class="fa-regular fa-chart-area mx-2"></i>
                            دریافت گزارش
                        </button>

                    </div>

                </div>
            </form>
        </div>
    </div>
    @if(isset($deposits))
        <div class="row  g-6 mt-3">
            <div class="col-sm-12 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="">تعداد واریزها</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{formatNumberTrimZeros($deposits->sum('total_transactions'))}}</h4>
                                </div>
                            </div>
                            <span class="badge bg-label-success rounded">
                                <i class="fa-regular fa-arrow-down-left fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="">مجموع واریزها</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{formatNumberTrimZeros($deposits->sum('total_amount'))}}</h4>
                                    <small>{{request()->input('currency_symbol')}}</small>
                                </div>
                            </div>
                            <span class="badge bg-label-success rounded">
                                <i class="fa-regular fa-arrow-down-left fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-12 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="">ارزش واریزها</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{formatNumber($totalDepositsValue,2)}}</h4>
                                    <small>USDT</small>
                                </div>
                            </div>
                            <span class="badge bg-label-warning rounded"><i
                                    class="fa-light fa-money-bill-wave"></i></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>



        <div class="row g-6 mt-3">
            <div class="col-lg-12">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0 card-title">نمودار واریز ها ({{request()->input('currency_symbol')}})</h5>
                        @if($deposits->isNotEmpty())
                            <img src="{{$deposits[0]->currency->coinLogo()}}" width="60">
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="badge rounded bg-label-primary p-2 me-3 rounded">
                                <i class="fa-solid fa-money-from-bracket"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="d-flex mb-4">
                                    <h6 class="mb-0">تعداد واریز ها:</h6>
                                    <div class="d-flex">
                                        <p class="mb-0 mx-2">{{$deposits->sum('total_transactions')}}</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-start">
                                    <h6 class="mb-0"> مجموع واریز ها موفق:</h6>
                                    <div class="d-flex">

                                        <p class="mb-0 mx-2">{{$deposits->sum('total_amount')}}</p>
                                        @if($deposits->isNotEmpty())
                                            <p class="mb-0">({{$deposits[0]->currency->symbol}})</p>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div id="depositAmountChart"
                             data-chartdata='@json($chartData)'
                             data-dates='@json($dates)'>
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
            'resources/assets/js/deposit.js',
         ])
@endsection

@section('vendor-style')
    @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
])
@endsection
