@extends('dashboard.layout.master')

@section('title', 'آزمایشگاه تست ربات')

@section('content')
    <style>
        .lab-card {
            margin-bottom: 1rem;
        }

        .lab-table {
            font-size: .82rem;
            font-family: ui-monospace, monospace;
        }

        .lab-table th,
        .lab-table td {
            vertical-align: middle;
            padding: .45rem .6rem;
        }

        .price-cell {
            font-weight: 600;
        }

        .tier-row td {
            background: #f7f9fc;
            font-size: .72rem;
            color: #5e6770;
        }

        .lab-stat {
            display: flex;
            justify-content: space-between;
            padding: .35rem 0;
            border-bottom: 1px dashed #eee;
        }

        .lab-stat:last-child {
            border-bottom: 0;
        }

        .lab-stat .k {
            color: #6c757d;
            font-size: .78rem;
        }

        .lab-stat .v {
            font-family: ui-monospace, monospace;
        }

        .lab-actions .btn {
            margin-inline-start: .25rem;
        }

        .lab-empty {
            padding: 1.5rem;
            text-align: center;
            color: #9aa3ad;
            font-size: .85rem;
        }

        .lab-bump .input-group {
            flex-wrap: nowrap;
            max-width: 230px;
            margin-inline-start: auto;
        }

        .lab-bump input {
            max-width: 70px;
        }

        .lab-pill {
            display: inline-block;
            font-size: .7rem;
            padding: .1rem .45rem;
            border-radius: .25rem;
            background: #eef1f5;
            color: #495057;
            margin-inline-end: .25rem;
        }

        .lab-input-narrow {
            width: 140px;
        }

        .lab-user-field .select2-container {
            width: 100% !important;
        }

        .badge-status {
            font-size: .7rem;
        }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="card lab-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0">
                        <i class="fa-light fa-flask me-1"></i>
                        آزمایشگاه تست ربات — کاربر #<span id="lab-user-id">{{ $userId }}</span>
                    </h5>
                    <div class="lab-actions">
                        <button type="button" class="btn btn-sm btn-label-secondary" id="btn-refresh"
                            @if (!$labEnabled) disabled @endif>
                            <i class="fa fa-sync-alt me-1"></i> به‌روزرسانی</button>
                        <button type="button" class="btn btn-sm btn-label-info" id="btn-sync"
                            @if (!$labEnabled) disabled @endif>
                            <i class="fa fa-bolt me-1"></i>
                            Sync فروش‌ها</button>
                        <button type="button" class="btn btn-sm btn-label-danger" id="btn-reset"
                            @if (!$labEnabled) disabled @endif>
                            <i class="fa fa-trash me-1"></i>
                            ریست کامل</button>
                    </div>
                </div>
                <div class="card-body">
                    @if (!$labEnabled)
                        <div class="alert alert-danger small mb-3">
                            این ویژگی فقط در محیط تست قابل استفاده است. در محیط production هیچ‌یک از عملیات آزمایشگاه فعال
                            نیست.
                        </div>
                    @endif

                    <div class="alert alert-warning small mb-0">
                        این صفحه فقط برای تست داخلی است. خرید/فروش روی صرافی مرجع <strong>شبیه‌سازی</strong> می‌شود و
                        قیمت‌های دستی روی Redis و DB نوشته می‌شوند.
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-lg-5 col-md-6 lab-user-field">
                            <label class="form-label small mb-1" for="selectUser">کاربر</label>
                            <x-user-selection-component input-name="user_id" multiple="0" selected="{{ $userId }}"
                                selected-label="{{ $selectedLabel }}" :disabled="!$labEnabled" />
                        </div>
                    </div>

                    <div class="row g-3 mt-2 align-items-end">
                        <div class="col-auto">
                            <label class="form-label small mb-1" for="cap-input">سرمایه شروع (USDT)</label>
                            <input type="number" id="cap-input" step="0.01" min="1" value="200"
                                class="form-control form-control-sm lab-input-narrow"
                                @if (!$labEnabled) disabled @endif>
                        </div>
                        <div class="col-auto">
                            <label class="form-label small mb-1" for="main-input">موجودی کیف اصلی (اختیاری)</label>
                            <input type="number" id="main-input" step="0.01" min="0" placeholder="پیش‌فرض 500"
                                class="form-control form-control-sm lab-input-narrow"
                                @if (!$labEnabled) disabled @endif>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-success btn-sm" id="btn-start"
                                @if (!$labEnabled) disabled @endif><i class="fa fa-play me-1"></i>
                                شروع چرخه (Reset + Buy)</button>
                        </div>
                    </div>
                    <div class="text-muted small mt-2" id="start-hint">
                        با زدن «شروع چرخه» داده‌های ربات کاربر پاک، سرمایه اعمال و BotBuyOrchestrator اجرا می‌شود.
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-4 col-md-5">
                    <div class="card lab-card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">کیف پول‌ها</h6>
                        </div>
                        <div class="card-body py-2" id="wallets-panel">
                            <div class="lab-empty">—</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 col-md-7">
                    <div class="card lab-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">کنترل قیمت ارزهای خریداری‌شده کاربر</h6>
                            <span class="badge bg-label-secondary" id="pcoins-count">0 ارز</span>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm lab-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ارز</th>
                                        <th>قیمت فعلی</th>
                                        <th>قیمت متوسط خرید</th>
                                        <th>اختلاف</th>
                                        <th style="width:240px">تغییر قیمت</th>
                                    </tr>
                                </thead>
                                <tbody id="pcoins-tbody">
                                    <tr>
                                        <td colspan="5" class="lab-empty">هنوز خریدی برای کاربر انجام نشده است.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card lab-card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">سفارش‌های خرید و سل‌اوردرها (Tier)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm lab-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Exec</th>
                                    <th>ارز</th>
                                    <th>وضعیت</th>
                                    <th>تخصیص</th>
                                    <th>مقدار خریداری‌شده</th>
                                    <th>قیمت متوسط خرید</th>
                                    <th>توضیح</th>
                                </tr>
                            </thead>
                            <tbody id="exec-tbody">
                                <tr>
                                    <td colspan="7" class="lab-empty">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card lab-card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">تسویه‌ها (Settlements)</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm lab-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>درآمد ناخالص</th>
                                        <th>هزینه</th>
                                        <th>کارمزد</th>
                                        <th>سود خالص</th>
                                    </tr>
                                </thead>
                                <tbody id="settle-tbody">
                                    <tr>
                                        <td colspan="5" class="lab-empty">—</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card lab-card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">تراکنش‌های ربات</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm lab-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>زیرنوع</th>
                                        <th>مبلغ</th>
                                        <th>موجودی</th>
                                        <th>توضیح</th>
                                    </tr>
                                </thead>
                                <tbody id="tx-tbody">
                                    <tr>
                                        <td colspan="5" class="lab-empty">—</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/js/select-user.js'])
