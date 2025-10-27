@extends('dashboard.layout.master')
@section('title', 'تنظیمات ربات معامله‌گر - ' . $currency->name)
@section('content')
    <!-- هدر صفحه -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <img src="{{ asset($currency->coinLogo()) }}" class="img-fluid" width="50px">
                            </div>
                            <div>
                                <h4 class="mb-1">
                                    <i class="fa-solid fa-robot me-2"></i>
                                    تنظیمات ربات معامله‌گر
                                </h4>
                                <p class="mb-0 text-muted">{{ $currency->name }} ({{ $currency->symbol }})</p>
                            </div>
                        </div>
                        <div class="text-end">
                            @if ($spotBotSetting && $spotBotSetting->is_active)
                                <span class="badge bg-success fs-6 p-2">
                                    <i class="fa-solid fa-robot me-1"></i>
                                    ربات فعال
                                </span>
                            @else
                                <span class="badge bg-danger fs-6 p-2">
                                    <i class="fa-solid fa-robot me-1"></i>
                                    ربات غیرفعال
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-exclamation-triangle me-2"></i>
            <strong>خطا در ذخیره تنظیمات:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('admin.setting.spot-bot.update', $currency) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">

            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fa-solid fa-cogs me-2"></i>
                            تنظیمات اصلی ربات
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12 mb-5">
                                <div class="form-check form-switch  switch-lg">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input " type="checkbox" id="is_active" name="is_active"
                                        value="1"
                                        {{ old('is_active', $spotBotSetting->is_active ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <i class="fa-solid fa-power-off me-1"></i>
                                        فعال‌سازی ربات معامله‌گر
                                    </label>
                                </div>
                                <small class="text-muted">با فعال کردن این گزینه، ربات شروع به معامله خودکار خواهد
                                    کرد</small>
                            </div>


                            <div class="col-md-6">
                                <label for="price_interval_seconds" class="form-label">
                                    <i class="fa-solid fa-clock me-1"></i>
                                    بازه زمانی بروزرسانی قیمت (ثانیه)
                                </label>
                                <input type="number"
                                    class="form-control @error('price_interval_seconds') is-invalid @enderror"
                                    id="price_interval_seconds" name="price_interval_seconds"
                                    value="{{ old('price_interval_seconds', $spotBotSetting->price_interval_seconds ?? 60) }}"
                                    min="1" required>
                                @error('price_interval_seconds')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">هر چند ثانیه یکبار قیمت بروزرسانی شود</small>
                            </div>


                            <div class="col-md-6">
                                <label for="order_margin" class="form-label">
                                    <i class="fa-solid fa-percentage me-1"></i>
                                    مارجین سفارش (درصد)
                                </label>
                                <input type="number" class="form-control @error('order_margin') is-invalid @enderror"
                                    id="order_margin" name="order_margin"
                                    value="{{ old('order_margin', $spotBotSetting->order_margin ?? 0.5) }}" step="0.0001"
                                    min="0" required>
                                @error('order_margin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">درصد اختلاف قیمت سفارش با قیمت بازار</small>
                            </div>


                            <div class="col-md-6">
                                <label for="buy_orders_count" class="form-label">
                                    <i class="fa-solid fa-arrow-up me-1 text-success"></i>
                                    تعداد سفارش‌های خرید
                                </label>
                                <input type="number" class="form-control @error('buy_orders_count') is-invalid @enderror"
                                    id="buy_orders_count" name="buy_orders_count"
                                    value="{{ old('buy_orders_count', $spotBotSetting->buy_orders_count ?? 5) }}"
                                    min="1" required>
                                @error('buy_orders_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                            <div class="col-md-6">
                                <label for="sell_orders_count" class="form-label">
                                    <i class="fa-solid fa-arrow-down me-1 text-danger"></i>
                                    تعداد سفارش‌های فروش
                                </label>
                                <input type="number" class="form-control @error('sell_orders_count') is-invalid @enderror"
                                    id="sell_orders_count" name="sell_orders_count"
                                    value="{{ old('sell_orders_count', $spotBotSetting->sell_orders_count ?? 5) }}"
                                    min="1" required>
                                @error('sell_orders_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                            <div class="col-md-6">
                                <label for="min_order_size" class="form-label">
                                    <i class="fa-solid fa-arrow-down-short-wide me-1"></i>
                                    حداقل اندازه سفارش
                                </label>
                                <input class="form-control @error('min_order_size') is-invalid @enderror"
                                    id="min_order_size" name="min_order_size"
                                    value="{{ old('min_order_size', formatNumberTrimZeros($spotBotSetting->min_order_size) ?? 10) }}"
                                    required>
                                @error('min_order_size')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                            <div class="col-md-6">
                                <label for="max_order_size" class="form-label">
                                    <i class="fa-solid fa-arrow-up-wide-short me-1"></i>
                                    حداکثر اندازه سفارش
                                </label>
                                <input class="form-control @error('max_order_size') is-invalid @enderror"
                                    id="max_order_size" name="max_order_size"
                                    value="{{ old('max_order_size', formatNumberTrimZeros($spotBotSetting->max_order_size) ?? 1000) }}"
                                    required>
                                @error('max_order_size')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="market_crash_percentage" class="form-label">
                                    <i class="fa-solid fa-chart-line-down me-1 text-warning"></i>
                                    درصد سقوط بازار
                                </label>
                                <input type="number"
                                    class="form-control @error('market_crash_percentage') is-invalid @enderror"
                                    id="market_crash_percentage" name="market_crash_percentage"
                                    value="{{ old('market_crash_percentage', $spotBotSetting->market_crash_percentage ?? 10) }}"
                                    step="0.01" min="0" max="100">
                                @error('market_crash_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">در صورت سقوط بیش از این درصد، ربات متوقف می‌شود</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fa-solid fa-user-robot me-2"></i>
                            کاربر صوری
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="fake_user_id" class="form-label">
                                <i class="fa-solid fa-user me-1"></i>
                                انتخاب کاربر صوری
                            </label>
                            <x-user-selection-component inputName="fake_user_id" multiple="0"
                                selected="{{ old('fake_user_id', $spotBotSetting->fake_user_id ?? '') }}"
                                selected-label="{{ $spotBotSetting->fakeUser
                                    ? '(' .
                                        $spotBotSetting->fakeUser->id .
                                        '#) ' .
                                        $spotBotSetting->fakeUser->fullname() .
                                        ' | ' .
                                        $spotBotSetting->fakeUser->email
                                    : '' }}">
                            </x-user-selection-component>
                            @error('fake_user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">کاربری که سفارش‌ها به نام او ثبت می‌شود</small>
                        </div>

                        @if ($spotBotSetting && $spotBotSetting->fakeUser)
                            <div class="alert alert-info">
                                <i class="fa-solid fa-info-circle me-2"></i>
                                <strong>کاربر فعلی:</strong><br>
                                {{ $spotBotSetting->fakeUser->username }}<br>
                                <small>{{ $spotBotSetting->fakeUser->email }}</small>
                            </div>
                        @endif
                    </div>
                </div>


                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fa-solid fa-chart-simple me-2"></i>
                            آمار سریع
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">وضعیت فعلی:</span>
                            @if ($spotBotSetting && $spotBotSetting->is_active)
                                <span class="badge bg-success">فعال</span>
                            @else
                                <span class="badge bg-danger">غیرفعال</span>
                            @endif
                        </div>

                        @if ($spotBotSetting)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">آخرین بروزرسانی:</span>
                                <small>{{ $spotBotSetting->updated_at->diffForHumans() }}</small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">تاریخ ایجاد:</span>
                                <small>{{ $spotBotSetting->created_at->format('Y/m/d') }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.setting.spot-bot.index') }}" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-right me-1"></i>
                                بازگشت به لیست
                            </a>

                            <div class="d-flex gap-2">
                                <button type="reset" class="btn btn-outline-warning">
                                    <i class="fa-solid fa-undo me-1"></i>
                                    بازنشانی
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save me-1"></i>
                                    ذخیره تنظیمات
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('form');
                const minOrderSize = document.getElementById('min_order_size');
                const maxOrderSize = document.getElementById('max_order_size');

                function validateOrderSizes() {
                    const minValue = parseFloat(minOrderSize.value) || 0;
                    const maxValue = parseFloat(maxOrderSize.value) || 0;

                    if (minValue > maxValue && maxValue > 0) {
                        maxOrderSize.setCustomValidity('حداکثر اندازه سفارش باید بیشتر از حداقل باشد');
                    } else {
                        maxOrderSize.setCustomValidity('');
                    }
                }

                minOrderSize.addEventListener('input', validateOrderSizes);
                maxOrderSize.addEventListener('input', validateOrderSizes);
            });
        </script>
    @endpush
@endsection
