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

                    <div class="col-md-6 col-xxl-4 mb-4">
                        <label class="form-label">کوین مورد نظر را انتخاب کنید:</label>
                        <x-currency-select
                            name="currency_id"
                            id="currency_select"
                            :currencies="$currencies"
                            :selected="$selectedCurrency?->id ?? ''"
                            :required="true"
                            error="currency_id"
                            placeholder="لطفا کوین مورد نظر را انتخاب کنید..."
                        />
                    </div>
                    @if($selectedCurrency === null)
                    <div class="ol-md-6 col-xxl-4 mb-4 d-flex align-items-end">
                        <div class="alert alert-secondary mb-0 w-100 py-2">
                            <small><span class="fa-solid fa-hand-pointer me-1"></span>ابتدا یک کوین انتخاب کنید تا شبکه‌های آن نمایش داده شود.</small>
                        </div>
                    </div>
                    @elseif($hasChains)
                    <div class="col-md-6 col-xxl-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="chain">شبکه مورد نظر را انتخاب کنید:</label>
                            <select id="chain" class="form-select" name="chain"
                                    data-placeholder="لطفا شبکه مورد نظر را انتخاب کنید.">
                                @foreach($currencyChains as $chain)
                                    <option value="{{$chain->chain}}">{{$chain->chain}}</option>
                                @endforeach
                            </select>
                            @error('chain')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    @else
                    <div class="col-md-6 col-xxl-4 mb-4 d-flex align-items-end">
                        <div class="alert alert-info mb-0 w-100 py-2">
                            <small><span class="fa-solid fa-circle-info me-1"></span>این کوین شبکه‌ای ندارد — اعتبار مستقیم اعمال می‌شود.</small>
                        </div>
                    </div>
                    @endif
                    <div class="w-100"></div>
                    <div class="col-md-6 col-xxl-4 mb-3">
                        <label for="numeral-mask" class="form-label ">میزان اعتبار مورد نظر:</label>
                        <input type="number"
                               name="amount"
                               step="0.0000001"
                               id="" class="form-control"
                               placeholder="میزان کوین مورد نظر را وارد کنید">
                        @error('amount')<small class="text-danger">{{$message}}</small>@enderror
                    </div>
                    <div class="w-100"></div>
                    <div class="col-md-6 col-xxl-4 mb-3">
                        <label for="numeral-mask" class="form-label "> هش تراکنش (TxID) (اختیاری)</label>
                        <input type="text"
                               name="transaction_hash"
                               class="form-control"
                               placeholder="هش تراکنش را وارد کنید (اختیاری)">
                        @error('transaction_hash')<small class="text-danger">{{$message}}</small>@enderror
                    </div>
                    <div class="w-100"></div>
                    <div class="col-md-12 col-xxl-4 user_role mt-3">
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

            // Reload page with selected currency to show its chains
            $(document).on('change', '#currency_select', function () {
                var currencyId = $(this).val();
                var userId = $('#selectUser').val() || '';
                var url = new URL(window.location.href);
                if (currencyId) {
                    url.searchParams.set('currency_id', currencyId);
                } else {
                    url.searchParams.delete('currency_id');
                }
                if (userId) {
                    url.searchParams.set('user', userId);
                }
                window.location.href = url.toString();
            });
        });
    </script>
@endpush




