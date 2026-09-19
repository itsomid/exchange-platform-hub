@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')
    @if(session('operation_errors'))
        <div class="alert alert-{{ session('operation_success_count', 0) > 0 ? 'warning' : 'danger' }} alert-dismissible fade show mb-4" role="alert">
            <div class="fw-semibold mb-2">جزئیات خطای عملیات</div>
            @if(session('operation_success_count', 0) > 0)
                <div class="mb-2 small">{{ session('operation_success_count') }} ارز با موفقیت پردازش شد، اما خطاهای زیر رخ داد:</div>
            @endif
            <ul class="mb-0 ps-3">
                @foreach(session('operation_errors', []) as $operationError)
                    <li>{{ $operationError }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد کل در خواست های برداشت</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$withdrawals->count()}}</h4>
                            </div>
                            <span>برداشت های در انتظار تکمیل</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$pendingWithdrawals->count()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center pb-2">
                    <div class="card-title mb-0">
                        <h5 class="mb-0">مجموع برداشت های در انتظار تکمیل</h5>
                        <small class="text-muted">{{$pendingWithdrawals->count()}} کوین</small>
                    </div>
                    {{-- دکمه تنظیمات برداشت ارزها --}}
                    <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#withdrawalSettingsModal">
                        <i class="fas fa-cog me-1"></i>تنظیمات برداشت ارزها
                    </button>
                </div>
                <div class="card-body pt-2">
                    @if($pendingWithdrawals->count() > 0)
                        <div class="row g-3" style="max-height: 220px; overflow-y: auto;">
                            @foreach($pendingWithdrawals as $withdraw)
                                @php
                                    $timerData = $currencyTimerData[$withdraw->currency->id] ?? null;
                                    $isReady = $timerData['is_ready'] ?? false;
                                    $isDisabled = !($timerData['enabled'] ?? true);
                                    $remainingSeconds = $timerData['remaining_seconds'] ?? 0;
                                    $pendingCount = $timerData['pending_count'] ?? 0;
                                    $minCount = $timerData['min_count'] ?? 1;
                                @endphp
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="currency-timer-card p-2 rounded border {{ $isDisabled ? 'border-danger bg-danger bg-opacity-10' : ($isReady ? 'border-success bg-success bg-opacity-10' : '') }}"
                                         data-currency-id="{{ $withdraw->currency->id }}"
                                         data-remaining="{{ $remainingSeconds }}"
                                         data-ready="{{ $isReady ? '1' : '0' }}"
                                         data-disabled="{{ $isDisabled ? '1' : '0' }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{asset($withdraw->currency->coinLogo())}}" class="rounded-circle" width="32" height="32">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="fw-semibold text-truncate" style="font-size: 0.85rem;">
                                                    {{$withdraw->currency->symbol}}
                                                    @if($isDisabled)
                                                        <i class="fas fa-ban text-danger ms-1" title="برداشت غیرفعال"></i>
                                                    @elseif($isReady)
                                                        <i class="fas fa-check-circle text-success ms-1" title="آماده تجمیع"></i>
                                                    @endif
                                                </div>
                                                <div class="font-number text-muted" style="font-size: 0.75rem;">{{formatNumberTrimZeros($withdraw->total_withdraw_amount)}}</div>
                                            </div>
                                        </div>
                                        {{-- Timer Row --}}
                                        <div class="mt-2 pt-2 border-top" style="font-size: 0.7rem;">
                                            @if($isDisabled)
                                                <div class="text-danger text-center">
                                                    <i class="fas fa-times-circle me-1"></i>غیرفعال
                                                </div>
                                            @elseif($isReady)
                                                <div class="text-success text-center fw-semibold">
                                                    <i class="fas fa-play-circle me-1"></i>آماده تجمیع
                                                </div>
                                            @else
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted"><i class="fas fa-clock me-1"></i>زمان:</span>
                                                    <span class="font-number fw-semibold timer-countdown" data-currency-id="{{ $withdraw->currency->id }}">--:--</span>
                                                </div>
                                            @endif
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span class="text-muted"><i class="fas fa-shopping-cart me-1"></i>خرید:</span>
                                                <span class="font-number {{ $pendingCount >= $minCount ? 'text-success fw-semibold' : '' }}">{{ $pendingCount }}/{{ $minCount }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-muted mb-0">برداشتی ثبت نشده است</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- کارت عملیات تجمیع --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>عملیات تجمیع دارایی‌ها</h5>
                <small class="text-muted">انتخاب ارزها و تعیین مقدار/درصد برای تجمیع</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllCurrencies">
                    <i class="fas fa-check-double me-1"></i>انتخاب همه
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAllCurrencies">
                    <i class="fas fa-times me-1"></i>لغو انتخاب همه
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($pendingWithdrawals->count() > 0)
                <form id="aggregationForm" action="{{ route('admin.ref-exchange.assets-gathering-to-hd-wallet.bulk-aggregate') }}" method="POST">
                    @csrf
                    
                    {{-- انتخاب صرافی مرجع --}}
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">صرافی مرجع:</label>
                            <select id="aggregation_exchange" name="exchange_slug" class="form-select">
                                @foreach($exchanges ?? [] as $exchange)
                                    <option value="{{ $exchange->slug }}">{{ $exchange->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- لیست ارزها --}}
                    <div class="table-responsive">
                        <table class="table table-hover" id="aggregationTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAllHeader">
                                        </div>
                                    </th>
                                    <th>ارز</th>
                                    <th>مقدار کل در انتظار</th>
                                    <th style="width: 160px;">نوع مقدار</th>
                                    <th style="width: 300px;">مقدار تجمیع</th>
                                    <th>مقدار نهایی</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingWithdrawals as $index => $withdraw)
                                    <tr class="aggregation-row" data-currency="{{ $withdraw->currency->symbol }}" data-total="{{ $withdraw->total_withdraw_amount }}" data-default-aggregation-percent="{{ $withdraw->currency->ref_exchange_withdrawal_aggregation_percent }}">
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input currency-checkbox" 
                                                       type="checkbox" 
                                                       name="currencies[{{ $index }}][selected]" 
                                                       value="1"
                                                       data-index="{{ $index }}">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ asset($withdraw->currency->coinLogo()) }}" 
                                                     class="rounded-circle" width="36" height="36">
                                                <div>
                                                    <div class="fw-semibold">{{ $withdraw->currency->symbol }}</div>
                                                    <small class="text-muted">{{ $withdraw->currency->name }}</small>
                                                </div>
                                            </div>
                                            <input type="hidden" name="currencies[{{ $index }}][currency_id]" value="{{ $withdraw->currency_id }}">
                                            <input type="hidden" name="currencies[{{ $index }}][symbol]" value="{{ $withdraw->currency->symbol }}">
                                        </td>
                                        <td>
                                            <span class="font-number fw-semibold text-primary total-amount">
                                                {{ formatNumberTrimZeros($withdraw->total_withdraw_amount) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check amount-type" 
                                                       name="currencies[{{ $index }}][type]" 
                                                       id="amount_type_{{ $index }}" 
                                                       value="amount" {{ $withdraw->currency->ref_exchange_withdrawal_aggregation_percent ? '' : 'checked' }}
                                                       data-index="{{ $index }}">
                                                <label class="btn btn-outline-primary btn-sm" for="amount_type_{{ $index }}">
                                                    <i class="fas fa-coins me-1"></i>تعداد
                                                </label>
                                                
                                                <input type="radio" class="btn-check amount-type" 
                                                       name="currencies[{{ $index }}][type]" 
                                                       id="percent_type_{{ $index }}" 
                                                       value="percent" {{ $withdraw->currency->ref_exchange_withdrawal_aggregation_percent ? 'checked' : '' }}
                                                       data-index="{{ $index }}">
                                                <label class="btn btn-outline-success btn-sm" for="percent_type_{{ $index }}">
                                                    <i class="fas fa-percentage me-1"></i>درصد
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       class="form-control font-number amount-input" 
                                                       name="currencies[{{ $index }}][value]" 
                                                       placeholder="مقدار"
                                                       step="any"
                                                       min="0"
                                                       data-index="{{ $index }}"
                                                       data-total="{{ $withdraw->total_withdraw_amount }}">
                                                <button type="button" class="btn btn-outline-secondary max-btn" 
                                                        data-index="{{ $index }}"
                                                        data-total="{{ $withdraw->total_withdraw_amount }}"
                                                        title="حداکثر مقدار">
                                                    <i class="fas fa-arrow-up"></i>
                                                </button>
                                                <span class="input-group-text unit-label" data-index="{{ $index }}">
                                                    {{ $withdraw->currency->symbol }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="font-number fw-semibold text-success calculated-amount" data-index="{{ $index }}">
                                                --
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- خلاصه و دکمه ارسال --}}
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="alert alert-info d-flex align-items-center mb-0">
                                <i class="fas fa-info-circle me-2 fs-5"></i>
                                <div>
                                    <strong>راهنما:</strong>
                                    برای هر ارز می‌توانید نوع مقدار را بین <strong>تعداد</strong> یا <strong>درصد</strong> انتخاب کنید.
                                    در حالت درصد، مقدار نهایی بر اساس درصد وارد شده از کل مقدار در انتظار محاسبه می‌شود.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2 justify-content-end h-100 align-items-center">
                                <span class="badge bg-label-primary fs-6" id="selectedCount">0 ارز انتخاب شده</span>
                                <button type="submit" class="btn btn-primary" id="submitAggregation" disabled>
                                    <i class="fas fa-play me-1"></i>شروع عملیات تجمیع
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">در حال حاضر ارزی برای تجمیع وجود ندارد</p>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست درخواست های تجمیع در انتظار تکمیل</h5>

            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>کوین</th>
                        <th>تراکنش</th>
                        <th>مقدار</th>
                        <th>تاریخ شروع</th>
                        <th>تاریخ تکمیل</th>
                        <th>وضعیت</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($withdrawals as $withdraw)

                        <tr>
                            <td>{{$withdraw->id}}</td>
                            <td class="text-heading fw-medium">
                                <img src="{{asset($withdraw->currency->coinLogo())}}"
                                     class="rounded-circle img-fluid" width="30">
                                {{$withdraw->currency->name}}
                            </td>
                            <td class="font-number">Transaction #{{$withdraw->transaction->id}}</td>
                            <td class="font-number">{{$withdraw->transaction->amount}}</td>
                            <td class="font-number">
                                {{\App\Helpers\DateFormatter::convertToPersianDate($withdraw->created_at,'H:i:s %Y/%m/%d')}}
                            </td>
                            <td class="font-number">
                                @if($withdraw->status === \App\Enums\OTCRefExchangeWithdrawalStatusEnum::COMPLETED || $withdraw->status === \App\Enums\OTCRefExchangeWithdrawalStatusEnum::CANCELLED)
                                    {{\App\Helpers\DateFormatter::convertToPersianDate($withdraw->updated_at,'H:i:s %Y/%m/%d')}}
                                @else
                                    در انتظار تکمیل
                                @endif

                            </td>
                            <td>
                                <span
                                    class="badge bg-label-{{$withdraw->status->color()}} align-self-baseline">{{$withdraw->status->label()}}</span>
                            </td>

                            {{--                            <td>--}}
                            {{--                                {{$withdraw->description}}--}}
                            {{--                            </td>--}}

                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal تنظیمات برداشت ارزها --}}
    <div class="modal fade" id="withdrawalSettingsModal" tabindex="-1" aria-labelledby="withdrawalSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawalSettingsModalLabel">
                        <i class="fas fa-cog me-2"></i>تنظیمات برداشت از صرافی مرجع
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- فیلتر و جستجو --}}
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="currencySearch" placeholder="جستجوی ارز...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="statusFilter">
                                <option value="all">همه ارزها</option>
                                <option value="enabled">فعال</option>
                                <option value="disabled">غیرفعال</option>
                            </select>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-outline-success btn-sm" id="enableAllWithdrawals">
                                <i class="fas fa-check me-1"></i>فعال‌سازی همه
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="disableAllWithdrawals">
                                <i class="fas fa-times me-1"></i>غیرفعال‌سازی همه
                            </button>
                        </div>
                    </div>

                    {{-- جدول تنظیمات --}}
                    <form id="withdrawalSettingsForm" action="{{ route('admin.ref-exchange.currency.withdrawal-settings.bulk-update') }}" method="POST">
                        @csrf
                        <div class="table-responsive" style="max-height: 500px;">
                            <table class="table table-hover table-sm" id="withdrawalSettingsTable">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th>ارز</th>
                                        <th style="width: 120px;">وضعیت برداشت</th>
                                        <th style="width: 180px;">بازه زمانی (دقیقه)</th>
                                        <th style="width: 180px;">حداقل تعداد خرید</th>
                                        <th style="width: 180px;">درصد تجمیع پیش‌فرض</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allCurrencies as $index => $currency)
                                        <tr class="currency-settings-row" 
                                            data-symbol="{{ strtolower($currency->symbol) }}" 
                                            data-name="{{ strtolower($currency->name) }}"
                                            data-enabled="{{ $currency->ref_exchange_withdrawal_enabled ? '1' : '0' }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="{{ asset($currency->coinLogo()) }}" class="rounded-circle" width="28" height="28">
                                                    <div>
                                                        <span class="fw-semibold">{{ $currency->symbol }}</span>
                                                        <small class="text-muted d-block">{{ $currency->name }}</small>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="currencies[{{ $index }}][id]" value="{{ $currency->id }}">
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input withdrawal-enabled-toggle" 
                                                           type="checkbox" 
                                                           role="switch"
                                                           name="currencies[{{ $index }}][ref_exchange_withdrawal_enabled]"
                                                           value="1"
                                                           data-currency-id="{{ $currency->id }}"
                                                           {{ $currency->ref_exchange_withdrawal_enabled ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="form-control form-control-sm font-number" 
                                                       name="currencies[{{ $index }}][ref_exchange_withdrawal_interval_minutes]"
                                                       value="{{ $currency->ref_exchange_withdrawal_interval_minutes }}"
                                                       min="1"
                                                       placeholder="پیش‌فرض ({{ $globalWithdrawalInterval }})">
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="form-control form-control-sm font-number" 
                                                       name="currencies[{{ $index }}][ref_exchange_withdrawal_min_count]"
                                                       value="{{ $currency->ref_exchange_withdrawal_min_count }}"
                                                       min="1"
                                                       placeholder="پیش‌فرض ({{ $globalWithdrawalMinCount }})">
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           class="form-control form-control-sm font-number" 
                                                           name="currencies[{{ $index }}][ref_exchange_withdrawal_aggregation_percent]"
                                                           value="{{ $currency->ref_exchange_withdrawal_aggregation_percent }}"
                                                           min="1"
                                                           max="100"
                                                           placeholder="خالی = 100%">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- راهنما --}}
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>راهنما:</strong> اگر مقادیر بازه زمانی یا حداقل تعداد خرید خالی باشد، از تنظیمات کلی سیستم استفاده می‌شود.
                            <br>
                            <strong>درصد تجمیع:</strong> اگر مقدار درصد تجمیع ست شود، هنگام تجمیع به صورت پیش‌فرض آن درصد از مقدار کل برداشت می‌شود. اگر خالی باشد، تمام مقدار تجمیع می‌شود.
                            <br>
                            <small class="text-muted">تنظیمات کلی فعلی: بازه زمانی = <strong>{{ $globalWithdrawalInterval }} دقیقه</strong> | حداقل تعداد خرید = <strong>{{ $globalWithdrawalMinCount }}</strong></small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="d-flex justify-content-between w-100">
                        <div>
                            <span class="text-muted" id="settingsCountInfo">{{ $allCurrencies->count() }} ارز</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                            <button type="submit" form="withdrawalSettingsForm" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>ذخیره تغییرات
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== Countdown Timers =====
    const timerCards = document.querySelectorAll('.currency-timer-card');
    const timerData = {};
    
    // Initialize timer data
    timerCards.forEach(card => {
        const currencyId = card.dataset.currencyId;
        const remaining = parseInt(card.dataset.remaining) || 0;
        const isReady = card.dataset.ready === '1';
        const isDisabled = card.dataset.disabled === '1';
        
        if (!isDisabled && !isReady && remaining > 0) {
            timerData[currencyId] = remaining;
        }
    });
    
    // Format seconds to HH:MM:SS or MM:SS
    function formatTime(seconds) {
        if (seconds <= 0) return '00:00';
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    // Update countdown displays
    function updateCountdowns() {
        Object.keys(timerData).forEach(currencyId => {
            const countdownEl = document.querySelector(`.timer-countdown[data-currency-id="${currencyId}"]`);
            if (countdownEl && timerData[currencyId] > 0) {
                timerData[currencyId]--;
                countdownEl.textContent = formatTime(timerData[currencyId]);
                
                // Change color as time decreases
                if (timerData[currencyId] <= 60) {
                    countdownEl.classList.add('text-warning');
                }
                if (timerData[currencyId] <= 0) {
                    countdownEl.classList.remove('text-warning');
                    countdownEl.classList.add('text-success');
                    countdownEl.textContent = 'آماده!';
                    
                    // Update card style
                    const card = document.querySelector(`.currency-timer-card[data-currency-id="${currencyId}"]`);
                    if (card) {
                        card.classList.add('border-success', 'bg-success', 'bg-opacity-10');
                    }
                }
            }
        });
    }
    
    // Initial display
    Object.keys(timerData).forEach(currencyId => {
        const countdownEl = document.querySelector(`.timer-countdown[data-currency-id="${currencyId}"]`);
        if (countdownEl) {
            countdownEl.textContent = formatTime(timerData[currencyId]);
        }
    });
    
    // Start countdown interval
    if (Object.keys(timerData).length > 0) {
        setInterval(updateCountdowns, 1000);
    }

    // ===== Aggregation Form =====
    const form = document.getElementById('aggregationForm');
    const checkboxes = document.querySelectorAll('.currency-checkbox');
    const checkAllHeader = document.getElementById('checkAllHeader');
    const selectAllBtn = document.getElementById('selectAllCurrencies');
    const deselectAllBtn = document.getElementById('deselectAllCurrencies');
    const submitBtn = document.getElementById('submitAggregation');
    const selectedCountBadge = document.getElementById('selectedCount');

    // تابع به‌روزرسانی تعداد انتخاب شده
    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.currency-checkbox:checked').length;
        selectedCountBadge.textContent = `${checkedCount} ارز انتخاب شده`;
        submitBtn.disabled = checkedCount === 0;
        
        // به‌روزرسانی چک‌باکس هدر
        checkAllHeader.checked = checkedCount === checkboxes.length && checkedCount > 0;
        checkAllHeader.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    }

    // تابع محاسبه مقدار نهایی
    function calculateFinalAmount(index) {
        const row = document.querySelector(`tr[data-currency] .amount-input[data-index="${index}"]`).closest('tr');
        const totalAmount = parseFloat(row.dataset.total);
        const amountInput = document.querySelector(`.amount-input[data-index="${index}"]`);
        const typeRadio = document.querySelector(`.amount-type[data-index="${index}"]:checked`);
        const calculatedSpan = document.querySelector(`.calculated-amount[data-index="${index}"]`);
        const unitLabel = document.querySelector(`.unit-label[data-index="${index}"]`);
        
        const inputValue = parseFloat(amountInput.value) || 0;
        const type = typeRadio ? typeRadio.value : 'amount';
        
        let finalAmount = 0;
        
        if (type === 'percent') {
            finalAmount = (totalAmount * inputValue) / 100;
            unitLabel.textContent = '%';
        } else {
            finalAmount = inputValue;
            unitLabel.textContent = row.dataset.currency;
        }
        
        // اطمینان از عدم تجاوز از مقدار کل
        if (finalAmount > totalAmount) {
            finalAmount = totalAmount;
            if (type === 'amount') {
                amountInput.value = totalAmount;
            } else {
                amountInput.value = 100;
            }
        }
        
        if (finalAmount > 0) {
            calculatedSpan.textContent = formatNumber(finalAmount) + ' ' + row.dataset.currency;
            calculatedSpan.classList.remove('text-muted');
            calculatedSpan.classList.add('text-success');
        } else {
            calculatedSpan.textContent = '--';
            calculatedSpan.classList.add('text-muted');
            calculatedSpan.classList.remove('text-success');
        }
    }

    // فرمت کردن اعداد
    function formatNumber(num) {
        if (num === 0) return '0';
        if (num < 0.000001) return num.toExponential(4);
        return parseFloat(num.toPrecision(8)).toString();
    }

    // انتخاب همه
    selectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = true);
        updateSelectedCount();
    });

    // لغو انتخاب همه
    deselectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
        updateSelectedCount();
    });

    // چک‌باکس هدر
    checkAllHeader.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });

    // تغییر چک‌باکس‌های ارز
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    // تغییر نوع مقدار (تعداد/درصد)
    document.querySelectorAll('.amount-type').forEach(radio => {
        radio.addEventListener('change', function() {
            const index = this.dataset.index;
            const amountInput = document.querySelector(`.amount-input[data-index="${index}"]`);
            const maxBtn = document.querySelector(`.max-btn[data-index="${index}"]`);
            
            // ریست مقدار
            amountInput.value = '';
            
            if (this.value === 'percent') {
                amountInput.placeholder = 'درصد (0-100)';
                amountInput.max = 100;
            } else {
                amountInput.placeholder = 'مقدار';
                amountInput.max = '';
            }
            
            calculateFinalAmount(index);
        });
    });

    // تغییر مقدار ورودی
    document.querySelectorAll('.amount-input').forEach(input => {
        input.addEventListener('input', function() {
            calculateFinalAmount(this.dataset.index);
        });
    });

    // دکمه حداکثر
    document.querySelectorAll('.max-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = this.dataset.index;
            const total = parseFloat(this.dataset.total);
            const typeRadio = document.querySelector(`.amount-type[data-index="${index}"]:checked`);
            const amountInput = document.querySelector(`.amount-input[data-index="${index}"]`);
            
            if (typeRadio && typeRadio.value === 'percent') {
                amountInput.value = 100;
            } else {
                amountInput.value = total;
            }
            
            calculateFinalAmount(index);
        });
    });

    // اعتبارسنجی فرم قبل از ارسال
    if (form) {
        form.addEventListener('submit', function(e) {
            const checkedCurrencies = document.querySelectorAll('.currency-checkbox:checked');
            
            if (checkedCurrencies.length === 0) {
                e.preventDefault();
                alert('لطفاً حداقل یک ارز را انتخاب کنید.');
                return false;
            }

            // بررسی مقادیر وارد شده
            let hasError = false;
            checkedCurrencies.forEach(cb => {
                const index = cb.dataset.index;
                const amountInput = document.querySelector(`.amount-input[data-index="${index}"]`);
                const value = parseFloat(amountInput.value);
                
                if (!value || value <= 0) {
                    hasError = true;
                    amountInput.classList.add('is-invalid');
                } else {
                    amountInput.classList.remove('is-invalid');
                }
            });

            if (hasError) {
                e.preventDefault();
                alert('لطفاً برای ارزهای انتخاب شده مقدار معتبر وارد کنید.');
                return false;
            }

            // نمایش تأیید
            if (!confirm('آیا از شروع عملیات تجمیع اطمینان دارید؟')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // مقداردهی اولیه
    updateSelectedCount();

    // ===== مقداردهی پیش‌فرض درصد تجمیع =====
    document.querySelectorAll('.aggregation-row').forEach(row => {
        const defaultPercent = row.dataset.defaultAggregationPercent;
        if (defaultPercent && parseInt(defaultPercent) > 0) {
            const index = row.querySelector('.amount-input')?.dataset.index;
            if (index !== undefined) {
                const amountInput = document.querySelector(`.amount-input[data-index="${index}"]`);
                const unitLabel = document.querySelector(`.unit-label[data-index="${index}"]`);
                
                // مقدار درصد پیش‌فرض را ست کن
                amountInput.value = defaultPercent;
                amountInput.placeholder = 'درصد (0-100)';
                amountInput.max = 100;
                if (unitLabel) unitLabel.textContent = '%';
                
                // محاسبه مقدار نهایی
                calculateFinalAmount(index);
            }
        }
    });

    // ===== Modal تنظیمات برداشت =====
    const currencySearch = document.getElementById('currencySearch');
    const statusFilter = document.getElementById('statusFilter');
    const settingsRows = document.querySelectorAll('.currency-settings-row');
    const settingsCountInfo = document.getElementById('settingsCountInfo');
    const enableAllBtn = document.getElementById('enableAllWithdrawals');
    const disableAllBtn = document.getElementById('disableAllWithdrawals');

    // فیلتر جستجو و وضعیت
    function filterSettingsTable() {
        const searchTerm = currencySearch ? currencySearch.value.toLowerCase() : '';
        const statusValue = statusFilter ? statusFilter.value : 'all';
        let visibleCount = 0;

        settingsRows.forEach(row => {
            const symbol = row.dataset.symbol || '';
            const name = row.dataset.name || '';
            const isEnabled = row.dataset.enabled === '1';
            
            const matchesSearch = symbol.includes(searchTerm) || name.includes(searchTerm);
            const matchesStatus = statusValue === 'all' || 
                (statusValue === 'enabled' && isEnabled) || 
                (statusValue === 'disabled' && !isEnabled);

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (settingsCountInfo) {
            settingsCountInfo.textContent = `${visibleCount} ارز`;
        }
    }

    if (currencySearch) {
        currencySearch.addEventListener('input', filterSettingsTable);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterSettingsTable);
    }

    // به‌روزرسانی data-enabled هنگام تغییر toggle
    document.querySelectorAll('.withdrawal-enabled-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const row = this.closest('.currency-settings-row');
            if (row) {
                row.dataset.enabled = this.checked ? '1' : '0';
            }
        });
    });

    // فعال‌سازی همه
    if (enableAllBtn) {
        enableAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.withdrawal-enabled-toggle').forEach(toggle => {
                toggle.checked = true;
                const row = toggle.closest('.currency-settings-row');
                if (row) row.dataset.enabled = '1';
            });
            filterSettingsTable();
        });
    }

    // غیرفعال‌سازی همه
    if (disableAllBtn) {
        disableAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.withdrawal-enabled-toggle').forEach(toggle => {
                toggle.checked = false;
                const row = toggle.closest('.currency-settings-row');
                if (row) row.dataset.enabled = '0';
            });
            filterSettingsTable();
        });
    }

    // تأیید قبل از ذخیره تنظیمات
    const settingsForm = document.getElementById('withdrawalSettingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            if (!confirm('آیا از ذخیره تنظیمات اطمینان دارید؟')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>
@endpush
