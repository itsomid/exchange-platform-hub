{{-- Currency Table (AJAX-refreshable partial) --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h5 class="mb-0">
            <i class="fa-light fa-list me-2"></i>
            لیست کوین‌ها
            <span class="badge bg-label-secondary ms-2">{{ $currencies->total() }}</span>
        </h5>
        <a href="{{ route('admin.currency.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus me-1"></i>
            افزودن کوین جدید
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="text-center">
                    <th class="fw-semibold" style="width: 50px">#</th>
                    <th class="fw-semibold text-start" style="min-width: 200px">کوین</th>
                    <th class="fw-semibold">شبکه‌ها</th>
                    <th class="fw-semibold">کارمزد برداشت صرافی</th>
                    <th class="fw-semibold">کارمزد شبکه</th>
                    <th class="fw-semibold">وضعیت</th>
                    <th class="fw-semibold" style="width: 100px">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($currencies as $currency)
                    <tr>
                        <td class="text-center text-muted">{{ $currency->id }}</td>

                        {{-- Coin Info (combined avatar + name + symbol) --}}
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ asset($currency->coinLogo()) }}" class="rounded-circle" width="40" height="40"
                                    alt="{{ $currency->symbol }}">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold">{{ $currency->name }}</div>
                                        @if(!$currency->is_active)
                                            <span class="badge bg-label-warning">غیرفعال</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $currency->symbol }}</small>
                                </div>
                            </div>
                        </td>

                        {{-- Chains --}}
                        <td class="text-center">
                            @if($currency->chains->count())
                                @foreach($currency->chains as $chain)
                                    <div class="d-flex flex-wrap justify-content-center gap-1 px-2 py-1 ">

                                        <span class="badge bg-label-primary d-block">{{ $chain->chain }}</span>

                                    </div>
                                @endforeach
                            @else
                                <span class="badge bg-label-danger">بدون شبکه</span>
                            @endif
                        </td>

                        {{-- Exchange Withdrawal Fee --}}
                        <td class="text-center">
                            @if($currency->chains->count())
                                <div class="d-flex flex-column gap-1 align-items-center">
                                    @foreach($currency->chains as $chain)
                                        <div class="d-flex align-items-center gap-1 rounded px-2 py-1 bg-label-secondary"
                                            style="font-size: 0.78rem;">
                                            <span class="text-muted">{{ $currency->symbol }}</span>
                                            <span
                                                class="fw-semibold font-number text-dark mb-1">{{ formatNumberTrimZeros($chain->exchange_withdrawal_fee, $currency->amount_precision) }}</span>

                                            <span class="badge bg-label-primary"
                                                style="font-size: 0.68rem;">{{ $chain->chain }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Network Fee --}}
                        <td class="text-center">
                            @if($currency->chains->count())
                                <div class="d-flex flex-column gap-1 align-items-center">
                                    @foreach($currency->chains as $chain)
                                        <div class="d-flex align-items-center gap-1 rounded px-2 py-1 bg-label-secondary"
                                            style="font-size: 0.78rem;">
                                            <span class="text-muted">{{ $currency->symbol }}</span>
                                            <span
                                                class="fw-semibold font-number text-dark mb-1">{{ formatNumberTrimZeros($chain->network_fee, $currency->amount_precision) }}</span>
                                            <span class="badge bg-label-info" style="font-size: 0.68rem;">{{ $chain->chain }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="text-center">
                            @if(!$currency->is_active)
                                <span class="badge bg-label-warning">کوین غیرفعال</span>
                            @elseif($currency->chains->count())
                                <div class="d-flex flex-column gap-2 align-items-center">
                                    @foreach($currency->chains as $chain)
                                        <div class="d-flex rounded border px-2 py-1" style="min-width: 130px; font-size: 0.75rem;">
                                            <div class="fw-semibold text-muted me-1 border-end pe-1" style="font-size: 0.7rem;">
                                                {{ $chain->chain }}
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="d-flex align-items-center gap-1">
                                                    <i
                                                        class="fa-solid fa-circle-arrow-down {{ $chain->deposit_enabled ? 'text-success' : 'text-danger' }}"></i>
                                                    <span
                                                        class="{{ $chain->deposit_enabled ? 'text-success' : 'text-danger' }}">واریز</span>
                                                </span>
                                                <span class="d-flex align-items-center gap-1">
                                                    <i
                                                        class="fa-solid fa-circle-arrow-up {{ $chain->withdraw_enabled ? 'text-success' : 'text-danger' }}"></i>
                                                    <span
                                                        class="{{ $chain->withdraw_enabled ? 'text-success' : 'text-danger' }}">برداشت</span>
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="badge bg-label-danger">بدون شبکه</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a class="btn btn-icon btn-sm btn-outline-primary"
                                    href="{{ route('admin.currency.edit', ['currency' => $currency->id]) }}" title="ویرایش">
                                    <i class="fa-light fa-pen-to-square"></i>
                                </a>
                                <button type="button" class="btn btn-icon btn-sm btn-outline-secondary btn-currency-preview"
                                    data-currency-id="{{ $currency->id }}"
                                    data-currency-url="{{ route('admin.currency.show', ['currency' => $currency->id]) }}"
                                    title="مشاهده">
                                    <i class="fa-light fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fa-light fa-inbox fa-3x mb-3 d-block"></i>
                                کوینی یافت نشد
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($currencies->hasPages())
        <div class="card-footer py-3">
            {{ $currencies->onEachSide(1)->links() }}
        </div>
    @endif
</div>
