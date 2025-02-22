@extends('dashboard.layout.master')
@section('title', 'فرم افزایش اعتبار')
@section('content')
    <section class="form-control-repeater">
        <div class="card">

            <div class="card-body">
                <h5 class="card-title">فرم واریز اعتبار</h5>
                <form class="row" method="post" action="{{route('admin.wallet.increase-credit')}}" id="chargeForm">
                    @csrf
                    <div class="col mt-2">
                        <div class="form-check form-check-inline">
                            <input name="transaction_type" class="form-check-input" type="radio" value="deposit"
                                   id="transaction-type-deposit" checked="">
                            <label class="form-check-label" for="transaction-type-deposit">واریز</label>
                        </div>
                    </div>
                    <div class="w-100"></div>
                    <div class="col-md-8 mb-4">
                        <label class="form-label" for="user">کاربر :</label>
                        <x-user-selection-component
                            input-name="user"
                            multiple="0"
                            selected="{{ $selectedUser ?$selectedUser->id: '' }}"
                            selected-label="{{$selectedUser
                                            ? '(' . $selectedUser->id . '#) ' . $selectedUser->fullname() . ' | ' . $selectedUser->email
                                            : ''}}"
                        ></x-user-selection-component>
                    </div>
                    <div class="w-100"></div>

                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="currency">کوین مورد نظر را انتخاب کنید:</label>
                            <select id="currency" class="form-select" name="currency"
                                    data-placeholder="لطفا کوین  مورد نظر را انتخاب کنید.">
                                @foreach($currencies as $currency)
                                    <option
                                        {{$selectedCurrency?->symbol === $currency->symbol ? 'selected' : ''}} value="{{$currency->symbol}}">{{$currency->name}}</option>
                                @endforeach
                            </select>
                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="chain">شبکه مورد نظر را انتخاب کنید:</label>
                            <select id="chain" class="form-select" name="chain"
                                    data-placeholder="لطفا شبکه  مورد نظر را انتخاب کنید.">
                                @foreach($currencyChains as $chain)
                                    <option value="{{$chain->chain}}">{{$chain->chain}}</option>
                                @endforeach
                            </select>
                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class="w-100"></div>
                    <div class="col-md-4 mb-3">
                        <label for="numeral-mask" class="form-label ">میزان اعتبار مورد نظر:</label>
                        <input type="number"
                               name="amount"
                               step="0.0000001"
                               id="" class="form-control"
                               placeholder="میزان کوین مورد نظر را وارد کنید">
                        @error('amount')<small class="text-danger">{{$message}}</small>@enderror
                    </div>
                    <div class="w-100"></div>
                    <div class="col-md-4 mb-3">
                        <label for="numeral-mask" class="form-label "> هش تراکنش (TxID) (اختیاری)</label>
                        <input type="text"
                               name="transaction_hash"
                               class="form-control"
                               placeholder="هش تراکنش را وارد کنید (اختیاری)">
                        @error('amount')<small class="text-danger">{{$message}}</small>@enderror
                    </div>
                    <div class="w-100"></div>
                    <div class="col-md-6 user_role mt-3">
                        <label class="form-label" for="admin_description">توضیحات تراکنش (اختیاری):</label>
                        <textarea class="form-control" id="admin_description" name="admin_description"
                                  placeholder="توضیحات..."></textarea>

                    </div>


                    <div class="row align-items-center g-2 mt-2">
                        <div class="col text-center">
                            <button type="submit" id="submitButton" class="btn btn-primary text-white ">
                                <span class="fa-regular fa-dollar-square me-2"></span>
                                شارژ اکانت
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
            document.getElementById('chargeForm').addEventListener('submit', function () {

                var submitButton = document.getElementById('submitButton');
                submitButton.disabled = true;
                submitButton.classList.add('disabled');
            });
        });
    </script>
@endpush

@section('vendor-style')
    @vite([

    ])

@endsection
@section('vendor-script')

    @vite([

    ])
@endsection


