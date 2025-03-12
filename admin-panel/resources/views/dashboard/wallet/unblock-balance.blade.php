@extends('dashboard.layout.master')
@section('title', 'فرم آزادسازی اعتبار')
@section('content')
    <section class="form-control-repeater">
        <div class="card">
            <div class="card-body invoice-preview-header rounded">
                <div class="row text-heading px-3">
                    <div class="col-md-7 mb-md-0 mb-6 ps-0">
                        <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center m-2">
                            <div class="d-flex align-items-center">
                                <img src="{{$wallet->currency->coinLogo()}}" class="img-fluid" width="50px">
                            </div>
                            <div class="flex-grow-1">
                                <div
                                    class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
                                    <div class="user-profile-info">
                                        <h4 class="mb-0">آزادسازی موجودی کاربر روی شبکه {{$wallet->currency_symbol}}</h4>
                                        <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 my-2">
                                            <li class="list-inline-item d-flex gap-1 align-items-center">
                                                <i class="fa-regular fa-hashtag"></i>
                                                <span class="font-number">{{$user->id}}</span>
                                            </li>
                                            <li class="list-inline-item d-flex gap-2 align-items-center">
                                                <i class="fa-regular fa-user-check"></i>
                                                <span class="text-body">{{$user->username}}</span>
                                            </li>
                                            <li class="list-inline-item d-flex gap-2 align-items-center">
                                                <i class="fa-regular fa-envelope"></i>
                                                <span class="text-body">{{$user->email}}</span>
                                            </li>
                                            <li class="list-inline-item d-flex gap-2 align-items-center">
                                                <i class="fa-regular fa-clock"></i>
                                                <span class="text-body">آخرین فعالیت
                                                @if($user->latestActiveToken)
                                                        {{\App\Helpers\DateFormatter::convertToPersianDate($user->latestActiveToken->last_used_at,'H:i:s %Y/%m/%d')}}
                                                    @else
                                                        <span>بدون فعالیت</span>
                                                    @endif
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
                        <dl class="row mb-0 gx-4">

                            <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                                <span class="fw-normal">موجودی کیف پول</span>
                            </dt>
                            <dd class="col-sm-7">
                                <div class="input-group">

                                    <input type="text" class="form-control font-number" readonly="readonly"
                                           value="{{formatNumberTrimZeros($wallet->balance)}}">
                                    <span class="input-group-text"> {{$wallet->currency->symbol}}</span>
                                </div>
                            </dd>
                            <dt class="col-sm-5 d-md-flex align-items-center justify-content-end">
                                <span class="fw-normal text-danger">موجودی بلاک شده</span>
                            </dt>
                            <dd class="col-sm-7 mb-2">
                                <div class="input-group">
                                    <input type="text" class="form-control font-number text-danger" readonly="readonly"
                                           value="{{formatNumberTrimZeros($wallet->locked_balance)}}">
                                    <span class="input-group-text"> {{$wallet->currency->symbol}}</span>
                                </div>

                            </dd>
                            <dt class="col-sm-5 d-md-flex align-items-center justify-content-end">
                                <span class="fw-normal text-success">موجودی در دسترس</span>
                            </dt>
                            <dd class="col-sm-7 mb-0">
                                <div class="input-group">

                                    <input type="text" class="form-control font-number text-success" readonly="readonly"
                                           value="{{formatNumberTrimZeros(bcsub($wallet->balance , $wallet->locked_balance,8) )}}">

                                    <span class="input-group-text"> {{$wallet->currency->symbol}}</span>
                                </div>
                            </dd>

                        </dl>
                    </div>
                </div>
            </div>
            <div class="card-body">

                <form class="row" method="post" action="{{route('admin.wallet.unblock-balance',['wallet'=>$wallet])}}"
                      id="unblockBalanceForm">
                    @csrf


                    <div class="col-md-6">
                        <label for="unblock_amount" class="form-label mb-0">میزان آزادسازی را وارد کنید:</label>
                        <input type="number"
                               name="unblock_amount"
                               id="unblock_amount"
                               step="0.000000001"
                               class="form-control font-number"

                               placeholder="میزان کوین مورد نظر را وارد کنید">
                        @error('amount')<small class="text-danger">{{$message}}</small>@enderror
                    </div>
                    <div class="w-100 my-3"></div>
                    <div class="col-md-6" dir="ltr" >

                        <input type="range"  class="form-range" value="0" min="0" max="{{$wallet->locked_balance}}" step="0.000000001"  id="amount_range">
                    </div>
                    <div class="w-100"></div>
                    <div class="col-md-6 mt-5">
                        <label class="form-label" for="admin_description">توضیحات (اختیاری):</label>
                        <textarea class="form-control" id="admin_description" name="admin_description"
                                  placeholder="توضیحات..."></textarea>

                    </div>


                    <div class="row align-items-center g-2 mt-2">
                        <div class="col ">
                            <button type="submit" id="submitButton" class="btn btn-success text-white ">
                                <span class="fa-regular fa-lock-open me-2"></span>
                                آزاد سازی موجودی
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById('unblockBalanceForm').addEventListener('submit', function () {

                var submitButton = document.getElementById('submitButton');
                submitButton.disabled = true;
                submitButton.classList.add('disabled');
            });
        });
    </script>
@endpush

@section('vendor-style')
{{--    @vite(['resources/assets/vendor/libs/range-slider/range.scss'])--}}
@endsection
@section('vendor-script')
{{--    @vite(['resources/assets/vendor/libs/range-slider/range.js'])--}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Get references to the range input and unblock amount input
            const rangeInput = document.getElementById('amount_range');
            const amountInput = document.getElementById('unblock_amount');

            // Listen for input changes on the range slider
            rangeInput.addEventListener('input', function () {

                // Update the value of the unblock amount input to match the range slider
                amountInput.value = this.value;
            });

            // Optional: Sync the range slider value when manually changing the unblock amount input
            amountInput.addEventListener('input', function () {

                console.log(this.value)
                if (parseFloat(this.value) <= parseFloat(rangeInput.max) && parseFloat(this.value) >= parseFloat(rangeInput.min)) {
                    rangeInput.value = this.value;
                }
            });
        });

    </script>
@endsection


