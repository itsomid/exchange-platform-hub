@extends('dashboard.layout.master')
@section('title', 'مدیریت کدهای معرف')
@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">ساخت کد دعوت اختصاصی</h5>
            <form class="row" method="post" action="{{route('admin.referral_code.store')}}">
                @csrf
                <div class="col-md-6 user_role mt-3">
                    <label class="form-label" for="codeInput">کد:</label>
                    <input type="text"
                           name="code"
                           id="codeInput"
                           class="form-control font-ernumber"
                           value="{{$sampleReferralCode}}"
                           placeholder="کد یا شناسه ی دلخواه چند حرفی را وارد کنید"
                           readonly
                    >
                    @error('code')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label mt-5" for="user_id">کاربر :</label>
                    <x-user-selection-component
                        inputName="user_id"
                        multiple="0"
                        selected=""
                        selected-label="">
                    </x-user-selection-component>
                </div>

                <div class="col-md-6 user_role mt-3">
                    <label class="form-label" for="introducerFee">نرخ کارمزد اهدایی به کاربر:  (درصد)</label>
                    <input type="number"
                           name="introducer_fee"
                           id="introducerFee"
                           class="form-control number_sep"
                           placeholder="مقدار را وارد کنید"
                           value="30"
                    >
                </div>
                <div class="col-md-6 user_role mt-3">
                    <label class="form-label" for="friendFee">نرخ کارمزد اهدایی به دوستان کاربر: (درصد)</label>
                    <input type="number"
                           name="friend_fee"
                           id="friendFee"
                           class="form-control number_sep"
                           placeholder="مقدار را وارد کنید"
                           value="0"
                    >
                </div>

                <div class="col-md-6 user_role mt-3">
                    <label class="form-label" for="usageLimit">محدودیت استفاده کاربر (تعداد)</label>
                    <input type="number"
                           name="gift_credit"
                           id="usageLimit"
                           class="form-control number_sep"
                           placeholder="مقدار را وارد کنید">
                </div>
                <div class="col-md-12 text-right mt-5">
                    <button class="btn btn-primary mt-2">
                        <i class="fa fa-plus mx-2"></i>
                        ثبت و ذخیره
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('vendor-style')
        @vite(['resources/assets/vendor/libs/select2/select2.scss'])

@endsection
@section('vendor-script')

    @vite(['resources/assets/vendor/libs/select2/select2.js'])
    @vite(['resources/assets/vendor/js/forms-selects.js'])

@endsection


