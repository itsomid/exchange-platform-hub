@extends('dashboard.layout.master')
@section('title', 'تنظیمات ربات معامله‌گر اسپات')
@section('content')
    <!-- آمار کلی ربات‌ها -->
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>کل ارزهای دیجیتال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalCurrencies }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-light fa-coins fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>ربات‌های فعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $activeBots }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-solid fa-robot fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>ربات‌های غیرفعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $inactiveBots }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="fa-solid fa-robot fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>ربات‌های پیکربندی شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $configuredBots }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-gear fa-lg"></i>
                        </span>
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

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">
                <i class="fa-solid fa-sliders me-2"></i>
                تنظیمات کلی
            </h5>
            <div class="row g-4">
                <div class="col-lg-6">
                    <form action="{{ route('admin.setting.spot-bot.bulk-interval') }}" method="POST">
                        @csrf
                        <label for="price_interval_seconds" class="form-label">
                            <i class="fa-solid fa-clock me-1"></i>
                            تغییر دسته‌ای بازه زمانی (ثانیه)
                        </label>
                        <div class="input-group">
                            <input type="number"
                                class="form-control @error('price_interval_seconds') is-invalid @enderror"
                                id="price_interval_seconds" name="price_interval_seconds"
                                value="{{ old('price_interval_seconds', 60) }}" min="1" required>
                            <button type="submit" class="btn btn-primary">
                                اعمال روی همه
                            </button>
                        </div>
                        @error('price_interval_seconds')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">این مقدار برای همه بازارهای پیکربندی‌شده ثبت می‌شود.</small>
                    </form>
                </div>
                <div class="col-lg-6">
                    <form action="{{ route('admin.setting.spot-bot.bulk-disable') }}" method="POST"
                        onsubmit="return confirm('آیا از غیرفعال کردن ربات همه بازارها اطمینان دارید؟');">
                        @csrf
                        <label class="form-label">
                            <i class="fa-solid fa-power-off me-1"></i>
                            غیرفعال کردن دسته‌ای بازارها
                        </label>
                        <div>
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-ban me-1"></i>
                                غیرفعال کردن همه
                            </button>
                        </div>
                        <small class="text-muted">ربات همه بازارهای فعال خاموش می‌شود. بقیه تنظیمات هر بازار حفظ می‌شود.</small>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">
                    <i class="fa-solid fa-robot me-2"></i>
                    تنظیمات ربات معامله‌گر اسپات
                </h5>
            </div>

            @if ($currencies->total())
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <i class="fa-solid fa-coins me-1"></i>
                                    ارز دیجیتال
                                </th>
                                <th>
                                    <i class="fa-solid fa-toggle-on me-1"></i>
                                    وضعیت ربات
                                </th>
                                <th>
                                    <i class="fa-solid fa-clock me-1"></i>
                                    بازه زمانی (ثانیه)
                                </th>
                                <th>
                                    <i class="fa-solid fa-percentage me-1"></i>
                                    مارجین
                                </th>
                                <th>
                                    <i class="fa-solid fa-user-robot me-1"></i>
                                    کاربر صوری
                                </th>
                                <th>
                                    <i class="fa-solid fa-cogs me-1"></i>
                                    عملیات
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($currencies as $currency)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3">
                                                <img src="{{ asset($currency->coinLogo()) }}" class="img-fluid"
                                                    width="50px">
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $currency->name }}</h6>
                                                <small class="text-muted">{{ $currency->symbol }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($currency->spotBotSetting && $currency->spotBotSetting->is_active)
                                            <span class="badge bg-success">
                                                <i class="fa-solid fa-robot me-1"></i>
                                                فعال
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fa-solid fa-robot me-1"></i>
                                                غیرفعال
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($currency->spotBotSetting)
                                            <span class="badge bg-label-info">
                                                {{ $currency->spotBotSetting->price_interval_seconds }} ثانیه
                                            </span>
                                        @else
                                            <span class="text-muted">تنظیم نشده</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($currency->spotBotSetting)
                                            <span class="badge bg-label-primary">
                                                {{ $currency->spotBotSetting->order_margin }}%
                                            </span>
                                        @else
                                            <span class="text-muted">تنظیم نشده</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($currency->spotBotSetting && $currency->spotBotSetting->fakeUser)
                                            <span class="badge bg-label-secondary">
                                                <i class="fa-solid fa-user me-1"></i>
                                                {{ $currency->spotBotSetting->fakeUser->username }}
                                            </span>
                                        @else
                                            <span class="text-muted">انتخاب نشده</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.setting.spot-bot.edit', $currency) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-edit me-1"></i>
                                            تنظیمات
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($currencies->hasPages())
                    <div class="mt-3">
                        {{ $currencies->onEachSide(1)->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fa-solid fa-robot fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">هیچ ارز دیجیتالی یافت نشد</h5>
                    <p class="text-muted">برای استفاده از ربات معامله‌گر، ابتدا ارزهای دیجیتال را اضافه کنید.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
