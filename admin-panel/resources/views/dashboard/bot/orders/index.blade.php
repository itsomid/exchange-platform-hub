@extends('dashboard.layout.master')

@section('title', 'سفارشات ربات معاملاتی')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">کاربران دارای سفارش ربات</h4>
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control"
                                    placeholder="جستجوی کاربر بر اساس ایمیل یا موبایل..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-search me-1"></i> جستجو
                                </button>
                                <a href="{{ route('admin.bot.order.index') }}"
                                    class="btn btn-outline-secondary ms-1">پاک‌کردن</a>
                            </div>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>کاربر</th>
                                    <th>تعداد سفارش</th>
                                    <th>مجموع تخصیص (USDT)</th>
                                    <th>موجودی کیف پول (USDT)</th>
                                    <th>وضعیت ربات</th>
                                    <th>آخرین سفارش</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    @php
                                        $wallet = $wallets[$row->user_id] ?? null;
                                        $setting = $settings[$row->user_id] ?? null;
                                        $walletTotal = $wallet
                                            ? (float) $wallet->balance + (float) $wallet->locked_balance
                                            : 0;
                                        $autoOn = $setting?->auto_trade_enabled ?? false;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div>{{ $row->email }}</div>
                                            <small class="text-muted">{{ $row->mobile }}</small>
                                        </td>
                                        <td><span class="badge bg-secondary">{{ $row->orders_count }}</span></td>
                                        <td class="font-number">{{ number_format((float) $row->total_allocated, 2) }}</td>
                                        <td class="font-number">{{ number_format($walletTotal, 2) }}</td>
                                        <td>
                                            @if ($autoOn)
                                                <span class="badge bg-success">روشن</span>
                                            @else
                                                <span class="badge bg-secondary">خاموش</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $row->last_order_at ? \Illuminate\Support\Carbon::parse($row->last_order_at)->format('Y-m-d H:i') : '—' }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.bot.order.user', $row->user_id) }}"
                                                class="btn btn-sm btn-outline-primary" title="مشاهده وضعیت">
                                                <i class="fas fa-eye me-1"></i> مشاهده
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            هیچ کاربری با سفارش ربات یافت نشد.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $rows->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
