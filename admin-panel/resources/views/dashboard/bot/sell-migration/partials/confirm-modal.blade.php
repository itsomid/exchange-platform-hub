{{--
    Sell-migration confirm modal. Trigger buttons (class js-sell-migration) carry:
        data-symbol, data-preview-url, data-start-url
--}}
<div class="modal fade" id="sellMigrationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sellMigrationTitle">انتقال پله‌های فروش</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="sellMigrationBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="sellMigrationClose">انصراف</button>
                <button type="button" class="btn btn-primary" id="sellMigrationConfirm" disabled>
                    <i class="fas fa-check me-1"></i> تایید و ایجاد پله‌ها در Binance
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('sellMigrationModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        var modal = new bootstrap.Modal(modalEl);
        var bodyEl = document.getElementById('sellMigrationBody');
        var titleEl = document.getElementById('sellMigrationTitle');
        var confirmBtn = document.getElementById('sellMigrationConfirm');
        var closeBtn = document.getElementById('sellMigrationClose');
        var csrf = '{{ csrf_token() }}';
        var statusUrlTemplate = @json(route('admin.bot.sell-migration.status', ['migration' => '__ID__']));
        var confirmHtml = confirmBtn.innerHTML;
        var active = null;
        var pollTimer = null;
        var reloadOnClose = false;
        var tooltips = [];

        function setBody(html) {
            tooltips.forEach(function(t) { t.dispose(); });
            bodyEl.innerHTML = html;
            tooltips = Array.prototype.map.call(bodyEl.querySelectorAll('[data-bs-toggle="tooltip"]'), function(el) {
                return new bootstrap.Tooltip(el, { container: modalEl });
            });
        }

        function pct(v) {
            var n = parseFloat(v);
            return '<span class="font-number" dir="ltr">' + (n >= 0 ? '+' : '') + fmt(n, 2) + '٪</span>';
        }

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

        function request(method, url) {
            return fetch(url, {
                method: method,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            }).then(function(res) {
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
            return '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div>' +
                '<div class="small text-muted mt-2">' + text + '</div></div>';
        }

        function renderError(message) {
            setBody('<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle me-1"></i>' +
                esc(message) + '</div>');
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
            var bal = d.balance || null;
            var html = '';

            if (d.block_reason) {
                html += '<div class="alert alert-danger"><i class="fas fa-ban me-1"></i>' + esc(d.block_reason) + '</div>';
            }

            html += '<div class="row g-2 text-center mb-3">' +
                stat('کاربران / سفارش‌ها', fmt(t.users, 0) + ' / ' + fmt(t.orders, 0)) +
                stat('پله‌های در انتظار انتقال', fmt(t.tiers, 0)) +
                stat('پله‌های پرشده (نادیده گرفته می‌شوند)', fmt(d.filled_tiers, 0), 'text-muted') +
                stat('قیمت لحظه‌ای', fmt(d.live_price) + ' <small class="text-muted">USDT</small>') +
                stat('مقدار موردنیاز ' + sym, fmt(d.required_amount), 'border-primary') +
                stat('موجودی آزاد Binance', bal ? fmt(bal.available) : '—', d.sufficient ? 'border-success' : 'border-danger') +
                stat('موجودی قفل Binance', bal ? fmt(bal.frozen) : '—') +
                stat('وضعیت موجودی', d.sufficient
                    ? '<span class="text-success">کافی</span>'
                    : '<span class="text-danger">کمبود ' + fmt(d.shortfall) + '</span>') +
                '</div>';

            html += '<div class="row g-2 text-center mb-3">' +
                stat('ثبت Limit در Binance', fmt(t.limit_tiers, 0) + ' پله · ' + fmt(t.limit_amount) + ' ' + sym, 'border-info') +
                stat('تارگت خورده → فروش Market و تسویه', fmt(t.market_tiers, 0) + ' پله · ' + fmt(t.market_amount) + ' ' + sym, 'border-warning') +
                stat('ادغام با پله مجاور', fmt(t.merge_tiers, 0) + ' پله · ' + fmt(t.merge_amount) + ' ' + sym, 'border-secondary') +
                stat('بستن و برگشت به کیف پول', fmt(t.close_tiers, 0) + ' پله · ' + fmt(t.close_amount) + ' ' + sym, 'border-danger') +
                stat('احتمال رد به‌خاطر سقف قیمت', fmt(t.price_limit_tiers, 0) + ' پله · ' + fmt(t.price_limit_amount) + ' ' + sym,
                    t.price_limit_tiers ? 'border-danger text-danger' : '') +
                '</div>';

            var limit = d.price_limit || null;
            if (limit) {
                var over = (d.tiers || []).filter(function(r) { return r.price_limit_exceeded; })
                    .map(function(r) { return parseFloat(r.over_max_percent); });
                html += '<div class="alert ' + (over.length ? 'alert-danger' : 'alert-info') + ' py-2 small">' +
                    '<div class="mb-1"><i class="fas fa-info-circle me-1"></i><strong>سقف قیمت فروش در Binance برای ' + sym + ':</strong> ' +
                    'Binance سفارش فروش Limit را حداکثر تا <span class="font-number">' + fmt(limit.multiplier_up, 4) + '</span> برابر ' +
                    'میانگین قیمت ' + (limit.avg_price_mins ? '<span class="font-number">' + limit.avg_price_mins + '</span> دقیقه اخیر' : 'اخیر') +
                    ' قبول می‌کند؛ یعنی حداکثر ' + pct(limit.max_percent_above) + ' بالاتر از قیمت بازار.</div>' +
                    '<div class="mb-1">' + (limit.reference === 'avg'
                        ? 'با میانگین فعلی <span class="font-number">' + fmt(limit.reference_price) + '</span> USDT'
                        : 'میانگین Binance دریافت نشد؛ با قیمت لحظه‌ای <span class="font-number">' + fmt(limit.reference_price) + '</span> USDT تخمین زده شد و') +
                    '، بالاترین قیمت فروش قابل ثبت الان <strong class="font-number">' + fmt(limit.max_price) + '</strong> USDT است.</div>';
                if (over.length) {
                    html += '<div><strong><span class="font-number">' + over.length + '</span> پله (' + fmt(t.price_limit_amount) + ' ' + sym + ')</strong> ' +
                        'قیمت فروششان بین ' + pct(Math.min.apply(null, over)) + ' تا ' + pct(Math.max.apply(null, over)) +
                        ' بالاتر از این سقف است و Binance احتمالاً آن‌ها را رد می‌کند. این پله‌ها دست‌نخورده می‌مانند و ' +
                        'وقتی قیمت بازار به اندازه کافی نزدیک شد، با اجرای دوباره قابل ثبت‌اند. چون میانگین قیمت مدام تغییر می‌کند، این عدد تخمینی است.</div>';
                } else {
                    html += '<div>همه پله‌های Limit زیر این سقف‌اند.</div>';
                }
                html += '</div>';
            } else if ((t.limit_tiers || 0) > 0) {
                html += '<div class="alert alert-secondary py-2 small"><i class="fas fa-info-circle me-1"></i>' +
                    'سقف قیمت فروش Binance برای این ارز دریافت نشد؛ پله‌ها از نظر فاصله با قیمت بازار بررسی نشدند.</div>';
            }

            if ((t.merge_tiers || 0) > 0 || (t.close_tiers || 0) > 0) {
                var close = d.close || null;
                html += '<div class="alert alert-warning py-2 small"><i class="fas fa-exclamation-triangle me-1"></i>' +
                    'پله‌هایی که مقدار یا ارزش‌شان کمتر از حداقل سفارش Binance است (فیلترهای LOT_SIZE / NOTIONAL خود Binance) ' +
                    'ابتدا با پله مجاور همان خرید ادغام می‌شوند (پله با تارگت پایین‌تر باقی می‌ماند و مقدار و سهم دو پله جمع می‌شود).';
                if ((t.close_tiers || 0) > 0) {
                    html += ' پله‌هایی که ادغام‌شان ممکن نیست، همه با هم در یک سفارش Market فروخته و با قاعده «لغو ارز» تسویه می‌شوند: ' +
                        'در سود، اصل سرمایه + سود منهای کارمزد عملکرد (' + fmt(d.performance_fee_percent, 2) + '٪)؛ در ضرر، فقط اصل سرمایه. ' +
                        'اگر فروش Market در Binance ناموفق باشد، تسویه با قیمت لحظه‌ای انجام می‌شود و کوین روی حساب پلتفرم می‌ماند.';
                    if (close && !close.sellable) {
                        html += ' <strong>مجموع این پله‌ها (' + fmt(close.amount) + ' ' + sym + ' ≈ ' + fmt(close.value, 2) +
                            ' USDT) هم کمتر از حداقل Binance است؛ چیزی فروخته نمی‌شود، تسویه با قیمت لحظه‌ای انجام می‌شود ' +
                            'و این مقدار کوین روی حساب پلتفرم باقی می‌ماند.</strong>';
                    }
                }
                html += '</div>';
            }

            var tiers = d.tiers || [];
            if (tiers.length) {
                html += '<div class="table-responsive mb-3" style="max-height:340px">' +
                    '<table class="table table-sm table-bordered align-middle mb-0"><thead class="table-light"><tr>' +
                    '<th>پله</th><th>کاربر / سفارش</th><th>تارگت</th><th>قیمت فروش</th><th>مقدار</th>' +
                    '<th>مقدار در Binance</th><th>سفارش قبلی CoinEx</th><th>اقدام</th></tr></thead><tbody>';
                tiers.forEach(function(r) {
                    var target = r.target_type === 'percent'
                        ? fmt(r.target_value, 4) + '٪ <small class="text-muted">(خرید ' + fmt(r.avg_buy_price) + ')</small>'
                        : fmt(r.target_value);
                    var action = {
                        market: '<span class="badge bg-label-warning">Market + تسویه</span>',
                        limit: '<span class="badge bg-label-info">Limit</span>',
                        merge: '<span class="badge bg-label-secondary">ادغام در #' + r.merge_into + '</span>',
                        close: '<span class="badge bg-label-danger">بستن + برگشت</span>',
                    }[r.action] || esc(r.action);
                    if (r.merged_amount) {
                        action += '<div><small class="text-muted">پس از ادغام: <span class="font-number">' + fmt(r.merged_amount) +
                            '</span> · ' + fmt(r.merged_share, 2) + '٪</small></div>';
                    }
                    if (r.estimated_refund) {
                        action += '<div><small class="text-muted">برگشت تقریبی: <span class="font-number">' + fmt(r.estimated_refund, 4) +
                            '</span> USDT</small></div>';
                    }
                    if (r.price_limit_exceeded && limit) {
                        var tip = 'قیمت فروش این پله ' + fmt(r.target_price) + ' USDT است، یعنی ' + fmt(r.above_live_percent, 2) +
                            '٪ بالاتر از قیمت فعلی بازار. Binance برای فروش ' + d.currency.symbol + ' حداکثر تا ' + fmt(limit.max_price) +
                            ' USDT (' + fmt(limit.multiplier_up, 4) + ' برابر میانگین قیمت اخیر، +' + fmt(limit.max_percent_above, 2) +
                            '٪) سفارش قبول می‌کند. این پله ' + fmt(r.over_max_percent, 2) + '٪ بالاتر از این سقف است، پس Binance احتمالاً ' +
                            'آن را رد می‌کند و پله دست‌نخورده می‌ماند تا قیمت بازار نزدیک‌تر شود.';
                        action += '<div class="mt-1"><span class="badge bg-label-danger">احتمال رد: سقف قیمت</span> ' +
                            '<i class="fas fa-info-circle text-danger" role="button" data-bs-toggle="tooltip" data-bs-placement="left" title="' +
                            esc(tip).replace(/"/g, '&quot;') + '"></i></div>';
                    }
                    var rowClass = { market: 'table-warning', merge: 'table-secondary', close: 'table-danger' }[r.action] ||
                        (r.price_limit_exceeded ? 'table-danger' : '');
                    html += '<tr class="' + rowClass + '">' +
                        '<td class="font-number">#' + r.sell_order_id + '<div><small class="text-muted">' + fmt(r.share_percent, 2) + '٪</small></div></td>' +
                        '<td><small>' + esc(r.user_email || r.user_mobile || ('#' + r.user_id)) + '</small>' +
                        '<div><small class="text-muted font-number">سفارش #' + r.bot_order_id + '</small></div></td>' +
                        '<td class="font-number">' + target + '</td>' +
                        '<td class="font-number">' + fmt(r.target_price) +
                            (r.above_live_percent !== null && r.above_live_percent !== undefined
                                ? '<div><small class="' + (r.price_limit_exceeded ? 'text-danger fw-semibold' : 'text-muted') + '">' +
                                    pct(r.above_live_percent) + ' نسبت به بازار</small></div>'
                                : '') + '</td>' +
                        '<td class="font-number">' + fmt(r.amount) + '</td>' +
                        '<td class="font-number">' + fmt(r.binance_quantity) + '</td>' +
                        '<td class="font-number"><small>' + esc(r.exchange_order_id || '—') + '</small></td>' +
                        '<td class="text-nowrap">' + action + '</td>' +
                        '</tr>';
                });
                html += '</tbody></table></div>';
            }

            html += '<div class="alert alert-info py-2 small mb-0"><i class="fas fa-info-circle me-1"></i>' +
                'برای هر پله، اگر قیمت لحظه‌ای هنوز به قیمت فروش نرسیده باشد یک سفارش Limit در Binance ثبت و شماره سفارش جدید ' +
                'روی همان رکورد پله ذخیره می‌شود (رکورد جدیدی ساخته نمی‌شود). اگر قیمت از تارگت عبور کرده باشد، همان مقدار با ' +
                'سفارش Market فروخته و پله با قیمت واقعی فروش، مثل یک پله پرشده، تسویه می‌شود. وضعیت تارگت هنگام اجرا با قیمت همان ' +
                'لحظه دوباره بررسی می‌شود. مقدار در Binance به گام مجاز همان بازار گرد می‌شود.</div>';

            setBody(html);
            confirmBtn.disabled = !d.can_start;
        }

        function renderStatus(m) {
            var cls = m.status === 'DONE' ? 'success' : (m.status === 'FAILED' ? 'danger' : 'warning');
            var html = '<div class="alert alert-' + cls + '">' +
                (m.finished ? '' : '<span class="spinner-border spinner-border-sm me-2"></span>') +
                'انتقال #' + m.id + ' — ' + esc(m.currency_symbol) + ': <strong>' + esc(m.status_label) + '</strong></div>';

            if (m.error) {
                html += '<div class="alert alert-danger small">' + esc(m.error) + '</div>';
            }
            if (m.status === 'DONE') {
                html += '<div class="row g-2 text-center">' +
                    stat('پله‌ها', fmt(m.tiers_count, 0)) +
                    stat('ثبت Limit در Binance', fmt(m.placed_count, 0), 'border-info') +
                    stat('تکمیل با Market', fmt(m.completed_count, 0), 'border-warning') +
                    stat('ادغام‌شده', fmt(m.merged_count, 0), 'border-secondary') +
                    stat('بسته و برگشت به کیف پول', fmt(m.closed_count, 0), 'border-danger') +
                    stat('ناموفق', fmt(m.failed_count, 0), m.failed_count ? 'border-danger' : '') +
                    '</div>';

                var close = (m.details || {}).close || null;
                if (close && m.closed_count) {
                    html += '<div class="alert alert-info small mt-3 mb-0">بستن پله‌های زیر حداقل: ' + fmt(close.amount) + ' ' +
                        esc(m.currency_symbol) + ' با قیمت ' + fmt(close.price) + ' USDT ' +
                        (close.price_source === 'market_sell'
                            ? '(فروش Market، سفارش ' + esc(close.exchange_order_id) + ')'
                            : (close.sell_error
                                ? '(قیمت لحظه‌ای؛ فروش Market در Binance ناموفق بود: ' + esc(close.sell_error) + ' — کوین روی حساب پلتفرم ماند)'
                                : '(قیمت لحظه‌ای؛ مجموع کمتر از حداقل Binance بود و فروخته نشد)')) +
                        '</div>';
                }

                var failed = ((m.details || {}).tiers || []).filter(function(r) { return r.outcome === 'failed'; });
                if (failed.length) {
                    html += '<div class="alert alert-warning small mt-3 mb-0"><strong>پله‌های ناموفق (دست‌نخورده، با اجرای دوباره تکرار می‌شوند):</strong><ul class="mb-0">' +
                        failed.map(function(r) { return '<li>#' + r.sell_order_id + ': ' + esc(r.error) + '</li>'; }).join('') +
                        '</ul></div>';
                }
            }
            setBody(html);
        }

        function poll(id) {
            request('GET', statusUrlTemplate.replace('__ID__', id)).then(function(d) {
                if (!d.ok || !d.migration) return;
                renderStatus(d.migration);
                if (d.migration.finished) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                    closeBtn.textContent = 'بستن';
                    if (d.migration.status === 'DONE') {
                        toast('انتقال پله‌های ' + d.migration.currency_symbol + ' انجام شد.', true);
                    } else {
                        toast('انتقال پله‌های ' + d.migration.currency_symbol + ' ناموفق بود.', false);
                    }
                }
            });
        }

        document.querySelectorAll('.js-sell-migration').forEach(function(btn) {
            btn.addEventListener('click', function() {
                active = { startUrl: btn.dataset.startUrl };
                titleEl.textContent = 'ایجاد پله‌های فروش ' + btn.dataset.symbol + ' در Binance';
                confirmBtn.classList.remove('d-none');
                confirmBtn.disabled = true;
                closeBtn.textContent = 'انصراف';
                setBody(spinner('در حال دریافت اطلاعات پله‌ها و موجودی Binance…'));
                modal.show();

                request('POST', btn.dataset.previewUrl).then(function(d) {
                    if (d.ok === false) {
                        renderError(d.error || 'خطا در دریافت پیش‌نمایش.');
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
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> در حال ثبت…';

            request('POST', active.startUrl).then(function(d) {
                if (d.ok === false) {
                    toast(d.error || 'خطا در شروع انتقال.', false);
                    confirmBtn.disabled = false;
                    return;
                }
                toast(d.message || 'انتقال در صف اجرا قرار گرفت.', true);
                reloadOnClose = true;
                confirmBtn.classList.add('d-none');
                setBody(spinner('انتقال در صف اجرا قرار گرفت…'));
                poll(d.migration_id);
                pollTimer = setInterval(function() { poll(d.migration_id); }, 3000);
            }).catch(function(err) {
                toast('ارتباط با سرور برقرار نشد: ' + (err && err.message ? err.message : String(err)), false);
                confirmBtn.disabled = false;
            }).finally(function() {
                confirmBtn.innerHTML = confirmHtml;
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
