{{--
    Shared form partial for BotSignal create / edit.
    Required variables:
        $currencies  — Collection of Currency
        $signal      — BotSignal model (optional, for edit; falls back to old() / defaults)
        $formAction  — route string
        $method      — 'POST' | 'PATCH'
--}}

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if ($method === 'PATCH') @method('PATCH') @endif

    <div class="row">
        {{-- Left column --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">اطلاعات پایه</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="currency_id" class="form-label">ارز <span class="text-danger">*</span></label>
                        <x-currency-select
                            name="currency_id"
                            id="currency_id"
                            :currencies="$currencies"
                            :selected="old('currency_id', $signal->currency_id ?? '')"
                            :required="true"
                            error="currency_id"
                        />
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="floor_price" class="form-label">قیمت کف (USDT) <span class="text-danger">*</span></label>
                            <input type="text" step="0.00000001" min="0"
                                class="form-control @error('floor_price') is-invalid @enderror"
                                id="floor_price" name="floor_price"
                                value="{{ old('floor_price', formatNumberTrimZeros($signal->floor_price ?? '')) }}">
                            @error('floor_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ceiling_price" class="form-label">قیمت سقف (USDT) <span class="text-danger">*</span></label>
                            <input type="text" step="0.00000001" min="0"
                                class="form-control @error('ceiling_price') is-invalid @enderror"
                                id="ceiling_price" name="ceiling_price"
                                value="{{ old('ceiling_price', formatNumberTrimZeros($signal->ceiling_price ?? '')) }}">
                            @error('ceiling_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="min_buy_amount_usdt" class="form-label">حداقل مبلغ خرید (USDT) (سمت خرید) <span class="text-danger">*</span></label>
                        <input type="text" step="0.01" min="0"
                            class="form-control @error('min_buy_amount_usdt') is-invalid @enderror"
                            id="min_buy_amount_usdt" name="min_buy_amount_usdt"
                            value="{{ old('min_buy_amount_usdt', formatNumberTrimZeros($signal->min_buy_amount_usdt ?? 5)) }}">
                        @error('min_buy_amount_usdt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="max_allocation_percent" class="form-label">سقف تخصیص (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="100"
                            class="form-control @error('max_allocation_percent') is-invalid @enderror"
                            id="max_allocation_percent" name="max_allocation_percent"
                            value="{{ old('max_allocation_percent', $signal->max_allocation_percent ?? 100) }}">
                        <small class="form-text text-muted">حداکثر درصدی از کل سرمایه که به این ارز تخصیص می‌یابد</small>
                        @error('max_allocation_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="p2p_min_order_value_override" class="form-label">حداقل ارزش هر اوردر فروش (USDT) (سمت فروش) (override)</label>
                        <input type="text" step="0.01" min="0.1" max="10000"
                            class="form-control @error('p2p_min_order_value_override') is-invalid @enderror"
                            id="p2p_min_order_value_override" name="p2p_min_order_value_override"
                            value="{{ old('p2p_min_order_value_override', formatNumberTrimZeros($signal->p2p_min_order_value_override ?? '')) }}"
                            placeholder="پیش‌فرض: {{ formatNumberTrimZeros($globalP2pMin ?? 5) }} USDT">
                        <small class="form-text text-muted">خالی = از تنظیمات کلی استفاده شود (D14)</small>
                        @error('p2p_min_order_value_override')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active"
                            name="is_active" value="1"
                            {{ old('is_active', $signal->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">سیگنال فعال باشد</label>
                    </div>

                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">تنظیمات سفارش فروش</h5></div>
                <div class="card-body">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sell_orders_count" class="form-label">تعداد سفارش فروش <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" max="10"
                                class="form-control @error('sell_orders_count') is-invalid @enderror"
                                id="sell_orders_count" name="sell_orders_count"
                                value="{{ old('sell_orders_count', $signal->sell_orders_count ?? 3) }}">
                            @error('sell_orders_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sell_mode" class="form-label">نوع هدف <span class="text-danger">*</span></label>
                            <select class="form-select @error('sell_mode') is-invalid @enderror"
                                    id="sell_mode" name="sell_mode">
                                <option value="percent" {{ old('sell_mode', $signal->sell_mode ?? 'percent') === 'percent' ? 'selected' : '' }}>درصد تغییر قیمت</option>
                                <option value="price"   {{ old('sell_mode', $signal->sell_mode ?? '') === 'price' ? 'selected' : '' }}>قیمت مطلق</option>
                            </select>
                            @error('sell_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">اهداف فروش</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addTarget">
                        <i class="fas fa-plus me-1"></i> افزودن هدف
                    </button>
                </div>
                <div class="card-body">
                    @error('sell_targets')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <div class="row g-1 mb-1">
                        <div class="col-6"><small class="text-muted">هدف (% یا قیمت)</small></div>
                        <div class="col-5"><small class="text-muted">سهم (%)</small></div>
                        <div class="col-1"></div>
                    </div>

                    <div id="targetsContainer">
                        @php
$targets = old('sell_targets', $signal->sell_targets ?? [
    ['trigger' => 20, 'share' => 50],
    ['trigger' => 40, 'share' => 30],
    ['trigger' => 60, 'share' => 20],
]);
                        @endphp

                        @foreach ($targets as $i => $target)
                            <div class="target-row row g-1 mb-1" data-index="{{ $i }}">
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" min="0"
                                            class="form-control form-control-sm trigger-input @error("sell_targets.{$i}.trigger") is-invalid @enderror"
                                            name="sell_targets[{{ $i }}][trigger]"
                                            value="{{ $target['trigger'] }}" aria-describedby="basic-addon1">
                                        <span class="input-group-text trigger-suffix" id="basic-addon1">{{ old('sell_mode', $signal->sell_mode ?? 'percent') === 'price' ? 'USDT' : 'سود' }}</span>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <input type="number" step="0.01" min="0.01" max="100"
                                        class="form-control form-control-sm share-input @error("sell_targets.{$i}.share") is-invalid @enderror"
                                        name="sell_targets[{{ $i }}][share]"
                                        value="{{ $target['share'] }}">
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-sm btn-outline-danger removeTarget w-100">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-2">
                        <small class="text-muted">مجموع سهم‌ها: <span id="shareTotal">0</span>٪
                            <span id="shareTotalError" class="text-danger d-none"> — باید ۱۰۰٪ باشد</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> ذخیره
        </button>
        <a href="{{ route('admin.bot.signal.index') }}" class="btn btn-secondary ms-2">انصراف</a>
    </div>

</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let targetIndex = {{ count($targets ?? []) }};
        const sellModeEl = document.getElementById('sell_mode');

        function isPriceMode() {
            return sellModeEl && sellModeEl.value === 'price';
        }

        function syncTriggerInputs() {
            const suffix = isPriceMode() ? 'USDT' : 'سود';
            document.querySelectorAll('.trigger-suffix').forEach(el => {
                el.textContent = suffix;
            });
        }

        function recalcTotal() {
            let total = 0;
            document.querySelectorAll('.share-input').forEach(el => {
                total += parseFloat(el.value) || 0;
            });
            total = Math.round(total * 100) / 100;
            document.getElementById('shareTotal').textContent = total;
            const err = document.getElementById('shareTotalError');
            if (Math.abs(total - 100) > 0.01) {
                err.classList.remove('d-none');
            } else {
                err.classList.add('d-none');
            }
        }

        document.getElementById('addTarget').addEventListener('click', function () {
            const container = document.getElementById('targetsContainer');
            const i = targetIndex++;
            const suffix = isPriceMode() ? 'USDT' : 'سود';
            const html = `
                <div class="target-row row g-1 mb-1" data-index="${i}">
                    <div class="col-6">
                        <div class="input-group input-group-sm">
                            <input type="number" step="any" min="0" class="form-control form-control-sm trigger-input"
                                name="sell_targets[${i}][trigger]" value="">
                            <span class="input-group-text trigger-suffix">${suffix}</span>
                        </div>
                    </div>
                    <div class="col-5">
                        <input type="number" step="0.01" min="0.01" max="100"
                            class="form-control form-control-sm share-input"
                            name="sell_targets[${i}][share]" value="">
                    </div>
                    <div class="col-1">
                        <button type="button" class="btn btn-sm btn-outline-danger removeTarget w-100">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>`;
            container.insertAdjacentHTML('beforeend', html);
            recalcTotal();
        });

        document.getElementById('targetsContainer').addEventListener('click', function (e) {
            if (e.target.closest('.removeTarget')) {
                e.target.closest('.target-row').remove();
                recalcTotal();
            }
        });

        document.getElementById('targetsContainer').addEventListener('input', function (e) {
            if (e.target.classList.contains('share-input')) recalcTotal();
        });

        if (sellModeEl) {
            sellModeEl.addEventListener('change', syncTriggerInputs);
        }

        syncTriggerInputs();
        recalcTotal();
    });
</script>
@endpush
