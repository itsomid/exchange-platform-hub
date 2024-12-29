@extends('dashboard.layout.master')
@section('title', 'تنظیمات و پیکربندی')
@section('content')
    <div class="row g-6">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">تنظیمات دعوت از دوستان</h5>
                    </div>
                    <form action="{{route('admin.setting.int.update-referral-setting')}}" method="post">
                        @csrf
                        <div class="row mt-5">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="referral_profit_percentage">درصد اهدایی به کاربران برای معرفی دوستان</label>
                                    <input type="number" name="referral_profit_percentage" id="referral_profit_percentage" class="form-control"
                                           placeholder="درصد اهدایی به کاربران برای معرفی دوستان"
                                           value="{{$referralProfitPercentage->value}}"
                                           required>
                                    @error('otcBuyFee')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mt-5">
                                    <label class="switch  switch-lg">
                                        <input type="checkbox" class="switch-input"
                                               name="referral_profit_status"
                                               value="1" {{ $referralProfitStatus->value ? 'checked' : '' }}/>
                                        <span class="switch-toggle-slider"></span>
                                        <span class="switch-label">وضعیت سیستم رفرال(فعال/غیرفعال)</span>
                                    </label>
                                </div>
                            </div>
                            <div class=" d-flex justify-content-start mt-5">
                                <div class="col-md-1">
                                    <button class="btn btn-primary ">
                                        <i class="fa fa-save mx-2"></i>
                                        ذخیره
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">کارمزد خرید و فروش OTC</h5>

                    </div>
                    <form action="{{route('admin.setting.int.update-otc-setting')}}" method="post">
                        @csrf
                        <div class="row mt-5">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="otc_buy_fee">کارمزد خرید مشتری (درصد)</label>
                                    <input name="otc_buy_fee" id="otc_buy_fee" class="form-control"
                                           placeholder="کارمزد خرید مشتری (درصد)"
                                           value="{{$otcBuyFee->value}}"
                                           required>
                                    @error('otcBuyFee')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="otc_sell_fee">کارمزد فروش مشتری (درصد) </label>
                                    <input name="otc_sell_fee" id="otc_sell_fee" class="form-control"
                                           placeholder="کارمزد فروش مشتری (درصد)"
                                           value="{{$otcSellFee->value}}" required>
                                    @error('otcSellFee')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class=" d-flex justify-content-start mt-5">
                                <div class="col-md-1">
                                    <button class="btn btn-primary ">
                                        <i class="fa fa-save mx-2"></i>
                                        ذخیره
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">تنظیمات و پیکربندی مجوزها </h5>
                    </div>
                    <form action="{{route('admin.setting.int.update-permissions')}}" method="post">
                        @csrf
                        <button class="btn btn-success w-100">
                            <i class="fa fa-refresh mx-2"></i>
                            بروزرسانی مجوزها
                        </button>
                    </form>
                    <h6 class="text-center my-3">آخرین مجوزهای اضافه شده:</h6>
                    @foreach($last3permissions as $last3permission)
                        <p>{{$last3permission->name}}</p>
                    @endforeach

                </div>
            </div>
        </div>
    </div>
    <div class="row">

    </div>

@endsection
