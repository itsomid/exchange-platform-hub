@extends('dashboard.exchange.currency.layout.master')
@section('title', 'ویرایش شبکه‌ها')
@section('currency-body')

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <h5 class="mb-0">
                    <i class="fa-light fa-link me-2"></i>
                    شبکه‌های {{ $currency->name }}
                </h5>
                @if (!count($currency->chains))
                    <span class="badge bg-danger">بدون شبکه</span>
                @else
                    <span class="badge bg-label-primary">{{ count($currency->chains) }} شبکه</span>
                @endif
            </div>

            <div class="card-body">
                @if (count($currency->chains))
                    <form action="{{ route('admin.currency.chains.update', ['currency' => $currency]) }}" method="post">
                        @method('PATCH')
                        @csrf

                        @foreach ($currency->chains as $index => $chain)
                            <div class="card border shadow-none mb-4">
                                {{-- Chain Header --}}
                                <div
                                    class="card-header bg-label-primary d-flex align-items-center justify-content-between py-2">
                                    <h6 class="mb-0">
                                        <i class="fa-light fa-cube me-2"></i>
                                        شبکه {{ $chain->chain }}
                                    </h6>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-{{ $chain->deposit_enabled ? 'success' : 'danger' }}">
                                            <i class="fa-solid fa-arrow-down fa-xs me-1"></i>واریز
                                        </span>
                                        <span class="badge bg-{{ $chain->withdraw_enabled ? 'success' : 'danger' }}">
                                            <i class="fa-solid fa-arrow-up fa-xs me-1"></i>برداشت
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body pt-4">
                                    {{-- Basic Info --}}
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-3">
                                            <label class="form-label" for="chain_{{ $chain->id }}">نام شبکه</label>
                                            <input name="chain" id="chain_{{ $chain->id }}" class="form-control"
                                                value="{{ $chain->chain }}" disabled>
                                        </div>
                                        <div class="col-lg-6">
                                            <div dir="ltr">
                                                <label class="form-label" for="contract_address_{{ $chain->id }}">Contract
                                                    Address</label>
                                                <input name="chains[{{ $chain->id }}][contract_address]"
                                                    id="contract_address_{{ $chain->id }}" class="form-control font-number"
                                                    placeholder="آدرس قرارداد هوشمند را وارد کنید."
                                                    value="{{ $chain->contract_address }}">
                                                @error('contract_address')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Explorer & Memo --}}
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-4">
                                            <div dir="ltr">
                                                <label class="form-label" for="explorer_address_url_{{ $chain->id }}">آدرس
                                                    اکسپلورر</label>
                                                <input name="chains[{{ $chain->id }}][explorer_address_url]"
                                                    id="explorer_address_url_{{ $chain->id }}" class="form-control"
                                                    placeholder="آدرس اکسپلورر را وارد کنید."
                                                    value="{{ $chain->explorer_address_url }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div dir="ltr">
                                                <label class="form-label" for="explorer_tx_url_{{ $chain->id }}">آدرس اکسپلورر
                                                    تراکنش</label>
                                                <input name="chains[{{ $chain->id }}][explorer_tx_url]"
                                                    id="explorer_tx_url_{{ $chain->id }}" class="form-control"
                                                    placeholder="آدرس اکسپلورر را وارد کنید."
                                                    value="{{ $chain->explorer_tx_url }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label" for="memo_{{ $chain->id }}">MEMO (اختیاری)</label>
                                            <input name="chains[{{ $chain->id }}][memo]" id="memo_{{ $chain->id }}"
                                                class="form-control" placeholder="ممو را وارد کنید." value="{{ $chain->memo }}">
                                        </div>
                                    </div>

                                    {{-- Deposit & Withdraw Limits --}}
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-4">
                                            <label class="form-label" for="min_deposit_amount_{{ $chain->id }}">حداقل مقدار
                                                واریز</label>
                                            <input name="chains[{{ $chain->id }}][min_deposit_amount]"
                                                id="min_deposit_amount_{{ $chain->id }}" class="form-control font-number"
                                                placeholder="حداقل مقدار واریز"
                                                value="{{ formatNumberTrimZeros($chain->min_deposit_amount) }}" required>
                                            @error('min_deposit_amount')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label" for="min_withdraw_amount_{{ $chain->id }}">حداقل مقدار
                                                برداشت</label>
                                            <input name="chains[{{ $chain->id }}][min_withdraw_amount]"
                                                id="min_withdraw_amount_{{ $chain->id }}" class="form-control font-number"
                                                placeholder="حداقل مقدار برداشت"
                                                value="{{ formatNumberTrimZeros($chain->min_withdraw_amount) }}" required>
                                            @error('min_withdraw_amount')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Confirmations & Delay --}}
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-4">
                                            <label class="form-label" for="deposit_delay_minutes_{{ $chain->id }}">تاخیر در
                                                واریز (دقیقه)</label>
                                            <input name="chains[{{ $chain->id }}][deposit_delay_minutes]"
                                                id="deposit_delay_minutes_{{ $chain->id }}" class="form-control"
                                                placeholder="زمان را وارد کنید." value="{{ $chain->deposit_delay_minutes }}"
                                                required>
                                            <div class="form-text">تاخیر زمانی پس از شناسایی واریز برای بررسی‌های امنیتی و تأیید
                                                تاییدیه‌های بلاک‌چین.</div>
                                            @error('deposit_delay_minutes')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label" for="safe_confirmations_{{ $chain->id }}">حداقل تعداد
                                                تایید شبکه</label>
                                            <input name="chains[{{ $chain->id }}][safe_confirmations]"
                                                id="safe_confirmations_{{ $chain->id }}" class="form-control"
                                                placeholder="تعداد تایید" value="{{ $chain->safe_confirmations }}" required>
                                            @error('safe_confirmations')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label" for="withdrawal_precision_{{ $chain->id }}">دقت اعشار
                                                برداشت</label>
                                            <input type="number" name="chains[{{ $chain->id }}][withdrawal_precision]"
                                                id="withdrawal_precision_{{ $chain->id }}" class="form-control"
                                                placeholder="مقدار اعشار" value="{{ $chain->withdrawal_precision }}" required>
                                            @error('precision')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Fees --}}
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-4">
                                            <label class="form-label" for="exchange_withdrawal_fee_{{ $chain->id }}">فی صرافی
                                                برای برداشت</label>
                                            <div class="input-group">
                                                <input type="text" name="chains[{{ $chain->id }}][exchange_withdrawal_fee]"
                                                    id="exchange_withdrawal_fee_{{ $chain->id }}"
                                                    class="form-control font-number" placeholder="فی صرافی"
                                                    value="{{ formatNumberTrimZeros($chain->exchange_withdrawal_fee) }}"
                                                    required>
                                                <span class="input-group-text">{{ $currency->symbol }}</span>
                                            </div>
                                            @error('exchange_withdrawal_fee')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label" for="network_fee_{{ $chain->id }}">
                                                فی صرافی مرجع
                                                <small class="text-muted">(از صرافی مرجع)</small>
                                            </label>
                                            <div class="input-group">
                                                <input name="chains[{{ $chain->id }}][network_fee]"
                                                    id="network_fee_{{ $chain->id }}" class="form-control font-number"
                                                    value="{{ formatNumberTrimZeros($chain->network_fee) }}"
                                                    disabled required>
                                                <span class="input-group-text">{{ $currency->symbol }}</span>
                                            </div>
                                            @error('network_fee')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label">مجموع فی برداشت کاربر</label>
                                            <div class="alert alert-info p-2 mb-0">
                                                <strong id="total_withdrawal_fee_{{ $chain->id }}" class="font-number">
                                                    {{ formatNumberTrimZeros($chain->network_fee + $chain->exchange_withdrawal_fee) }}
                                                    <small class="ms-1">{{ $currency->symbol }}</small>
                                                </strong>
                                                <small class="d-block text-muted mt-1">فی مرجع + فی صرافی = فی کل</small>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Toggle Switches --}}
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 d-flex align-items-center justify-content-between">
                                                <div>
                                                    <i class="fa-solid fa-arrow-down text-success me-2"></i>
                                                    <span class="fw-semibold">وضعیت واریز</span>
                                                </div>
                                                <div class="me-8">
                                                    <label class="switch switch-lg mb-0">
                                                        <input type="checkbox" class="switch-input"
                                                            name="chains[{{ $chain->id }}][deposit_enabled]" value="1" {{ $chain->deposit_enabled ? 'checked' : '' }} />
                                                        <span class="switch-toggle-slider"></span>
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 d-flex align-items-center justify-content-between">
                                                <div>
                                                    <i class="fa-solid fa-arrow-up text-primary me-2"></i>
                                                    <span class="fw-semibold">وضعیت برداشت</span>
                                                </div>
                                                <div class="me-8">
                                                    <label class="switch switch-lg mb-0">
                                                        <input type="checkbox" class="switch-input"
                                                            name="chains[{{ $chain->id }}][withdraw_enabled]" value="1" {{ $chain->withdraw_enabled ? 'checked' : '' }} />
                                                        <span class="switch-toggle-slider"></span>
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="d-flex justify-content-start">
                            <button class="btn btn-primary">
                                <i class="fa fa-save me-2"></i>
                                ذخیره تغییرات
                            </button>
                        </div>
                    </form>
                @else
                <div class="alert alert-danger" role="alert">
                    <i class="fa-light fa-triangle-exclamation me-2"></i>
                    ساخت شبکه برای تخصیص کوین به بازار الزامی است.
                </div>
                @endempty

            </div>
        </div>
    </div>
</div>

@endsection