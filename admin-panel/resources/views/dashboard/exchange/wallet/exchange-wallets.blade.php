@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول های صرافی')
@section('content')
    <div class="row g-6">
        <div class="col-12">
            <div class="card mb-6">
                <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center m-2">

                    <div class="flex-grow-1">
                        <div
                            class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
                            <div class="user-profile-info">
                                <h4 class="mb-2">دارایی در صرافی مرجع (Coinex)</h4>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-6 mt-3">
        @foreach($coinexAssets as $asset)
            <div class="col-lg-3 col-sm-6">
                <div class="card card-border-shadow-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <div class="avatar flex-shrink-0 me-4">
                                    <img src="http://127.0.0.1:8000/images/coins/usdt.svg" class="img-fluid" width="50px">
                                </div>
                            </div>
                            <h4 class="mb-0">کیف پول {{$asset->getCcy()}}</h4>
                        </div>
                        <h3 class="mt-4 mb-1 font-number">{{formatNumberTrimZeros($asset->getAvailable())}}
                            <span class="text-muted h4">{{$asset->getCcy()}}</span>
                        </h3>


                        <p class="mb-0">
                            <small class="text-danger">موجودی مسدود شده:</small>
                            <small
                                class="text-danger fw-bold ms-2 font-number">{{formatNumberTrimZeros($asset->getFrozen())}}
                                <span class="text-danger ">{{$asset->getCcy()}}</span>
                            </small>
                        </p>


                    </div>

                </div>
            </div>
        @endforeach

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
