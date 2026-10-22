{{--
    Per-tier report of one sell-migration run. Trigger buttons (class js-migration-report) carry:
        data-report-url, data-title
--}}
<div class="modal fade" id="migrationReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="migrationReportTitle">گزارش انتقال</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="migrationReportSummary"></div>

                <div class="row g-2 align-items-end mb-2" id="migrationReportFilters">
                    <div class="col-md-5">
                        <label class="form-label small mb-1" for="reportSearch">جستجو</label>
                        <input type="text" class="form-control form-control-sm" id="reportSearch"
                            placeholder="شماره پله، سفارش ربات، کاربر، شماره سفارش صرافی یا متن خطا">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1" for="reportOutcome">نتیجه</label>
                        <select class="form-select form-select-sm" id="reportOutcome">
                            <option value="">همه</option>
                            <option value="placed">ثبت Limit</option>
                            <option value="completed">تکمیل با Market</option>
                            <option value="merged">ادغام‌شده</option>
                            <option value="closed">بسته و برگشت</option>
                            <option value="waiting">در انتظار ثبت</option>
                            <option value="failed">ناموفق</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="reportClear">
                            <i class="fa-light fa-xmark me-1"></i> پاک کردن فیلترها
                        </button>
                        <small class="text-muted align-self-center" id="reportCount"></small>
                    </div>
                </div>

                <div class="table-responsive" style="max-height:55vh">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light" style="position:sticky;top:0;z-index:1">
                            <tr>
                                <th>پله</th>
                                <th>کاربر / سفارش ربات</th>
                                <th>اقدام</th>
                                <th>نتیجه</th>
                                <th style="min-width:320px">جزئیات</th>
                                <th>وضعیت فعلی پله</th>
                            </tr>
                        </thead>
                        <tbody id="migrationReportBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modalEl = document.getElementById('migrationReportModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;

            var modal = new bootstrap.Modal(modalEl);
            var titleEl = document.getElementById('migrationReportTitle');
            var summaryEl = document.getElementById('migrationReportSummary');
            var filtersEl = document.getElementById('migrationReportFilters');
            var bodyEl = document.getElementById('migrationReportBody');
            var searchEl = document.getElementById('reportSearch');
            var outcomeEl = document.getElementById('reportOutcome');
            var countEl = document.getElementById('reportCount');
            var rows = [];
            var report = null;

            var outcomes = {
                placed: ['bg-label-info', 'ثبت Limit'],
                completed: ['bg-label-warning', 'تکمیل با Market'],
                merged: ['bg-label-secondary', 'ادغام‌شده'],
                closed: ['bg-label-primary', 'بسته و برگشت'],
                waiting: ['bg-label-dark', 'در انتظار ثبت'],
                failed: ['bg-label-danger', 'ناموفق'],
            };
            var actions = {
                limit: 'Limit',
                market: 'Market',
                merge: 'ادغام',
                close: 'بستن',
                wait: 'انتظار'
            };
            var statuses = {
                OPEN: 'bg-label-info',
                FILLED: 'bg-label-success',
                CANCELED: 'bg-label-secondary'
            };

            function toast(text) {
                Toastify({
                    text: text,
                    duration: 5000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    style: {
                        background: '#EA5455'
                    },
                }).showToast();
            }

            function esc(v) {
                var d = document.createElement('div');
                d.textContent = v === null || v === undefined ? '' : String(v);
                return d.innerHTML;
            }

            function fmt(v, maxDec) {
                var n = parseFloat(v);
                if (!isFinite(n)) return '—';
                return n.toLocaleString('en-US', {
                    maximumFractionDigits: maxDec === undefined ? 8 : maxDec
                });
            }

            function line(label, value) {
                return '<div><small class="text-muted">' + label + ':</small> <span class="font-number">' + value +
                    '</span></div>';
            }

            function stat(label, value, extraClass) {
                return '<div class="col-6 col-md-2"><div class="p-2 border rounded h-100 text-center ' + (
                        extraClass || '') + '">' +
                    '<small class="text-muted d-block">' + label + '</small>' +
                    '<strong class="font-number">' + value + '</strong></div></div>';
            }

            function abovePercent(r) {
                if (r.above_live_percent === null || r.above_live_percent === undefined) return '';
                var p = parseFloat(r.above_live_percent);
                var limit = report.price_limit;
                var over = limit && p > parseFloat(limit.max_percent_above);
                return '<div class="small ' + (over ? 'text-danger fw-semibold' : 'text-muted') + '">' +
                    '<span class="font-number" dir="ltr">' + (p >= 0 ? '+' : '') + fmt(p, 2) + '٪</span> ' +
                    (p >= 0 ? 'بالاتر' : 'پایین‌تر') + ' از قیمت لحظه‌ای هنگام اجرا' +
                    (limit ? ' (سقف Binance: <span class="font-number" dir="ltr">+' + fmt(limit.max_percent_above,
                        2) + '٪</span>)' : '') +
                    '</div>';
            }

            function details(r) {
                var exchange = (r.current && r.current.exchange) || 'binance';
                switch (r.outcome) {
                    case 'placed':
                        return '<div class="fw-semibold mb-1">سفارش فروش Limit در ' + esc(exchange) +
                            ' ثبت شد</div>' +
                            line('شماره سفارش', esc(r.new_exchange_order_id)) +
                            line('قیمت', fmt(r.target_price) + ' USDT') + abovePercent(r) +
                            line('مقدار', fmt(r.amount)) +
                            line('ارزش', fmt(parseFloat(r.amount) * parseFloat(r.target_price), 4) + ' USDT');
                    case 'completed':
                        return '<div class="fw-semibold mb-1">تارگت خورده بود؛ با سفارش Market فروخته و تسویه شد</div>' +
                            line('شماره سفارش', esc(r.new_exchange_order_id)) +
                            line('مقدار پرشده', fmt(r.filled_amount)) +
                            line('میانگین قیمت', fmt(r.avg_price) + ' USDT') +
                            line('کارمزد صرافی', fmt(r.exchange_fee)) +
                            line('قیمت تارگت', fmt(r.target_price) + ' USDT');
                    case 'merged':
                        return '<div class="fw-semibold mb-1">کمتر از حداقل Binance بود و در پله #' + esc(r
                                .merge_into) + ' ادغام شد</div>' +
                            line('مقدار منتقل‌شده', fmt(r.amount)) +
                            '<small class="text-muted">برای این ردیف تسویه‌ای ثبت نمی‌شود؛ فروش از طریق پله مقصد انجام می‌شود.</small>';
                    case 'closed':
                        var close = report.migration.close || {};
                        return '<div class="fw-semibold mb-1">کمتر از حداقل Binance بود؛ بسته و به کیف پول کاربر برگشت</div>' +
                            line('قیمت تسویه', fmt(r.price) + ' USDT' + (close.price_source === 'market_sell' ?
                                ' (فروش Market، سفارش ' + esc(close.exchange_order_id) + ')' :
                                ' (قیمت لحظه‌ای)')) +
                            line('مقدار', fmt(r.amount)) +
                            line('مبلغ برگشتی', fmt(r.refund, 4) + ' USDT') +
                            line('سود خالص', fmt(r.net_pnl, 4) + ' USDT');
                    case 'waiting':
                        return '<div class="fw-semibold mb-1">قیمتش بالاتر از سقف Binance بود؛ ثبت نشد و «در انتظار ثبت» ماند</div>' +
                            line('قیمت فروش', fmt(r.target_price) + ' USDT') + abovePercent(r) +
                            line('مقدار', fmt(r.amount)) +
                            '<small class="text-muted">وقتی قیمت بازار به اندازه کافی بالا بیاید، خودکار در Binance ثبت می‌شود.</small>' +
                            (r.error && r.error.indexOf('PERCENT_PRICE') !== -1 ?
                                '<div><small class="text-muted" dir="ltr">' + esc(r.error) + '</small></div>' : '');
                    case 'failed':
                        return '<div class="small fw-semibold text-danger mb-1">' + esc(r.error_hint || r.error ||
                                'خطای نامشخص') + '</div>' +
                            (r.target_price ? line('قیمت درخواستی', fmt(r.target_price) + ' USDT') + abovePercent(
                                r) : '') +
                            (r.amount ? line('مقدار', fmt(r.amount)) : '') +
                            (r.error_hint ? '<div><small class="text-muted" dir="ltr">' + esc(r.error) +
                                '</small></div>' : '');
                }
                return '—';
            }

            function target(r) {
                if (r.target_type === 'percent') {
                    return fmt(r.target_value, 4) + '٪ <small class="text-muted">(خرید ' + fmt(r.avg_buy_price) +
                        ')</small>';
                }
                return r.target_value ? fmt(r.target_value) : '—';
            }

            function current(r) {
                var c = r.current;
                if (!c) return '<small class="text-muted">ردیف یافت نشد</small>';
                return '<span class="badge ' + (statuses[c.status] || 'bg-label-secondary') + '">' + esc(c.status) +
                    '</span>' +
                    (c.status === 'OPEN' && c.placement_status === 'WAITING' ?
                        ' <span class="badge bg-label-dark">در انتظار ثبت</span>' : '') +
                    '<div><small class="text-muted">' + esc(c.exchange) + '</small></div>' +
                    (c.exchange_order_id ? '<div><small class="font-number">' + esc(c.exchange_order_id) +
                        '</small></div>' : '');
            }

            function renderRow(r) {
                var o = outcomes[r.outcome] || ['bg-label-secondary', r.outcome];
                var cls = r.outcome === 'failed' ? 'table-danger' : '';
                return '<tr class="' + cls + '">' +
                    '<td class="font-number">#' + esc(r.sell_order_id) +
                    (r.share_percent ? '<div><small class="text-muted">' + fmt(r.share_percent, 2) +
                        '٪</small></div>' : '') +
                    '<div><small class="text-muted">تارگت: ' + target(r) + '</small></div></td>' +
                    '<td><small>' + esc(r.user_label || '—') + '</small>' +
                    (r.bot_order_url ? '<div><a class="small font-number" target="_blank" href="' + esc(r
                        .bot_order_url) + '">سفارش #' + esc(r.bot_order_id) + '</a></div>' : '') +
                    (r.old_exchange_order_id ?
                        '<div><small class="text-muted">CoinEx قبلی: <span class="font-number">' + esc(r
                            .old_exchange_order_id) + '</span></small></div>' : '') +
                    '</td>' +
                    '<td class="text-nowrap">' + esc(actions[r.action] || r.action) + '</td>' +
                    '<td class="text-nowrap"><span class="badge ' + o[0] + '">' + o[1] + '</span></td>' +
                    '<td>' + details(r) + '</td>' +
                    '<td class="text-nowrap">' + current(r) + '</td>' +
                    '</tr>';
            }

            function haystack(r) {
                return [r.sell_order_id, r.bot_order_id, r.user_label, r.new_exchange_order_id, r
                    .old_exchange_order_id,
                    r.error, r.current && r.current.exchange_order_id
                ].join(' ').toLowerCase();
            }

            function applyFilters() {
                var q = searchEl.value.trim().toLowerCase();
                var outcome = outcomeEl.value;
                var shown = rows.filter(function(r) {
                    return (!outcome || r.outcome === outcome) && (!q || r._search.indexOf(q) !== -1);
                });
                bodyEl.innerHTML = shown.length ?
                    shown.map(renderRow).join('') :
                    '<tr><td colspan="6" class="text-center text-muted py-4">موردی یافت نشد.</td></tr>';
                countEl.textContent = shown.length + ' از ' + rows.length + ' پله';
            }

            function renderSummary(m) {
                var html = '';
                if (m.error) {
                    html +=
                        '<div class="alert alert-danger small"><i class="fas fa-exclamation-triangle me-1"></i>' +
                        esc(m.error) + '</div>';
                }
                html += '<div class="row g-2 mb-3">' +
                    stat('پله‌ها', fmt(m.tiers_count, 0)) +
                    stat('ثبت Limit', fmt(m.placed_count, 0), 'border-info') +
                    stat('تکمیل با Market', fmt(m.completed_count, 0), 'border-warning') +
                    stat('ادغام‌شده', fmt(m.merged_count, 0), 'border-secondary') +
                    stat('بسته و برگشت', fmt(m.closed_count, 0), 'border-primary') +
                    stat('در انتظار ثبت', fmt(m.waiting_count, 0), 'border-dark') +
                    stat('ناموفق', fmt(m.failed_count, 0), m.failed_count ? 'border-danger' : '') +
                    '</div>';
                if (m.live_price) {
                    html +=
                        '<div class="small text-muted mb-2">قیمت لحظه‌ای هنگام اجرا: <span class="font-number">' +
                        fmt(m.live_price) + '</span> USDT</div>';
                }

                var reasons = {};
                rows.forEach(function(r) {
                    if (r.outcome !== 'failed') return;
                    var key = r.error || 'خطای نامشخص';
                    reasons[key] = reasons[key] || {
                        count: 0,
                        hint: r.error_hint
                    };
                    reasons[key].count++;
                });
                var keys = Object.keys(reasons).sort(function(a, b) {
                    return reasons[b].count - reasons[a].count;
                });
                if (keys.length) {
                    html += '<div class="alert alert-warning small"><strong>دلایل شکست:</strong><ul class="mb-0">' +
                        keys.map(function(k) {
                            return '<li><span class="font-number">' + reasons[k].count + '</span> پله — ' +
                                esc(reasons[k].hint || k) + (reasons[k].hint ? ' <code dir="ltr">' + esc(k) +
                                    '</code>' : '') + '</li>';
                        }).join('') + '</ul></div>';
                }

                var priceFailed = rows.filter(function(r) {
                    return (r.outcome === 'waiting' || (r.outcome === 'failed' && (r.error || '').indexOf('PERCENT_PRICE') !== -1)) &&
                        r.above_live_percent !== null;
                }).map(function(r) {
                    return parseFloat(r.above_live_percent);
                });
                if (priceFailed.length) {
                    var limit = report.price_limit;
                    var sym = esc(m.currency_symbol);
                    html += '<div class="alert alert-info small">';
                    if (limit) {
                        html += '<div class="mb-1"><strong>سقف قیمت فروش در Binance برای ' + sym + ':</strong> ' +
                            'حداکثر <span class="font-number">' + fmt(limit.multiplier_up, 4) +
                            '</span> برابر میانگین قیمت ' +
                            (limit.avg_price_mins ? '<span class="font-number">' + limit.avg_price_mins +
                                '</span> دقیقه اخیر' : 'اخیر') +
                            '، یعنی سفارش فروش حداکثر <strong class="font-number" dir="ltr">+' + fmt(limit
                                .max_percent_above, 2) + '٪</strong> ' +
                            'بالاتر از قیمت بازار ثبت می‌شود.</div>';
                        if (limit.max_price) {
                            html += '<div class="mb-1">با میانگین فعلی <span class="font-number">' + fmt(limit
                                    .avg_price) + '</span> USDT، ' +
                                'بالاترین قیمت فروش قابل ثبت الان <strong class="font-number">' + fmt(limit
                                    .max_price) + '</strong> USDT است.</div>';
                        }
                    } else {
                        html += '<div class="mb-1">سقف قیمت این ارز از Binance دریافت نشد.</div>';
                    }
                    html += '<div>پله‌های بالای سقف (در انتظار ثبت یا ردشده) بین <span class="font-number" dir="ltr">+' + fmt(Math.min.apply(
                            null, priceFailed), 2) +
                        '٪</span> تا <span class="font-number" dir="ltr">+' + fmt(Math.max.apply(null, priceFailed),
                            2) +
                        '٪</span> بالاتر از قیمت لحظه‌ای هنگام اجرا بودند.</div></div>';
                }
                summaryEl.innerHTML = html;
            }

            function load(url) {
                summaryEl.innerHTML =
                    '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
                filtersEl.classList.add('d-none');
                bodyEl.innerHTML = '';
                countEl.textContent = '';

                fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(res) {
                        return res.json().catch(function() {
                            return null;
                        });
                    })
                    .then(function(d) {
                        if (!d || !d.ok) throw new Error((d && (d.error || d.message)) ||
                            'دریافت گزارش ناموفق بود.');
                        report = d;
                        rows = (d.rows || []).map(function(r) {
                            r._search = haystack(r);
                            return r;
                        });
                        renderSummary(d.migration);
                        filtersEl.classList.remove('d-none');
                        applyFilters();
                    })
                    .catch(function(e) {
                        summaryEl.innerHTML = '<div class="alert alert-danger mb-0">' + esc(e.message) +
                            '</div>';
                        toast(e.message);
                    });
            }

            searchEl.addEventListener('input', applyFilters);
            outcomeEl.addEventListener('change', applyFilters);
            document.getElementById('reportClear').addEventListener('click', function() {
                searchEl.value = '';
                outcomeEl.value = '';
                applyFilters();
            });

            document.querySelectorAll('.js-migration-report').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    titleEl.textContent = btn.dataset.title;
                    searchEl.value = '';
                    outcomeEl.value = '';
                    modal.show();
                    load(btn.dataset.reportUrl);
                });
            });
        });
    </script>
@endpush
