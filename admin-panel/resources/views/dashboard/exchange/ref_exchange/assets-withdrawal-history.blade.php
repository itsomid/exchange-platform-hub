@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد برداشت از صرافی مرجع</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$withdraws->count()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-arrow-up-right"></i>
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
                            <span>مجموع کارمزد پرداخت شده به صرافی مرجع (Coinex)</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{formatNumberTrimZeros($withdrawalFeeSum)}}
                                    <small>CET</small>
                                </h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست درخواست های برداشت به HD Wallet</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{route('admin.ref-exchange.assets-gathering-to-hd-wallet.create')}}" class="btn btn-primary">
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
                        <th>صرافی مرجع</th>
                        <th>کوین</th>
                        <th>شبکه</th>
                        <th>مقدار</th>
                        <th>فی برداشت</th>
                        <th>آدرس برداشت (HD Wallet)</th>
                        <th>تاریخ برداشت</th>
                        {{--                        <th>توضیحات</th>--}}
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($withdraws as $withdraw)

                        <tr>
                            <td>{{$withdraw->id}}</td>
                            <td>{{$withdraw->exchange}}</td>
                            <td>
                                {{$withdraw->currency_symbol}}
                            </td>

                            <td>
                                {{$withdraw->currency_chain}}
                            </td>
                            <td class="font-number">{{$withdraw->amount}}</td>
                            <td class="font-number"> ({{$withdraw->fee_currency}}) {{formatNumberTrimZeros($withdraw->fee)}}</td>
                            <td>
                                <h6 class="mb-0">
                                    @if($withdraw->explore_address_url)
                                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary p-0 copy-btn me-1"
                                            data-copy-text="{{ $withdraw->hd_wallet_address }}" title="کپی آدرس">
                                            <i class="fa-regular fa-clone"></i>
                                        </button>
                                        <a href="{{ $withdraw->explore_address_url }}" target="_blank" class="me-1">
                                            <small class="font-number">{{ shorten_hash($withdraw->hd_wallet_address) }}</small>
                                        </a>
                                    @elseif($withdraw->hd_wallet_address)   
                                        <span>{{$withdraw->hd_wallet_address}}</span>
                                    @else
                                        <span>N/A Address</span>
                                    @endif
                                </h6>
                            </td>
                            <td>
                                {{\App\Helpers\DateFormatter::convertToPersianDate( $withdraw->withdrawal_date,'H:i:s %Y/%m/%d')}}
                            </td>
                            {{--                            <td>--}}
                            {{--                                {{$withdraw->description}}--}}
                            {{--                            </td>--}}

                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
@section('vendor-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('click', function (event) {
                const button = event.target.closest('.copy-btn');
                if (!button) {
                    return;
                }

                const textToCopy = button.getAttribute('data-copy-text');
                if (!textToCopy) {
                    return;
                }

                navigator.clipboard.writeText(textToCopy).then(function () {
                    const icon = button.querySelector('i');
                    if (!icon) {
                        return;
                    }

                    icon.classList.replace('fa-clone', 'fa-check');
                    setTimeout(function () {
                        icon.classList.replace('fa-check', 'fa-clone');
                    }, 1500);
                }).catch(function () {});
            });
        });
    </script>
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