@endsection

@push('scripts')
    <script>
        (function() {
            const LAB_ENABLED = @json($labEnabled);
            let userId = {{ (int) $userId }};
            const URL = {
                status: @json(route('admin.bot.test-lab.status')),
                start: @json(route('admin.bot.test-lab.start')),
                bump: @json(route('admin.bot.test-lab.bump-price')),
                sync: @json(route('admin.bot.test-lab.sync')),
                reset: @json(route('admin.bot.test-lab.reset')),
            };
            const CSRF = @json(csrf_token());

            function toast(msg, ok = true) {
                if (typeof Toastify === 'undefined') {
                    console.log(msg);
                    return;
                }
                Toastify({
                    text: msg,
                    duration: ok ? 3000 : 5000,
                    gravity: 'top',
                    position: 'right',
                    style: {
                        background: ok ? '#28C76F' : '#EA5455'
                    }
                }).showToast();
            }

            function api(method, url, body) {
                const opts = {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                };
                if (method !== 'GET') {
                    opts.headers['Content-Type'] = 'application/json';
                    opts.body = JSON.stringify(body || {});
                }
                return fetch(url, opts).then(async r => {
                    const j = await r.json().catch(() => ({}));
                    if (!r.ok) {
                        const msg = j.error || j.message || ('HTTP ' + r.status);
                        const err = new Error(msg);
                        err.body = j;
                        throw err;
                    }
                    return j;
                });
            }

            function formatNumberTrimZeros(v, dp = 8) {
                if (v === null || v === undefined || v === '') return '—';
                const n = Number(v);
                if (isNaN(n)) return v;
                if (n === 0) return '0';
                return n.toFixed(dp).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
            }

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [c]));
            }

            function badge(status) {
                const map = {
                    PENDING: 'bg-label-secondary',
                    BUYING: 'bg-label-info',
                    BOUGHT: 'bg-label-success',
                    FAILED: 'bg-label-danger',
                    SKIPPED: 'bg-label-warning',
                    OPEN: 'bg-label-info',
                    FILLED: 'bg-label-success',
                    CANCELED: 'bg-label-secondary'
                };
                return `<span class="badge badge-status ${map[status] || 'bg-label-secondary'}">${esc(status)}</span>`;
            }

            function renderWallets(snap) {
                const mw = snap.main_wallet,
                    bw = snap.bot_wallet;
                document.getElementById('wallets-panel').innerHTML = `
                                            <div class="lab-stat"><span class="k">کیف USDT اصلی</span><span class="v">${mw ? formatNumberTrimZeros(mw.balance) : '—'}</span></div>
                                            <div class="lab-stat"><span class="k">کیف ربات — موجودی</span><span class="v">${bw ? formatNumberTrimZeros(bw.balance) : '—'}</span></div>
                                            <div class="lab-stat"><span class="k">کیف ربات — قفل‌شده</span><span class="v">${bw ? formatNumberTrimZeros(bw.locked_balance) : '—'}</span></div>
                                            <div class="lab-stat"><span class="k">کیف ربات — سود</span><span class="v">${bw ? formatNumberTrimZeros(bw.profit_balance) : '—'}</span></div>
                                            <div class="lab-stat"><span class="k">auto_trade</span>
                                              <span class="v">${snap.settings?.auto_trade_enabled ? '<span class="text-success">ON</span>' : '<span class="text-danger">OFF</span>'}</span></div>
                                        `;
            }

            function renderPriceControls(snap) {
                const byCoin = new Map();
                (snap.executions || []).forEach(e => {
                    if (!e.currency) return;
                    if (e.status !== 'BOUGHT' && Number(e.avg_buy_price || 0) <= 0) return;
                    const prev = byCoin.get(e.currency);
                    if (!prev || Number(e.id) > Number(prev.id)) byCoin.set(e.currency, e);
                });
                const priceMap = {};
                (snap.signals || []).forEach(s => {
                    if (s.symbol) priceMap[s.symbol] = s.current_price;
                });

                const tb = document.getElementById('pcoins-tbody');
                const cnt = document.getElementById('pcoins-count');
                if (byCoin.size === 0) {
                    cnt.textContent = '0 ارز';
                    tb.innerHTML =
                        '<tr><td colspan="5" class="lab-empty">هنوز خریدی برای کاربر انجام نشده است.</td></tr>';
                    return;
                }
                cnt.textContent = byCoin.size + ' ارز';
                const rows = [];
                byCoin.forEach((e, sym) => {
                    const cur = priceMap[sym];
                    const avg = e.avg_buy_price;
                    let diff = '—';
                    if (cur && avg && Number(avg) > 0) {
                        const d = ((Number(cur) - Number(avg)) / Number(avg)) * 100;
                        diff =
                            `<span class="${d >= 0 ? 'text-success' : 'text-danger'}">${d >= 0 ? '+' : ''}${d.toFixed(2)}%</span>`;
                    }
                    rows.push(`
                                              <tr>
                                                <td><strong>${esc(sym)}</strong></td>
                                                <td class="price-cell">${formatNumberTrimZeros(cur, 6)}</td>
                                                <td class="price-cell">${formatNumberTrimZeros(avg, 6)}</td>
                                                <td>${diff}</td>
                                                <td class="lab-bump">
                                                  <div class="input-group input-group-sm">
                                                    <input type="number" step="0.1" min="0.01" value="5" class="form-control bump-step" data-sym="${esc(sym)}">
                                                    <span class="input-group-text">%</span>
                                                    <button type="button" class="btn btn-success bump-btn" data-sym="${esc(sym)}" data-dir="up" title="افزایش">▲</button>
                                                    <button type="button" class="btn btn-danger bump-btn" data-sym="${esc(sym)}" data-dir="down" title="کاهش">▼</button>
                                                  </div>
                                                </td>
                                              </tr>`);
                });
                tb.innerHTML = rows.join('');
            }

            function renderExecutions(snap) {
                const tb = document.getElementById('exec-tbody');
                if (!snap.executions?.length) {
                    tb.innerHTML =
                        '<tr><td colspan="7" class="lab-empty">سفارش خریدی برای کاربر ثبت نشده است.</td></tr>';
                    return;
                }
                tb.innerHTML = snap.executions.map(e => {
                    const tiers = (e.sell_orders || []).map(so => `
                                                <tr class="tier-row">
                                                  <td></td>
                                                  <td colspan="2">Tier #${so.id} ${badge(so.status)}</td>
                                                  <td>${esc(so.target_type)}: ${formatNumberTrimZeros(so.target_value, 4)}</td>
                                                  <td>limit≈ <span class="price-cell">${formatNumberTrimZeros(so.limit_price, 6)}</span></td>
                                                  <td>سهم: ${formatNumberTrimZeros(so.share_percent, 2)}%</td>
                                                  <td>مقدار: ${formatNumberTrimZeros(so.amount_to_sell, 8)}</td>
                                                </tr>`).join('');
                    return `
                                              <tr>
                                                <td>#${e.id}</td>
                                                <td><strong>${esc(e.currency)}</strong></td>
                                                <td>${badge(e.status)}</td>
                                                <td>${formatNumberTrimZeros(e.allocated_usdt, 4)}</td>
                                                <td>${formatNumberTrimZeros(e.filled_amount, 8)}</td>
                                                <td class="price-cell">${formatNumberTrimZeros(e.avg_buy_price, 6)}</td>
                                                <td class="text-danger small">${esc(e.failure_reason || '')}</td>
                                              </tr>${tiers}`;
                }).join('');
            }

            function renderSettlements(snap) {
                const tb = document.getElementById('settle-tbody');
                if (!snap.settlements?.length) {
                    tb.innerHTML = '<tr><td colspan="5" class="lab-empty">—</td></tr>';
                    return;
                }
                tb.innerHTML = snap.settlements.map(s => `
                                            <tr><td>${s.id}</td><td>${formatNumberTrimZeros(s.gross_revenue, 4)}</td><td>${formatNumberTrimZeros(s.cost_basis, 4)}</td>
                                                <td>${formatNumberTrimZeros(s.performance_fee, 4)}</td>
                                                <td class="${Number(s.net_pnl) >= 0 ? 'text-success' : 'text-danger'}">${formatNumberTrimZeros(s.net_pnl, 4)}</td></tr>
                                        `).join('');
            }

            function renderTransactions(snap) {
                const tb = document.getElementById('tx-tbody');
                if (!snap.transactions?.length) {
                    tb.innerHTML = '<tr><td colspan="5" class="lab-empty">—</td></tr>';
                    return;
                }
                tb.innerHTML = snap.transactions.map(t => `
                                            <tr><td>${t.id}</td>
                                                <td><span class="lab-pill">${esc(t.subtype)}</span></td>
                                                <td class="${Number(t.amount) >= 0 ? 'text-success' : 'text-danger'}">${formatNumberTrimZeros(t.amount, 4)}</td>
                                                <td>${formatNumberTrimZeros(t.balance, 4)}</td>
                                                <td class="small">${esc(t.description)}</td></tr>
                                        `).join('');
            }

            function render(snap) {
                if (!snap) return;
                renderWallets(snap);
                renderPriceControls(snap);
                renderExecutions(snap);
                renderSettlements(snap);
                renderTransactions(snap);
            }

            function getUserId() {
                const sel = document.getElementById('selectUser');
                const v = sel?.value;
                return v ? parseInt(v, 10) : userId;
            }

            function updateUserIdLabel() {
                const id = getUserId();
                const el = document.getElementById('lab-user-id');
                if (el) el.textContent = id;
            }

            function refresh() {
                if (!LAB_ENABLED) return Promise.resolve();
                userId = getUserId();
                updateUserIdLabel();
                return api('GET', URL.status + '?user_id=' + userId).then(render).catch(e => toast(e.message, false));
            }

            function setHint(html) {
                const el = document.getElementById('start-hint');
                if (el) el.innerHTML = html;
            }

            // Event delegation — robust against DOM nodes being replaced after this script runs
            // (e.g. by debugbar injection or layout post-processing).
            document.addEventListener('click', (ev) => {
                if (!LAB_ENABLED) return;

                const t = ev.target.closest('button, .bump-btn');
                if (!t || t.disabled) return;

                if (t.id === 'btn-refresh') {
                    refresh();
                    return;
                }

                if (t.id === 'btn-sync') {
                    api('POST', URL.sync).then(() => {
                        toast('Sync اجرا شد');
                        refresh();
                    }).catch(e => toast(e.message, false));
                    return;
                }

                if (t.id === 'btn-reset') {
                    userId = getUserId();
                    if (!confirm('همه داده‌های ربات کاربر #' + userId + ' پاک شود؟')) return;
                    api('POST', URL.reset, {
                            user_id: userId
                        }).then(j => {
                            toast('ریست شد');
                            render(j.snapshot);
                            setHint('ریست شد. آماده شروع چرخه جدید.');
                        })
                        .catch(e => toast(e.message, false));
                    return;
                }

                if (t.id === 'btn-start') {
                    userId = getUserId();
                    const cap = Number(document.getElementById('cap-input').value);
                    const mb = document.getElementById('main-input').value;
                    if (!userId) {
                        toast('لطفاً یک کاربر انتخاب کنید', false);
                        return;
                    }
                    if (!cap || cap < 1) {
                        toast('سرمایه نامعتبر', false);
                        return;
                    }
                    const body = {
                        user_id: userId,
                        capital_usdt: cap
                    };
                    if (mb) body.main_balance = Number(mb);
                    setHint(
                        '<span class="text-info"><i class="fa fa-spinner fa-spin"></i> در حال اجرای چرخه…</span>'
                    );
                    api('POST', URL.start, body).then(j => {
                        render(j.snapshot);
                        const bo = j.bot_order;
                        if (!bo) {
                            setHint('<span class="text-warning">BotBuyOrchestrator چیزی برنگرداند. علت: ' +
                                esc(j.reason || 'نامشخص') + '</span>');
                            toast('چرخه شروع شد ولی سفارشی ساخته نشد: ' + (j.reason || 'نامشخص'),
                                false);
                        } else {
                            setHint('<span class="text-success">BotOrder #' + bo.id + ' ساخته شد (' +
                                esc(bo.status) + ').</span>');
                            toast('چرخه شروع شد — BotOrder #' + bo.id);
                        }
                    }).catch(e => {
                        setHint('<span class="text-danger">خطا: ' + esc(e.message) + '</span>');
                        toast(e.message, false);
                    });
                    return;
                }

                if (t.classList && t.classList.contains('bump-btn')) {
                    const sym = t.dataset.sym,
                        dir = t.dataset.dir;
                    const step = parseFloat(document.querySelector(`.bump-step[data-sym="${sym}"]`)?.value ||
                        '5');
                    api('POST', URL.bump, {
                            symbol: sym,
                            direction: dir,
                            step,
                            mode: 'percent'
                        })
                        .then(j => {
                            toast(`${sym} → ${formatNumberTrimZeros(j.new_price, 6)}`);
                            refresh();
                        })
                        .catch(e => toast(e.message, false));
                    return;
                }
            });

            $(document).on('change', '#selectUser', function() {
                if (!LAB_ENABLED) return;
                userId = getUserId();
                updateUserIdLabel();
                refresh();
            });

            if (LAB_ENABLED) {
                refresh();
                setInterval(refresh, 50000);
            }
        })();
    </script>
@endpush
