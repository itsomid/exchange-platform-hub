@extends('dashboard.layout.master')
@section('title', 'لیست ارزهای HD Wallet')
@section('content')

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h5 class="card-title mb-1">
                    <i class="fa-regular fa-wallet me-2"></i>
                    لیست ارزهای قابل همگام‌سازی
                </h5>
                <small class="text-secondary">مدیریت ارزها در Sweeper و سرویس جدید HD wallet </small>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold mb-1">سرویس HD Wallet</div>
                            @if ($serviceStatus['success'])
                                <span class="badge bg-label-success">فعال</span>
                            @else
                                <span class="badge bg-label-danger">غیرفعال</span>
                                <small class="text-secondary d-block mt-1">
                                    {{ $serviceStatus['error'] }}
                                </small>
                            @endif
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-regular fa-cloud"></i>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold mb-1">سرویس Sweeper</div>
                            @if ($sweeperStatus['success'])
                                <span class="badge bg-label-success">فعال</span>
                            @else
                                <span class="badge bg-label-danger">غیرفعال</span>
                                <small class="text-secondary d-block mt-1">
                                    {{ $sweeperStatus['error'] }}
                                </small>
                            @endif
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-regular fa-server"></i>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-regular fa-search"></i></span>
                        <input type="text" class="form-control" id="currencySearch" placeholder="جستجوی کوین یا شبکه...">
                    </div>
                </div>
                <div class="col-md-6">
                    <select class="form-select" id="chainFilter">
                        <option value="all">همه شبکه‌ها</option>
                        <option value="TRC20">TRC20</option>
                        <option value="ERC20">ERC20</option>
                        <option value="BSC">BSC (BEP20)</option>
                        <option value="BTC">BTC</option>
                        <option value="DOGE">DOGE</option>
                        <option value="OPTIMISM">Optimism</option>
                        <option value="LTC">LTC</option>
                        <option value="ARBITRUM">Arbitrum One</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr class="">
                            <th>آواتار</th>
                            <th>شبکه</th>
                            <th>hd-wallet-sweeper</th>
                            <th>hd-wallet-service_new</th>
                            <th>کیف پول اختصاص یافته</th>
                        </tr>
                    </thead>
                    @php
                        // Build network -> assignedWalletId map from native coins
                        $nativeWalletMap = [];
                        foreach ($items as $_nativeItem) {
                            if (($_nativeItem['service_payload']['isNative'] ?? false) && $_nativeItem['network']) {
                                $nativeWalletMap[$_nativeItem['network']] = $_nativeItem['service_payload']['assignedWalletId'] ?? null;
                            }
                        }
                        // Build walletId -> wallet name map
                        $walletNameMap = [];
                        foreach ($wallets as $_w) {
                            $walletNameMap[$_w['walletId']] = $_w['name'];
                        }

                        $nativeSymbolsByChain = [
                            'TRC20' => 'TRX',
                            'ERC20' => 'ETH',
                            'BSC' => 'BNB',
                            'BTC' => 'BTC',
                            'DOGE' => 'DOGE',
                            'LTC' => 'LTC',
                            'POLYGON' => 'POL',
                            'OPTIMISM' => 'ETH',
                            'ARBITRUM' => 'ETH',
                        ];

                        $groupedItems = collect($items)
                            ->groupBy(fn($item) => $item['chain'] ?? 'other')
                            ->map(function ($group, $chain) use ($nativeSymbolsByChain) {
                                $nativeSymbol = $nativeSymbolsByChain[$chain] ?? null;
                                $sortedGroup = collect($group)
                                    ->sortBy(function ($item) use ($nativeSymbol) {
                                        $symbol = strtoupper($item['symbol'] ?? '');
                                        $isNative = ($item['service_payload']['isNative'] ?? false)
                                            || ($nativeSymbol && $symbol === $nativeSymbol);

                                        return sprintf(
                                            '%d-%s-%s',
                                            $isNative ? 0 : 1,
                                            $symbol,
                                            strtolower($item['display_name'] ?? '')
                                        );
                                    })
                                    ->values();

                                $parentItem = $sortedGroup->first(function ($item) use ($nativeSymbol) {
                                    $symbol = strtoupper($item['symbol'] ?? '');

                                    return ($item['service_payload']['isNative'] ?? false)
                                        || ($nativeSymbol && $symbol === $nativeSymbol);
                                });

                                $rows = collect();

                                if ($parentItem) {
                                    $rows->push([
                                        'item' => $parentItem,
                                        'is_child' => false,
                                        'is_parent' => true,
                                    ]);

                                    foreach ($sortedGroup as $groupItem) {
                                        if (($groupItem['id'] ?? null) === ($parentItem['id'] ?? null)) {
                                            continue;
                                        }

                                        $rows->push([
                                            'item' => $groupItem,
                                            'is_child' => true,
                                            'is_parent' => false,
                                        ]);
                                    }
                                } else {
                                    foreach ($sortedGroup as $groupItem) {
                                        $rows->push([
                                            'item' => $groupItem,
                                            'is_child' => false,
                                            'is_parent' => false,
                                        ]);
                                    }
                                }

                                return [
                                    'chain' => $chain,
                                    'label' => $sortedGroup->first()['network_label'] ?? $chain,
                                    'rows' => $rows,
                                ];
                            })
                            ->sortBy(function ($group) {
                                return strtolower(($group['label'] ?? '') . '-' . ($group['chain'] ?? ''));
                            });
                    @endphp
                    @foreach ($groupedItems as $group)
                        <tbody class="table-border-bottom-0 currency-group" data-group-chain="{{ $group['chain'] }}">
                            <tr class="table-light">
                                <td colspan="5" class="fw-semibold text-dark">
                                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                        <span>
                                            <i class="fa-regular fa-layer-group me-1"></i>
                                            {{ $group['label'] }}
                                        </span>
                                        <span class="badge bg-label-secondary">
                                            {{ $group['rows']->count() }} ارز
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @foreach ($group['rows'] as $groupRow)
                                @php
                                    $item = $groupRow['item'];
                                    $symbol = strtoupper($item['symbol'] ?? '');
                                    $chainValue = $item['chain'] ?? null;
                                    $isNativeCoin = $groupRow['is_parent'];
                                    $chainIcon = $isNativeCoin
                                        ? null
                                        : match ($chainValue) {
                                            'TRC20' => asset('images/coins/trx.svg'),
                                            'ERC20' => asset('images/coins/eth.svg'),
                                            'BSC' => asset('images/coins/bnb.svg'),
                                            'BTC' => asset('images/coins/btc.svg'),
                                            'DOGE' => asset('images/coins/doge.svg'),
                                            'LTC' => asset('images/coins/ltc.svg'),
                                            'POLYGON' => asset('images/coins/pol.svg'),
                                            'OPTIMISM' => asset('images/coins/eth.svg'),
                                            'ARBITRUM' => asset('images/coins/eth.svg'),
                                            default => null,
                                        };
                                @endphp
                                <tr class="{{ $groupRow['is_child'] ? 'table-row-child' : '' }}" data-chain="{{ $item['chain'] }}"
                                    data-group-row="1" data-group-parent="{{ $groupRow['is_parent'] ? '1' : '0' }}"
                                    data-search="{{ strtolower($item['symbol'] . ' ' . ($item['persian_name'] ?? '') . ' ' . ($item['display_name'] ?? '') . ' ' . ($item['network_label'] ?? '') . ' ' . ($item['chain'] ?? '')) }}">
                                    <td>
                                        <div class="d-flex align-items-center gap-2 {{ $groupRow['is_child'] ? 'ps-4' : '' }}">
                                            <div class="position-relative flex-shrink-0">
                                                <img src="{{ $item['logo_url'] }}" class="rounded-circle" width="36" height="36">
                                                @if ($chainIcon)
                                                    <img src="{{ $chainIcon }}"
                                                        class="position-absolute rounded-circle border border-white" width="18"
                                                        height="18" style="bottom: -2px; right: -2px; background: #fff;"
                                                        title="{{ $item['network_label'] }}">
                                                @endif
                                            </div>
                                            <div class="text-start">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="fw-semibold">{{ $item['symbol'] }}</span>
                                                    @if ($groupRow['is_parent'])
                                                        <span class="badge bg-label-success">ارز مادر</span>
                                                    @elseif ($groupRow['is_child'])
                                                        <span class="badge bg-label-warning">زیرمجموعه</span>
                                                    @endif
                                                </div>
                                                <small class="text-secondary d-block">
                                                    {{ $item['persian_name'] ?? $item['display_name'] }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>

                                    <td>{{ $item['network_label'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            @if ($item['exists_sweeper'])
                                                <span
                                                    class="badge bg-label-{{ $item['sweeper_payload']['isActive'] ?? true ? 'success' : 'danger' }}">
                                                    <i class="fa-regular fa-check me-1"></i>
                                                    {{ $item['sweeper_payload']['isActive'] ?? true ? 'فعال' : 'غیرفعال' }}
                                                </span>
                                                <button type="button" class="btn btn-sm btn-outline-warning" data-edit-currency
                                                    data-target="sweeper" data-identifier="{{ $item['sweeper_identifier'] ?? '' }}"
                                                    data-display-name="{{ $item['sweeper_payload']['displayName'] ?? $item['display_name'] }}"
                                                    data-symbol="{{ $item['sweeper_payload']['symbol'] ?? $item['symbol'] }}"
                                                    data-contract-address="{{ $item['sweeper_payload']['contractAddress'] ?? $item['contract_address'] }}"
                                                    data-decimals="{{ $item['sweeper_payload']['decimals'] ?? $item['decimals'] }}"
                                                    data-required-confirmations="{{ $item['sweeper_payload']['networkConfig']['confirmations'] ?? '' }}"
                                                    data-description="{{ $item['sweeper_payload']['description'] ?? $item['description'] }}"
                                                    data-is-active="{{ $item['sweeper_payload']['isActive'] ?? true ? '1' : '0' }}">
                                                    <i class="fa-regular fa-edit"></i>
                                                </button>
                                                @if ($item['sweeper_payload']['isActive'] ?? true)
                                                    <form method="POST" action="{{ route('admin.hd-wallet.currencies.delete') }}"
                                                        style="display: inline;">
                                                        @csrf
                                                        <input type="hidden" name="target" value="sweeper">
                                                        <input type="hidden" name="identifier"
                                                            value="{{ $item['sweeper_identifier'] ?? '' }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning"
                                                            title="غیرفعال‌سازی در Sweeper"
                                                            onclick="return confirm('غیرفعال‌سازی این ارز در sweeper انجام شود؟');">
                                                            <i class="fa-solid fa-toggle-off"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                @if ($item['create_allowed'])
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-create-currency
                                                        data-target="sweeper" data-currency-name="{{ $item['currency_name'] }}"
                                                        data-display-name="{{ $item['display_name'] }}" data-symbol="{{ $item['symbol'] }}"
                                                        data-network="{{ $item['network'] }}"
                                                        data-contract-address="{{ $item['contract_address'] }}"
                                                        data-decimals="{{ $item['decimals'] }}"
                                                        data-description="{{ $item['description'] }}">
                                                        ایجاد
                                                    </button>
                                                @else
                                                    <span class="badge bg-label-secondary">پشتیبانی نمی‌شود</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            @if ($item['exists_service'] && $item['service_identifier'])
                                                <span
                                                    class="badge bg-label-{{ $item['service_payload']['isActive'] ?? true ? 'success' : 'danger' }}">
                                                    <i class="fa-regular fa-check me-1"></i>
                                                    {{ $item['service_payload']['isActive'] ?? true ? 'فعال' : 'غیرفعال' }}
                                                </span>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-edit-currency
                                                    data-target="service_new" data-identifier="{{ $item['service_identifier'] }}"
                                                    data-display-name="{{ $item['service_payload']['displayName'] ?? $item['display_name'] }}"
                                                    data-symbol="{{ $item['service_payload']['symbol'] ?? $item['symbol'] }}"
                                                    data-contract-address="{{ $item['service_payload']['contractAddress'] ?? $item['contract_address'] }}"
                                                    data-decimals="{{ $item['service_payload']['decimals'] ?? $item['decimals'] }}"
                                                    data-required-confirmations="{{ $item['service_payload']['networkConfig']['confirmations'] ?? '' }}"
                                                    data-description="{{ $item['service_payload']['description'] ?? $item['description'] }}"
                                                    data-is-active="{{ $item['service_payload']['isActive'] ?? true ? '1' : '0' }}"
                                                    data-assigned-wallet-id="{{ $item['service_payload']['assignedWalletId'] ?? '' }}"
                                                    data-network="{{ $item['network'] }}"
                                                    data-is-native="{{ ($item['service_payload']['isNative'] ?? false) ? '1' : '0' }}">
                                                    <i class="fa-regular fa-edit"></i>
                                                </button>
                                                @if ($item['service_payload']['isActive'] ?? true)
                                                    <form method="POST" action="{{ route('admin.hd-wallet.currencies.delete') }}"
                                                        style="display: inline;">
                                                        @csrf
                                                        <input type="hidden" name="target" value="service_new">
                                                        <input type="hidden" name="identifier" value="{{ $item['service_identifier'] }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning"
                                                            title="غیرفعال‌سازی در Service"
                                                            onclick="return confirm('غیرفعال‌سازی این ارز در service_new انجام شود؟');">
                                                            <i class="fa-solid fa-toggle-off"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                @if ($item['create_allowed'])
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-create-currency
                                                        data-target="service_new" data-currency-name="{{ $item['currency_name'] }}"
                                                        data-display-name="{{ $item['display_name'] }}" data-symbol="{{ $item['symbol'] }}"
                                                        data-network="{{ $item['network'] }}"
                                                        data-contract-address="{{ $item['contract_address'] }}"
                                                        data-decimals="{{ $item['decimals'] }}"
                                                        data-description="{{ $item['description'] }}">
                                                        ایجاد
                                                    </button>
                                                @else
                                                    <span class="badge bg-label-secondary">پشتیبانی نمی‌شود</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($item['exists_service'] && $item['service_identifier'])
                                            @php
                                                $isNative = $item['service_payload']['isNative'] ?? false;
                                                $assignedWallet = $item['service_payload']['assignedWalletId'] ?? null;
                                            @endphp
                                            @if ($isNative)
                                                @if ($assignedWallet)
                                                    <span class="badge bg-label-info">
                                                        <i class="fa-regular fa-wallet me-1"></i>
                                                        {{ $assignedWallet }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-label-secondary">
                                                        <i class="fa-regular fa-random me-1"></i>
                                                        پیش‌فرض شبکه
                                                    </span>
                                                @endif
                                            @else
                                                @php
                                                    $parentWalletId = $nativeWalletMap[$item['network']] ?? null;
                                                    $parentWalletName = $parentWalletId
                                                        ? ($walletNameMap[$parentWalletId] ?? $parentWalletId)
                                                        : null;
                                                @endphp
                                                <span class="badge bg-label-warning"
                                                    title="{{ $parentWalletName ? 'ارث‌بری از ' . $parentWalletName : 'ارث‌بری از کوین مادر' }}">
                                                    <i class="fa-regular fa-link me-1"></i>
                                                    {{ $parentWalletName ?? $parentWalletId ?? 'پیش‌فرض شبکه' }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-secondary">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createCurrencyModal" tabindex="-1" aria-labelledby="createCurrencyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.hd-wallet.currencies.create') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createCurrencyModalLabel">ایجاد ارز</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="target" id="currencyTarget">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">نام داخلی</label>
                                <input type="text" class="form-control" name="currency_name" id="currencyName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نام نمایشی</label>
                                <input type="text" class="form-control" name="display_name" id="displayName" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">نماد</label>
                                <input type="text" class="form-control" name="symbol" id="symbol" required readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">شبکه</label>
                                <input type="text" class="form-control" name="network" id="network" required readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">تعداد اعشار</label>
                                <input type="number" class="form-control" name="decimals" id="decimals" min="0" max="30">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">آدرس قرارداد (Contract Address)</label>
                                <input type="text" class="form-control" name="contract_address" id="contractAddress"
                                    required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">توضیحات</label>
                                <input type="text" class="form-control" name="description" id="description">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary">ایجاد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCurrencyModal" tabindex="-1" aria-labelledby="editCurrencyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.hd-wallet.currencies.update') }}">
                    @csrf
                    <input type="hidden" name="target" id="editTarget">
                    <input type="hidden" name="identifier" id="editIdentifier">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCurrencyModalLabel">ویرایش ارز</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">نام نمایشی</label>
                                <input type="text" class="form-control" name="display_name" id="editDisplayName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نماد</label>
                                <input type="text" class="form-control" name="symbol" id="editSymbol" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">تعداد اعشار</label>
                                <input type="number" class="form-control" name="decimals" id="editDecimals" min="0"
                                    max="30">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">تعداد تاییدیه لازم</label>
                                <input type="number" class="form-control" name="confirmations"
                                    id="editRequiredConfirmations" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">وضعیت</label>
                                <select class="form-select" name="is_active" id="editIsActive">
                                    <option value="1">فعال</option>
                                    <option value="0">غیرفعال</option>
                                </select>
                            </div>
                            <div class="col-md-12" id="walletDropdownWrapper" style="display: none;">
                                <label class="form-label">کیف پول </label>
                                <select class="form-select" name="assigned_wallet_id" id="editAssignedWalletId">
                                    <option value="">پیش‌فرض شبکه (اولین کیف پول فعال)</option>
                                    @foreach ($wallets as $w)
                                        <option value="{{ $w['walletId'] }}"
                                            data-networks="{{ implode(',', $w['supportedNetworks'] ?? []) }}">
                                            {{ $w['name'] }} ({{ $w['walletId'] }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text d-none" id="walletTokenHint">
                                    <i class="fa-regular fa-info-circle me-1"></i>
                                    تغییر کیف پول فقط برای کوین‌های مادر امکان‌پذیر است. توکن‌ها به صورت خودکار از HDWallet
                                    ای که به کوین مادر منصوب شد استفاده می‌کنند.
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">آدرس قرارداد (Contract Address)</label>
                                <input type="text" class="form-control" name="contract_address" id="editContractAddress">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">توضیحات</label>
                                <input type="text" class="form-control" name="description" id="editDescription">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('createCurrencyModal');
            const modal = new bootstrap.Modal(modalElement);
            const title = document.getElementById('createCurrencyModalLabel');
            const targetInput = document.getElementById('currencyTarget');
            const currencyName = document.getElementById('currencyName');
            const displayName = document.getElementById('displayName');
            const symbol = document.getElementById('symbol');
            const network = document.getElementById('network');
            const contractAddress = document.getElementById('contractAddress');
            const decimals = document.getElementById('decimals');
            const description = document.getElementById('description');

            const editModalElement = document.getElementById('editCurrencyModal');
            const editModal = new bootstrap.Modal(editModalElement);
            const editTarget = document.getElementById('editTarget');
            const editIdentifier = document.getElementById('editIdentifier');
            const editDisplayName = document.getElementById('editDisplayName');
            const editSymbol = document.getElementById('editSymbol');
            const editContractAddress = document.getElementById('editContractAddress');
            const editDecimals = document.getElementById('editDecimals');
            const editRequiredConfirmations = document.getElementById('editRequiredConfirmations');
            const editIsActive = document.getElementById('editIsActive');
            const editDescription = document.getElementById('editDescription');
            const editAssignedWalletId = document.getElementById('editAssignedWalletId');
            const walletDropdownWrapper = document.getElementById('walletDropdownWrapper');
            const currencySearch = document.getElementById('currencySearch');
            const chainFilter = document.getElementById('chainFilter');

            const labels = {
                sweeper: 'HD Wallet Sweeper',
                service_new: 'HD Wallet Service New'
            };

            document.addEventListener('click', function (e) {
                const button = e.target.closest('[data-create-currency]');
                if (!button) {
                    return;
                }

                const target = button.dataset.target;
                targetInput.value = target;
                currencyName.value = button.dataset.currencyName || '';
                displayName.value = button.dataset.displayName || '';
                symbol.value = button.dataset.symbol || '';
                network.value = button.dataset.network || '';
                contractAddress.value = button.dataset.contractAddress || '';
                decimals.value = button.dataset.decimals || '';
                description.value = button.dataset.description || '';

                title.textContent = 'ایجاد ارز در ' + (labels[target] || target);
                modal.show();
            });

            document.addEventListener('click', function (e) {
                const button = e.target.closest('[data-edit-currency]');
                if (!button) {
                    return;
                }

                const target = button.dataset.target || '';
                editTarget.value = target;
                editIdentifier.value = button.dataset.identifier || '';
                editDisplayName.value = button.dataset.displayName || '';
                editSymbol.value = button.dataset.symbol || '';
                editContractAddress.value = button.dataset.contractAddress || '';
                editDecimals.value = button.dataset.decimals || '';
                editRequiredConfirmations.value = button.dataset.requiredConfirmations || '';
                editIsActive.value = button.dataset.isActive === '0' ? '0' : '1';
                editDescription.value = button.dataset.description || '';

                const walletTokenHint = document.getElementById('walletTokenHint');

                // Show/hide and populate wallet dropdown only for service_new
                if (target === 'service_new') {
                    walletDropdownWrapper.style.display = '';
                    const currentNetwork = button.dataset.network || '';
                    const currentWalletId = button.dataset.assignedWalletId || '';
                    const isNative = button.dataset.isNative === '1';

                    // Filter wallet options by network
                    Array.from(editAssignedWalletId.options).forEach(function (opt) {
                        if (!opt.value) {
                            // Always show the default option
                            return;
                        }
                        const networks = (opt.dataset.networks || '').split(',');
                        opt.style.display = (!currentNetwork || networks.includes(currentNetwork)) ? '' : 'none';
                    });

                    // Only native coins can change wallet; tokens inherit from parent
                    if (isNative) {
                        editAssignedWalletId.disabled = false;
                        editAssignedWalletId.value = currentWalletId;
                        walletTokenHint.classList.add('d-none');
                    } else {
                        editAssignedWalletId.disabled = true;
                        editAssignedWalletId.value = '';
                        walletTokenHint.classList.remove('d-none');
                    }
                } else {
                    walletDropdownWrapper.style.display = 'none';
                    editAssignedWalletId.value = '';
                    editAssignedWalletId.disabled = false;
                }

                editModal.show();
            });

            function applyFilters() {
                const query = (currencySearch.value || '').toLowerCase().trim();
                const chain = chainFilter.value;
                const groups = document.querySelectorAll('tbody.currency-group');

                groups.forEach(group => {
                    const rows = Array.from(group.querySelectorAll('tr[data-search]'));
                    let hasVisibleRow = false;

                    rows.forEach(row => {
                        const haystack = row.dataset.search || '';
                        const rowChain = row.dataset.chain || '';
                        const matchQuery = !query || haystack.includes(query);
                        const matchChain = chain === 'all' || rowChain === chain;
                        const isVisible = matchQuery && matchChain;

                        row.style.display = isVisible ? '' : 'none';

                        if (isVisible) {
                            hasVisibleRow = true;
                        }
                    });

                    const parentRow = group.querySelector('tr[data-group-parent="1"]');
                    if (hasVisibleRow && parentRow) {
                        parentRow.style.display = '';
                    }

                    group.style.display = hasVisibleRow ? '' : 'none';
                });
            }

            if (currencySearch) {
                currencySearch.addEventListener('input', applyFilters);
            }
            if (chainFilter) {
                chainFilter.addEventListener('change', applyFilters);
            }
        });
    </script>
@endpush