<div class="modal fade" id="deposit-{{ $deposit->id }}" tabindex="-1" aria-model="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" dir="ltr">
                <h5 class="modal-title font-number">Deposit #{{ $deposit->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">شناسه تراکنش</h6>
                    <div class="d-flex flex-wrap gap-4 font-number">
                        Transaction #{{ $deposit->transaction->id }}
                    </div>
                </div>
                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر قبل از واریز</h6>
                    <div class="d-flex gap-2 align-items-end">
                        <small>{{ $deposit->currency_symbol }}</small>
                        <span class="font-number">{{ formatNumberTrimZeros($deposit->transaction->balance) }}</span>
                    </div>
                </div>
                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر پس از واریز</h6>
                    <div class="d-flex gap-2 align-items-end">
                        <small>{{ $deposit->currency_symbol }}</small>
                        <span
                            class="font-number text-success">{{ formatNumberTrimZeros($deposit->transaction->balance + $deposit->transaction->amount) }}</span>
                    </div>
                </div>
                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات واریز</h6>
                    <div class="text-wrap font-number w-60 text-end">
                        {{ $deposit->description }}
                    </div>
                </div>
                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات تراکنش</h6>
                    <div class="text-wrap font-number w-60 text-end">
                        {{ $deposit->transaction->description }}
                    </div>
                </div>
                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات ادمین</h6>
                    <div class="text-wrap font-number w-60 text-end">
                        {{ $deposit->transaction->admin_description }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>
