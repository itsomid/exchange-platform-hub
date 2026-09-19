<script>
    document.addEventListener('DOMContentLoaded', function() {
        function toast(text, ok) {
            if (typeof Toastify === 'undefined') return;
            Toastify({
                text: text,
                duration: ok ? 3000 : 5000,
                gravity: 'top',
                position: 'right',
                style: {
                    background: ok ? '#28C76F' : '#EA5455'
                },
            }).showToast();
        }

        // ── Admin notes modal ─────────────────────────────────────────────
        (function() {
            var modalEl = document.getElementById('botOrderDescriptionModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;

            var modal = new bootstrap.Modal(modalEl);
            var input = document.getElementById('botOrderDescriptionInput');
            var saveBtn = document.getElementById('botOrderDescriptionSaveBtn');
            var orderIdLabel = document.getElementById('botOrderDescriptionModalOrderId');
            var activeUrl = null;
            var activeOrderId = null;
            var activeDisplayEl = null;

            function syncAdminDescriptionButton(btn, desc) {
                btn.dataset.adminDescription = desc;
                if (btn.classList.contains('btn-secondary') || btn.classList.contains('btn-outline-secondary')) {
                    btn.classList.toggle('btn-secondary', !!desc);
                    btn.classList.toggle('btn-outline-secondary', !desc);
                    btn.title = desc ? 'یادداشت ادمین (ثبت‌شده)' : 'یادداشت ادمین';
                }
            }

            document.querySelectorAll('.js-edit-order-admin-description').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    activeUrl = btn.dataset.updateUrl;
                    activeOrderId = btn.dataset.orderId;
                    activeDisplayEl = document.getElementById('order-admin-description-display');
                    if (input) input.value = btn.dataset.adminDescription || '';
                    if (orderIdLabel) orderIdLabel.textContent = activeOrderId ? ('#' + activeOrderId) : '';
                    modal.show();
                });
            });

            if (!saveBtn) return;

            saveBtn.addEventListener('click', function() {
                if (!activeUrl) return;
                saveBtn.disabled = true;
                fetch(activeUrl, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            admin_description: input ? input.value : ''
                        }),
                    })
                    .then(function(res) {
                        if (!res.ok) throw new Error('save failed');
                        return res.json();
                    })
                    .then(function(data) {
                        var desc = data.admin_description || '';
                        document.querySelectorAll(
                            '.js-edit-order-admin-description[data-order-id="' + activeOrderId + '"]'
                        ).forEach(function(btn) {
                            syncAdminDescriptionButton(btn, desc);
                        });
                        if (activeDisplayEl) {
                            if (desc) {
                                activeDisplayEl.classList.remove('border', 'border-dashed');
                                activeDisplayEl.style.background = 'rgba(105,108,255,.05)';
                                activeDisplayEl.innerHTML =
                                    '<div class="small text-break" style="white-space:pre-wrap;"></div>';
                                activeDisplayEl.querySelector('div').textContent = desc;
                            } else {
                                activeDisplayEl.classList.add('border', 'border-dashed');
                                activeDisplayEl.style.background = 'rgba(0,0,0,.015)';
                                activeDisplayEl.innerHTML =
                                    '<span class="text-muted small">هنوز یادداشت ادمینی ثبت نشده است.</span>';
                            }
                        }
                        modal.hide();
                        toast(data.message || 'ذخیره شد.', true);
                    })
                    .catch(function() {
                        toast('خطا در ذخیره یادداشت ادمین.', false);
                    })
                    .finally(function() {
                        saveBtn.disabled = false;
                    });
            });
        })();

        // ── System notes modal (read-only) ────────────────────────────────
        (function() {
            var modalEl = document.getElementById('botOrderSystemDescriptionModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;

            var modal = new bootstrap.Modal(modalEl);
            var bodyEl = document.getElementById('botOrderSystemDescriptionModalBody');
            var orderIdLabel = document.getElementById('botOrderSystemDescriptionModalOrderId');

            document.querySelectorAll('.js-view-order-system-description').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var orderId = btn.dataset.orderId;
                    var source = document.getElementById('system-description-html-' + orderId);
                    if (orderIdLabel) orderIdLabel.textContent = orderId ? ('#' + orderId) : '';
                    if (bodyEl) {
                        bodyEl.innerHTML = source ? source.innerHTML :
                            '<p class="text-muted small mb-0 text-center py-3">توضیح سیستمی ثبت نشده است.</p>';
                    }
                    modal.show();
                });
            });
        })();
    });
</script>
