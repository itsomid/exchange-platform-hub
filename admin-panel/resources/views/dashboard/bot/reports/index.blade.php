@extends('dashboard.layout.master')

@section('title', 'گزارش‌های ربات معاملاتی')

@php
    $fmt = fn($v, $d = 2) => number_format((float) $v, $d);
    $exportQuery = http_build_query(array_filter([
        'from' => $from,
        'to' => $to,
        'currency_id' => $currencyId,
    ]));
@endphp

@section('content')

    {{-- Filters --}}
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">گزارش‌های ربات معاملاتی</h4>
                    <div class="btn-group">
                        <a href="{{ route('admin.bot.report.export.orders') }}?{{ $exportQuery }}"
                            class="btn btn-sm btn-outline-info">
                            <i class="fas fa-download me-1"></i> CSV سفارش‌ها
                        </a>
                        <a href="{{ route('admin.bot.report.export.executions') }}?{{ $exportQuery }}"
                            class="btn btn-sm btn-outline-info">
                            <i class="fas fa-download me-1"></i> CSV اجراها
                        </a>
                        <a href="{{ route('admin.bot.report.export.settlements') }}?{{ $exportQuery }}"
                            class="btn btn-sm btn-outline-info">
                            <i class="fas fa-download me-1"></i> CSV تسویه‌ها
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small">از تاریخ</label>
                            <input type="date" name="from" class="form-control" value="{{ $from }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">تا تاریخ</label>
                            <input type="date" name="to" class="form-control" value="{{ $to }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">ارز</label>
                            <select name="currency_id" class="form-select">
                                <option value="">همه ارزها</option>
                                @foreach ($currencies as $c)
                                    <option value="{{ $c->id }}" {{ (int) $currencyId === $c->id ? 'selected' : '' }}>
                                        {{ $c->symbol }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-filter me-1"></i> اعمال فیلتر
                            </button>
                            <a href="{{ route('admin.bot.report.index') }}"
                                class="btn btn-outline-secondary ms-1">پاک‌کردن</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="row g-3 mb-2">
        @php
            $cards = [
                ['حجم خرید (USDT)', $fmt($kpi['bought_volume']), 'primary', 'fa-cart-shopping'],
                ['درآمد فروش (USDT)', $fmt($kpi['gross_revenue']), 'info', 'fa-sack-dollar'],
                ['سود خالص مجموعه (USDT)', $fmt($kpi['net_pnl']), $kpi['net_pnl'] >= 0 ? 'success' : 'danger', 'fa-chart-line'],
                ['کارمزد عملکرد (USDT)', $fmt($kpi['performance_fee']), 'warning', 'fa-percent'],
                ['کارمزد لغو (USDT)', $fmt($kpi['cancel_fee']), 'warning', 'fa-ban'],
                ['کارمزد صرافی (USDT)', $fmt($kpi['exchange_fee']), 'secondary', 'fa-building-columns'],
                ['کارمزد شبکه (USDT)', $fmt($kpi['network_fee']), 'secondary', 'fa-network-wired'],
                ['کارمزد انتقال (USDT)', $fmt($kpi['transfer_fee']), 'secondary', 'fa-right-left'],
                ['کاربران فعال', number_format($kpi['active_users']), 'dark', 'fa-users'],
                ['تعداد Skipped', number_format($kpi['skipped_count']), 'dark', 'fa-forward'],
                ['تعداد Collapsed', number_format($kpi['collapsed_count']), 'dark', 'fa-compress'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $color, $icon])
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center">
                        <span class="badge bg-label-{{ $color }} rounded p-2 me-3">
                            <i class="fas {{ $icon }} fa-lg"></i>
                        </span>
                        <div>
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="h5 mb-0 fw-bold text-{{ $color }}">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Per-coin breakdown --}}
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">تفکیک به ازای ارز</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ارز</th>
                                    <th>حجم خرید (USDT)</th>
                                    <th>مقدار خریداری‌شده</th>
                                    <th>سود/زیان خالص (USDT)</th>
                                    <th>کارمزد عملکرد</th>
                                    <th>کارمزد لغو</th>
                                    <th>Skipped</th>
                                    <th>Collapsed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($perCoin as $row)

                                    <tr>
                                        <td>
                                            @if ($row['logo'])
                                                <img src="{{ $row['logo'] }}" alt="" width="20" height="20"
                                                    class="rounded-circle me-1">
                                            @endif
                                            <strong>{{ $row['symbol'] ?? '—' }}</strong>

                                        </td>
                                        <td>{{ $fmt($row['bought_volume']) }}</td>
                                        <td>{{ $fmt($row['bought_amount'], 8) }}</td>
                                        <td class="{{ (float) $row['net_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $fmt($row['net_pnl']) }}
                                        </td>
                                        <td>{{ $fmt($row['performance_fee']) }}</td>
                                        <td>{{ $fmt($row['cancel_fee']) }}</td>
                                        <td><span class="badge bg-label-dark">{{ number_format($row['skipped_count']) }}</span>
                                        </td>
                                        <td><span
                                                class="badge bg-label-warning text-dark">{{ number_format($row['collapsed_count']) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">داده‌ای برای نمایش وجود ندارد.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Settlements --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">تسویه‌ها</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>کاربر</th>
                                    <th>ارز</th>
                                    <th>درآمد ناخالص</th>
                                    <th>بهای تمام‌شده</th>
                                    <th>کارمزد عملکرد</th>
                                    <th>کارمزد لغو</th>
                                    <th>سود/زیان خالص</th>
                                    <th>تاریخ تسویه</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($settlements as $s)
                                    <tr>
                                        <td>{{ $s->id }}</td>
                                        <td>{{ $s->user?->email ?? $s->user?->mobile ?? 'N/A' }}</td>
                                        <td>{{ $s->buyExecution?->currency?->symbol ?? '—' }}</td>
                                        <td>{{ $fmt($s->gross_revenue) }}</td>
                                        <td>{{ $fmt($s->cost_basis) }}</td>
                                        <td>{{ $fmt($s->performance_fee) }}</td>
                                        <td>{{ $fmt($s->cancel_fee) }}</td>
                                        <td class="{{ (float) $s->net_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $fmt($s->net_pnl) }}
                                        </td>
                                        <td>{{ optional($s->settled_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">هیچ تسویه‌ای یافت نشد.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $settlements->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
