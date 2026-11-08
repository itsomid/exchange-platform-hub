@php
    $avatarAdmin = auth('admin')->user();
    $currentAvatarKey = $avatarAdmin?->avatarSelection();
    $avatarKeys = \App\Models\Admin::availableAvatars();
    $avatarStyles = \App\Http\Controllers\Admin\ProfileAvatarController::STYLES;
@endphp

@if($avatarAdmin)
    <div class="modal fade" id="changeAvatarModal" tabindex="-1" aria-labelledby="changeAvatarModalLabel" aria-hidden="true"
         data-selected="{{ $currentAvatarKey }}"
         data-url-update="{{ route('admin.profile.avatar.update') }}"
         data-url-coins="{{ route('admin.profile.avatar.coins') }}"
         data-url-preview="{{ route('admin.profile.avatar.preview') }}"
         data-url-generate="{{ route('admin.profile.avatar.generate') }}"
         data-url-upload="{{ route('admin.profile.avatar.upload') }}">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeAvatarModalLabel">
                        <i class="fa-regular fa-camera me-1"></i>
                        انتخاب آواتار
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-pills nav-fill flex-column flex-sm-row mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#avatarTabBundled" role="tab">
                                <i class="fa-regular fa-user me-1"></i> آماده
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#avatarTabCoins" role="tab">
                                <i class="fa-brands fa-bitcoin me-1"></i> ارز دیجیتال
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#avatarTabOnline" role="tab">
                                <i class="fa-regular fa-robot me-1"></i> ساخت آنلاین
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#avatarTabUpload" role="tab">
                                <i class="fa-regular fa-upload me-1"></i> آپلود عکس
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content p-0 shadow-none">
                        {{-- Bundled avatars --}}
                        <div class="tab-pane fade show active" id="avatarTabBundled" role="tabpanel">
                            <div class="row g-3">
                                @foreach($avatarKeys as $key)
                                    <div class="col-4 col-sm-3 col-md-2 text-center">
                                        <button type="button" class="avatar-option btn p-1 border rounded-circle position-relative" data-key="{{ $key }}" aria-pressed="false">
                                            <img src="{{ asset('images/avatars/' . $key) }}" alt="" class="rounded-circle" loading="lazy">
                                            <span class="avatar-option-check bg-primary text-white rounded-circle"><i class="fa-regular fa-check"></i></span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Currency logos (loaded on first open) --}}
                        <div class="tab-pane fade" id="avatarTabCoins" role="tabpanel">
                            <div class="text-center text-muted py-5" id="avatarCoinsLoading">
                                <span class="spinner-border spinner-border-sm me-1"></span> در حال بارگذاری...
                            </div>
                            <div class="row g-3" id="avatarCoinsGrid"></div>
                        </div>

                        {{-- Online generator (images are proxied through this server) --}}
                        <div class="tab-pane fade" id="avatarTabOnline" role="tabpanel">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                <select class="form-select w-auto" id="avatarStyleSelect" aria-label="سبک آواتار">
                                    @foreach($avatarStyles as $style => $label)
                                        <option value="{{ $style }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-label-secondary" id="avatarShuffleBtn">
                                    <i class="fa-regular fa-sync me-1"></i> نمونه‌های جدید
                                </button>
                            </div>
                            <div class="row g-3" id="avatarOnlineGrid"></div>
                        </div>

                        {{-- Personal photo --}}
                        <div class="tab-pane fade" id="avatarTabUpload" role="tabpanel">
                            <div class="text-center py-3">
                                <img src="" alt="" id="avatarUploadPreview" class="rounded-circle border mb-3 d-none" width="128" height="128" style="object-fit:cover">
                                <div>
                                    <label for="avatarUploadInput" class="btn btn-label-primary">
                                        <i class="fa-regular fa-upload me-1"></i> انتخاب عکس
                                    </label>
                                    <input type="file" id="avatarUploadInput" class="d-none" accept="image/png,image/jpeg,image/webp">
                                </div>
                                <p class="text-muted small mt-3 mb-0">فرمت PNG، JPG یا WebP، حداکثر ۲ مگابایت. عکس به‌صورت مربع برش می‌خورد.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-primary" id="avatarSaveBtn" disabled>
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        ذخیره آواتار
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        #changeAvatarModal .avatar-option {
            width: 100%;
            max-width: 96px;
            background: transparent;
            border-width: 2px !important;
            transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
        }

        #changeAvatarModal .avatar-option img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
        }

        #changeAvatarModal .avatar-option.is-coin img {
            object-fit: contain;
            padding: 10px;
        }

        #changeAvatarModal .avatar-option:hover {
            transform: translateY(-2px);
            border-color: var(--bs-primary) !important;
        }

        #changeAvatarModal .avatar-option.is-selected {
            border-color: var(--bs-primary) !important;
            box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .25);
        }

        #changeAvatarModal .avatar-option-check {
            display: none;
            position: absolute;
            bottom: 0;
            inset-inline-end: 0;
            width: 22px;
            height: 22px;
            align-items: center;
            justify-content: center;
        }

        #changeAvatarModal .avatar-option.is-selected .avatar-option-check {
            display: flex;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('changeAvatarModal');
                if (!modal) return;

                const urls = {
                    update: modal.dataset.urlUpdate,
                    coins: modal.dataset.urlCoins,
                    preview: modal.dataset.urlPreview,
                    generate: modal.dataset.urlGenerate,
                    upload: modal.dataset.urlUpload,
                };
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';
                const saveBtn = document.getElementById('avatarSaveBtn');
                const spinner = saveBtn.querySelector('.spinner-border');
                const onlineGrid = document.getElementById('avatarOnlineGrid');
                const coinsGrid = document.getElementById('avatarCoinsGrid');
                const styleSelect = document.getElementById('avatarStyleSelect');
                const uploadInput = document.getElementById('avatarUploadInput');
                const uploadPreview = document.getElementById('avatarUploadPreview');

                let savedKey = modal.dataset.selected;
                let pending = null; // {kind: 'key'|'generate'|'upload', el?, key?, style?, seed?, file?}
                let coinsLoaded = false;
                let onlineLoaded = false;
                let previewErrorShown = false;

                const toast = (text, ok = true) => Toastify({
                    text,
                    duration: ok ? 3000 : 5000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    stopOnFocus: true,
                    style: {background: ok ? '#28C76F' : '#EA5455'},
                }).showToast();

                const render = () => {
                    modal.querySelectorAll('.avatar-option').forEach(o => {
                        const active = pending?.el ? o === pending.el : (!pending && o.dataset.key === savedKey);
                        o.classList.toggle('is-selected', active);
                        o.setAttribute('aria-pressed', active ? 'true' : 'false');
                    });
                    saveBtn.disabled = !pending || (pending.kind === 'key' && pending.key === savedKey);
                };

                const setPending = (value) => {
                    pending = value;
                    render();
                };

                const resetUpload = () => {
                    uploadInput.value = '';
                    uploadPreview.classList.add('d-none');
                };

                const optionButton = (img, extraClass = '') => {
                    const col = document.createElement('div');
                    col.className = 'col-4 col-sm-3 col-md-2 text-center';
                    col.innerHTML = `<button type="button" class="avatar-option btn p-1 border rounded-circle position-relative ${extraClass}" aria-pressed="false">
                            <img alt="" class="rounded-circle" loading="lazy">
                            <span class="avatar-option-check bg-primary text-white rounded-circle"><i class="fa-regular fa-check"></i></span>
                        </button>`;
                    const button = col.querySelector('button');
                    const image = col.querySelector('img');
                    image.src = img;
                    return {col, button, image};
                };

                // Bundled + coin avatars are picked by key (event delegation covers the lazily added coin buttons).
                modal.addEventListener('click', (event) => {
                    const option = event.target.closest('.avatar-option[data-key]');
                    if (option) setPending({kind: 'key', key: option.dataset.key, el: option});
                });

                // ----- Coins tab -----
                const loadCoins = async () => {
                    coinsLoaded = true;
                    try {
                        const response = await fetch(urls.coins, {headers: {'Accept': 'application/json'}});
                        const {coins} = await response.json();
                        coins.forEach(coin => {
                            const {col, button, image} = optionButton(coin.url, 'is-coin');
                            button.dataset.key = coin.key;
                            image.alt = coin.symbol;
                            col.insertAdjacentHTML('beforeend', `<div class="small text-muted mt-1"></div>`);
                            col.lastElementChild.textContent = coin.symbol;
                            coinsGrid.appendChild(col);
                        });
                        if (!coins.length) coinsGrid.innerHTML = '<p class="text-center text-muted py-4">لوگوی ارزی یافت نشد.</p>';
                        render();
                    } catch (e) {
                        coinsLoaded = false;
                        toast('بارگذاری لوگوی ارزها ناموفق بود.', false);
                    } finally {
                        document.getElementById('avatarCoinsLoading').classList.add('d-none');
                    }
                };

                // ----- Online tab -----
                const randomSeed = () => Array.from(crypto.getRandomValues(new Uint8Array(6)), b => b.toString(16).padStart(2, '0')).join('');

                const loadOnline = () => {
                    onlineLoaded = true;
                    setPending(null);
                    onlineGrid.innerHTML = '';
                    const style = styleSelect.value;
                    for (let i = 0; i < 12; i++) {
                        const seed = randomSeed();
                        const {col, button, image} = optionButton(`${urls.preview}?style=${encodeURIComponent(style)}&seed=${seed}`);
                        image.addEventListener('error', () => {
                            button.disabled = true;
                            image.style.opacity = .3;
                            if (!previewErrorShown) {
                                previewErrorShown = true;
                                toast('دریافت آواتار از سرویس آنلاین ممکن نشد.', false);
                            }
                        });
                        button.addEventListener('click', () => setPending({kind: 'generate', style, seed, el: button}));
                        onlineGrid.appendChild(col);
                    }
                };

                styleSelect.addEventListener('change', loadOnline);
                document.getElementById('avatarShuffleBtn').addEventListener('click', loadOnline);

                // ----- Upload tab -----
                uploadInput.addEventListener('change', () => {
                    const file = uploadInput.files[0];
                    if (!file) return setPending(null);

                    if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
                        resetUpload();
                        setPending(null);
                        return toast('فرمت عکس باید PNG، JPG یا WebP باشد.', false);
                    }
                    if (file.size > 2 * 1024 * 1024) {
                        resetUpload();
                        setPending(null);
                        return toast('حجم عکس نباید بیشتر از ۲ مگابایت باشد.', false);
                    }

                    uploadPreview.src = URL.createObjectURL(file);
                    uploadPreview.classList.remove('d-none');
                    setPending({kind: 'upload', file});
                });

                // ----- Tabs -----
                modal.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                    tab.addEventListener('shown.bs.tab', () => {
                        setPending(null);
                        resetUpload();
                        const target = tab.dataset.bsTarget;
                        if (target === '#avatarTabCoins' && !coinsLoaded) loadCoins();
                        if (target === '#avatarTabOnline' && !onlineLoaded) loadOnline();
                    });
                });

                // Reopening the modal starts from the saved avatar.
                modal.addEventListener('show.bs.modal', () => {
                    resetUpload();
                    setPending(null);
                });

                // ----- Save -----
                saveBtn.addEventListener('click', async () => {
                    if (!pending) return;
                    saveBtn.disabled = true;
                    spinner.classList.remove('d-none');

                    const headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf};
                    let request;
                    if (pending.kind === 'key') {
                        request = fetch(urls.update, {
                            method: 'PATCH',
                            headers: {...headers, 'Content-Type': 'application/json'},
                            body: JSON.stringify({avatar: pending.key}),
                        });
                    } else if (pending.kind === 'generate') {
                        request = fetch(urls.generate, {
                            method: 'POST',
                            headers: {...headers, 'Content-Type': 'application/json'},
                            body: JSON.stringify({style: pending.style, seed: pending.seed}),
                        });
                    } else {
                        const form = new FormData();
                        form.append('photo', pending.file);
                        request = fetch(urls.upload, {method: 'POST', headers, body: form});
                    }

                    try {
                        const response = await request;
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) {
                            const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                            throw new Error(firstError || data.message || '');
                        }

                        document.querySelectorAll('img[data-admin-avatar]').forEach(img => img.src = data.avatar);
                        savedKey = data.key;
                        resetUpload();
                        setPending(null);
                        bootstrap.Modal.getInstance(modal)?.hide();
                        toast(data.message);
                    } catch (e) {
                        toast(e.message || 'خطا در تغییر آواتار. لطفاً دوباره تلاش کنید.', false);
                        saveBtn.disabled = false;
                    } finally {
                        spinner.classList.add('d-none');
                    }
                });

                render();
            });
        </script>
    @endpush
@endif
