{{--
    Shared cancel-preview/confirm modal for bot orders.

    Trigger button contract (class js-bot-cancel):
        data-preview-url : POST endpoint returning the cancel preview
        data-cancel-url  : POST endpoint executing the cancel
        data-title       : modal title
        data-mode        : "single" | "all"
--}}
<div class="modal fade" id="botCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="botCancelModalTitle">لغو سفارش</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="botCancelModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                    <div class="small text-muted mt-2">در حال دریافت پیش‌نمایش لغو…</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-danger" id="botCancelConfirmBtn" disabled>
                    <i class="fas fa-ban me-1"></i> تایید و لغو
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
        var modalEl = document.getElementById('botCancelModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        var modal = new bootstrap.Modal(modalEl);
        var bodyEl = document.getElementById('botCancelModalBody');
        var titleEl = document.getElementById('botCancelModalTitle');
        var confirmBtn = document.getElementById('botCancelConfirmBtn');
        var activeCancelUrl = null;
        var activeMode = 'single';

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

        function post(url) {
            console.info('[bot-cancel] POST', url);
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
                        console.error('[bot-cancel] non-JSON response', {
                            url: url,
                            status: res.status,
                            body: text.slice(0, 2000),
                        });
                        data = {
                            ok: false,
                            error: 'پاسخ غیر JSON از سرور (HTTP ' + res.status + '): ' +
                                text.slice(0, 300),
                            raw: text.slice(0, 2000),
                        };
                    }
                    console.info('[bot-cancel] response', {
                        url: url,
                        status: res.status,
                        ok: res.ok,
                        data: data,
                    });
                    return {
                        status: res.status,
                        ok: res.ok,
                        data: data || { ok: false, error: 'پاسخ خالی از سرور (HTTP ' + res.status + ').' }
                    };
                });
            });
        }

        function renderError(message) {
            disposeFeePopovers();
            bodyEl.innerHTML =
                '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle me-1"></i>' +
                message + '</div>';
            confirmBtn.disabled = true;
        }

        /* ── Per-tier fee breakdown popovers ─────────────────────────────── */
        var feePopContents = {};
        var feePopovers = [];

        function disposeFeePopovers() {
            feePopovers.forEach(function(p) {
                try {
                    p.dispose();
                } catch (e) {}
            });
            feePopovers = [];
            feePopContents = {};
        }

        function popRow(label, value) {
            return '<div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">' +
                '<small class="text-muted">' + label + '</small>' +
                '<span class="font-number">' + fmt(value) +
                ' <small class="text-muted" style="font-size:.6rem">USDT</small></span>' +
                '</div>';
        }

        function popNote(text) {
            return '<div class="small text-muted py-1 border-bottom" style="font-size:.7rem; line-height:1.7">' +
                text + '</div>';
        }

        function buildFeePopContent(it, perfPct) {
            var html = '';

            // 1) network / withdrawal fee
            html += popRow('کارمزد شبکه' + (it.chain ? ' — چین ' + it.chain : ''), it.network_fee);
            if (parseFloat(it.network_fee_coin) > 0) {
                html += popNote('= ' + fmt(it.network_fee_coin) + ' ' + it.currency_symbol +
                    ' (network_fee + exchange_withdrawal_fee چین) × ' + fmt(it.current_price) +
                    ' (قیمت لحظه‌ای)');
            } else {
                html += popNote(
                    'برای این ارز چین برداشتی با کارمزد تعریف نشده است، بنابراین کارمزد شبکه صفر محاسبه شد.'
                );
            }

            // 2) buy-side ref-exchange fee share
            html += popRow('سهم کارمزد خرید صرافی مرجع', it.exchange_fee);
            if (parseFloat(it.buy_fee_total) > 0) {
                html += popNote(fmt(it.buy_fee_total) + ' (کل کارمزد خرید این ارز) × ' +
                    fmt(it.buy_fee_share_pct, 2) + '٪ (سهم این پله = ' + fmt(it.amount_to_sell) +
                    ' از کل ' + it.currency_symbol + ' خریداری‌شده)');
            } else {
                html += popNote('برای خرید این ارز کارمزدی در صرافی مرجع ثبت نشده است.');
            }

            // 3) performance fee
            html += popRow('کارمزد عملکرد (' + fmt(perfPct, 2) + '٪ از سود)', it.performance_fee);
            if (!(parseFloat(it.performance_fee) > 0)) {
                html += popNote(
                    'این پله در قیمت فعلی سودی ندارد؛ کارمزد عملکرد فقط از سود مثبت (پس از کسر سایر کارمزدها) گرفته می‌شود.'
                );
            }

            // total
            html += '<div class="d-flex justify-content-between align-items-center py-1 gap-3">' +
                '<small class="fw-bold">جمع کارمزد این پله</small>' +
                '<span class="font-number fw-bold text-warning">' + fmt(it.total_fee) +
                ' <small class="text-muted" style="font-size:.6rem">USDT</small></span>' +
                '</div>';

            html +=
                '<div class="small text-muted pt-1 mt-1 border-top" style="font-size:.68rem; line-height:1.7">' +
                '<i class="fas fa-info-circle me-1"></i>' +
                'کارمزد فروش Market در صرافی مرجع تا لحظه اجرا مشخص نیست و در این برآورد نیامده؛ در تسویه نهایی لحاظ می‌شود.' +
                '</div>';

            return html;
        }

        function initFeePopovers() {
            bodyEl.querySelectorAll('.js-cancel-fee-info').forEach(function(icon) {
                var content = feePopContents[icon.dataset.sellId];
                if (!content) return;
                feePopovers.push(new bootstrap.Popover(icon, {
                    html: true,
                    sanitize: false,
                    trigger: 'click',
                    placement: 'top',
                    title: 'نحوه محاسبه کارمزد این پله',
                    content: content,
                    customClass: 'popover-fee-detail',
                    container: modalEl.querySelector('.modal-content'),
                }));
            });
        }

        // Close open fee popovers when clicking anywhere else inside the modal.
        modalEl.addEventListener('click', function(e) {
            if (e.target.closest('.js-cancel-fee-info') || e.target.closest('.popover-fee-detail'))
                return;
            feePopovers.forEach(function(p) {
                try {
                    p.hide();
                } catch (err) {}
            });
        });

        function renderPreview(data) {
            disposeFeePopovers();
            var html = '';
            var blocked = Array.isArray(data.in_flight) && data.in_flight.length > 0;

            if (blocked) {
                var ids = data.in_flight.map(function(f) {
                    return '#' + f.order_id;
                }).join('، ');
                html += '<div class="alert alert-warning">' +
                    '<i class="fas fa-hourglass-half me-1"></i>' +
                    'سفارش ' + ids + ' هنوز در حال پردازش خرید / ثبت پله‌های فروش در صرافی مرجع است. ' +
                    'تا پایان پردازش، لغو امکان‌پذیر نیست. چند لحظه بعد دوباره تلاش کنید.' +
                    '</div>';
            }

            if (data.sell_on_exchange === false) {
                html += '<div class="alert alert-info py-2 small">' +
                    '<i class="fas fa-info-circle me-1"></i>' +
                    'فروش خودکار در صرافی مرجع (هنگام لغو) در تنظیمات سراسری غیرفعال است؛ تسویه فقط بر اساس قیمت لحظه‌ای ثبت می‌شود و موجودی در صرافی مرجع فروخته نمی‌شود.' +
                    '</div>';
            }

            if (activeMode === 'all') {
                html += '<div class="alert alert-danger py-2 small">' +
                    '<i class="fas fa-power-off me-1"></i>' +
                    'با تایید این عملیات، ربات کاربر <strong>خاموش</strong> می‌شود، همه سفارش‌های باز لغو و پله‌های فروش باقی‌مانده در صرافی مرجع بسته و به فروش می‌رسند تا کل وجوه کاربر آزاد شود.' +
                    '</div>';
            }

            var orders = data.orders || [];
            var anySells = false;

            orders.forEach(function(o) {
                var items = o.items || [];
                if ((o.open_sell_orders || 0) > 0) anySells = true;

                html += '<div class="border rounded p-2 mb-3">';
                html +=
                    '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">' +
                    '<strong>سفارش #' + o.order_id + '</strong>' +
                    '<span class="badge bg-secondary">' + (o.open_sell_orders || 0) +
                    ' پله فروش باز</span>' +
                    '</div>';

                if (items.length) {
                    html +=
                        '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-2">' +
                        '<thead class="table-light"><tr>' +
                        '<th>ارز</th><th>مقدار فروش</th><th>قیمت خرید</th><th>قیمت لحظه‌ای</th>' +
                        '<th>کارمزدها</th><th>برگشتی برآوردی</th>' +
                        '</tr></thead><tbody>';
                    items.forEach(function(it) {
                        feePopContents[it.sell_order_id] = buildFeePopContent(it, o
                            .performance_fee_percent);
                        html += '<tr>' +
                            '<td><strong>' + (it.currency_symbol || '—') + '</strong></td>' +
                            '<td class="font-number">' + fmt(it.amount_to_sell) + '</td>' +
                            '<td class="font-number">' + fmt(it.avg_buy_price) + '</td>' +
                            '<td class="font-number">' + fmt(it.current_price) + '</td>' +
                            '<td class="font-number text-warning text-nowrap">' + fmt(it
                                .total_fee) +
                            ' <i class="fa-regular fa-exclamation-circle js-cancel-fee-info ms-1" style="cursor:pointer"' +
                            ' data-sell-id="' + it.sell_order_id +
                            '" title="جزئیات محاسبه کارمزد"></i></td>' +
                            '<td class="font-number ' + ((parseFloat(it.gross_pnl) || 0) >= 0 ?
                                'text-success' : 'text-danger') + '">' +
                            fmt(it.refund) + '</td>' +
                            '</tr>';
                    });
                    html += '</tbody></table></div>';
                } else {
                    html +=
                        '<div class="small text-muted mb-1">پله فروش بازی ندارد — فقط وضعیت سفارش به CANCELED تغییر می‌کند.</div>';
                }
                html += '</div>';
            });

            if (!orders.length) {
                html += '<div class="alert alert-secondary mb-3">سفارش باز و قابل لغوی یافت نشد.' +
                    (activeMode === 'all' ? ' (فقط ربات کاربر خاموش می‌شود)' : '') + '</div>';
            }

            var t = data.totals || {};
            html += '<div class="row g-2 text-center">' +
                '<div class="col-4"><div class="p-2 border rounded">' +
                '<small class="text-muted d-block">ارزش لحظه‌ای پله‌های باز</small>' +
                '<strong class="font-number">' + fmt(t.total_current_value) + ' USDT</strong></div></div>' +
                '<div class="col-4"><div class="p-2 border rounded">' +
                '<small class="text-muted d-block">مجموع کارمزد برآوردی</small>' +
                '<strong class="font-number text-warning">' + fmt(t.total_fee) + ' USDT</strong></div></div>' +
                '<div class="col-4"><div class="p-2 border rounded border-success" style="background:rgba(40,199,111,.06)">' +
                '<small class="text-muted d-block">برگشتی برآوردی به موجودی</small>' +
                '<strong class="font-number text-success">' + fmt(t.refund_to_balance) +
                ' USDT</strong></div></div>' +
                '</div>';

            html += '<div class="small text-muted mt-3">' +
                '<i class="fas fa-shield-alt me-1"></i>' +
                'ترتیب اجرا: ابتدا سفارش‌های فروش باز در صرافی مرجع لغو می‌شوند، سپس دقیقاً به اندازه مجموع همان پله‌های لغوشده، موجودی به صورت Market فروخته می‌شود. پله‌های تکمیل‌شده دست نمی‌خورند.' +
                '</div>';

            bodyEl.innerHTML = html;
            initFeePopovers();

            // Allow confirm when nothing is in flight. "No open sells" is still
            // confirmable: single mode flips the order to CANCELED, all mode
            // turns the bot off.
            confirmBtn.disabled = blocked || (activeMode === 'single' && !orders.length);
        }

        document.querySelectorAll('.js-bot-cancel').forEach(function(btn) {
            btn.addEventListener('click', function() {
                activeCancelUrl = btn.dataset.cancelUrl;
                activeMode = btn.dataset.mode || 'single';
                titleEl.textContent = btn.dataset.title || 'لغو سفارش';
                confirmBtn.disabled = true;
                bodyEl.innerHTML =
                    '<div class="text-center py-4">' +
                    '<div class="spinner-border text-danger" role="status"></div>' +
                    '<div class="small text-muted mt-2">در حال دریافت پیش‌نمایش لغو…</div></div>';
                modal.show();

                post(btn.dataset.previewUrl)
                    .then(function(res) {
                        if (!res.ok || res.data.ok === false) {
                            console.error('[bot-cancel] preview failed', res);
                            var msg = (res.data && res.data.error) ||
                                ('خطا در دریافت پیش‌نمایش لغو (HTTP ' + res.status + ').');
                            renderError(msg);
                            return;
                        }
                        renderPreview(res.data);
                    })
                    .catch(function(err) {
                        console.error('[bot-cancel] preview network/parse error', err);
                        renderError('ارتباط با سرور برقرار نشد: ' +
                            (err && err.message ? err.message : String(err)));
                    });
            });
        });

        confirmBtn.addEventListener('click', function() {
            if (!activeCancelUrl) return;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span> در حال لغو…';

            post(activeCancelUrl)
                .then(function(res) {
                    if (!res.ok || res.data.ok === false) {
                        toast(res.data.error || 'خطا در لغو سفارش.', false);
                        renderError(res.data.error || 'خطا در لغو سفارش.');
                        return;
                    }
                    toast(res.data.message || 'عملیات لغو با موفقیت انجام شد.', true);
                    modal.hide();
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                })
                .catch(function(err) {
                    console.error('[bot-cancel] confirm network/parse error', err);
                    toast('ارتباط با سرور برقرار نشد: ' +
                        (err && err.message ? err.message : String(err)), false);
                })
                .finally(function() {
                    confirmBtn.innerHTML = '<i class="fas fa-ban me-1"></i> تایید و لغو';
                });
        });
    });
</script>
@endpush
