@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول های صرافی')
@section('content')

    @php
        $groupedWalletChains = collect($exchangeWalletChains)
            ->groupBy(fn($walletChain) => strtoupper($walletChain->currency_chain ?? 'OTHER'))
            ->map(function ($group, $chainKey) {
                return [
                    'chain' => $chainKey,
                    'items' => collect($group)
                        ->sortBy(function ($walletChain) {
                            return strtolower($walletChain->wallet->currency_symbol ?? '');
                        })
                        ->values(),
                ];
            })
            ->sortBy(fn($group) => strtolower($group['chain']))
            ->values();

        $allChains = $groupedWalletChains->pluck('chain')->values();
    @endphp

    <div class="card">
        <div class="card-header pb-3">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div>
                    <h4 class="mb-1">دارایی Hot Wallet</h4>
                    <small class="text-muted">نمایش بهینه برای تعداد زیاد کوین با گروه‌بندی شبکه</small>
                </div>
                <span class="badge bg-label-primary">{{ collect($exchangeWalletChains)->count() }} کیف پول</span>
            </div>
        </div>
        <div class="card-body pt-2">
            <div class="row g-3 mb-4">
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-regular fa-search"></i></span>
                        <input type="text" class="form-control" id="walletSearch"
                            placeholder="جستجو بر اساس نماد، شبکه یا آدرس...">
                    </div>
                </div>
                <div class="col-md-5">
                    <select class="form-select" id="chainFilter">
                        <option value="all">همه شبکه‌ها</option>
                        @foreach ($allChains as $chain)
                            <option value="{{ $chain }}">{{ $chain }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @foreach ($groupedWalletChains as $group)
                <div class="card border mb-4 wallet-group" data-chain="{{ $group['chain'] }}">
                    <div class="card-header py-3">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                @if (!empty($chainLogoMap[$group['chain']]))
                                    <img src="{{ $chainLogoMap[$group['chain']] }}" width="24" height="24" class="rounded-circle"
                                        alt="{{ $group['chain'] }}">
                                @endif
                                <h6 class="mb-0">شبکه {{ $group['chain'] }}</h6>
                            </div>
                            <span class="badge bg-label-secondary">{{ $group['items']->count() }} کوین</span>
                        </div>
                    </div>

                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle mb-0">

                            <tbody>
                                @foreach ($group['items'] as $walletChain)
                                    @php
                                        $symbol = $walletChain->wallet->currency_symbol;
                                        $chain = strtoupper($walletChain->currency_chain ?? 'OTHER');
                                        $address = $walletChain->address;
                                        $searchText = strtolower($symbol . ' ' . $chain . ' ' . ($address ?? ''));
                                    @endphp
                                    <tr data-search="{{ $searchText }}" data-chain="{{ $chain }}"
                                        data-wallet-chain-id="{{ $walletChain->id }}"
                                        data-balance-url="{{ route('admin.exchange.hot-wallet.balance', $walletChain) }}"
                                        data-symbol="{{ $symbol }}">
                                        <td class="w-25">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative flex-shrink-0">
                                                    <img src="{{ $walletChain->wallet->currency->coinLogo() }}"
                                                        class="rounded-circle" width="38" height="38" alt="{{ $symbol }}">
                                                    @if (!empty($chainLogoMap[$chain]))
                                                        <img src="{{ $chainLogoMap[$chain] }}"
                                                            class="position-absolute rounded-circle border border-white" width="18"
                                                            height="18" style="bottom: -2px; right: -2px; background: #fff;"
                                                            alt="{{ $chain }}" title="{{ $chain }}">
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $symbol }}</div>
                                                    <small class="text-muted">{{ $chain }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="w-25">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="spinner-border spinner-border-sm text-primary wallet-balance-spinner"
                                                    role="status" aria-hidden="true"></span>
                                                <span class="font-number fw-bold h5 wallet-balance-value mb-2 d-none" dir="ltr"></span>
                                                <small class="text-muted me-1">{{ $symbol }}</small>
                                            </div>
                                        
                                       
                                              <small class="text-muted d-block wallet-balance-status" style="font-size: 11px; line-height: 1.2;">
                                                در حال دریافت...
                                            </small>
                                        </td>
                                        <td class="w-50">
                                            @if ($address)
                                                <div class="d-flex align-items-center gap-1">
                                                    <a href="javascript:void(0);" class="mx-1 copy-btn d-flex" data-copy-text="{{ $address }}"
                                                        title="کپی آدرس">
                                                        <i class="fa-regular fa-clone"></i>
                                                    </a>
                                                    <a href="{{ $walletChain->explorer_address_url }}" target="_blank"
                                                        class="text-primary font-number" dir="ltr">
                                                        {{ $address }}
                                                    </a>
                                                </div>
                                            @else
                                                <span class="text-muted">بدون آدرس</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="javascript:void(0);" class="d-inline-flex wallet-row-refresh"
                                                data-wallet-chain-id="{{ $walletChain->id }}" title="بروزرسانی">
                                                <i class="fa fa-refresh"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div id="walletEmptyState" class="text-center py-5 d-none">
                <div class="mb-2 text-muted">
                    <i class="fa-regular fa-folder-open fa-2x"></i>
                </div>
                <h6 class="mb-1">نتیجه‌ای یافت نشد</h6>
                <small class="text-muted">فیلتر یا عبارت جستجو را تغییر دهید.</small>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const walletSearch = document.getElementById('walletSearch');
            const chainFilter = document.getElementById('chainFilter');
            const walletEmptyState = document.getElementById('walletEmptyState');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const MAX_CONCURRENT_REQUESTS = 4;
            const rowElements = Array.from(document.querySelectorAll('tr[data-wallet-chain-id][data-balance-url]'));
            const fetchQueue = [];
            let activeRequests = 0;

            function showRowLoading(row, isLoading) {
                const spinner = row.querySelector('.wallet-balance-spinner');
                const refreshBtn = row.querySelector('.wallet-row-refresh');
                const valueElement = row.querySelector('.wallet-balance-value');

                row.dataset.loading = isLoading ? '1' : '0';

                if (spinner) {
                    spinner.classList.toggle('d-none', !isLoading);
                }

                if (valueElement) {
                    valueElement.classList.toggle('d-none', isLoading);
                }

                if (refreshBtn) {
                    refreshBtn.classList.toggle('disabled', isLoading);
                    refreshBtn.style.pointerEvents = isLoading ? 'none' : '';
                    refreshBtn.style.opacity = isLoading ? '0.5' : '';
                }
            }

            function setRowBalance(row, amount) {
                const valueElement = row.querySelector('.wallet-balance-value');
                if (valueElement) {
                    valueElement.textContent = amount;
                }
            }

            function setRowStatus(row, status) {
                const statusElement = row.querySelector('.wallet-balance-status');
                if (!statusElement) {
                    return;
                }

                if (status === 'cached') {
                    statusElement.textContent = 'موجودی کش شده هست';
                    return;
                }

                if (status === 'updated') {
                    statusElement.textContent = 'موجودی به روز';
                    return;
                }

                statusElement.textContent = status || 'در حال دریافت...';
            }

            function setRowErrorState(row) {
                setRowBalance(row, '--');
                setRowStatus(row, 'خطا در دریافت');
            }

            function showErrorToast(message) {
                if (typeof Toastify !== 'function') {
                    return;
                }

                Toastify({
                    text: message || 'خطا در دریافت موجودی',
                    duration: 5000,
                    gravity: 'top',
                    position: 'right',
                    style: {
                        background: '#EA5455'
                    }
                }).showToast();
            }

            function fetchRowBalance(row, options = {}) {
                const balanceUrl = row.dataset.balanceUrl;
                const forceRefresh = !!options.forceRefresh;

                if (!balanceUrl) {
                    return Promise.resolve();
                }

                if (row.dataset.loading === '1') {
                    return Promise.resolve();
                }

                showRowLoading(row, true);
                setRowStatus(row, 'در حال دریافت...');

                const requestUrl = forceRefresh ? `${balanceUrl}?force_refresh=1` : balanceUrl;

                return fetch(requestUrl, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                }).then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (errorPayload) {
                            throw new Error(errorPayload?.error || 'خطا در دریافت موجودی');
                        }).catch(function () {
                            throw new Error('خطا در دریافت موجودی');
                        });
                    }

                    return response.json();
                }).then(function (data) {
                    const amount = data?.amount ?? '0';
                    const status = data?.status || '';
                    setRowBalance(row, amount);
                    setRowStatus(row, status);
                }).catch(function (error) {
                    setRowErrorState(row);
                    console.error('Error fetching balance for wallet chain ID ' + row.dataset.walletChainId, error);
                    if (forceRefresh) {
                        showErrorToast(error?.message || 'خطا در بروزرسانی موجودی');
                    }
                }).finally(function () {
                    showRowLoading(row, false);
                });
            }

            function runBalanceQueue() {
                while (activeRequests < MAX_CONCURRENT_REQUESTS && fetchQueue.length > 0) {
                    const row = fetchQueue.shift();
                    activeRequests++;

                    fetchRowBalance(row).finally(function () {
                        activeRequests--;
                        runBalanceQueue();
                    });
                }
            }

            function enqueueBalanceFetch(row, options = {}) {
                if (!row) {
                    return;
                }

                if (options.forceRefresh) {
                    fetchRowBalance(row, { forceRefresh: true });
                    return;
                }

                fetchQueue.push(row);
                runBalanceQueue();
            }

            function applyWalletFilters() {
                const query = (walletSearch?.value || '').toLowerCase().trim();
                const selectedChain = chainFilter?.value || 'all';
                const groups = document.querySelectorAll('.wallet-group');
                let hasVisibleGroup = false;

                groups.forEach(function (group) {
                    const rows = Array.from(group.querySelectorAll('tbody tr[data-search]'));
                    let hasVisibleRow = false;

                    rows.forEach(function (row) {
                        const haystack = row.dataset.search || '';
                        const rowChain = row.dataset.chain || '';
                        const matchQuery = !query || haystack.includes(query);
                        const matchChain = selectedChain === 'all' || rowChain === selectedChain;
                        const isVisible = matchQuery && matchChain;

                        row.style.display = isVisible ? '' : 'none';
                        if (isVisible) {
                            hasVisibleRow = true;
                        }
                    });

                    group.style.display = hasVisibleRow ? '' : 'none';
                    if (hasVisibleRow) {
                        hasVisibleGroup = true;
                    }
                });

                walletEmptyState.classList.toggle('d-none', hasVisibleGroup);
            }

            walletSearch?.addEventListener('input', applyWalletFilters);
            chainFilter?.addEventListener('change', applyWalletFilters);

            rowElements.forEach(function (row) {
                enqueueBalanceFetch(row);
            });

            document.addEventListener('click', function (event) {
                const refreshButton = event.target.closest('.wallet-row-refresh');
                if (refreshButton) {
                    event.preventDefault();
                    const row = refreshButton.closest('tr[data-wallet-chain-id]');
                    enqueueBalanceFetch(row, { forceRefresh: true });
                    return;
                }

                const button = event.target.closest('.copy-btn');
                if (!button) {
                    return;
                }

                const textToCopy = button.getAttribute('data-copy-text');
                if (!textToCopy) {
                    return;
                }

                navigator.clipboard.writeText(textToCopy).then(function () {
                    const icon = button.querySelector('i');
                    if (!icon) {
                        return;
                    }

                    icon.classList.replace('fa-clone', 'fa-check');
                    setTimeout(function () {
                        icon.classList.replace('fa-check', 'fa-clone');
                    }, 1500);
                }).catch(function () { });
            });
        });
    </script>
@endpush

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/spinkit/spinkit.scss'])
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/block-ui/block-ui.js', 'resources/assets/js/cards-actions.js'])
@endsection