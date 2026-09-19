{{--
    "Why didn't this free balance get bought?"

    Renders bot_buy_attempts — one row api-service writes on every
    BotBuyOrchestrator run — next to the settlement timeline, so an idle free
    balance can be traced to the trigger that failed to deploy it and to the
    sell fill that freed it in the first place.

    Trigger button contract (class js-bot-buy-attempts):
        data-attempts-url : GET endpoint returning { attempts, settlements }
--}}
<div class="modal fade" id="botBuyAttemptsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-search-dollar me-1 text-success"></i>
                    سرنوشت موجودی آزاد کاربر
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="botBuyAttemptsBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status"></div>
                    <div class="small text-muted mt-2">در حال خواندن سابقه تلاش‌های خرید…</div>
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
        var modalEl = document.getElementById('botBuyAttemptsModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        var modal = new bootstrap.Modal(modalEl);
        var bodyEl = document.getElementById('botBuyAttemptsBody');

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

        function renderError(message) {
            bodyEl.innerHTML =
                '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle me-1"></i>' +
                esc(message) + '</div>';
        }

        function table(headers, rows) {
            if (!rows) return '';
            return '<div class="table-responsive mb-2">' +
                '<table class="table table-sm table-bordered align-middle mb-0">' +
                '<thead class="table-light"><tr><th>' + headers.join('</th><th>') + '</th></tr></thead>' +
                '<tbody>' + rows + '</tbody></table></div>';
        }

        function sectionTitle(icon, text) {
            return '<div class="small text-muted mb-1 mt-2">' +
                '<i class="fas ' + icon + ' fa-xs me-1"></i>' + text + '</div>';
        }

        function renderDetails(a) {
            var html = '';

            if (a.allocations && a.allocations.length) {
                var rows = '';
                a.allocations.forEach(function(x) {
                    rows += '<tr>' +
                        '<td><strong>' + esc(x.currency_symbol) + '</strong></td>' +
                        '<td class="font-number">' + fmt(x.amount_usdt) + '</td>' +
                        '<td class="font-number">' + fmt(x.share_percent, 2) + '%</td>' +
                        '<td class="font-number">' + fmt(x.current_price) + '</td>' +
                        '<td class="font-number">' + fmt(x.floor_price) + ' — ' + fmt(x.ceiling_price) + '</td>' +
                        '</tr>';
                });
                html += sectionTitle('fa-check-circle text-success', 'ارزهایی که سهم خرید گرفتند');
                html += table(['ارز', 'مبلغ (USDT)', 'سهم', 'قیمت لحظه‌ای', 'بازه مجاز'], rows);
            }

            if (a.skipped && a.skipped.length) {
                var rows2 = '';
                a.skipped.forEach(function(x) {
                    rows2 += '<tr>' +
                        '<td><strong>' + esc(x.currency_symbol || '—') + '</strong></td>' +
                        '<td class="font-number">' + fmt(x.would_have_received) + '</td>' +
                        '<td>' + (x.cap_exhausted ?
                            '<span class="badge bg-label-secondary" style="font-size:.65rem">سقف تخصیص پر بود</span>' :
                            '<span class="badge bg-label-warning" style="font-size:.65rem">کمتر از حداقل خرید</span>') +
                        '</td>' +
                        '<td class="small text-muted">' + esc(x.reason) + '</td>' +
                        '</tr>';
                });
                html += sectionTitle('fa-ban text-warning', 'ارزهایی که سهم گرفتند اما خریداری نشدند');
                html += table(['ارز', 'سهم محاسبه‌شده (USDT)', 'علت', 'توضیح'], rows2);
            }

            if (a.out_of_range && a.out_of_range.length) {
                var rows3 = '';
                a.out_of_range.forEach(function(x) {
                    rows3 += '<tr>' +
                        '<td><strong>' + esc(x.currency_symbol || '—') + '</strong></td>' +
                        '<td class="font-number">' + fmt(x.current_price) + '</td>' +
                        '<td class="font-number">' + fmt(x.floor_price) + ' — ' + fmt(x.ceiling_price) + '</td>' +
                        '</tr>';
                });
                html += sectionTitle('fa-arrows-alt-h', 'سیگنال‌هایی که قیمت لحظه‌ای‌شان بیرون از بازه بود');
                html += table(['ارز', 'قیمت لحظه‌ای', 'بازه مجاز'], rows3);
            }

            if (a.unpriced && a.unpriced.length) {
                var names = a.unpriced.map(function(x) {
                    return esc(x.currency_symbol || ('#' + x.currency_id));
                }).join('، ');
                html += '<div class="alert alert-danger py-2 small mb-2">' +
                    '<i class="fas fa-plug me-1"></i>قیمت لحظه‌ای این ارزها در دسترس نبود: <strong>' +
                    names + '</strong> — خطای قیمت‌دهی صرافی مرجع.</div>';
            }

            if (a.exception) {
                html += '<div class="alert alert-danger py-2 small mb-2">' +
                    '<i class="fas fa-bug me-1"></i><strong>خطای سیستمی:</strong> ' +
                    '<code style="direction:ltr;display:inline-block">' + esc(a.exception) + '</code></div>';
            }

            return html || '<div class="small text-muted">جزئیات ارزی برای این تلاش ثبت نشده است.</div>';
        }

        function outcomeBadge(a) {
            if (a.outcome === 'ORDER_CREATED') {
                return '<span class="badge bg-success">' + esc(a.outcome_label) + '</span>';
            }
            if (a.outcome === 'EXCEPTION') {
                return '<span class="badge bg-danger">' + esc(a.outcome_label) + '</span>';
            }
            return '<span class="badge bg-label-warning">' + esc(a.outcome_label) + '</span>';
        }

        function chip(label, value, note) {
            return '<div class="col-6 col-md-3"><div class="border rounded p-2 text-center h-100">' +
                '<small class="text-muted d-block" style="font-size:.65rem">' + label + '</small>' +
                '<strong class="font-number d-block">' + value + '</strong>' +
                '<small class="text-muted" style="font-size:.6rem">' + (note || 'USDT') + '</small>' +
                '</div></div>';
        }

        function renderAttempt(a) {
            var border = a.outcome === 'ORDER_CREATED' ? 'border-success' :
                (a.outcome === 'EXCEPTION' ? 'border-danger' : 'border-warning');
            var collapseId = 'botAttemptDetail' + a.id;
            var gateNote = a.gate_kind === 'buy_floor' ?
                'کف خرید ارزان‌ترین ارز' : 'حداقل واریز خالص';

            var links = '';
            if (a.bot_order_id) {
                links += '<span class="badge bg-label-primary ms-1" style="font-size:.65rem">سفارش #' +
                    esc(a.bot_order_id) + '</span>';
            }
            if (a.bot_sell_order_id) {
                links += '<span class="badge bg-label-info ms-1" style="font-size:.65rem">ناشی از پله فروش #' +
                    esc(a.bot_sell_order_id) + '</span>';
            }

            return '<div class="border ' + border + ' rounded p-3 mb-2">' +
                '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">' +
                '<div>' + outcomeBadge(a) +
                ' <strong class="ms-1">' + esc(a.trigger_label) + '</strong>' + links + '</div>' +
                '<small class="text-muted font-number">' + esc(a.at_display) + '</small>' +
                '</div>' +

                '<div class="small mb-2">' + esc(a.reason_message) + '</div>' +

                '<div class="row g-2 mb-2">' +
                chip('موجودی آزاد در آن لحظه', fmt(a.free_balance)) +
                chip('حداقل لازم', a.gate_amount === null ? '—' : fmt(a.gate_amount), gateNote) +
                chip('قفل‌شده در آن لحظه', fmt(a.locked_balance)) +
                chip('مبلغ تخصیص‌یافته', fmt(a.total_allocated)) +
                '</div>' +

                '<button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" ' +
                'data-bs-target="#' + collapseId + '">' +
                '<i class="fas fa-list-ul fa-xs me-1"></i>جزئیات ارزها (' +
                'خریداری‌شده ' + esc(a.allocated_count) +
                '، ردشده ' + esc(a.skipped_count) +
                '، خارج از بازه ' + esc(a.out_of_range_count) +
                '، بدون قیمت ' + esc(a.unpriced_count) + ')' +
                '</button>' +
                '<div class="collapse mt-2" id="' + collapseId + '">' + renderDetails(a) + '</div>' +
                '</div>';
        }

        /* Dominant cause across the recorded window — the one line that answers
           "why is this money still sitting here?" without reading every row. */
        function renderSummary(attempts) {
            if (!attempts.length) {
                return '<div class="alert alert-secondary mb-3">' +
                    '<i class="fas fa-info-circle me-1"></i>' +
                    'هنوز هیچ تلاش خریدی برای این کاربر ثبت نشده است. ثبت سابقه از زمان فعال شدن این قابلیت آغاز می‌شود، ' +
                    'پس تلاش‌های قبل از آن در اینجا دیده نمی‌شوند.</div>';
            }

            var counts = {};
            var blocked = 0;
            attempts.forEach(function(a) {
                if (a.outcome === 'ORDER_CREATED') return;
                blocked++;
                var key = a.reason_label || '—';
                counts[key] = (counts[key] || 0) + 1;
            });

            var last = attempts[0];
            var html = '<div class="alert ' + (blocked ? 'alert-warning' : 'alert-success') + ' mb-3">' +
                '<div class="mb-1"><i class="fas fa-clock me-1"></i><strong>آخرین تلاش:</strong> ' +
                esc(last.at_display) + ' — ' + esc(last.trigger_label) + ' — ' + esc(last.reason_message) +
                '</div>';

            if (blocked) {
                var parts = Object.keys(counts).map(function(k) {
                    return esc(k) + ' (' + counts[k] + ' بار)';
                });
                html += '<div class="small"><i class="fas fa-chart-bar me-1"></i>' +
                    'از ' + attempts.length + ' تلاش اخیر، ' + blocked + ' تلاش به خرید منجر نشد: ' +
                    parts.join('، ') + '.</div>';
            }

            return html + '</div>';
        }

        function renderSettlements(list) {
            if (!list || !list.length) return '';

            var rows = '';
            list.forEach(function(s) {
                var pnl = parseFloat(s.net_pnl) || 0;
                rows += '<tr>' +
                    '<td class="font-number">' + esc(s.at_display) + '</td>' +
                    '<td><strong>' + esc(s.currency_symbol || '—') + '</strong></td>' +
                    '<td class="font-number">#' + esc(s.bot_sell_order_id) + '</td>' +
                    '<td class="font-number">' + fmt(s.freed_usdt) + '</td>' +
                    '<td class="font-number ' + (pnl >= 0 ? 'text-success' : 'text-danger') + '">' +
                    (pnl >= 0 ? '+' : '') + fmt(pnl) + '</td>' +
                    '</tr>';
            });

            return '<h6 class="mt-4 mb-2"><i class="fas fa-history me-1 text-muted"></i>' +
                'زمان‌هایی که پول کاربر آزاد شده است</h6>' +
                '<div class="small text-muted mb-2">' +
                'هر ردیف یک پله فروش است که پر (یا لغو) شده و اصل پول آن از حالت قفل خارج شده. ' +
                'این زمان‌ها را با زمان تلاش‌های بالا مقایسه کنید تا ببینید کدام آزادسازی بدون خرید مانده است.' +
                '</div>' +
                table(['زمان', 'ارز', 'پله فروش', 'اصل آزادشده (USDT)', 'سود/زیان'], rows);
        }

        function render(data) {
            var attempts = data.attempts || [];
            var html = renderSummary(attempts);

            if (attempts.length) {
                html += '<h6 class="mb-2"><i class="fas fa-stream me-1 text-muted"></i>تلاش‌های خرید (جدیدترین اول)</h6>';
                attempts.forEach(function(a) {
                    html += renderAttempt(a);
                });
            }

            html += renderSettlements(data.settlements);
            bodyEl.innerHTML = html;
        }

        document.querySelectorAll('.js-bot-buy-attempts').forEach(function(btn) {
            btn.addEventListener('click', function() {
                bodyEl.innerHTML =
                    '<div class="text-center py-4">' +
                    '<div class="spinner-border text-success" role="status"></div>' +
                    '<div class="small text-muted mt-2">در حال خواندن سابقه تلاش‌های خرید…</div></div>';
                modal.show();

                fetch(btn.dataset.attemptsUrl, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(res) {
                        return res.json().catch(function() {
                            throw new Error('پاسخ نامعتبر از سرور (HTTP ' + res.status + ').');
                        });
                    })
                    .then(function(data) {
                        if (!data || data.ok === false) {
                            renderError((data && data.error) || 'خطا در دریافت سابقه تلاش‌های خرید.');
                            return;
                        }
                        render(data);
                    })
                    .catch(function(err) {
                        console.error('[bot-buy-attempts]', err);
                        renderError('ارتباط با سرور برقرار نشد: ' +
                            (err && err.message ? err.message : String(err)));
                    });
            });
        });
    });
</script>
@endpush
