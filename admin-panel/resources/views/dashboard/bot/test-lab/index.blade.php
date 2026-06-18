@extends('dashboard.layout.master')

@section('title', 'آزمایشگاه تست ربات')

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .lab-card {
            margin-bottom: 1rem
        }

        .lab-table {
            font-size: .82rem;
            font-family: ui-monospace, monospace;
        }

        .lab-table th,
        .lab-table td {
            vertical-align: middle;
            padding: .45rem .6rem
        }

        .price-cell {

            font-weight: 600
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
            border-bottom: 0
        }

        .lab-stat .k {
            color: #6c757d;
            font-size: .78rem
        }

        .lab-stat .v {
            font-family: ui-monospace, monospace
        }

        .lab-actions .btn {
            margin-inline-start: .25rem
        }

        .lab-empty {
            padding: 1.5rem;
            text-align: center;
            color: #9aa3ad;
            font-size: .85rem
        }

        .lab-bump .input-group {
            flex-wrap: nowrap;
            max-width: 230px;
            margin-inline-start: auto
        }

        .lab-bump input {
            max-width: 70px
        }

        .lab-pill {
            display: inline-block;
            font-size: .7rem;
            padding: .1rem .45rem;
            border-radius: .25rem;
            background: #eef1f5;
            color: #495057;
            margin-inline-end: .25rem
        }

        .lab-toolbar {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: end
        }

        .lab-toolbar .form-control {
            max-width: 160px
        }

        .badge-status {
            font-size: .7rem
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card lab-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0">
                        <i class="fa-light fa-flask me-1"></i>
                        آزمایشگاه تست ربات — کاربر #<span id="lab-user-id">{{ $userId }}</span>
                    </h5>
                    <div class="lab-actions">
                        <button type="button" class="btn btn-sm btn-label-secondary" id="btn-refresh"><i
                                class="fa fa-sync-alt"></i> به‌روزرسانی</button>
                        <button type="button" class="btn btn-sm btn-label-info" id="btn-sync"><i class="fa fa-bolt"></i>
                            Sync فروش‌ها</button>
                        <button type="button" class="btn btn-sm btn-label-danger" id="btn-reset"><i class="fa fa-trash"></i>
                            ریست کامل</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning small mb-0">
                        این صفحه فقط برای تست داخلی است. خرید/فروش روی صرافی مرجع <strong>شبیه‌سازی</strong> می‌شود و
                        قیمت‌های دستی روی Redis و DB نوشته می‌شوند.
                    </div>

                    <div class="lab-toolbar mt-3">
                        <div>
                            <label class="form-label small mb-1">سرمایه شروع (USDT)</label>
                            <input type="number" id="cap-input" step="0.01" min="1" value="200"
                                class="form-control form-control-sm">
                        </div>
                        <div>
                            <label class="form-label small mb-1">موجودی کیف اصلی (اختیاری)</label>
                            <input type="number" id="main-input" step="0.01" min="0" placeholder="پیش‌فرض 500"
                                class="form-control form-control-sm">
                        </div>
                        <div>
                            <button type="button" class="btn btn-success btn-sm" id="btn-start"><i class="fa fa-play"></i>
                                شروع چرخه (Reset + Buy)</button>
                        </div>
                        <div class="text-muted small flex-grow-1 align-self-center" id="start-hint">
                            با زدن «شروع چرخه» داده‌های ربات کاربر پاک، سرمایه اعمال و BotBuyOrchestrator اجرا می‌شود.
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
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

            <div class="card lab-card">
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

            <div class="row">
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

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        (function () {
            const USER_ID = {{ (int) $userId }};
            const URL = {
                status: @json(route('admin.bot.test-lab.status')),
                start: @json(route('admin.bot.test-lab.start')),
                bump: @json(route('admin.bot.test-lab.bump-price')),
                sync: @json(route('admin.bot.test-lab.sync')),
                reset: @json(route('admin.bot.test-lab.reset')),
            };
            const CSRF = @json(csrf_token());

            function toast(msg, ok = true) {
                if (typeof Toastify === 'undefined') { console.log(msg); return; }
                Toastify({
                    text: msg, duration: ok ? 3000 : 5000, gravity: 'top', position: 'right',
                    style: { background: ok ? '#28C76F' : '#EA5455' }
                }).showToast();
            }
            function api(method, url, body) {
                const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } };
                if (method !== 'GET') { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body || {}); }
                return fetch(url, opts).then(async r => {
                    const j = await r.json().catch(() => ({}));
                    if (!r.ok) { const msg = j.error || j.message || ('HTTP ' + r.status); const err = new Error(msg); err.body = j; throw err; }
                    return j;
                });
            }
            function fmt(v, dp = 8) {
                if (v === null || v === undefined || v === '') return '—';
                const n = Number(v); if (isNaN(n)) return v;
                if (n === 0) return '0';
                return n.toFixed(dp).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
            }
            function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
            function badge(status) {
                const map = { PENDING: 'bg-label-secondary', BUYING: 'bg-label-info', BOUGHT: 'bg-label-success', FAILED: 'bg-label-danger', SKIPPED: 'bg-label-warning', OPEN: 'bg-label-info', FILLED: 'bg-label-success', CANCELED: 'bg-label-secondary' };
                return `<span class="badge badge-status ${map[status] || 'bg-label-secondary'}">${esc(status)}</span>`;
            }

            function renderWallets(snap) {
                const mw = snap.main_wallet, bw = snap.bot_wallet;
                document.getElementById('wallets-panel').innerHTML = `
                                            <div class="lab-stat"><span class="k">کیف USDT اصلی</span><span class="v">${mw ? fmt(mw.balance, 4) : '—'}</span></div>
                                            <div class="lab-stat"><span class="k">کیف ربات — موجودی</span><span class="v">${bw ? fmt(bw.balance, 4) : '—'}</span></div>
                                            <div class="lab-stat"><span class="k">کیف ربات — قفل‌شده</span><span class="v">${bw ? fmt(bw.locked_balance, 4) : '—'}</span></div>
                                            <div class="lab-stat"><span class="k">کیف ربات — سود</span><span class="v">${bw ? fmt(bw.profit_balance, 4) : '—'}</span></div>
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
                (snap.signals || []).forEach(s => { if (s.symbol) priceMap[s.symbol] = s.current_price; });

                const tb = document.getElementById('pcoins-tbody');
                const cnt = document.getElementById('pcoins-count');
                if (byCoin.size === 0) {
                    cnt.textContent = '0 ارز';
                    tb.innerHTML = '<tr><td colspan="5" class="lab-empty">هنوز خریدی برای کاربر انجام نشده است.</td></tr>';
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
                        diff = `<span class="${d >= 0 ? 'text-success' : 'text-danger'}">${d >= 0 ? '+' : ''}${d.toFixed(2)}%</span>`;
                    }
                    rows.push(`
                                              <tr>
                                                <td><strong>${esc(sym)}</strong></td>
                                                <td class="price-cell">${fmt(cur, 6)}</td>
                                                <td class="price-cell">${fmt(avg, 6)}</td>
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
                    tb.innerHTML = '<tr><td colspan="7" class="lab-empty">سفارش خریدی برای کاربر ثبت نشده است.</td></tr>';
                    return;
                }
                tb.innerHTML = snap.executions.map(e => {
                    const tiers = (e.sell_orders || []).map(so => `
                                                <tr class="tier-row">
                                                  <td></td>
                                                  <td colspan="2">Tier #${so.id} ${badge(so.status)}</td>
                                                  <td>${esc(so.target_type)}: ${fmt(so.target_value, 4)}</td>
                                                  <td>limit≈ <span class="price-cell">${fmt(so.limit_price, 6)}</span></td>
                                                  <td>سهم: ${fmt(so.share_percent, 2)}%</td>
                                                  <td>مقدار: ${fmt(so.amount_to_sell, 8)}</td>
                                                </tr>`).join('');
                    return `
                                              <tr>
                                                <td>#${e.id}</td>
                                                <td><strong>${esc(e.currency)}</strong></td>
                                                <td>${badge(e.status)}</td>
                                                <td>${fmt(e.allocated_usdt, 4)}</td>
                                                <td>${fmt(e.filled_amount, 8)}</td>
                                                <td class="price-cell">${fmt(e.avg_buy_price, 6)}</td>
                                                <td class="text-danger small">${esc(e.failure_reason || '')}</td>
                                              </tr>${tiers}`;
                }).join('');
            }

            function renderSettlements(snap) {
                const tb = document.getElementById('settle-tbody');
                if (!snap.settlements?.length) { tb.innerHTML = '<tr><td colspan="5" class="lab-empty">—</td></tr>'; return; }
                tb.innerHTML = snap.settlements.map(s => `
                                            <tr><td>${s.id}</td><td>${fmt(s.gross_revenue, 4)}</td><td>${fmt(s.cost_basis, 4)}</td>
                                                <td>${fmt(s.performance_fee, 4)}</td>
                                                <td class="${Number(s.net_pnl) >= 0 ? 'text-success' : 'text-danger'}">${fmt(s.net_pnl, 4)}</td></tr>
                                        `).join('');
            }

            function renderTransactions(snap) {
                const tb = document.getElementById('tx-tbody');
                if (!snap.transactions?.length) { tb.innerHTML = '<tr><td colspan="5" class="lab-empty">—</td></tr>'; return; }
                tb.innerHTML = snap.transactions.map(t => `
                                            <tr><td>${t.id}</td>
                                                <td><span class="lab-pill">${esc(t.subtype)}</span></td>
                                                <td class="${Number(t.amount) >= 0 ? 'text-success' : 'text-danger'}">${fmt(t.amount, 4)}</td>
                                                <td>${fmt(t.balance, 4)}</td>
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

            function refresh() {
                return api('GET', URL.status + '?user_id=' + USER_ID).then(render).catch(e => toast(e.message, false));
            }
            function setHint(html) { const el = document.getElementById('start-hint'); if (el) el.innerHTML = html; }

            // Event delegation — robust against DOM nodes being replaced after this script runs
            // (e.g. by debugbar injection or layout post-processing).
            document.addEventListener('click', (ev) => {
                const t = ev.target.closest('button, .bump-btn');
                if (!t) return;

                if (t.id === 'btn-refresh') { refresh(); return; }

                if (t.id === 'btn-sync') {
                    api('POST', URL.sync).then(() => { toast('Sync اجرا شد'); refresh(); }).catch(e => toast(e.message, false));
                    return;
                }

                if (t.id === 'btn-reset') {
                    if (!confirm('همه داده‌های ربات کاربر #' + USER_ID + ' پاک شود؟')) return;
                    api('POST', URL.reset, { user_id: USER_ID }).then(j => { toast('ریست شد'); render(j.snapshot); setHint('ریست شد. آماده شروع چرخه جدید.'); })
                        .catch(e => toast(e.message, false));
                    return;
                }

                if (t.id === 'btn-start') {
                    const cap = Number(document.getElementById('cap-input').value);
                    const mb = document.getElementById('main-input').value;
                    if (!cap || cap < 1) { toast('سرمایه نامعتبر', false); return; }
                    const body = { user_id: USER_ID, capital_usdt: cap };
                    if (mb) body.main_balance = Number(mb);
                    setHint('<span class="text-info"><i class="fa fa-spinner fa-spin"></i> در حال اجرای چرخه…</span>');
                    api('POST', URL.start, body).then(j => {
                        render(j.snapshot);
                        const bo = j.bot_order;
                        if (!bo) {
                            setHint('<span class="text-warning">BotBuyOrchestrator چیزی برنگرداند. علت: ' + esc(j.reason || 'نامشخص') + '</span>');
                            toast('چرخه شروع شد ولی سفارشی ساخته نشد: ' + (j.reason || 'نامشخص'), false);
                        } else {
                            setHint('<span class="text-success">BotOrder #' + bo.id + ' ساخته شد (' + esc(bo.status) + ').</span>');
                            toast('چرخه شروع شد — BotOrder #' + bo.id);
                        }
                    }).catch(e => { setHint('<span class="text-danger">خطا: ' + esc(e.message) + '</span>'); toast(e.message, false); });
                    return;
                }

                if (t.classList && t.classList.contains('bump-btn')) {
                    const sym = t.dataset.sym, dir = t.dataset.dir;
                    const step = parseFloat(document.querySelector(`.bump-step[data-sym="${sym}"]`)?.value || '5');
                    api('POST', URL.bump, { symbol: sym, direction: dir, step, mode: 'percent' })
                        .then(j => { toast(`${sym} → ${fmt(j.new_price, 6)}`); refresh(); })
                        .catch(e => toast(e.message, false));
                    return;
                }
            });

            refresh();
            setInterval(refresh, 50000);
        })();
    </script>
@endpush
