@extends('dashboard.layout.master')

@section('title', 'سفارش‌های اسپات صرافی مرجع')

@section('vendor-style')
    <style>
        .spot-orders-exchange-picker .exchange-option {
            display: block;
            border: 1px solid #dee2e6;
            border-radius: 1rem;
            padding: 1.5rem 1rem;
            text-align: center;
            color: inherit;
            transition: all 0.2s ease;
        }

        .spot-orders-exchange-picker .exchange-option:hover {
            border-color: #7367f0;
            box-shadow: 0 8px 20px rgba(115, 103, 240, 0.18);
            transform: translateY(-2px);
        }

        .spot-orders-exchange-picker .exchange-option .icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            color: #fff;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
@endsection

@section('content')
    <div class="spot-orders-exchange-picker">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fa-light fa-building-columns fa-3x mb-3 text-primary"></i>
                    <h5 class="mb-1">انتخاب صرافی مرجع</h5>
                    <p class="text-muted mb-0">
                        صرافی مرجعی که می‌خواهید سفارش‌های اسپات آن را مدیریت کنید انتخاب کنید.
                        این انتخاب ذخیره می‌شود و بعداً از بالای صفحه قابل تغییر است.
                    </p>
                </div>

                @if ($exchanges->isEmpty())
                    <div class="alert alert-warning mb-0">هیچ صرافی مرجع پشتیبانی‌شده‌ای در سیستم ثبت نشده است.</div>
                @else
                    <div class="row g-3 justify-content-center">
                        @foreach ($exchanges as $exchange)
                            <div class="col-sm-6 col-lg-3">
                                <a href="{{ route('admin.ref-exchange.spot-orders.index', ['exchange' => $exchange->slug]) }}"
                                    class="exchange-option">
                                    <span class="icon"><i class="fa-light fa-chart-candlestick fa-lg"></i></span>
                                    <h6 class="mb-1">{{ $exchange->name }}</h6>
                                    @if ($exchange->is_active)
                                        <span class="badge bg-label-success">صرافی فعال</span>
                                    @endif
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
