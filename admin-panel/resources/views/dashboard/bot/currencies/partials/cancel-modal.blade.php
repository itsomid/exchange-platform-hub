{{--
    Cancel-by-coin modal. Trigger buttons (class js-currency-cancel) carry:
        data-symbol, data-preview-url, data-cancel-url
--}}
<div class="modal fade" id="currencyCancelModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="currencyCancelTitle">لغو ارز</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="currencyCancelBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="currencyCancelClose">انصراف</button>
                <button type="button" class="btn btn-danger" id="currencyCancelConfirm" disabled>
                    <i class="fas fa-ban me-1"></i> تایید و لغو
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('currencyCancelModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        var modal = new bootstrap.Modal(modalEl);
        var bodyEl = document.getElementById('currencyCancelBody');
        var titleEl = document.getElementById('currencyCancelTitle');
        var confirmBtn = document.getElementById('currencyCancelConfirm');
        var closeBtn = document.getElementById('currencyCancelClose');
        var csrf = '{{ csrf_token() }}';
        var statusUrlTemplate = @json(route('admin.bot.currency.cancellation.status', ['cancellation' => '__ID__']));
        var active = null;
        var pollTimer = null;
        var reloadOnClose = false;

        function toast(text, ok) {
            Toastify({
                text: text,
                duration: ok ? 3000 : 5000,
                close: true,
                gravity: 'top',
                position: 'right',
                style: { background: ok ? '#28C76F' : '#EA5455' },
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
            return n.toLocaleString('en-US', { maximumFractionDigits: maxDec === undefined ? 8 : maxDec });
        }

        function request(method, url, payload) {
            var opts = {
                method: method,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            };
            if (payload) {
                opts.headers['Content-Type'] = 'application/json';
                opts.body = JSON.stringify(payload);
            }
            return fetch(url, opts).then(function(res) {
                return res.text().then(function(text) {
                    var data = null;
                    try { data = text ? JSON.parse(text) : null; } catch (e) {}
                    if (!data) data = { ok: false, error: 'پاسخ نامعتبر از سرور (HTTP ' + res.status + ').' };
                    if (!res.ok && data.ok !== false) {
                        data.ok = false;
                        data.error = data.message || data.error || ('خطا (HTTP ' + res.status + ')');
                    }
                    return data;
                });
            });
        }

        function spinner(text) {
            return '<div class="text-center py-4"><div class="spinner-border text-danger" role="status"></div>' +
                '<div class="small text-muted mt-2">' + text + '</div></div>';
        }

        function renderError(message) {
            bodyEl.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle me-1"></i>' +
                esc(message) + '</div>';
            confirmBtn.disabled = true;
        }

        function stat(label, value, extraClass) {
            return '<div class="col-6 col-md-3"><div class="p-2 border rounded h-100 ' + (extraClass || '') + '">' +
                '<small class="text-muted d-block">' + label + '</small>' +
                '<strong class="font-number">' + value + '</strong></div></div>';
        }

        function renderPreview(d) {
            var t = d.totals || {};
            var sym = esc(d.currency.symbol);
            var blocked = false;
            var html = '';

            if (d.running) {
                blocked = true;
                html += '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin me-1"></i>' +
                    'لغو دیگری برای این ارز (#' + esc(d.running) + ') در حال اجراست.</div>';
            }
            var fl = d.in_flight || {};
            if ((fl.buys_in_flight || 0) > 0 || (fl.sells_pending || 0) > 0) {
                blocked = true;
                html += '<div class="alert alert-warning"><i class="fas fa-hourglass-half me-1"></i>' +
                    'برخی سفارش‌های ' + sym + ' هنوز در حال خرید (' + (fl.buys_in_flight || 0) + ') یا ثبت پله‌های فروش (' +
                    (fl.sells_pending || 0) + ') هستند. تا پایان پردازش، لغو امکان‌پذیر نیست.</div>';
            }
            if (!(parseFloat(d.live_price) > 0)) {
                html += '<div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-triangle me-1"></i>' +
                    'قیمت لحظه‌ای ' + sym + ' در دسترس نیست؛ برآوردها ناقص است و بدون فروش در صرافی مرجع لغو اجرا نمی‌شود.</div>';
            }

            html += '<div class="row g-2 text-center mb-3">' +
                stat('کاربران درگیر', fmt(t.users, 0)) +
                stat('سفارش‌ها / پله‌های باز', fmt(t.orders, 0) + ' / ' + fmt(t.sell_orders, 0)) +
                stat('کل مقدار ' + sym, fmt(t.amount)) +
                stat('قیمت لحظه‌ای', fmt(d.live_price) + ' <small class="text-muted">USDT</small>') +
                stat('اصل سرمایه', fmt(t.principal, 4) + ' <small class="text-muted">USDT</small>') +
                stat('ارزش لحظه‌ای', fmt(t.current_value, 4) + ' <small class="text-muted">USDT</small>') +
                stat('کارمزد عملکرد (' + fmt(d.performance_fee_percent, 2) + '٪ از سود)', fmt(t.performance_fee, 4) +
                    ' <small class="text-muted">USDT</small>', 'text-warning') +
                stat('برگشتی برآوردی به کیف پول ربات', fmt(t.refund, 4) + ' <small class="text-muted">USDT</small>',
                    'border-success') +
                '</div>';

            html += '<div class="small text-muted mb-3"><i class="fas fa-calculator me-1"></i>' +
                'برای هر کاربر: اگر ارز در سود باشد، اصل سرمایه + (سود − کارمزد عملکرد) برمی‌گردد؛ اگر در ضرر باشد، دقیقاً اصل سرمایه برمی‌گردد. ' +
                'کارمزد شبکه و کارمزد صرافی مرجع از کاربر کسر نمی‌شود.</div>';

            var users = d.users || [];
            if (users.length) {
                html += '<div class="table-responsive mb-3" style="max-height:280px">' +
                    '<table class="table table-sm table-bordered align-middle mb-0"><thead class="table-light"><tr>' +
                    '<th>کاربر</th><th>سفارش / پله</th><th>مقدار</th><th>اصل سرمایه</th><th>ارزش لحظه‌ای</th>' +
                    '<th>سود/زیان</th><th>کارمزد عملکرد</th><th>برگشتی برآوردی</th></tr></thead><tbody>';
                users.forEach(function(u) {
                    var pnl = parseFloat(u.gross_pnl) || 0;
                    html += '<tr>' +
                        '<td><small>' + esc(u.email || u.mobile || ('#' + u.user_id)) + '</small></td>' +
                        '<td class="font-number">' + u.orders_count + ' / ' + u.tiers + '</td>' +
                        '<td class="font-number">' + fmt(u.amount) + '</td>' +
                        '<td class="font-number">' + fmt(u.principal, 4) + '</td>' +
                        '<td class="font-number">' + fmt(u.current_value, 4) + '</td>' +
                        '<td class="font-number ' + (pnl >= 0 ? 'text-success' : 'text-danger') + '">' + fmt(pnl, 4) + '</td>' +
                        '<td class="font-number text-warning">' + fmt(u.performance_fee, 4) + '</td>' +
                        '<td class="font-number text-success fw-bold">' + fmt(u.refund, 4) + '</td>' +
                        '</tr>';
                });
                html += '</tbody></table></div>';
            } else {
                blocked = true;
                html += '<div class="alert alert-secondary">برای این ارز پله فروش بازی در سفارش کاربران وجود ندارد.</div>';
            }

            html += '<div class="border rounded p-3 mb-3" style="background:rgba(0,0,0,.015)">' +
                '<h6 class="mb-3"><i class="fas fa-exchange-alt me-1"></i> تعیین تکلیف ' + sym + ' در صرافی مرجع</h6>' +
                '<div class="form-check form-switch mb-2">' +
                '<input class="form-check-input" type="checkbox" id="ccCancelOnExchange">' +
                '<label class="form-check-label" for="ccCancelOnExchange">لغو همه پله‌های فروش ' + sym + ' در صرافی مرجع</label></div>' +
                '<div class="form-check form-switch mb-2">' +
                '<input class="form-check-input" type="checkbox" id="ccSellOnExchange" disabled>' +
                '<label class="form-check-label" for="ccSellOnExchange">فروش ' + sym + ' در صرافی مرجع بعد از لغو پله‌ها (یک سفارش Market برای کل مقدار)</label></div>' +
                '<div class="small mt-2" id="ccModeHint"></div>' +
                '</div>';

            html += '<div class="mb-3">' +
                '<label class="form-label fw-semibold" for="ccReason">دلیل لغو <span class="text-danger">*</span></label>' +
                '<textarea class="form-control" id="ccReason" rows="3" maxlength="2000" ' +
                'placeholder="مثلاً: حذف ' + sym + ' از صرافی مرجع / توقف معاملات این ارز"></textarea>' +
                '<div class="form-text">این متن روی کارت اجرای خرید و توضیحات سیستمی هر سفارش درگیر ثبت می‌شود.</div></div>';

            html += '<div class="alert alert-warning py-2 small mb-0"><i class="fas fa-power-off me-1"></i>' +
                (d.signal_active
                    ? 'سیگنال ' + sym + ' همزمان با شروع لغو غیرفعال می‌شود تا ربات دوباره این ارز را نخرد.'
                    : 'سیگنال ' + sym + ' در حال حاضر فعال نیست.') +
                ' لغو در پس‌زمینه اجرا می‌شود و نتیجه همین‌جا نمایش داده می‌شود.</div>';

            bodyEl.innerHTML = html;

            var cancelSw = document.getElementById('ccCancelOnExchange');
            var sellSw = document.getElementById('ccSellOnExchange');
            var hint = document.getElementById('ccModeHint');

            function syncSwitches() {
                if (!cancelSw.checked) sellSw.checked = false;
                sellSw.disabled = !cancelSw.checked;
                if (sellSw.checked) {
                    hint.className = 'small mt-2 text-danger';
                    hint.innerHTML = '<i class="fas fa-info-circle me-1"></i>ابتدا پله‌های فروش باز در صرافی مرجع لغو می‌شوند، سپس مجموع ' +
                        sym + ' آزادشده با یک سفارش Market فروخته می‌شود و همه کاربران با میانگین قیمت همان فروش تسویه می‌شوند.';
                } else if (cancelSw.checked) {
                    hint.className = 'small mt-2 text-warning';
                    hint.innerHTML = '<i class="fas fa-info-circle me-1"></i>پله‌های فروش در صرافی مرجع لغو می‌شوند اما ' + sym +
                        ' در حساب صرافی مرجع باقی می‌ماند. تسویه کاربران با قیمت لحظه‌ای انجام می‌شود.';
                } else {
                    hint.className = 'small mt-2 text-muted';
                    hint.innerHTML = '<i class="fas fa-info-circle me-1"></i>هیچ ارتباطی با صرافی مرجع برقرار نمی‌شود؛ پله‌ها فقط در سیستم لغو ' +
                        'و کاربران با قیمت لحظه‌ای تسویه می‌شوند. سفارش‌های فروش در صرافی مرجع دست نمی‌خورند.';
                }
            }
            cancelSw.addEventListener('change', syncSwitches);
            sellSw.addEventListener('change', syncSwitches);
            syncSwitches();

            confirmBtn.disabled = blocked;
        }

        function renderStatus(c) {
            var finished = c.finished;
            var cls = c.status === 'DONE' ? 'success' : (c.status === 'FAILED' ? 'danger' : 'warning');
            var html = '<div class="alert alert-' + cls + '">' +
                (finished ? '' : '<span class="spinner-border spinner-border-sm me-2"></span>') +
                'لغو #' + c.id + ' — ' + esc(c.currency_symbol) + ': <strong>' + esc(c.status_label) + '</strong></div>';

            if (c.error) {
                html += '<div class="alert alert-danger small">' + esc(c.error) + '</div>';
            }
            if (c.sell_error) {
                html += '<div class="alert alert-warning small"><i class="fas fa-exclamation-triangle me-1"></i>' +
                    'فروش در صرافی مرجع ناموفق بود و تسویه با قیمت لحظه‌ای انجام شد: ' + esc(c.sell_error) + '</div>';
            }
            if (c.status === 'DONE') {
                html += '<div class="row g-2 text-center">' +
                    stat('کاربران', fmt(c.users_count, 0)) +
                    stat('سفارش‌ها / پله‌های لغوشده', fmt(c.orders_count, 0) + ' / ' + fmt(c.sell_orders_count, 0)) +
                    stat('قیمت تسویه', fmt(c.settlement_price) + ' <small class="text-muted">' +
                        (c.price_source === 'market_sell' ? 'فروش واقعی' : 'لحظه‌ای') + '</small>') +
                    stat('برگشتی به کیف پول ربات', fmt(c.total_refund, 4) + ' <small class="text-muted">USDT</small>', 'border-success') +
                    '</div>';
                if (c.skipped_count > 0) {
                    html += '<div class="alert alert-warning small mt-3 mb-0">' + c.skipped_count +
                        ' پله لغو نشد (در صرافی مرجع پر شده بود یا لغو آن در صرافی ناموفق بود) و باز باقی ماند.</div>';
                }
            }
            bodyEl.innerHTML = html;
        }

        function poll(id) {
            var url = statusUrlTemplate.replace('__ID__', id);
            request('GET', url).then(function(d) {
                if (!d.ok || !d.cancellation) return;
                renderStatus(d.cancellation);
                if (d.cancellation.finished) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                    closeBtn.textContent = 'بستن';
                    if (d.cancellation.status === 'DONE') {
                        toast('لغو ' + d.cancellation.currency_symbol + ' انجام شد.', true);
                    } else {
                        toast('لغو ' + d.cancellation.currency_symbol + ' ناموفق بود.', false);
                    }
                }
            });
        }

        document.querySelectorAll('.js-currency-cancel').forEach(function(btn) {
            btn.addEventListener('click', function() {
                active = { symbol: btn.dataset.symbol, cancelUrl: btn.dataset.cancelUrl };
                titleEl.textContent = 'لغو ارز ' + btn.dataset.symbol + ' در سفارش‌های همه کاربران';
                confirmBtn.classList.remove('d-none');
                confirmBtn.disabled = true;
                closeBtn.textContent = 'انصراف';
                bodyEl.innerHTML = spinner('در حال دریافت پیش‌نمایش لغو…');
                modal.show();

                request('POST', btn.dataset.previewUrl).then(function(d) {
                    if (d.ok === false) {
                        renderError(d.error || 'خطا در دریافت پیش‌نمایش لغو.');
                        return;
                    }
                    renderPreview(d);
                }).catch(function(err) {
                    renderError('ارتباط با سرور برقرار نشد: ' + (err && err.message ? err.message : String(err)));
                });
            });
        });

        confirmBtn.addEventListener('click', function() {
            if (!active) return;
            var reasonEl = document.getElementById('ccReason');
            var reason = reasonEl ? reasonEl.value.trim() : '';
            if (reason.length < 3) {
                reasonEl.classList.add('is-invalid');
                toast('نوشتن دلیل لغو الزامی است.', false);
                return;
            }
            reasonEl.classList.remove('is-invalid');

            var payload = {
                cancel_on_exchange: document.getElementById('ccCancelOnExchange').checked,
                sell_on_exchange: document.getElementById('ccSellOnExchange').checked,
                reason: reason,
            };

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> در حال ثبت…';

            request('POST', active.cancelUrl, payload).then(function(d) {
                if (d.ok === false) {
                    toast(d.error || 'خطا در ثبت لغو.', false);
                    confirmBtn.disabled = false;
                    return;
                }
                toast(d.message || 'لغو در صف اجرا قرار گرفت.', true);
                reloadOnClose = true;
                confirmBtn.classList.add('d-none');
                bodyEl.innerHTML = spinner('لغو در صف اجرا قرار گرفت…');
                poll(d.cancellation_id);
                pollTimer = setInterval(function() { poll(d.cancellation_id); }, 3000);
            }).catch(function(err) {
                toast('ارتباط با سرور برقرار نشد: ' + (err && err.message ? err.message : String(err)), false);
                confirmBtn.disabled = false;
            }).finally(function() {
                confirmBtn.innerHTML = '<i class="fas fa-ban me-1"></i> تایید و لغو';
            });
        });

        modalEl.addEventListener('hidden.bs.modal', function() {
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
            if (reloadOnClose) window.location.reload();
        });
    });
</script>
@endpush
