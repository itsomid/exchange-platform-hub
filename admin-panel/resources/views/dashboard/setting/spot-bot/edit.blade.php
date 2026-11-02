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

        @include('dashboard.setting.spot-bot._fields')


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
                const minOrderSize = document.getElementById('spot_bot_min_order_size');
                const maxOrderSize = document.getElementById('spot_bot_max_order_size');

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
