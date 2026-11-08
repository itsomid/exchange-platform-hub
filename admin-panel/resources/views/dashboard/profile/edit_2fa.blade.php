@extends('dashboard.profile.layout.master')
@section('vendor-style')
    <style>
        .tfa-hero-icon {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .tfa-step-no {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            flex: 0 0 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            font-weight: 600;
        }

        .tfa-qr {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .1);
            border-radius: 14px;
            padding: 14px;
            width: 100%;
            max-width: 240px;
            margin-inline: auto;
            line-height: 0;
        }

        .tfa-qr svg {
            width: 100%;
            height: auto;
            display: block;
        }

        .tfa-key {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            letter-spacing: .12rem;
            text-align: center;
        }

        .tfa-otp {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 1.75rem;
            letter-spacing: .6rem;
            text-align: center;
            padding-block: .6rem;
            direction: ltr;
        }

        .tfa-otp::placeholder {
            letter-spacing: .6rem;
            opacity: .35;
        }

        .tfa-codes {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
            direction: ltr;
        }

        .tfa-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .95rem;
            text-align: center;
            padding: .55rem .4rem;
            border-radius: 8px;
            border: 1px dashed rgba(115, 103, 240, .45);
            user-select: all;
            overflow-wrap: anywhere;
        }

        .tfa-step-box {
            border: 1px solid var(--bs-border-color, rgba(0, 0, 0, .1));
            border-radius: 12px;
            padding: 1.1rem;
            height: 100%;
        }

        @media (max-width: 575.98px) {
            .tfa-hero-icon {
                width: 52px;
                height: 52px;
                font-size: 1.4rem;
            }

            .tfa-otp {
                font-size: 1.5rem;
                letter-spacing: .4rem;
            }

            .tfa-codes {
                gap: .5rem;
            }

            .tfa-code {
                font-size: .85rem;
            }
        }
    </style>
