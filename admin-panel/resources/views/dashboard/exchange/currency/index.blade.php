@extends('dashboard.layout.master')
@section('title', 'مدیریت کوین ها')
@section('content')

    {{-- Statistics Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card border-start border-primary border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">مجموع کوین‌ها</div>
                            <h3 class="mb-0 fw-bold">{{ $currencies->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="fa-solid fa-coins fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card border-start border-success border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">کوین‌های فعال</div>
                            <h3 class="mb-0 fw-bold text-success">{{ $currenciesWithChainsCount }}</h3>
                        </div>
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="fa-solid fa-circle-check fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card border-start border-warning border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">کوین‌های غیرفعال</div>
                            <h3 class="mb-0 fw-bold text-warning">{{ $currenciesWithoutChainsCount }}</h3>
                        </div>
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="fa-solid fa-circle-xmark fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3 align-items-end"
                  action="{{ route('admin.currency.index') }}" method="get">

                <div class="col-md-4">
                    <label class="form-label" for="search">
                        <i class="fa-light fa-magnifying-glass me-1"></i>
                        جست‌وجو (نام / سیمبول)
                    </label>
                    <input type="text" id="search" name="search" class="form-control"
                           placeholder="مثال: Bitcoin یا BTC ..."
                           value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="chain">
                        <i class="fa-light fa-link me-1"></i>
                        شبکه
                    </label>
                    <select id="chain" name="chain" class="form-select">
                        <option value="">همه شبکه‌ها</option>
                        @foreach($availableChains as $chainValue)
                            <option value="{{ $chainValue }}" {{ request('chain') == $chainValue ? 'selected' : '' }}>
                                {{ $chainValue }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="status">
                        <i class="fa-light fa-toggle-on me-1"></i>
                        وضعیت
                    </label>
                    <select id="status" name="status" class="form-select">
                        <option value="">همه</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال (دارای شبکه)</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غیرفعال (بدون شبکه)</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fa-light fa-filter me-1"></i>
                        فیلتر
                    </button>
                    <a href="{{ route('admin.currency.index') }}" class="btn btn-outline-secondary" title="پاک کردن فیلتر">
                        <i class="fa-light fa-xmark"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Currency Table --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h5 class="mb-0">
                <i class="fa-light fa-list me-2"></i>
                لیست کوین‌ها
                <span class="badge bg-label-secondary ms-2">{{ $currencies->count() }}</span>
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
                                <img src="{{ asset($currency->coinLogo()) }}" class="rounded-circle" width="40" height="40" alt="{{ $currency->symbol }}">
                                <div>
                                    <div class="fw-semibold">{{ $currency->name }}</div>
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
                                        <div class="d-flex align-items-center gap-1 rounded px-2 py-1 bg-label-secondary" style="font-size: 0.78rem;">
                                              <span class="text-muted">{{ $currency->symbol }}</span>
                                            <span class="fw-semibold font-number text-dark mb-1">{{ formatNumberTrimZeros($chain->exchange_withdrawal_fee, $currency->amount_precision) }}</span>
                                        
                                              <span class="badge bg-label-primary" style="font-size: 0.68rem;">{{ $chain->chain }}</span>
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
                                        <div class="d-flex align-items-center gap-1 rounded px-2 py-1 bg-label-secondary" style="font-size: 0.78rem;">
                                            <span class="text-muted">{{ $currency->symbol }}</span>
                                            <span class="fw-semibold font-number text-dark mb-1">{{ formatNumberTrimZeros($chain->network_fee, $currency->amount_precision) }}</span>
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
                            @if($currency->chains->count())
                                <div class="d-flex flex-column gap-2 align-items-center">
                                    @foreach($currency->chains as $chain)
                                        <div class="rounded border px-2 py-1" style="min-width: 130px; font-size: 0.75rem;">
                                            <div class="fw-semibold text-muted mb-1 border-bottom pb-1" style="font-size: 0.7rem;">{{ $chain->chain }}</div>
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="d-flex align-items-center gap-1">
                                                    <i class="fa-solid fa-circle-arrow-down {{ $chain->deposit_enabled ? 'text-success' : 'text-danger' }}"></i>
                                                    <span class="{{ $chain->deposit_enabled ? 'text-success' : 'text-danger' }}">واریز</span>
                                                </span>
                                                <span class="d-flex align-items-center gap-1">
                                                    <i class="fa-solid fa-circle-arrow-up {{ $chain->withdraw_enabled ? 'text-success' : 'text-danger' }}"></i>
                                                    <span class="{{ $chain->withdraw_enabled ? 'text-success' : 'text-danger' }}">برداشت</span>
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
                                   href="{{ route('admin.currency.edit', ['currency' => $currency->id]) }}"
                                   title="ویرایش">
                                    <i class="fa-light fa-pen-to-square"></i>
                                </a>
                                <a class="btn btn-icon btn-sm btn-outline-secondary"
                                   href="{{ route('admin.currency.show', ['currency' => $currency->id]) }}"
                                   title="مشاهده">
                                    <i class="fa-light fa-eye"></i>
                                </a>
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
    </div>

@endsection
