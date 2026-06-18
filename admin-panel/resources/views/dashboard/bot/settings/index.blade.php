@extends('dashboard.layout.master')

@section('title', 'تنظیمات ربات معاملاتی')

@section('content')

        <div class="row">
            <div class="col-12">

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">تنظیمات کلی ربات معاملاتی</h4>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.bot.settings.update') }}">
                            @csrf
                            @method('PATCH')

                            <div class="row">
                                {{-- General --}}
                                <div class="col-xxl-6 col-12 mb-0 mb-xl-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>تنظیمات عمومی</h5>
                                        </div>
                                        <div class="card-body">

                                            <div class="mb-3">
                                                <label for="min_deposit_usdt" class="form-label">حداقل واریز (USDT) <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0"
                                                    class="form-control @error('min_deposit_usdt') is-invalid @enderror"
                                                    id="min_deposit_usdt" name="min_deposit_usdt"
                                                    value="{{ old('min_deposit_usdt', $settings?->min_deposit_usdt ?? 20) }}">
                                                @error('min_deposit_usdt')<div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="alpha_weight" class="form-label">وزن آلفا (α) <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0" max="1"
                                                    class="form-control @error('alpha_weight') is-invalid @enderror"
                                                    id="alpha_weight" name="alpha_weight"
                                                    value="{{ old('alpha_weight', $settings?->alpha_weight ?? 0.15) }}">
                                                <small class="form-text text-muted">عدد بین ۰ تا ۱</small>
                                                @error('alpha_weight')<div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="default_sell_orders_count" class="form-label">تعداد پیش‌فرض سفارش
                                                    فروش <span class="text-danger">*</span></label>
                                                <input type="number" step="1" min="1" max="10"
                                                    class="form-control @error('default_sell_orders_count') is-invalid @enderror"
                                                    id="default_sell_orders_count" name="default_sell_orders_count"
                                                    value="{{ old('default_sell_orders_count', $settings?->default_sell_orders_count ?? 3) }}">
                                                @error('default_sell_orders_count')<div class="invalid-feedback">{{ $message }}
                                                </div>@enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="performance_fee_percent" class="form-label">کارمزد عملکرد (%) <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0" max="100"
                                                    class="form-control @error('performance_fee_percent') is-invalid @enderror"
                                                    id="performance_fee_percent" name="performance_fee_percent"
                                                    value="{{ old('performance_fee_percent', $settings?->performance_fee_percent ?? 22) }}">
                                                @error('performance_fee_percent')<div class="invalid-feedback">{{ $message }}
                                                </div>@enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="p2p_min_order_value" class="form-label">حداقل ارزش هر اوردر فروش (USDT) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0.1" max="10000"
                                                    class="form-control @error('p2p_min_order_value') is-invalid @enderror"
                                                    id="p2p_min_order_value" name="p2p_min_order_value"
                                                    value="{{ old('p2p_min_order_value', $settings?->p2p_min_order_value ?? 5) }}">
                                                <small class="form-text text-muted">حداقل مبلغ معتبر برای هر سفارش روی روی صرافی مرجع به صورت پیش فرض (D14)</small>
                                                @error('p2p_min_order_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">روش محاسبه کف تخصیص (D14 Pre-check) <span class="text-danger">*</span></label>
                                                @error('precheck_floor_mode')<div class="text-danger small mb-1">{{ $message }}</div>@enderror
                                                @php $floorMode = old('precheck_floor_mode', $settings?->precheck_floor_mode ?? 'multi'); @endphp

                                                <div class="border rounded p-3 mb-2 {{ $floorMode === 'multi' ? 'border-primary' : '' }}">
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input" type="radio" name="precheck_floor_mode"
                                                            id="floor_mode_multi" value="multi"
                                                            {{ $floorMode === 'multi' ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold" for="floor_mode_multi">
                                                            محافظه‌کارانه — <code>N × p2p_min</code>
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block" style="padding-right: 1.5rem;">
                                                        کف تخصیص برابر <strong>تعداد تارگت‌های فروش × حداقل ارزش هر اوردر</strong> محاسبه می‌شود.
                                                        مثال: اگه سیگنال ۳ تارگت داشته باشد و p2p_min = 5، باید حداقل <strong>15 USDT</strong> تخصیص بگیرد.
                                                        بدین ترتیب تضمین می‌شود همه تارگت‌ها بدون collapse خریداری شوند؛
                                                        اما کوین‌هایی که سرمایه کمتری می‌گیرند ممکن است skip شوند.
                                                    </small>
                                                </div>

                                                <div class="border rounded p-3 {{ $floorMode === 'single' ? 'border-primary' : '' }}">
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input" type="radio" name="precheck_floor_mode"
                                                            id="floor_mode_single" value="single"
                                                            {{ $floorMode === 'single' ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold" for="floor_mode_single">
                                                            آزادانه‌تر — <code>p2p_min</code> (تک‌تارگت)
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block" style="padding-right: 1.5rem;">
                                                        کف تخصیص فقط برابر <strong>حداقل ارزش یک اوردر</strong> است، بدون در نظر گرفتن تعداد تارگت‌ها.
                                                        مثال: با p2p_min = 5، کافی است تخصیص ≥ <strong>5 USDT</strong> باشد.
                                                        کوین‌های بیشتری خریداری می‌شوند، ولی Smart Collapse بعد از خرید ممکن است
                                                        تارگت‌ها را ادغام کند و با تعداد کمتری سل‌اوردر مواجه شوید.
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="mb-3 form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_enabled"
                                                    name="is_enabled" value="1" {{ old('is_enabled', $settings?->is_enabled ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_enabled">ربات فعال باشد</label>
                                            </div>

                                            <div class="mb-3 form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="cancel_sell_on_exchange_enabled"
                                                    name="cancel_sell_on_exchange_enabled" value="1"
                                                    {{ old('cancel_sell_on_exchange_enabled', $settings?->cancel_sell_on_exchange_enabled ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="cancel_sell_on_exchange_enabled">
                                                    فروش کوین‌ها روی صرافی مرجع هنگام لغو
                                                </label>
                                                <small class="form-text text-muted d-block">
                                                    اگر روشن باشد، هنگام لغو سفارش ربات، کوین‌های خریداری‌شده با مارکت‌سل روی صرافی مرجع فروخته می‌شوند و معادل USDT (پس از کسر کارمزدها) به کیف پول کاربر بازمی‌گردد.
                                                    اگر خاموش باشد، فروش واقعی انجام نمی‌شود و فقط تراکنش‌های لازم بر اساس قیمت لحظه‌ای ثبت می‌شوند.
                                                </small>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                {{-- Fee Tiers --}}
                                <div class="col-xxl-6 col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">ردیف کارمزد انتقال</h5>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addTier">
                                                <i class="fas fa-plus me-1"></i> افزودن ردیف
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            @error('transfer_fee_tiers')
                                                <div class="alert alert-danger">{{ $message }}</div>
                                            @enderror

                                            <div id="feeTiersContainer">
                                                @php
    $tiers = old('transfer_fee_tiers', $settings?->transfer_fee_tiers ?? [
        ['from' => 20, 'to' => 100, 'fee_type' => 'flat', 'fee_value' => 1],
        ['from' => 100, 'to' => 1000, 'fee_type' => 'percent', 'fee_value' => 1],
        ['from' => 1000, 'to' => null, 'fee_type' => 'flat', 'fee_value' => 12],
    ]);
                                                @endphp

                                                @foreach ($tiers as $i => $tier)
                                                    <div class="tier-row border rounded p-2 mb-2" data-index="{{ $i }}">
                                                        <div class="row g-2 align-items-end">
                                                            <div class="col-md-3">
                                                                <label class="form-label small">از (USDT)</label>
                                                                <input type="number" step="0.01"
                                                                    class="form-control form-control-sm"
                                                                    name="transfer_fee_tiers[{{ $i }}][from]"
                                                                    value="{{ $tier['from'] }}">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label small">تا (USDT)</label>
                                                                <input type="number" step="0.01"
                                                                    class="form-control form-control-sm"
                                                                    name="transfer_fee_tiers[{{ $i }}][to]"
                                                                    value="{{ $tier['to'] ?? '' }}" placeholder="بی‌نهایت">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label small">نوع</label>
                                                                <select class="form-select form-select-sm"
                                                                    name="transfer_fee_tiers[{{ $i }}][fee_type]">
                                                                    <option value="flat" {{ ($tier['fee_type'] ?? '') === 'flat' ? 'selected' : '' }}>ثابت</option>
                                                                    <option value="percent" {{ ($tier['fee_type'] ?? '') === 'percent' ? 'selected' : '' }}>درصد</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label class="form-label small">مقدار</label>
                                                                <input type="number" step="0.01"
                                                                    class="form-control form-control-sm"
                                                                    name="transfer_fee_tiers[{{ $i }}][fee_value]"
                                                                    value="{{ $tier['fee_value'] }}">
                                                            </div>
                                                            <div class="col-md-1">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger removeTier w-100">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> ذخیره تنظیمات
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let tierIndex = {{ count($tiers ?? []) }};

            document.getElementById('addTier').addEventListener('click', function () {
                const container = document.getElementById('feeTiersContainer');
                const i = tierIndex++;
                const html = `
                    <div class="tier-row border rounded p-2 mb-2" data-index="${i}">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small">از (USDT)</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="transfer_fee_tiers[${i}][from]" value="">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">تا (USDT)</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="transfer_fee_tiers[${i}][to]" placeholder="بی‌نهایت">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">نوع</label>
                                <select class="form-select form-select-sm" name="transfer_fee_tiers[${i}][fee_type]">
                                    <option value="flat">ثابت</option>
                                    <option value="percent">درصد</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">مقدار</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="transfer_fee_tiers[${i}][fee_value]" value="">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger removeTier w-100"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>`;
                container.insertAdjacentHTML('beforeend', html);
            });

            document.getElementById('feeTiersContainer').addEventListener('click', function (e) {
                if (e.target.closest('.removeTier')) {
                    e.target.closest('.tier-row').remove();
                }
            });
        });
    </script>
@endpush
