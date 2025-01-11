<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">مقدار دریافتی کاربر</h6>
    <div class="d-flex gap-4 align-items-center">
        <small> {{$withdraw->currency_symbol}}</small>
        <span class="font-number">{{ formatNumberTrimZeros($withdraw->amount - $withdraw->fee) }}</span>
    </div>
</div>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد برداشت</h6>
    <div class="d-flex gap-4 align-items-center">
        <small> {{$withdraw->currency_symbol}}</small>
        <span class="font-number">{{ formatNumberTrimZeros($withdraw->fee) }}</span>
    </div>
</div>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد صرافی</h6>
    <div class="d-flex gap-4 align-items-center">
        <small> {{$withdraw->currency_symbol}}</small>
        <span class="font-number">{{ formatNumberTrimZeros($withdraw->exchange_fee) }}</span>
    </div>
</div>

@if($withdraw->status === \App\Enums\WithdrawalStatusEnum::COMPLETED)
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر پس از برداشت</h6>
        <div class="d-flex gap-4 align-items-center">
            <small> {{$withdraw->currency_symbol}}</small>
            <span class="font-number text-primary">{{ formatNumberTrimZeros($withdraw->transaction->balance) }}</span>
        </div>
    </div>
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">زمان تایید</h6>
        <div class="text-wrap font-number">
            {{ \App\Helpers\DateFormatter::convertToPersianDate($withdraw->confirmed_at,'H:i:s %Y/%m/%d') }}
        </div>
    </div>

@endif

@if($withdraw->status === \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL ||
    $withdraw->status === \App\Enums\WithdrawalStatusEnum::PENDING)
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کلی کاربر</h6>
        <div class="text-wrap font-number">
            {{$withdraw->wallet->balance}}
        </div>
    </div>
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی در دسترس کاربر</h6>
        <div class="text-wrap font-number">
            {{$withdraw->wallet->balance - $withdraw->wallet->locked_balance}}
        </div>
    </div>
@endif

@if($withdraw->status === \App\Enums\WithdrawalStatusEnum::FAILED)
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12 text-danger">عدم تایید توسط ادمین</h6>
        <div class="text-wrap font-number">
            @if($withdraw->admin_id)
                {{$withdraw->admin->fullname()}} - #{{$withdraw->admin->id}}
            @else
                <span class="badge bg-label-danger">خیر</span>
            @endif
        </div>
    </div>
@endif

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات برداشت</h6>
    <div class="text-wrap font-number w-60">
        {{$withdraw->description}}
    </div>
</div>

@if($withdraw->transaction)
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات تراکنش</h6>
        <div class="text-wrap font-number w-60">
            {{$withdraw->transaction->description}}
        </div>
    </div>
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
        <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات ادمین</h6>
        <div class="text-wrap font-number">
            {{$withdraw->transaction->admin_description}}
        </div>
    </div>
@endif
