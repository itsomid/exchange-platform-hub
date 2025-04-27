@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="col-md-7 mb-md-0 mb-6 ps-0 d-flex align-items-center">
                <img src="{{$currency->coinLogo()}}" width="60px">
                <h5 class="mb-0 ms-3 card-title">فرم انتقال ({{$currency->name}}) از HD Wallet به Cold Wallet</h5>
            </div>

            <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">

                <dl class="row mb-0 gx-4">

                    @foreach($currencyChains as $chain)
                        <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                            <span class="fw-bold text-primary ">کارمزد برداشت</span>
                        </dt>
                        <dd class="col-sm-7">
                            <div class="input-group">

                                <input type="text" class="form-control font-number fw-bold" readonly="readonly"
                                       dir="ltr"
                                       value="{{formatNumberTrimZeros($chain->network_fee)}} {{$currency->symbol}} ≈ {{$chain->network_fee * $chain->currency->exchangePrice}} USD ">
                                <span class="input-group-text text-primary fw-bold "> {{$chain->chain}}</span>
                            </div>
                        </dd>
                    @endforeach


                </dl>
            </div>
        </div>
        <div class="card-body">
            <form action="{{route('admin.ref-exchange.assets-gathering-to-hd-wallet.store')}}" method="post" id="withdrawalForm">
                @csrf
                <div class="row">

                    <div class="col-xl-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="currency_symbol">کوین مورد نظر:</label>
                            <input type="text" name="currency_symbol" id="currency_symbol" class="form-control"
                                   value="{{$currency->symbol}}" placeholder="نام کوین" readonly>

                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class="col-xl-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="currency_chain">شبکه مورد نظر را انتخاب کنید:</label>
                            <select id="currency_chain" class="form-select" name="currency_chain"
                                    data-placeholder="لطفا شبکه  مورد نظر را انتخاب کنید.">
                                @foreach($currencyChains as $chain)
                                    <option value="{{$chain->chain}}">{{$chain->chain}}</option>
                                @endforeach
                            </select>
                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class="w-100"></div>
                    <div class="col-xl-4 mb-3">
                        <label for="amount" class="form-label">میزان برداشت</label>
                        <input type="number" name="amount" id="amount" step="0.00000001" class="form-control"
                               value="{{request()->amount}}" max="{{request()->amount}}"
                               min="0" placeholder="میزان کوین مورد نظر را وارد کنید">
                    </div>
                    <div class="w-100"></div>
                    @foreach($walletChains as $chain)
                        <div class="col-xl-4 mb-3">
                            <label for="withdrawal_address" class="form-label">
                                <span>آدرس برداشت</span>
                                <span class="mx-2 ">({{$chain->currency_chain}})</span>
                            </label>
                            <input type="text" name="withdrawal_address" id="withdrawal_address_{{ $chain->currency_chain }}" class="form-control text-end font-number"
                                   value="{{$chain->address}}" placeholder="آدرس برداشت">
                        </div>
                        <div class="w-100"></div>
                    @endforeach

                    <div class=" d-flex justify-content-start mt-5">
                        <button type="button" class="btn btn-primary" id="confirmWithdrawalBtn">
                            <i class="fa fa-save mx-2"></i>
                            برداشت از صرافی مرجع
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="withdrawalConfirmationModal" tabindex="-1" aria-labelledby="withdrawalConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawalConfirmationModalLabel">تایید برداشت</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        <strong>توجه:</strong> لطفا آدرس برداشت را با دقت بررسی کنید و مطمئن شوید که به آدرس صحیح برداشت انجام می‌شود.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">کوین:</label>
                        <div id="modalCurrencySymbol" class="form-control bg-light"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">شبکه:</label>
                        <div id="modalChain" class="form-control bg-light"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">مقدار:</label>
                        <div id="modalAmount" class="form-control bg-light"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">آدرس برداشت:</label>
                        <div id="modalAddress" class="form-control bg-light text-end font-number" style="word-break: break-all;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-primary" id="confirmSubmitBtn">تایید و برداشت</button>
                </div>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('withdrawalForm');
        const confirmBtn = document.getElementById('confirmWithdrawalBtn');
        const submitBtn = document.getElementById('confirmSubmitBtn');
        const modal = new bootstrap.Modal(document.getElementById('withdrawalConfirmationModal'));

        confirmBtn.addEventListener('click', function() {
            // Get form values
            const currencySymbol = document.getElementById('currency_symbol').value;
            const chainSelect = document.getElementById('currency_chain');
            const selectedChain = chainSelect.value;
            const chainText = chainSelect.options[chainSelect.selectedIndex].text;
            const amount = document.getElementById('amount').value;
            
            // Get the correct withdrawal address based on the selected chain
            const addressField = document.getElementById('withdrawal_address_' + selectedChain);
            const address = addressField ? addressField.value : '';

            // Validate form
            if (!amount || amount <= 0) {
                alert('لطفا میزان برداشت را وارد کنید');
                return;
            }

            if (!address) {
                alert('لطفا آدرس برداشت را وارد کنید');
                return;
            }

            // Set modal values
            document.getElementById('modalCurrencySymbol').textContent = currencySymbol;
            document.getElementById('modalChain').textContent = chainText;
            document.getElementById('modalAmount').textContent = amount;
            document.getElementById('modalAddress').textContent = address;

            // Show modal
            modal.show();
        });

        submitBtn.addEventListener('click', function() {
            form.submit();
        });
    });
</script>
@endpush
