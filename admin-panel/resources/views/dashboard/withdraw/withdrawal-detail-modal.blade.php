<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">مقدار درخواستی برداشت</h6>
    <div class="d-flex gap-4 align-items-center">
        <small> {{ $withdraw->currency_symbol }}</small>
        <span class="font-number">{{ formatNumberTrimZeros($withdraw->amount) }}</span>
    </div>
</div>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">مقدار دریافتی کاربر</h6>
    <div class="d-flex gap-4 align-items-center">
        <small> {{ $withdraw->currency_symbol }}</small>
        <span class="font-number">{{ formatNumberTrimZeros($withdraw->amount - $withdraw->total_fee) }}</span>
    </div>
</div>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد صرافی</h6>
    <div class="d-flex gap-4 align-items-center">
        <small> {{ $withdraw->currency_symbol }}</small>
        <span class="font-number">{{ formatNumberTrimZeros($withdraw->exchange_fee) }}</span>
    </div>
</div>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد صرافی مرجع</h6>
    <div class="d-flex gap-4 align-items-center">
        <small> {{ $withdraw->currency_symbol }}</small>
        <span class="font-number">{{ formatNumberTrimZeros($withdraw->network_fee) }}</span>
    </div>
</div>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد برداشت (صرافی مرجع + بیتکس روم)</h6>
    <div class="d-flex gap-4 align-items-center">
        <small> {{ $withdraw->currency_symbol }}</small>
        <span class="font-number">{{ formatNumberTrimZeros($withdraw->total_fee) }}</span>
    </div>
</div>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد واقعی شبکه</h6>
    <div class="d-flex gap-4 align-items-center">
        <small> {{ $withdraw->currency_symbol }}</small>
        <span class="font-number">{{ formatNumberTrimZeros($withdraw->hd_wallet_network_fee) }}</span>
    </div>
</div>
@if ($withdraw->status === \App\Enums\WithdrawalStatusEnum::COMPLETED)
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر پس از برداشت</h6>
        <div class="d-flex gap-4 align-items-center">
            <small> {{ $withdraw->currency_symbol }}</small>
            @if ($withdraw->transaction)
                <span
                    class="font-number text-primary">{{ formatNumberTrimZeros($withdraw->transaction->balance) }}</span>
            @else
                <span class="font-number text-warning">تراکنش یافت نشد</span>
                <!-- Debug info: Transaction is null for withdraw ID: {{ $withdraw->id }} -->
            @endif
        </div>
    </div>
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">زمان تایید</h6>
        <div class="text-wrap font-number">
            {{ \App\Helpers\DateFormatter::convertToPersianDate($withdraw->confirmed_at, 'H:i:s %Y/%m/%d') }}
        </div>
    </div>

@endif

@if (
    $withdraw->status === \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL ||
        $withdraw->status === \App\Enums\WithdrawalStatusEnum::PENDING)
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کلی کاربر</h6>
        <div class="text-wrap font-number">
            {{ $withdraw->wallet->balance }}
        </div>
    </div>
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی در دسترس کاربر</h6>
        <div class="text-wrap font-number">
            {{ $withdraw->wallet->balance - $withdraw->wallet->locked_balance }}
        </div>
    </div>
@endif

