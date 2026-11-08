@extends('dashboard.profile.layout.master')
@section('profile-body')
    <div class="card">
        <form action="{{ route('admin.profile.password.update') }}" method="post" id="passwordForm" autocomplete="off">
            @csrf
            @method('PATCH')
            <div class="row card-body">
                <div class="my-3 form-password-toggle">
                    <label class="form-label" for="current_password">
                        کلمه عبور فعلی
                    </label>
                    <div class="input-group input-group-merge">
                        <input type="password" id="current_password" name="current_password" @class([
                            'form-control',
                            'is-invalid' => $errors->has('current_password'),
                        ])
                            placeholder="کلمه عبور فعلی خود را وارد کنید" autocomplete="current-password" />
                        <span class="input-group-text cursor-pointer">
                            <i class="fa-light fa-eye-slash"></i>
                        </span>
                    </div>
                    @error('current_password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="my-3 form-password-toggle">
                    <label class="form-label" for="password">
                        کلمه عبور جدید
                    </label>
                    <div class="input-group input-group-merge">
                        <input type="password" id="password" name="password" @class(['form-control', 'is-invalid' => $errors->has('password')])
                            placeholder="کلمه عبور جدید خود را وارد کنید" autocomplete="new-password" />
                        <span class="input-group-text cursor-pointer">
                            <i class="fa-light fa-eye-slash"></i>
                        </span>
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="my-3 form-password-toggle">
                    <label class="form-label" for="password_confirmation">
                        تکرار کلمه عبور جدید
                    </label>
                    <div class="input-group input-group-merge">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                            placeholder="***********" autocomplete="new-password" />
                        <span class="input-group-text cursor-pointer">
                            <i class="fa-light fa-eye-slash"></i>
                        </span>
                    </div>
                    <div class="small mt-1" id="confirmHint"></div>
                </div>

                <div class="col-12 mt-4">
                    <h6>شرایط رمز عبور:</h6>

                    <ul class="list-unstyled mb-0" id="passwordChecklist"></ul>
                    <div id="passwordSummary" class="alert alert-secondary py-2 px-3 small mt-3 mb-0" role="status">
                        گذرواژه جدید را وارد کنید.
                    </div>
                </div>

                <div class="col-12 text-right  mt-4">
                    <button class="btn btn-primary" id="btnSubmit" disabled>
                        <i class="fa fa-save mx-2"></i>
                        ثبت تغییرات
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
@push('scripts')
    <script>
        // #app is mounted by Vue (it re-renders the DOM), so wait for it before grabbing elements.
        document.addEventListener('DOMContentLoaded', function() {
            const requirements = @json($passwordRequirements);
            const forbiddenTokens = @json($forbiddenTokens);

            const current = document.getElementById('current_password');
            const password = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');
            const checklist = document.getElementById('passwordChecklist');
            const summary = document.getElementById('passwordSummary');
            const confirmHint = document.getElementById('confirmHint');
            const submit = document.getElementById('btnSubmit');

            const checks = requirements.map(function(r) {
                const regex = new RegExp(r.pattern, r.flags);
                return {
                    label: r.label,
                    test: function(v) {
                        return regex.test(v) === r.must_match;
                    }
                };
            });

            if (forbiddenTokens.length) {
                checks.push({
                    label: 'بدون ایمیل یا شماره موبایل خودتان',
                    test: function(v) {
                        const lower = v.toLowerCase();
                        return !forbiddenTokens.some(function(t) {
                            return lower.indexOf(t) !== -1;
                        });
                    },
                });
            }

            const items = checks.map(function(c) {
                const li = document.createElement('li');
                li.className = 'mb-1 d-flex align-items-center';
                li.innerHTML = '<i class="me-2"></i><span></span>';
                li.querySelector('span').textContent = c.label;
                checklist.appendChild(li);
                return li;
            });

            function paint(li, state) {
                const icon = li.querySelector('i');
                li.classList.remove('text-success', 'text-danger', 'text-muted');
                if (state === 'ok') {
                    li.classList.add('text-success');
                    icon.className = 'fa-solid fa-circle-check me-2';
                } else if (state === 'bad') {
                    li.classList.add('text-danger');
                    icon.className = 'fa-solid fa-circle-xmark me-2';
                } else {
                    li.classList.add('text-muted');
                    icon.className = 'fa-regular fa-circle me-2';
                }
            }

            function update() {
                const value = password.value;
                const touched = value.length > 0;
                const missing = [];

                checks.forEach(function(c, i) {
                    const ok = c.test(value);
                    paint(items[i], !touched ? 'idle' : (ok ? 'ok' : 'bad'));
                    if (!ok) {
                        missing.push(c.label);
                    }
                });

                const strong = touched && missing.length === 0;
                const confirmed = confirmation.value.length > 0 && confirmation.value === value;
                const differs = value !== current.value;

                if (summary) {
                    if (!touched) {
                        summary.className = 'alert alert-secondary py-2 px-3 small mt-3 mb-0';
                        summary.textContent = 'گذرواژه جدید را وارد کنید.';
                    } else if (strong && !differs) {
                        summary.className = 'alert alert-warning py-2 px-3 small mt-3 mb-0';
                        summary.textContent = 'گذرواژه جدید باید با گذرواژه فعلی متفاوت باشد.';
                    } else if (strong) {
                        summary.className = 'alert alert-success py-2 px-3 small mt-3 mb-0';
                        summary.textContent = 'گذرواژه شما همه شرایط را دارد.';
                    } else {
                        summary.className = 'alert alert-warning py-2 px-3 small mt-3 mb-0';
                        summary.textContent = 'گذرواژه هنوز ضعیف است. موارد کم: ' + missing.join('، ');
                    }
                }

                if (confirmation.value.length === 0) {
                    confirmHint.textContent = '';
                } else {
                    confirmHint.className = 'small mt-1 ' + (confirmed ? 'text-success' : 'text-danger');
                    confirmHint.textContent = confirmed ? 'تکرار گذرواژه مطابقت دارد.' :
                        'تکرار گذرواژه با گذرواژه جدید یکسان نیست.';
                }

                submit.disabled = !(strong && confirmed && current.value.length > 0 && differs);
            }

            [current, password, confirmation].forEach(function(el) {
                el.addEventListener('input', update);
            });
            update();
        });
    </script>
@endpush
