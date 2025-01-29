@extends('dashboard.layout.master')
@section('title', 'مدیریت کوین ها')
@section('content')

    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد کوین</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$currencies->count()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-users"></i>
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
                            <span>کوین های فعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$currencies->where('is_active')->count()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-solid fa-user-check"></i>
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
                            <span>کوین های غیر فعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$currencies->where('is_active', false)->count()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                                <i class="fa-solid fa-user-xmark"></i>
                            </span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">جست و جو</h5>

            <form class="row mt-3 d-flex align-items-end justify-content-between"
                  action="{{route('admin.currency.index')}}" method="get">

                <div class="col-md-4 user_status ">
                    <label class="form-label" for="status">شبکه :</label>
                    <select id="status" name="type" class="form-select text-capitalize mb-md-0 ">
                        <option value="" {{ request('type') == '' ? 'selected' : '' }}>همه</option>
                        @foreach(\App\Enums\CurrencyChainEnum::cases() as $chain)
                            <option
                                value="ERC20" {{ request('type') == 'ERC20' ? 'selected' : '' }}>{{$chain->value}}</option>
                        @endforeach

                    </select>
                </div>
            </form>
        </div>
    </div>



    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست کوین ها</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{route('admin.currency.create')}}" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i>
                        افزودن کوین جدید
                    </a>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr class="text-center">
                            <th>#</th>
                            <th>آواتار</th>
                            <th>نام</th>
                            <th>سیمبول</th>
                            <th>شبکه های موجود</th>
                            <th>کارمزد برداشت صرافی</th>
                            <th>کارمزد برداشت شبکه</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($currencies as $currency)

                        <tr >
                            <td>{{$currency->id}}</td>
                            <td>
                                <img src="{{asset($currency->coinLogo())}}" class="img-fluid" width="50px">
                            </td>

                            <td>
                                {{$currency->name}}
                            </td>
                            <td>
                                {{$currency->symbol}}
                            </td>
                            <td>
                                <div class="d-flex flex-column align-items-center justify-content-center mb-2">
                                @if(count($currency->chains))

                                        @foreach($currency->chains as $chain)
                                            <span class="badge bg-label-primary mb-2">{{$chain->chain}}</span>
                                        @endforeach

                                @else
                                    <span class="badge bg-label-danger ms-2">بدون شبکه</span>
                                @endif
                                </div>
                            </td>

                            <td>
                                @if(count($currency->chains))
                                    @foreach($currency->chains as $chain)
                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                            <span class="badge bg-label-primary ms-2 font-number">

                                                <smal>{{$chain->chain}} -> </smal>

                                                <span class="fw-bold text-primary">{{formatNumberTrimZeros($chain->exchange_withdrawal_fee)}}</span>
                                                <smal class="me-2">{{$currency->symbol}}</smal>
                                            </span>

                                        </div>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                @if(count($currency->chains))
                                    @foreach($currency->chains as $chain)
                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                            <span class="badge bg-label-primary ms-2 font-number">

                                                <smal>{{$chain->chain}} -> </smal>

                                                <span class="fw-bold text-primary">{{formatNumberTrimZeros($chain->network_fee)}}</span>
                                                <smal class="me-2">{{$currency->symbol}}</smal>
                                            </span>

                                        </div>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                @if(count($currency->chains))
                                    @foreach($currency->chains as $chain)
                                        <div class="d-flex align-items-center mb-2">
                                            @if($chain->deposit_enabled)
                                                <span class="badge bg-label-success ms-2">{{$chain->chain}} -> واریز فعال</span>
                                            @else
                                                <span class="badge bg-label-danger ms-2">{{$chain->chain}} -> واریز غیرفعال</span>
                                            @endif
                                            @if($chain->withdraw_enabled)
                                                <span class="badge bg-label-success ms-2">{{$chain->chain}} -> برداشت فعال</span>
                                            @else
                                                <span class="badge bg-label-danger ms-2">{{$chain->chain}} -> برداشت غیرفعال</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span class="badge bg-label-danger ms-2">بدون شبکه</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center">

                                    <a class="text-secondary me-3"
                                       href="{{ route('admin.currency.edit', ['currency' => $currency->id]) }}">
                                        <i class="fa-light fa-pen-to-square fa-lg"></i>
                                    </a>
                                    <a class="text-secondary me-3" href="">
                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
