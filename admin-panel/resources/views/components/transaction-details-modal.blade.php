@props(['transaction'])

<div class="modal fade" id="transaction-{{ $transaction->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" dir="ltr">
                <h5 class="modal-title font-number">Transaction #{{ $transaction->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">مشخصات کاربر</h6>
                    <div class="d-flex gap-4 align-items-center">
                        <div class="d-flex flex-column">
                            <small>{{ $transaction->user->fullname() }}</small>
                            <small>{{ $transaction->user->username }}</small>
                            <a href="" class="text-heading text-truncate">
                                <span class="fw-medium">{{ $transaction->user->email }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش تراکنش</h6>
                    <div class="d-flex gap-4 align-items-center">
                        <span class="font-number text-info">
                            {{ formatNumberTrimZeros(abs($transaction->coin_price * $transaction->amount)) }}
                            USDT
                        </span>
                        <img src="{{ asset('images/coins/usdt.svg') }}" width="30" alt="USDT" />
                    </div>
                </div>

                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">قیمت کوین هنگام تراکنش</h6>
                    <div class="d-flex gap-4 align-items-center">
                        <span class="font-number text-info">
                            {{ formatNumberTrimZeros($transaction->coin_price) }} USDT
                        </span>
                        <img src="{{ asset('images/coins/usdt.svg') }}" width="30" alt="USDT" />
                    </div>
                </div>

                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر قبل از تراکنش</h6>
                    <div class="d-flex gap-4 align-items-center">
                        <span class="font-number">{{ formatNumberTrimZeros($transaction->balance) }}</span>
                        <img src="{{ asset($transaction->wallet->currency->coinLogo()) }}" width="30" />
                    </div>
                </div>

                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر پس از تراکنش</h6>
                    <div class="d-flex gap-4 align-items-center">
                        <span class="font-number text-primary"
                            dir="ltr">{{ formatNumberTrimZeros($transaction->balance + $transaction->amount) }}</span>
                        <img src="{{ asset($transaction->wallet->currency->coinLogo()) }}" width="30" />
                    </div>
                </div>

                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">ایجاد شده توسط ادمین</h6>
                    <div class="text-wrap font-number">
                        @if ($transaction->admin_id)
                            {{ $transaction->admin->fullname() }} - #{{ $transaction->admin->id }}
                        @else
                            <span class="badge bg-label-danger">خیر</span>
                        @endif
                    </div>
                </div>

                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-6">توضیحات تراکنش</h6>
                    <div class="text-wrap font-number w-60 text-start">
                        {{ $transaction->description }}
                    </div>
                </div>

                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات ادمین</h6>
                    <div class="text-wrap font-number">
                        {{ $transaction->admin_description }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>
