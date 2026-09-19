{{--
    Admin-triggered buy from a user's free bot balance.

    Trigger button contract (class js-bot-buy-now):
        data-preview-url : POST endpoint returning the buy preview (never buys)
        data-buy-url     : POST endpoint executing the buy

    The preview is produced by api-service's BotBuyOrchestrator::preview(), i.e.
    the exact same gate + allocation pipeline the real buy runs, so what this
    modal shows is what the confirm button will do.
--}}
<div class="modal fade" id="botBuyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cart-plus me-1 text-success"></i>
                    خرید با موجودی آزاد کاربر
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="botBuyModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status"></div>
                    <div class="small text-muted mt-2">در حال محاسبه پیش‌نمایش خرید…</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-success" id="botBuyConfirmBtn" disabled>
                    <i class="fas fa-check me-1"></i> تایید و خرید
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Pushed to the layout's script stack: this partial is included inside #app,
     which the dashboard mounts a Vue app on, and Vue's runtime template compiler
     strips <script> tags out of a client component template (and warns loudly).
     @stack('scripts') renders outside #app. --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('botBuyModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        var modal = new bootstrap.Modal(modalEl);
        var bodyEl = document.getElementById('botBuyModalBody');
        var confirmBtn = document.getElementById('botBuyConfirmBtn');
        var activeBuyUrl = null;

        var csrf = '{{ csrf_token() }}';

        function toast(text, ok) {
            Toastify({
                text: text,
                duration: ok ? 3000 : 5000,
                close: true,
                gravity: 'top',
                position: 'right',
                style: {
                    background: ok ? '#28C76F' : '#EA5455'
                },
            }).showToast();
        }

        function fmt(v, maxDec) {
            var n = parseFloat(v);
            if (!isFinite(n)) return '—';
            return n.toLocaleString('en-US', {
                maximumFractionDigits: maxDec === undefined ? 8 : maxDec
            });
        }

        function esc(v) {
            return String(v === null || v === undefined ? '' : v)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function post(url) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            }).then(function(res) {
                return res.text().then(function(text) {
                    var data = null;
                    try {
                        data = text ? JSON.parse(text) : null;
                    } catch (e) {
                        console.error('[bot-buy] non-JSON response', {
                            url: url,
                            status: res.status,
                            body: text.slice(0, 2000),
                        });
                        data = {
                            ok: false,
                            error: 'پاسخ غیر JSON از سرور (HTTP ' + res.status + '): ' +
                                text.slice(0, 300),
                        };
                    }
                    console.info('[bot-buy] response', {
                        url: url,
                        status: res.status,
                        data: data
                    });
                    return {
                        status: res.status,
                        ok: res.ok,
                        data: data || {
                            ok: false,
                            error: 'پاسخ خالی از سرور (HTTP ' + res.status + ').'
                        }
                    };
                });
            });
        }

        function renderError(message) {
            bodyEl.innerHTML =
                '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle me-1"></i>' +
                esc(message) + '</div>';
            confirmBtn.disabled = true;
        }

        /* ── Wallet snapshot + the gate the free balance has to clear ─────── */
        function statCard(label, value, cls, note) {
            return '<div class="col-6 col-md-3"><div class="border rounded p-2 text-center h-100 ' +
                (cls || '') + '">' +
                '<small class="text-muted d-block mb-1">' + label + '</small>' +
                '<strong class="font-number d-block">' + value + '</strong>' +
                '<small class="text-muted" style="font-size:.65rem">' + (note || 'USDT') + '</small>' +
                '</div></div>';
        }

        function renderWallet(data) {
            var w = data.wallet || {};
            var g = data.gate || {};
            var gateLabel = g.kind === 'buy_floor' ?
                'حداقل لازم (کف خرید ارزان‌ترین ارز)' :
                'حداقل لازم (حداقل واریز خالص)';

            return '<div class="row g-2 mb-3">' +
                statCard('موجودی کیف پول ربات', fmt(w.balance), '') +
                statCard('قفل‌شده در معامله', fmt(w.locked_balance), 'border-warning') +
                statCard('آزاد و قابل تخصیص', fmt(w.free_balance), 'border-success') +
                statCard(gateLabel, fmt(g.amount), 'border-info') +
                '</div>';
        }

        /* ── Sell ladder that would be opened on top of a bought position ── */
        function renderSellPlan(a) {
            var plan = a.sell_plan || {};
            var tiers = plan.tiers || [];

            if (!tiers.length) {
                return '<div class="alert alert-warning py-2 small mb-0">' +
                    '<i class="fas fa-exclamation-triangle me-1"></i>' +
                    'برای این ارز پله فروشی تعریف نشده است؛ خرید انجام می‌شود اما سفارش فروشی ثبت نخواهد شد.' +
                    '</div>';
            }

            var anyBelowMin = false;
            var rows = '';
            tiers.forEach(function(t, i) {
                if (t.below_p2p_min) anyBelowMin = true;
                var target = t.type === 'price' ?
                    fmt(t.trigger) + ' <small class="text-muted">قیمت</small>' :
                    '+' + fmt(t.trigger, 2) + '%';
                var profit = (parseFloat(t.revenue_usdt) || 0) - (parseFloat(t.cost_usdt) || 0);

                rows += '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td class="font-number">' + target + '</td>' +
                    '<td class="font-number">' + fmt(t.share_percent, 2) + '%</td>' +
                    '<td class="font-number">' + fmt(t.amount_coin) + '</td>' +
                    '<td class="font-number">' + fmt(t.sell_price) + '</td>' +
                    '<td class="font-number">' + fmt(t.revenue_usdt) + '</td>' +
                    '<td class="font-number ' + (profit >= 0 ? 'text-success' : 'text-danger') + '">' +
                    (profit >= 0 ? '+' : '') + fmt(profit) + '</td>' +
                    '<td>' + (t.below_p2p_min ?
                        '<span class="badge bg-warning text-dark" style="font-size:.65rem">زیر حد مجاز</span>' :
                        '<span class="badge bg-label-success" style="font-size:.65rem">مجاز</span>') +
                    '</td>' +
                    '</tr>';
            });

            var html = '<div class="table-responsive">' +
                '<table class="table table-sm table-bordered align-middle mb-2">' +
                '<thead class="table-light"><tr>' +
                '<th>پله</th><th>هدف فروش</th><th>سهم</th><th>مقدار ' + esc(a.currency_symbol) +
                '</th><th>قیمت فروش</th><th>درآمد برآوردی</th><th>سود برآوردی</th><th>وضعیت</th>' +
                '</tr></thead><tbody>' + rows + '</tbody></table></div>';

            if (anyBelowMin) {
                html += '<div class="alert alert-warning py-2 small mb-0">' +
                    '<i class="fas fa-compress-arrows-alt me-1"></i>' +
                    'ارزش برخی پله‌ها از حداقل ارزش سفارش (' + fmt(plan.p2p_min_order_value) +
                    ' USDT) کمتر است؛ هنگام ثبت فروش، این پله‌ها به‌صورت خودکار ادغام می‌شوند (Smart Collapse) و تعداد پله‌های نهایی کمتر از ' +
                    esc(plan.count) + ' خواهد بود.' +
                    '</div>';
            }

            return html;
        }

        function renderAllocation(a) {
            var plan = a.sell_plan || {};

            return '<div class="border rounded p-3 mb-3">' +
                '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">' +
                '<div>' +
                '<strong class="fs-6">' + esc(a.currency_symbol) + '</strong>' +
                (a.currency_name ? ' <span class="text-muted small">' + esc(a.currency_name) + '</span>' : '') +
                ' <span class="badge bg-label-secondary ms-1" style="font-size:.65rem">اولویت ' +
                esc(a.priority) + '</span>' +
                '</div>' +
                '<div class="text-end">' +
                '<span class="badge bg-success px-3 font-number">' + fmt(a.amount_usdt) + ' USDT</span>' +
                '<span class="badge bg-label-success ms-1 font-number" style="font-size:.65rem">' +
                fmt(a.share_percent, 2) + '% از خرید</span>' +
                '</div>' +
                '</div>' +

                '<div class="row g-2 mb-3 small">' +
                statCard('قیمت لحظه‌ای', fmt(a.current_price), '') +
                statCard('بازه مجاز خرید', fmt(a.floor_price) + ' — ' + fmt(a.ceiling_price), '') +
                statCard('مقدار تقریبی خرید', fmt(a.estimated_amount_coin), '', esc(a.currency_symbol)) +
                statCard('سقف تخصیص این ارز', fmt(a.max_allocation_percent, 2) + '%', '', 'از کل کیف پول') +
                '</div>' +

                '<div class="small text-muted mb-2">' +
                '<i class="fas fa-layer-group fa-xs me-1"></i>' +
                'پله‌های فروش (' + esc(plan.count) + ' پله، حالت ' +
                (plan.mode === 'price' ? 'قیمت مشخص' : 'درصد سود') + '، حداقل ارزش سفارش ' +
                fmt(plan.p2p_min_order_value) + ' USDT)' +
                '</div>' +
                renderSellPlan(a) +
                '</div>';
        }

        function renderSkipped(skipped) {
            if (!skipped || !skipped.length) return '';

            var rows = '';
            skipped.forEach(function(s) {
                rows += '<tr>' +
                    '<td><strong>' + esc(s.currency_symbol || '—') + '</strong></td>' +
                    '<td class="font-number">' + fmt(s.would_have_received) + '</td>' +
                    '<td>' + (s.cap_exhausted ?
                        '<span class="badge bg-label-secondary" style="font-size:.65rem">سقف تخصیص پر است</span>' :
                        '<span class="badge bg-label-warning" style="font-size:.65rem">کمتر از حداقل خرید</span>') +
                    '</td>' +
                    '<td class="small text-muted">' + esc(s.reason) + '</td>' +
                    '</tr>';
            });

            return '<div class="mt-2">' +
                '<div class="small text-muted mb-2"><i class="fas fa-ban fa-xs me-1"></i>' +
                'ارزهایی که سهم گرفتند اما خریداری نمی‌شوند</div>' +
                '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">' +
                '<thead class="table-light"><tr>' +
                '<th>ارز</th><th>سهم محاسبه‌شده (USDT)</th><th>علت</th><th>توضیح</th>' +
                '</tr></thead><tbody>' + rows + '</tbody></table></div></div>';
        }

        function renderOutOfRange(list) {
            if (!list || !list.length) return '';

            var rows = '';
            list.forEach(function(s) {
                rows += '<tr>' +
                    '<td><strong>' + esc(s.currency_symbol || '—') + '</strong></td>' +
                    '<td class="font-number">' + fmt(s.current_price) + '</td>' +
                    '<td class="font-number">' + fmt(s.floor_price) + ' — ' + fmt(s.ceiling_price) +
                    '</td>' +
                    '</tr>';
            });

            return '<div class="mt-3">' +
                '<div class="small text-muted mb-2"><i class="fas fa-arrows-alt-h fa-xs me-1"></i>' +
                'سیگنال‌های فعالی که قیمت لحظه‌ای‌شان بیرون از بازه است</div>' +
                '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">' +
                '<thead class="table-light"><tr><th>ارز</th><th>قیمت لحظه‌ای</th><th>بازه مجاز</th></tr></thead>' +
                '<tbody>' + rows + '</tbody></table></div></div>';
        }

        function renderUnpriced(list) {
            if (!list || !list.length) return '';

            var names = list.map(function(s) {
                return esc(s.currency_symbol || ('#' + s.currency_id));
            }).join('، ');

            return '<div class="alert alert-danger py-2 small">' +
                '<i class="fas fa-plug me-1"></i>' +
                'قیمت لحظه‌ای این ارزها در دسترس نیست: <strong>' + names + '</strong>. ' +
                'برای آن‌ها ردیف خرید ناموفق ثبت می‌شود (بدون قفل وجه) تا مشکل قیمت‌دهی قابل پیگیری باشد.' +
                '</div>';
        }

        function renderPreview(data) {
            var html = renderWallet(data);

            if (!data.buyable) {
                html += '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-triangle me-1"></i>' +
                    '<strong>خرید انجام نمی‌شود.</strong> ' + esc(data.blocker) +
                    '</div>';
                html += renderUnpriced(data.unpriced);
                html += renderSkipped(data.skipped);
                html += renderOutOfRange(data.out_of_range);
                bodyEl.innerHTML = html;
                confirmBtn.disabled = true;
                return;
            }

            var t = data.totals || {};
            html += '<div class="alert alert-success">' +
                '<i class="fas fa-check-circle me-1"></i>' +
                'با تایید، <strong class="font-number">' + fmt(t.total_allocated) +
                ' USDT</strong> از موجودی آزاد کاربر روی <strong>' + esc(t.allocated_count) +
                '</strong> ارز خرید می‌شود و پله‌های فروش زیر روی صرافی مرجع ثبت می‌گردد. ' +
                '<span class="font-number">' + fmt(t.unallocated) +
                ' USDT</span> تخصیص‌نیافته باقی می‌ماند.' +
                '</div>';

            html += renderUnpriced(data.unpriced);
            (data.allocations || []).forEach(function(a) {
                html += renderAllocation(a);
            });
            html += renderSkipped(data.skipped);
            html += renderOutOfRange(data.out_of_range);

            html += '<div class="small text-muted mt-3">' +
                '<i class="fas fa-info-circle me-1"></i>' +
                'اعداد بالا با قیمت لحظه‌ای محاسبه شده‌اند. خرید به صورت Market در صرافی مرجع انجام می‌شود، ' +
                'پس قیمت و مقدار نهایی و در نتیجه قیمت پله‌های فروش کمی متفاوت خواهد بود. ' +
                'کل مبلغ تخصیص‌یافته در لحظه ثبت سفارش روی کیف پول ربات کاربر قفل می‌شود.' +
                '</div>';

            bodyEl.innerHTML = html;
            confirmBtn.disabled = false;
        }

        document.querySelectorAll('.js-bot-buy-now').forEach(function(btn) {
            btn.addEventListener('click', function() {
                activeBuyUrl = btn.dataset.buyUrl;
                confirmBtn.disabled = true;
                bodyEl.innerHTML =
                    '<div class="text-center py-4">' +
                    '<div class="spinner-border text-success" role="status"></div>' +
                    '<div class="small text-muted mt-2">در حال محاسبه پیش‌نمایش خرید…</div></div>';
                modal.show();

                post(btn.dataset.previewUrl)
                    .then(function(res) {
                        if (!res.ok || res.data.ok === false) {
                            renderError((res.data && res.data.error) ||
                                ('خطا در دریافت پیش‌نمایش خرید (HTTP ' + res.status + ').'));
                            return;
                        }
                        renderPreview(res.data);
                    })
                    .catch(function(err) {
                        console.error('[bot-buy] preview network/parse error', err);
                        renderError('ارتباط با سرور برقرار نشد: ' +
                            (err && err.message ? err.message : String(err)));
                    });
            });
        });

        confirmBtn.addEventListener('click', function() {
            if (!activeBuyUrl) return;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span> در حال ثبت خرید…';

            post(activeBuyUrl)
                .then(function(res) {
                    if (!res.ok || res.data.ok === false) {
                        var msg = res.data.error || 'خطا در ثبت خرید.';
                        toast(msg, false);
                        renderError(msg);
                        return;
                    }
                    toast(res.data.message || 'سفارش خرید ثبت شد.', true);
                    modal.hide();
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                })
                .catch(function(err) {
                    console.error('[bot-buy] confirm network/parse error', err);
                    toast('ارتباط با سرور برقرار نشد: ' +
                        (err && err.message ? err.message : String(err)), false);
                })
                .finally(function() {
                    confirmBtn.innerHTML = '<i class="fas fa-check me-1"></i> تایید و خرید';
                });
        });
    });
</script>
@endpush