@endsection
@section('profile-body')
    @php
        $stateMeta = [
            'disabled' => ['label' => 'غیرفعال', 'class' => 'secondary'],
            'pending' => ['label' => 'در انتظار تأیید', 'class' => 'warning'],
            'enabled' => ['label' => 'فعال', 'class' => 'success'],
        ][$state];
        $justActivated = $state === 'enabled' && $flash === 'activated';
    @endphp

    <div id="tfaRoot" data-state="{{ $state }}">
        {{-- ===================== Header ===================== --}}
        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <span class="tfa-hero-icon bg-label-{{ $stateMeta['class'] }}">
                    <i class="fa-solid fa-shield-check"></i>
                </span>
                <div style="flex: 1 1 11rem; min-width: 0">
                    <h5 class="mb-1">احراز هویت دو مرحله‌ای (2FA)</h5>
                    <p class="mb-0 text-muted small">
                        علاوه بر رمز عبور، هنگام ورود کدی موقت از برنامه‌ی احراز هویت روی گوشی‌تان درخواست می‌شود.
                    </p>
                </div>
                <span class="badge bg-label-{{ $stateMeta['class'] }} fs-6">{{ $stateMeta['label'] }}</span>
            </div>
        </div>

        {{-- ===================== State: disabled ===================== --}}
        @if ($state === 'disabled')
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                        <i class="fa-regular fa-triangle-exclamation mt-1"></i>
                        <div>حساب شما فعلاً فقط با رمز عبور محافظت می‌شود. برای امنیت بیشتر، احراز هویت دو مرحله‌ای را فعال
                            کنید.</div>
                    </div>

                    <div class="row g-3 my-2">
                        <div class="col-md-4">
                            <div class="tfa-step-box">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="tfa-step-no bg-label-primary">۱</span>
                                    <strong>نصب برنامه</strong>
                                </div>
                                <p class="small text-muted mb-0">Google Authenticator یا هر برنامه‌ی TOTP دیگر را روی گوشی
                                    نصب کنید.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="tfa-step-box">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="tfa-step-no bg-label-primary">۲</span>
                                    <strong>اسکن QR</strong>
                                </div>
                                <p class="small text-muted mb-0">بارکد را اسکن کنید یا کلید راه‌اندازی را دستی وارد کنید.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="tfa-step-box">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="tfa-step-no bg-label-primary">۳</span>
                                    <strong>تأیید کد</strong>
                                </div>
                                <p class="small text-muted mb-0">کد ۶ رقمی نمایش‌داده‌شده را وارد کنید و کدهای بازیابی را
                                    ذخیره کنید.</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid d-sm-flex justify-content-sm-end mt-4">
                        <button type="button" class="btn btn-primary" id="btnStart">
                            <i class="fa-regular fa-shield-check me-2"></i>
                            شروع فعال‌سازی
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== State: pending ===================== --}}
        @if ($state === 'pending')
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                        <i class="fa-regular fa-triangle-exclamation mt-1"></i>
                        <div>فعال‌سازی هنوز کامل نشده است. تا زمانی که کد را تأیید نکنید، ورود شما فقط با رمز عبور انجام
                            می‌شود.</div>
                    </div>

                    {{-- Step 1 --}}
                    <div class="tfa-step-box mb-3">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="tfa-step-no bg-label-primary">۱</span>
                            <strong>برنامه‌ی احراز هویت را نصب کنید</strong>
                        </div>
                        <div class="d-grid d-sm-flex gap-2">
                            <a class="btn btn-dark" target="_blank" rel="noopener"
                                href="https://apps.apple.com/us/app/google-authenticator/id388497605">
                                <i class="fa-brands fa-apple me-2"></i> App Store
                            </a>
                            <a class="btn btn-success" target="_blank" rel="noopener"
                                href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">
                                <i class="fa-brands fa-google-play me-2"></i> Google Play
                            </a>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- Step 2 --}}
                        <div class="col-md-6">
                            <div class="tfa-step-box">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="tfa-step-no bg-label-primary">۲</span>
                                    <strong>بارکد را اسکن کنید</strong>
                                </div>

                                @if ($qrCodeSvg)
                                    <div class="tfa-qr mb-3" aria-label="QR code">{!! $qrCodeSvg !!}</div>

                                    <a class="btn btn-label-primary w-100 mb-3 d-lg-none" href="{{ $otpauthUrl }}">
                                        <i class="fa-regular fa-arrow-up-right-from-square me-2"></i>
                                        باز کردن در برنامه (روی همین گوشی)
                                    </a>

                                    <label class="form-label small text-muted" for="tfaKey">
                                        اسکن نمی‌شود؟ این کلید را دستی در برنامه وارد کنید:
                                    </label>
                                    <div class="input-group">
                                        <input type="text" id="tfaKey" class="form-control tfa-key" readonly
                                            value="{{ implode(' ', str_split($secretKey, 4)) }}"
                                            data-raw="{{ $secretKey }}" autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" id="btnCopyKey"
                                            title="کپی کلید" aria-label="کپی کلید">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <span class="tfa-hero-icon bg-label-secondary mb-3"><i
                                                class="fa-regular fa-lock"></i></span>
                                        <p class="small text-muted">برای نمایش بارکد و کلید، ابتدا رمز عبور خود را تأیید
                                            کنید.</p>
                                        <button type="button" class="btn btn-label-primary" id="btnReveal">
                                            <i class="fa-regular fa-eye me-2"></i> نمایش بارکد
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Step 3 --}}
                        <div class="col-md-6">
                            <div class="tfa-step-box">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="tfa-step-no bg-label-primary">۳</span>
                                    <strong>کد ۶ رقمی را وارد کنید</strong>
                                </div>
                                <form id="confirmForm" novalidate>
                                    <input type="text" id="tfaCode" name="code" class="form-control tfa-otp"
                                        maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                        placeholder="000000" aria-label="کد ۶ رقمی" @disabled(!$qrCodeSvg)>
                                    <div class="invalid-feedback d-block text-center mt-2" id="codeError" role="alert">
                                    </div>
                                    <p class="small text-muted mt-2 mb-3">
                                        کد هر ۳۰ ثانیه تغییر می‌کند. اگر کد قبول نشد، تاریخ و ساعت گوشی را روی حالت خودکار
                                        بگذارید.
                                    </p>
                                    <button type="submit" class="btn btn-success w-100" id="btnConfirm"
                                        @disabled(!$qrCodeSvg)>
                                        <i class="fa-regular fa-check me-2"></i> تأیید و فعال‌سازی
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-sm btn-text-secondary" id="btnCancelSetup">
                            انصراف از فعال‌سازی
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== State: enabled ===================== --}}
        @if ($state === 'enabled')
            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-success d-flex align-items-start gap-2 mb-0" role="alert">
                        <i class="fa-regular fa-circle-check mt-1"></i>
                        <div>
                            احراز هویت دو مرحله‌ای برای حساب شما فعال است
                            @if ($admin->two_factor_confirmed_at)
                                <span class="d-block small mt-1">
                                    از تاریخ
                                    {{ \Morilog\Jalali\Jalalian::fromCarbon($admin->two_factor_confirmed_at)->format('%d %B %Y') }}
                                </span>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4" id="recoveryCard">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-regular fa-key text-primary"></i>
                        <h6 class="mb-0">کدهای بازیابی</h6>
                    </div>
                    <p class="small text-muted">
                        اگر به گوشی خود دسترسی نداشتید، با هر یک از این کدها می‌توانید یک بار وارد شوید. آن‌ها را در جایی
                        امن نگه دارید.
                        نمایش کدها نیاز به تأیید رمز عبور دارد.
                    </p>

                    @if ($justActivated)
                        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                            <i class="fa-regular fa-triangle-exclamation mt-1"></i>
                            <div>همین حالا کدهای بازیابی را ذخیره کنید. بدون آن‌ها در صورت گم شدن گوشی، دسترسی به حساب دشوار
                                می‌شود.</div>
                        </div>
                    @endif

                    <div id="recoveryList" class="d-none mb-3">
                        <div class="tfa-codes" id="recoveryGrid"></div>
                    </div>

                    <div class="d-grid d-sm-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-label-primary" id="btnShowCodes">
                            <i class="fa-regular fa-eye me-2"></i> نمایش کدها
                        </button>
                        <button type="button" class="btn btn-label-secondary d-none" id="btnCopyCodes">
                            <i class="fa-regular fa-copy me-2"></i> کپی همه
                        </button>
                        <button type="button" class="btn btn-label-secondary d-none" id="btnDownloadCodes">
                            <i class="fa-regular fa-download me-2"></i> دانلود
                        </button>
                        <button type="button" class="btn btn-label-warning ms-sm-auto" id="btnRegenerate">
                            <i class="fa-regular fa-rotate me-2"></i> ساخت کدهای جدید
                        </button>
                    </div>
                </div>
            </div>

            <div class="card border border-danger">
                <div class="card-body d-flex flex-wrap align-items-center gap-3">
                    <div class="flex-grow-1">
                        <h6 class="text-danger mb-1">غیرفعال‌سازی</h6>
                        <p class="small text-muted mb-0">با غیرفعال‌کردن، ورود فقط با رمز عبور انجام می‌شود و کدهای بازیابی
                            فعلی باطل می‌شوند.</p>
                    </div>
                    <button type="button" class="btn btn-outline-danger flex-grow-1 flex-sm-grow-0" id="btnDisable">
                        <i class="fa-regular fa-shield-xmark me-2"></i> غیرفعال‌سازی
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- ===================== Password confirmation modal ===================== --}}
    <div class="modal fade" id="pwdModal" tabindex="-1" aria-labelledby="pwdModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="pwdForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="pwdModalTitle">
                        <i class="fa-regular fa-lock me-2 text-primary"></i> تأیید رمز عبور
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">برای ادامه، رمز عبور حساب خود را وارد کنید.</p>
                    <div class="input-group input-group-merge">
                        <input type="password" id="pwdInput" class="form-control" autocomplete="current-password"
                            placeholder="رمز عبور" aria-label="رمز عبور" required>
                        <button type="button" class="btn btn-outline-secondary" id="btnTogglePwd"
                            aria-label="نمایش رمز">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback d-block" id="pwdError" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary" id="btnPwdSubmit">تأیید</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== Disable confirmation modal ===================== --}}
    <div class="modal fade" id="disableModal" tabindex="-1" aria-labelledby="disableModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="disableModalTitle">
                        <i class="fa-regular fa-triangle-exclamation me-2"></i> غیرفعال‌سازی احراز هویت دو مرحله‌ای
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">مطمئن هستید؟ حساب شما دیگر با کد موقت محافظت نمی‌شود.</p>
                    @if (app()->environment('production'))
                        <p class="small text-danger mb-0">در این سامانه دسترسی به بخش‌های پنل بدون احراز هویت دو مرحله‌ای
                            ممکن نیست و باید دوباره آن را فعال کنید.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger" id="btnDisableConfirm">بله، غیرفعال شود</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const routes = {
            enable: @json(route('two-factor.enable')),
            confirm: @json(route('two-factor.confirm')),
            disable: @json(route('two-factor.disable')),
            secretKey: @json(route('two-factor.secret-key')),
            codes: @json(route('two-factor.recovery-codes')),
            regenerate: @json(route('two-factor.regenerate-recovery-codes')),
            password: @json(route('password.confirm.store')),
            page: @json(route('admin.profile.2fa.edit')),
        };
        const flashMessages = {
            activated: 'احراز هویت دو مرحله‌ای با موفقیت فعال شد.',
            disabled: 'احراز هویت دو مرحله‌ای غیرفعال شد.',
            cancelled: 'فعال‌سازی لغو شد.',
        };
        const justActivated = @json($justActivated);
        const $ = (id) => document.getElementById(id);
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        function toast(text, ok = true) {
            if (typeof Toastify === 'undefined') return;
            Toastify({
                text,
                duration: ok ? 3000 : 5000,
                close: true,
                gravity: 'top',
                position: 'right',
                stopOnFocus: true,
                style: {
                    background: ok ? '#28C76F' : '#EA5455'
                },
            }).showToast();
        }

        async function copyText(text) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (e) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.cssText = 'position:fixed;opacity:0';
                document.body.appendChild(ta);
                ta.select();
                let ok = false;
                try {
                    ok = document.execCommand('copy');
                } catch (_) {}
                ta.remove();
                return ok;
            }
        }

        async function call(method, url, body) {
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            };
            if (body) headers['Content-Type'] = 'application/json';
            const res = await fetch(url, {
                method,
                headers,
                credentials: 'same-origin',
                body: body ? JSON.stringify(body) : undefined
            });
            let data = null;
            try {
                data = await res.json();
            } catch (_) {}
            return {
                ok: res.ok,
                status: res.status,
                data
            };
        }

        // ---- password re-confirmation ("sudo mode") ----
        const pwdModalEl = $('pwdModal');
        const pwdModal = new bootstrap.Modal(pwdModalEl);
        let pwdResolve = null;

        function askPassword() {
            return new Promise((resolve) => {
                pwdResolve = resolve;
                $('pwdInput').value = '';
                $('pwdError').textContent = '';
                pwdModal.show();
            });
        }
        pwdModalEl.addEventListener('shown.bs.modal', () => $('pwdInput').focus());
        pwdModalEl.addEventListener('hidden.bs.modal', () => {
            if (pwdResolve) {
                pwdResolve(false);
                pwdResolve = null;
            }
        });
        $('btnTogglePwd').addEventListener('click', () => {
            const input = $('pwdInput');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            $('btnTogglePwd').firstElementChild.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
        $('pwdForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const password = $('pwdInput').value;
            if (!password) {
                $('pwdError').textContent = 'رمز عبور را وارد کنید.';
                return;
            }
            const btn = $('btnPwdSubmit');
            btn.disabled = true;
            const r = await call('POST', routes.password, {
                password
            });
            btn.disabled = false;
            if (r.ok) {
                const resolve = pwdResolve;
                pwdResolve = null;
                pwdModal.hide();
                resolve && resolve(true);
            } else {
                $('pwdError').textContent = r.status === 429 ?
                    'تعداد تلاش‌ها زیاد است. کمی بعد دوباره امتحان کنید.' :
                    'رمز عبور نادرست است.';
                $('pwdInput').select();
            }
        });

        // Runs a request; if the server demands password confirmation (423) asks for it and retries once.
        async function guarded(method, url, body) {
            let r = await call(method, url, body);
            if (r.status === 423) {
                if (!(await askPassword())) return null;
                r = await call(method, url, body);
            }
            if (r.status === 419) {
                toast('نشست شما منقضی شده است. صفحه را رفرش کنید.', false);
                return null;
            }
            return r;
        }

        const goTo = (flash) => {
            window.location.href = routes.page + (flash ? '?flash=' + flash : '');
        };
        const busy = (btn, on) => {
            btn.disabled = on;
            btn.classList.toggle('opacity-75', on);
        };

        // ---- state: disabled ----
        $('btnStart')?.addEventListener('click', async (e) => {
            busy(e.currentTarget, true);
            const r = await guarded('POST', routes.enable);
            if (r && r.ok) return goTo();
            busy(e.currentTarget, false);
            if (r) toast('فعال‌سازی انجام نشد. دوباره تلاش کنید.', false);
        });

        // ---- state: pending ----
        $('btnReveal')?.addEventListener('click', async (e) => {
            busy(e.currentTarget, true);
            const r = await guarded('GET', routes.secretKey);
            if (r && r.ok) return goTo();
            busy(e.currentTarget, false);
        });
        $('btnCopyKey')?.addEventListener('click', async () => {
            const ok = await copyText($('tfaKey').dataset.raw);
            toast(ok ? 'کلید کپی شد.' : 'کپی انجام نشد؛ کلید را دستی انتخاب کنید.', ok);
        });
        $('btnCancelSetup')?.addEventListener('click', async (e) => {
            busy(e.currentTarget, true);
            const r = await guarded('DELETE', routes.disable);
            if (r && r.ok) return goTo('cancelled');
            busy(e.currentTarget, false);
        });

        const codeInput = $('tfaCode');
        const confirmForm = $('confirmForm');
        if (codeInput && confirmForm) {
            let submitting = false;
            const submitCode = async () => {
                if (submitting) return;
                const code = codeInput.value.trim();
                $('codeError').textContent = '';
                codeInput.classList.remove('is-invalid');
                if (code.length !== 6) {
                    $('codeError').textContent = 'کد باید ۶ رقم باشد.';
                    return;
                }
                submitting = true;
                busy($('btnConfirm'), true);
                const r = await guarded('POST', routes.confirm, {
                    code
                });
                if (r && r.ok) return goTo('activated');
                submitting = false;
                busy($('btnConfirm'), false);
                if (r) {
                    $('codeError').textContent = r.status === 422 ?
                        'کد وارد‌شده نادرست یا منقضی است. ساعت گوشی را بررسی کنید و دوباره تلاش کنید.' :
                        'خطایی رخ داد. دوباره تلاش کنید.';
                    codeInput.classList.add('is-invalid');
                    codeInput.select();
                }
            };
            codeInput.addEventListener('input', () => {
                codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
                if (codeInput.value.length === 6) submitCode();
            });
            confirmForm.addEventListener('submit', (e) => {
                e.preventDefault();
                submitCode();
            });
            if (!codeInput.disabled) codeInput.focus();
        }

        // ---- state: enabled ----
        let currentCodes = [];

        function renderCodes(codes) {
            currentCodes = codes;
            const grid = $('recoveryGrid');
            grid.replaceChildren(...codes.map((c) => {
                const el = document.createElement('div');
                el.className = 'tfa-code';
                el.textContent = c;
                return el;
            }));
            $('recoveryList').classList.remove('d-none');
            $('btnCopyCodes').classList.remove('d-none');
            $('btnDownloadCodes').classList.remove('d-none');
            $('btnShowCodes').innerHTML = '<i class="fa-regular fa-eye-slash me-2"></i> پنهان کردن';
            $('btnShowCodes').dataset.visible = '1';
        }

        function hideCodes() {
            currentCodes = [];
            $('recoveryGrid').replaceChildren();
            $('recoveryList').classList.add('d-none');
            $('btnCopyCodes').classList.add('d-none');
            $('btnDownloadCodes').classList.add('d-none');
            $('btnShowCodes').innerHTML = '<i class="fa-regular fa-eye me-2"></i> نمایش کدها';
            delete $('btnShowCodes').dataset.visible;
        }
        async function loadCodes() {
            const r = await guarded('GET', routes.codes);
            if (r && r.ok && Array.isArray(r.data)) {
                renderCodes(r.data);
                return true;
            }
            if (r) toast('دریافت کدهای بازیابی انجام نشد.', false);
            return false;
        }

        $('btnShowCodes')?.addEventListener('click', async (e) => {
            if (e.currentTarget.dataset.visible) return hideCodes();
            busy(e.currentTarget, true);
            await loadCodes();
            busy(e.currentTarget, false);
        });
        $('btnCopyCodes')?.addEventListener('click', async () => {
            const ok = await copyText(currentCodes.join('\n'));
            toast(ok ? 'کدهای بازیابی کپی شد.' : 'کپی انجام نشد.', ok);
        });
        $('btnDownloadCodes')?.addEventListener('click', () => {
            const text = 'Recovery codes (each code can be used once)\n\n' + currentCodes.join('\n') + '\n';
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([text], {
                type: 'text/plain'
            }));
            a.download = 'recovery-codes.txt';
            a.click();
            URL.revokeObjectURL(a.href);
        });
        $('btnRegenerate')?.addEventListener('click', async (e) => {
            if (!confirm('با ساخت کدهای جدید، همه‌ی کدهای قبلی باطل می‌شوند. ادامه می‌دهید؟')) return;
            busy(e.currentTarget, true);
            const r = await guarded('POST', routes.regenerate);
            if (r && r.ok && await loadCodes()) toast('کدهای بازیابی جدید ساخته شد. آن‌ها را ذخیره کنید.');
            busy(e.currentTarget, false);
        });

        const disableModal = $('disableModal') ? new bootstrap.Modal($('disableModal')) : null;
        $('btnDisable')?.addEventListener('click', () => disableModal.show());
        $('btnDisableConfirm')?.addEventListener('click', async (e) => {
            busy(e.currentTarget, true);
            // hide first so the password modal never stacks on top of this one
            await new Promise((resolve) => {
                $('disableModal').addEventListener('hidden.bs.modal', resolve, {
                    once: true
                });
                disableModal.hide();
            });
            const r = await guarded('DELETE', routes.disable);
            if (r && r.ok) return goTo('disabled');
            busy(e.currentTarget, false);
            if (r) toast('غیرفعال‌سازی انجام نشد. دوباره تلاش کنید.', false);
        });

        // ---- one-shot notices from the previous action ----
        const flash = new URLSearchParams(window.location.search).get('flash');
        if (flash) {
            if (flashMessages[flash]) toast(flashMessages[flash]);
            history.replaceState(null, '', routes.page);
        }
        if (justActivated) loadCodes();
    </script>
@endpush