@if ($withdraw->status === \App\Enums\WithdrawalStatusEnum::QUEUED)
    @if ($withdraw->job_failed_at)
        <div class="alert alert-danger mb-4">
            <div class="d-flex align-items-center mb-2">
                <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                <h6 class="mb-0 text-danger">خطا در ارسال برداشت به HD Wallet</h6>
            </div>
            <div class="bg-white rounded p-2 mb-2" dir="ltr">
                <small class="font-number text-dark">{{ $withdraw->description }}</small>
            </div>
            <small class="text-muted">
                <i class="fa-regular fa-clock me-1"></i>
                {{ $withdraw->job_failed_at->diffForHumans() }}
                ({{ \App\Helpers\DateFormatter::convertToPersianDate($withdraw->job_failed_at, 'H:i:s %Y/%m/%d') }})
            </small>
        </div>
    @endif

    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">ارسال مجدد به صف برداشت</h6>
        <div class="d-flex gap-2 align-items-center">
            <form action="{{ route('admin.withdrawal.redispatch-job', $withdraw) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید جاب برداشت را مجدداً در صف قرار دهید؟')">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="fa-solid fa-rotate-right me-1"></i>
                    ارسال مجدد جاب برداشت
                </button>
            </form>
            <button type="button" class="btn btn-danger btn-sm"
                    onclick="document.getElementById('cancelConfirmPanel-{{ $withdraw->id }}').classList.remove('d-none')">
                <i class="fa-solid fa-ban me-1"></i>
                لغو برداشت
            </button>
        </div>
    </div>

    {{-- Inline Cancel Confirmation Panel --}}
    <div id="cancelConfirmPanel-{{ $withdraw->id }}" class="d-none border border-danger rounded p-3 mb-4 bg-danger bg-opacity-10">
        <h6 class="text-danger mb-2">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            تأیید لغو برداشت و بازگشت وجه
        </h6>
        <p class="text-muted small mb-3">با لغو این برداشت، مبلغ
            <strong class="text-dark font-number">{{ formatNumberTrimZeros($withdraw->amount) }} {{ $withdraw->currency_symbol }}</strong>
            به کیف پول کاربر بازگردانده می‌شود.
        </p>
        <form action="{{ route('admin.withdrawal.cancel-queued', $withdraw) }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="cancel_reason_{{ $withdraw->id }}" class="form-label fw-medium small">
                    دلیل لغو <span class="text-danger">*</span>
                </label>
                <textarea class="form-control form-control-sm" id="cancel_reason_{{ $withdraw->id }}"
                          name="cancel_reason" rows="2" required
                          placeholder="دلیل لغو برداشت را بنویسید..."></textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="document.getElementById('cancelConfirmPanel-{{ $withdraw->id }}').classList.add('d-none')">
                    <i class="fa-regular fa-xmark me-1"></i>انصراف
                </button>
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fa-regular fa-ban me-1"></i>لغو و بازگشت وجه
                </button>
            </div>
        </form>
    </div>
@endif

@if ($withdraw->admin_id)
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6
            class="m-0 mb-2 mb-md-0 me-12 {{ in_array($withdraw->status, [\App\Enums\WithdrawalStatusEnum::REJECTED, \App\Enums\WithdrawalStatusEnum::FAILED]) ? 'text-danger' : 'text-success' }}">
            {{ in_array($withdraw->status, [\App\Enums\WithdrawalStatusEnum::REJECTED, \App\Enums\WithdrawalStatusEnum::FAILED]) ? 'عدم تایید توسط ادمین' : 'تایید توسط ادمین' }}
        </h6>
        <div class="text-wrap font-number">
            @if ($withdraw->admin)
                {{ $withdraw->admin->fullname() }} - {{ $withdraw->admin->id }}#
            @else
                #{{ $withdraw->admin_id }}
            @endif
        </div>
    </div>
@endif

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات برداشت</h6>
    <div class="text-wrap font-number w-60 text-end">
        {{ $withdraw->description }}
    </div>
</div>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات کاربر (Remark)</h6>
    <div class="text-wrap font-number w-60 text-end">
        {{ $withdraw->remark }}
    </div>
</div>

@if ($withdraw->transaction)
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات تراکنش</h6>
        <div class="text-wrap font-number w-60 text-end">
            {{ $withdraw->transaction->description }}
        </div>
    </div>
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات ادمین</h6>
        <div class="text-wrap font-number">
            {{ $withdraw->transaction->admin_description }}
        </div>
    </div>
@else
    <!-- Debug info: No transaction found for withdraw ID: {{ $withdraw->id }} -->
@endif
