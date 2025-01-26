@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد درخواست های تجمیع در HD Wallet</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$withdraws->count()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-users"></i>
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
                <h5 class="m-0 me-2">لیست درخواست های برداشت به HD Wallet</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{route('admin.wallets.assets-gathering-to-hd-wallet.create')}}" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i>
                        درخواست برداشت جدید
                    </a>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>کوین</th>
                        <th>شبکه</th>
                        <th>مقدار</th>
                        <th>آی دی تراکنش در کوینکس</th>
                        <th>آدرس برداشت</th>
                        <th>تاریخ برداشت</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($withdraws as $withdraw)

                        <tr>
                            <td>{{$withdraw->id}}</td>
                            <td>
                                {{$withdraw->currency_symbol}}
                            </td>

                            <td>
                                {{$withdraw->currency_chain}}
                            </td>
                            <td>
                                {{$withdraw->amount}}
                            </td>
                            <td>
                                {{$withdraw->hd_wallet_address}}
                            </td>
                            <td>
                                {{$withdraw->withdrawal_date}}
                            </td>
                            <td>
                                {{$withdraw->description}}
                            </td>
                            <td>
                                <div class="d-flex align-items-center">

                                    <a class="text-secondary me-3"
                                       href="{{ route('admin.currency.edit', ['currency' => $withdraw->id]) }}">
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
@section('vendor-script')
    @vite([
            'resources/assets/vendor/libs/apex-charts/apexcharts.js',
             'resources/assets/js/config.js',
            'resources/assets/js/wallet.js'
         ])
@endsection

@section('vendor-style')
    @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
])
@endsection
