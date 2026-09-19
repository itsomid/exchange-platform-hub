@extends('dashboard.layout.master')

@section('title', 'گزارش‌های ربات معاملاتی')

@php
    $exportQuery = http_build_query(
        array_filter([
            'from' => $from,
            'to' => $to,
            'currency_id' => $currencyId,
        ]),
    );
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
                                    <option value="{{ $c->id }}"
                                        {{ (int) $currencyId === $c->id ? 'selected' : '' }}>
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

    @php
        $perfPct = (float) $settings->performance_fee_percent;
        $refPct = (float) $settings->referral_fee_percent;

        // Profit of the winning settlements *before* the performance fee was taken.
        // Shown in the guide so the admin can reconcile the two headline numbers.
        $winnersProfitBeforeFee =
            $perfPct > 0 ? bcdiv(bcmul($kpi['performance_fee'], '100', 8), (string) $perfPct, 8) : '0';
    @endphp

    {{-- Admin guide --}}
    <div class="row">
        <div class="col-12">
            <div class="card mb-4 border-info">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-circle-info text-info me-1"></i> راهنمای خواندن این گزارش
                    </h5>
                    <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse"
                        data-bs-target="#report-guide" aria-expanded="false">
                        نمایش / پنهان‌کردن راهنما
                    </button>
                </div>
                <div class="collapse" id="report-guide">
                    <div class="card-body pt-0">

                        <div class="alert alert-warning mb-4">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-triangle-exclamation me-1"></i>
                                چرا «سود/زیان خالص کاربران» می‌تواند از «کارمزد عملکرد» کمتر باشد؟
                            </h6>
                            <p class="mb-2" style="line-height: 2;">
                                کارمزد عملکرد روی <strong>هر تسویه سودده به‌صورت جداگانه</strong> گرفته می‌شود
                                ({{ formatNumberTrimZeros($perfPct, 2) }}٪ از سود همان تسویه). تسویه‌های زیان‌ده
                                کارمزد عملکرد ندارند، اما زیانشان در «سود/زیان خالص کاربران» جمع می‌شود.
                                یعنی زیان یک معامله، کارمزدی را که از معامله سودده دیگری گرفته شده
                                <strong>پس نمی‌دهد</strong>. پس این دو عدد مستقل از هم حرکت می‌کنند و مقایسه‌شان
                                با هم بی‌معنی است.
                            </p>
                            <div class="row g-2 mt-1">
                                <div class="col-md-3 col-6">
                                    <div class="border rounded bg-white p-2 text-center h-100">
                                        <div class="text-muted" style="font-size: .72rem;">تسویه‌های سودده</div>
                                        <div class="fw-bold text-success">
                                            {{ number_format($kpi['winning_count']) }} مورد
                                        </div>
                                        <div class="text-success" style="font-size: .78rem;" dir="ltr">
                                            +{{ formatNumberTrimZeros($kpi['winning_pnl'], 2) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded bg-white p-2 text-center h-100">
                                        <div class="text-muted" style="font-size: .72rem;">تسویه‌های زیان‌ده</div>
                                        <div class="fw-bold text-danger">
                                            {{ number_format($kpi['losing_count']) }} مورد
                                        </div>
                                        <div class="text-danger" style="font-size: .78rem;" dir="ltr">
                                            {{ formatNumberTrimZeros($kpi['losing_pnl'], 2) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded bg-white p-2 text-center h-100">
                                        <div class="text-muted" style="font-size: .72rem;">جمع جبری = خالص کاربران</div>
                                        <div class="fw-bold {{ $kpi['net_pnl'] >= 0 ? 'text-success' : 'text-danger' }}"
                                            dir="ltr">
                                            {{ formatNumberTrimZeros($kpi['net_pnl'], 2) }}
                                        </div>
                                        <div class="text-muted" style="font-size: .72rem;">USDT</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded bg-white p-2 text-center h-100">
                                        <div class="text-muted" style="font-size: .72rem;">کارمزد عملکرد (فقط از سوددها)
                                        </div>
                                        <div class="fw-bold text-warning" dir="ltr">
                                            {{ formatNumberTrimZeros($kpi['performance_fee'], 2) }}
                                        </div>
                                        <div class="text-muted" style="font-size: .72rem;">USDT</div>
                                    </div>
                                </div>
                            </div>
                            <p class="mb-0 mt-3" style="font-size: .82rem; line-height: 2;">
                                <strong>راه راستی‌آزمایی:</strong> سود تسویه‌های سودده قبل از کسر کارمزد عملکرد باید
                                حدوداً
                                <span class="fw-bold"
                                    dir="ltr">{{ formatNumberTrimZeros($winnersProfitBeforeFee, 2) }}</span>
                                USDT بوده باشد
                                (کارمزد عملکرد ÷ {{ formatNumberTrimZeros($perfPct, 2) }}٪). از این مقدار،
                                {{ formatNumberTrimZeros($perfPct, 2) }}٪ سهم پلتفرم شده و بقیه یعنی
                                <span class="fw-bold text-success" dir="ltr">
                                    {{ formatNumberTrimZeros(bcsub($winnersProfitBeforeFee, $kpi['performance_fee'], 8), 2) }}
                                </span>
                                به کاربران رسیده — که با ستون «تسویه‌های سودده» بالا هم‌خوان است. تفاوت آن با عدد نهایی،
                                دقیقاً همان زیان تسویه‌های زیان‌ده است.
                            </p>
                        </div>

                        <h6 class="mb-2"><i class="fas fa-database text-muted me-1"></i> هر عدد از کجا می‌آید؟</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered align-middle mb-0" style="font-size: .82rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 22%;">شاخص</th>
                                        <th style="width: 26%;">منبع داده</th>
                                        <th style="width: 18%;">فیلتر تاریخ روی</th>
                                        <th>توضیح</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>حجم خرید</td>
                                        <td>اجراهای خرید (وضعیت BOUGHT)</td>
                                        <td>تاریخ ایجاد اجرا</td>
                                        <td>مبلغ تخصیص‌یافته به خریدها؛ شامل پوزیشن‌هایی که هنوز فروخته نشده‌اند.</td>
                                    </tr>
                                    <tr>
                                        <td>درآمد فروش / بهای تمام‌شده / همه کارمزدها / سود خالص</td>
                                        <td>جدول تسویه‌ها</td>
                                        <td>تاریخ تسویه</td>
                                        <td>فقط پوزیشن‌های <strong>بسته‌شده</strong> (فروش‌رفته یا لغوشده) در این اعداد
                                            هستند.</td>
                                    </tr>
                                    <tr>
                                        <td>کارمزد انتقال</td>
                                        <td>تراکنش‌های نوع «کارمزد انتقال ربات»</td>
                                        <td>تاریخ تراکنش</td>
                                        <td>مربوط به واریز/برداشت بین کیف اصلی و کیف ربات است و به ارز خاصی وابسته نیست.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>کاربران فعال / Skipped / Failed / Collapsed</td>
                                        <td>اجراهای خرید</td>
                                        <td>تاریخ ایجاد اجرا</td>
                                        <td>شاخص‌های عملیاتی ربات، نه مالی.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="mb-2"><i class="fas fa-list-check text-muted me-1"></i> نکته‌های مهم در تفسیر اعداد
                        </h6>
                        <ul class="mb-0 ps-3" style="font-size: .84rem; line-height: 2.1;">
                            <li>
                                <strong>«حجم خرید» و «درآمد فروش» هم‌دوره نیستند.</strong> حجم خرید بر اساس تاریخ خرید
                                فیلتر می‌شود و درآمد فروش بر اساس تاریخ تسویه. اختلاف بزرگ بین این دو معمولاً یعنی بیشتر
                                پوزیشن‌ها هنوز باز هستند، نه اینکه ضرر داده‌اند.
                            </li>
                            <li>
                                <strong>سود/زیان تحقق‌نیافته در این صفحه نیست.</strong> پوزیشن‌های باز تا زمان فروش هیچ
                                اثری روی هیچ‌کدام از اعداد مالی ندارند.
                            </li>
                            <li>
                                <strong>«سود/زیان خالص کاربران» سود پلتفرم نیست.</strong> این عدد چیزی است که به موجودی
                                کاربران اضافه (یا از آن کم) شده و کارمزد عملکرد قبلاً از آن کسر شده است. سود خود مجموعه
                                در کارت «درآمد خالص پلتفرم» است.
                            </li>
                            <li>
                                <strong>کارمزد صرافی مرجع و کارمزد شبکه درآمد نیستند.</strong> این‌ها به صرافی مرجع و
                                شبکه بلاک‌چین پرداخت می‌شوند و فقط از سود کاربر کم می‌شوند؛ به همین دلیل در «درآمد خالص
                                پلتفرم» حساب نشده‌اند.
                            </li>
                            <li>
                                <strong>کارمزد صرافی مرجعِ این صفحه فقط سهم پوزیشن‌های تسویه‌شده است.</strong> کارمزد
                                خریدهایی که هنوز فروخته نشده‌اند، تا زمان تسویه در این عدد دیده نمی‌شود.
                            </li>
                            <li>
                                <strong>کارمزد معرف از دل کارمزد عملکرد برداشته می‌شود</strong>
                                ({{ formatNumberTrimZeros($refPct, 2) }}٪ از سود، حداکثر تا سقف کارمزد عملکرد) و هیچ اثری
                                روی سود کاربر ندارد؛ فقط سهم پلتفرم را کم می‌کند.
                            </li>
                            <li>
                                <strong>در لغو سفارش،</strong> کارمزد عملکرد صفر است و سود خالص کاربر منفیِ کارمزد لغو
                                می‌شود؛ پس تعداد زیاد لغو، «سود خالص کاربران» را پایین می‌آورد بدون اینکه کارمزد عملکرد
                                را بالا ببرد.
                            </li>
                            <li>
                                <strong>کاربران فعال شامل اجراهای Skipped هم می‌شود</strong> — یعنی کاربری که ربات برایش
                                هیچ خریدی انجام نداده هم شمرده می‌شود.
                            </li>
                            <li>
                                <strong>با انتخاب فیلتر ارز، «کارمزد انتقال» نمایش داده نمی‌شود</strong> چون این کارمزد
                                به ارز خاصی تعلق ندارد و در آن حالت از «درآمد خالص پلتفرم» هم کنار گذاشته می‌شود.
                            </li>
                        </ul>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI cards --}}
    @php
        $netPnlColor = $kpi['net_pnl'] >= 0 ? 'success' : 'danger';
        $cardGroups = [
            [
                'title' => 'گردش معاملات کاربران',
                'cards' => [
                    [
                        'حجم خرید (USDT)',
                        formatNumberTrimZeros($kpi['bought_volume'], 2),
                        'primary',
                        'fa-cart-shopping',
                        'مجموع مبلغ تخصیص‌یافته به اجراهای خریدِ موفق (' .
                        number_format($kpi['bought_count']) .
                        ' اجرا) در بازه انتخابی، بر اساس تاریخ خرید. پوزیشن‌های هنوز فروخته‌نشده هم در این عدد هستند.',
                    ],
                    [
                        'درآمد فروش (USDT)',
                        formatNumberTrimZeros($kpi['gross_revenue'], 2),
                        'info',
                        'fa-sack-dollar',
                        'مجموع (مقدار فروخته‌شده × قیمت فروش) در تسویه‌های همین بازه. فقط پوزیشن‌های بسته‌شده.',
                    ],
                    [
                        'بهای تمام‌شده فروش‌ها (USDT)',
                        formatNumberTrimZeros($kpi['cost_basis'], 2),
                        'info',
                        'fa-scale-balanced',
                        'مجموع (مقدار فروخته‌شده × میانگین قیمت خرید) برای همان تسویه‌ها. اختلاف آن با درآمد فروش، سود/زیان قیمتی قبل از کارمزدهاست.',
                    ],
                    [
                        'سود/زیان خالص کاربران (USDT)',
                        formatNumberTrimZeros($kpi['net_pnl'], 2),
                        $netPnlColor,
                        'fa-chart-line',
                        'مجموع net_pnl تسویه‌ها: (درآمد فروش − بهای تمام‌شده) منهای کارمزدهای شبکه، صرافی مرجع، عملکرد و لغو. این عدد سود پلتفرم نیست؛ سود کاربران است و کارمزد عملکرد از آن کسر شده.',
                    ],
                ],
            ],
            [
                'title' => 'درآمد پلتفرم',
                'cards' => [
                    [
                        'درآمد خالص پلتفرم (USDT)',
                        formatNumberTrimZeros($kpi['platform_revenue'], 2),
                        'success',
                        'fa-building-columns',
                        'کارمزد عملکرد + لغو + انتقال، منهای کارمزد معرف. کارمزد صرافی مرجع و شبکه چون به بیرون پرداخت می‌شوند در این عدد نیستند.',
                    ],
                    [
                        'کارمزد عملکرد (USDT)',
                        formatNumberTrimZeros($kpi['performance_fee'], 2),
                        'warning',
                        'fa-percent',
                        formatNumberTrimZeros($perfPct, 2) .
                        '٪ از سود هر تسویه سودده، بعد از کسر کارمزد شبکه/صرافی. روی تسویه‌های زیان‌ده صفر است.',
                    ],
                    [
                        'کارمزد لغو (USDT)',
                        formatNumberTrimZeros($kpi['cancel_fee'], 2),
                        'warning',
                        'fa-ban',
                        'کارمزدی که هنگام لغو سفارش فروش از کاربر گرفته می‌شود. در تسویه‌های لغو، سود خالص کاربر منفیِ همین مقدار است.',
                    ],
                    [
                        'کارمزد انتقال (USDT)',
                        $kpi['transfer_fee'] === null ? '—' : formatNumberTrimZeros($kpi['transfer_fee'], 2),
                        'warning',
                        'fa-right-left',
                        $kpi['transfer_fee'] === null
                            ? 'این کارمزد به ارز خاصی وابسته نیست، بنابراین با فعال‌بودن فیلتر ارز نمایش داده نمی‌شود. برای دیدن آن فیلتر ارز را بردارید.'
                            : 'کارمزد جابه‌جایی موجودی بین کیف پول اصلی و کیف پول ربات، بر اساس تاریخ تراکنش.',
                    ],
                    [
                        'کارمزد معرف (پرداختی) (USDT)',
                        formatNumberTrimZeros($kpi['referral_fee'], 2),
                        'info',
                        'fa-user-group',
                        formatNumberTrimZeros($refPct, 2) .
                        '٪ از سود، که از دل کارمزد عملکرد به معرف پرداخت می‌شود. سود کاربر را تغییر نمی‌دهد و فقط سهم پلتفرم را کم می‌کند.',
                    ],
                ],
            ],
            [
                'title' => 'هزینه‌های پرداختی به بیرون',
                'cards' => [
                    [
                        'کارمزد صرافی مرجع (USDT)',
                        formatNumberTrimZeros($kpi['exchange_fee'], 2),
                        'secondary',
                        'fa-arrow-right-arrow-left',
                        'کارمزد معامله در صرافی مرجع (خرید + فروش) که در تسویه‌ها ثبت شده. کارمزد خریدهای هنوز فروخته‌نشده در این عدد نیست.',
                    ],
                    [
                        'کارمزد شبکه (USDT)',
                        formatNumberTrimZeros($kpi['network_fee'], 2),
                        'secondary',
                        'fa-network-wired',
                        'کارمزد برداشت/انتقال روی بلاک‌چین که هنگام تسویه از سود کاربر کم شده است.',
                    ],
                ],
            ],
            [
                'title' => 'عملکرد عملیاتی ربات',
                'cards' => [
                    [
                        'کاربران فعال',
                        number_format($kpi['active_users']),
                        'dark',
                        'fa-users',
                        'کاربرانی که در این بازه حداقل یک اجرای خرید داشته‌اند — شامل اجراهای Skipped و Failed هم می‌شود.',
                    ],
                    [
                        'تسویه‌ها',
                        number_format($kpi['settlement_count']),
                        'dark',
                        'fa-receipt',
                        number_format($kpi['winning_count']) .
                        ' تسویه سودده و ' .
                        number_format($kpi['losing_count']) .
                        ' تسویه زیان‌ده در این بازه.',
                    ],
                    [
                        'تعداد Skipped',
                        number_format($kpi['skipped_count']),
                        'dark',
                        'fa-forward',
                        'اجراهایی که ربات عمداً خرید نکرده (مثلاً نبود نقدینگی یا نرسیدن به حداقل مبلغ سفارش). علت هر مورد در CSV اجراها ستون «علت شکست/توضیح» است.',
                    ],
                    [
                        'تعداد Failed',
                        number_format($kpi['failed_count']),
                        'danger',
                        'fa-circle-exclamation',
                        'اجراهایی که با خطا شکست خورده‌اند (مثلاً نبود قیمت زنده). نیاز به بررسی دارند.',
                    ],
                    [
                        'تعداد Collapsed',
                        number_format($kpi['collapsed_count']),
                        'dark',
                        'fa-compress',
                        'اجراهایی که تعداد پله‌های فروششان از تعداد اولیه کمتر شده، چون حجم هر پله به حداقل مبلغ سفارش نمی‌رسیده و پله‌ها ادغام شده‌اند.',
                    ],
                ],
            ],
        ];
    @endphp

    @foreach ($cardGroups as $group)
        <div class="d-flex align-items-center gap-2 mb-2 mt-1">
            <span class="text-muted small fw-bold">{{ $group['title'] }}</span>
            <hr class="flex-grow-1 my-0">
        </div>
        <div class="row g-3 mb-3">
            @foreach ($group['cards'] as [$label, $value, $color, $icon, $hint])
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <span class="badge bg-label-{{ $color }} rounded p-2 me-3">
                                <i class="fas {{ $icon }} fa-lg"></i>
                            </span>
                            <div>
                                <div class="text-muted small">
                                    {{ $label }}
                                    <i class="fas fa-circle-info text-muted js-kpi-hint" role="button" tabindex="0"
                                        style="font-size: .7rem;" data-bs-toggle="popover" data-bs-trigger="focus"
                                        data-bs-placement="bottom" data-bs-title="{{ $label }}"
                                        data-bs-content="{{ $hint }}"></i>
                                </div>
                                <div class="h5 mb-0 fw-bold text-{{ $color }}" dir="ltr">{{ $value }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    {{-- Per-coin breakdown --}}
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">تفکیک به ازای ارز</h5>
                    <small class="text-muted">
                        ستون‌های «حجم خرید» و «مقدار خریداری‌شده» از اجراهای خرید (تاریخ خرید) می‌آیند و بقیه ستون‌ها از
                        تسویه‌ها (تاریخ تسویه). به همین دلیل ممکن است ارزی حجم خرید داشته باشد ولی سود/زیانش هنوز صفر
                        باشد — یعنی هنوز فروخته نشده است.
                    </small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ارز</th>
                                    <th>حجم خرید (USDT)</th>
                                    <th>مقدار خریداری‌شده</th>
                                    <th>
                                        سود/زیان خالص (USDT)
                                        <i class="fas fa-circle-info text-muted js-kpi-hint" role="button"
                                            tabindex="0" style="font-size: .7rem;" data-bs-toggle="popover"
                                            data-bs-trigger="focus" data-bs-placement="bottom"
                                            data-bs-title="سود/زیان خالص هر ارز"
                                            data-bs-content="سهم کاربران از این ارز بعد از کسر همه کارمزدها. جمع جبری تسویه‌های سودده و زیان‌ده همان ارز است، پس می‌تواند از کارمزد عملکرد همان سطر کمتر باشد."></i>
                                    </th>
                                    <th>کارمزد عملکرد</th>
                                    <th>کارمزد لغو</th>
                                    <th>
                                        Skipped
                                        <i class="fas fa-circle-info text-muted js-kpi-hint" role="button"
                                            tabindex="0" style="font-size: .7rem;" data-bs-toggle="popover"
                                            data-bs-trigger="focus" data-bs-placement="bottom" data-bs-title="Skipped"
                                            data-bs-content="تعداد اجراهایی که ربات برای این ارز عمداً خرید نکرده است."></i>
                                    </th>
                                    <th>
                                        Collapsed
                                        <i class="fas fa-circle-info text-muted js-kpi-hint" role="button"
                                            tabindex="0" style="font-size: .7rem;" data-bs-toggle="popover"
                                            data-bs-trigger="focus" data-bs-placement="bottom" data-bs-title="Collapsed"
                                            data-bs-content="تعداد اجراهایی که پله‌های فروششان به‌خاطر حداقل مبلغ سفارش ادغام شده و از تعداد اولیه کمتر شده است."></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($perCoin as $row)
                                    <tr>
                                        <td>
                                            @if ($row['logo'])
                                                <img src="{{ $row['logo'] }}" alt="" width="20"
                                                    height="20" class="rounded-circle me-1">
                                            @endif
                                            <strong>{{ $row['symbol'] ?? '—' }}</strong>

                                        </td>
                                        <td>{{ formatNumberTrimZeros($row['bought_volume']) }}</td>
                                        <td>{{ formatNumberTrimZeros($row['bought_amount']) }}</td>
                                        <td class="{{ (float) $row['net_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ formatNumberTrimZeros($row['net_pnl']) }}
                                        </td>
                                        <td>{{ formatNumberTrimZeros($row['performance_fee']) }}</td>
                                        <td>{{ formatNumberTrimZeros($row['cancel_fee']) }}</td>
                                        <td><span
                                                class="badge bg-label-dark">{{ number_format($row['skipped_count']) }}</span>
                                        </td>
                                        <td><span
                                                class="badge bg-label-warning text-dark">{{ number_format($row['collapsed_count']) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">داده‌ای برای نمایش وجود
                                            ندارد.</td>
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
                    <small class="text-muted">
                        هر سطر یک فروش یا لغو تمام‌شده است. رابطه ستون‌ها:
                        <span dir="ltr" class="fw-bold">net_pnl = درآمد ناخالص − بهای تمام‌شده − شبکه − صرافی −
                            عملکرد −
                            لغو</span>.
                        کارمزد معرف در این رابطه نیست چون از سهم پلتفرم برداشته می‌شود؛ برای دیدن آن از خروجی CSV تسویه‌ها
                        استفاده کنید.
                    </small>
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
                                    <th>بهای تمام‌شده (Cost_basis)</th>
                                    <th>کارمزد شبکه</th>
                                    <th>کارمزد صرافی</th>
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
                                        <td>{{ $s->user?->email ?? ($s->user?->mobile ?? 'N/A') }}</td>
                                        <td>{{ $s->buyExecution?->currency?->symbol ?? '—' }}</td>
                                        <td>{{ formatNumberTrimZeros($s->gross_revenue) }}</td>
                                        <td>{{ formatNumberTrimZeros($s->cost_basis) }}</td>
                                        <td>{{ formatNumberTrimZeros($s->network_fee) }}</td>
                                        <td>{{ formatNumberTrimZeros($s->exchange_fee) }}</td>
                                        <td>{{ formatNumberTrimZeros($s->performance_fee) }}</td>
                                        <td>{{ formatNumberTrimZeros($s->cancel_fee) }}</td>
                                        <td class="{{ (float) $s->net_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ formatNumberTrimZeros($s->net_pnl) }}
                                        </td>
                                        <td>{{ optional($s->settled_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">هیچ تسویه‌ای یافت نشد.</td>
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

@section('vendor-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof bootstrap === 'undefined') return;

            document.querySelectorAll('.js-kpi-hint').forEach(function(el) {
                new bootstrap.Popover(el, {
                    customClass: 'popover-kpi-hint',
                });
            });
        });
    </script>
@endsection

@section('vendor-style')
    <style>
        .popover-kpi-hint {
            direction: rtl;
            text-align: right;
            font-size: .8rem;
            max-width: 320px;
        }

        .popover-kpi-hint .popover-header {
            font-size: .8rem;
            font-weight: 700;
        }

        .js-kpi-hint {
            cursor: pointer;
        }
    </style>
@endsection
