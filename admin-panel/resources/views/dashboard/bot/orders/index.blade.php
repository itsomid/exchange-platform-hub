@extends('dashboard.layout.master')

@section('title', 'سفارشات ربات معاملاتی')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">سفارشات ربات</h4>
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control"
                                    placeholder="جستجو بر اساس UUID یا ایمیل/موبایل کاربر..."
                                    value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">همه وضعیت‌ها</option>
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                            {{ $s }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
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
                                    <th>#</th>
                                    <th>کاربر</th>
                                    <th>UUID</th>
                                    <th>مبلغ کل (USDT)</th>
                                    <th>وضعیت</th>
                                    <th>تریگر</th>
                                    <th>تاریخ ایجاد</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td>{{ $order->id }}</td>
                                        <td>
                                            <div>{{ $order->user?->email }}</div>
                                            <small class="text-muted">{{ $order->user?->mobile }}</small>
                                        </td>
                                        <td>
                                            <code class="small">{{ Str::limit($order->batch_uuid, 20) }}</code>
                                        </td>
                                        <td>{{ number_format($order->total_amount_usdt, 2) }}</td>
                                        <td>
                                            @php
                                                $badgeClass = match ($order->status) {
                                                    'FILLED' => 'bg-success',
                                                    'PENDING' => 'bg-warning text-dark',
                                                    'PARTIALLY_FILLED' => 'bg-info',
                                                    'CANCELED' => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $order->status }}</span>
                                        </td>
                                        <td><small>{{ $order->triggered_by }}</small></td>
                                        <td><small>{{ $order->created_at?->format('Y-m-d H:i') }}</small></td>
                                        <td>
                                            <a href="{{ route('admin.bot.order.show', $order) }}"
                                                class="btn btn-sm btn-outline-primary" title="جزئیات">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            هیچ سفارشی یافت نشد.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
