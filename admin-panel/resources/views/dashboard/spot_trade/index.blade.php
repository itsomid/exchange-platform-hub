@extends('dashboard.layout.master')
@section('title', 'مدیریت معاملات Spot')
@section('content')
    {{--    TODO: Complete OTC ORder Card --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left"><span>تعداد معاملات اسپات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $spotTrades->total() }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-light fa-swap fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر پیشرفته معاملات اسپات</h5>
                <div class="card-title-elements ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="toggleAdvancedFilter">
                        <i class="fas fa-chevron-down me-1"></i>
                        نمایش فیلترهای پیشرفته
                    </button>
                </div>
            </div>
            <form action="{{ route('admin.spot_trades.index') }}" method="get" id="filterForm">
                <!-- Basic Filters Row -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="market">بازار:</label>
                        <select name="market" class="form-select" id="market">
                            <option value="">همه بازارها</option>
                            @foreach ($markets as $market)
                                <option value="{{ $market->id }}"
                                    {{ request()->input('market') == $market->id ? 'selected' : '' }}>
                                    {{ $market->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="user">کاربر:</label>
                        <x-user-selection-component input-name="user" multiple="0"
                            selected="{{ request()->filled('user') ? request()->input('user') : '' }}"
                            selected-label="{{ request()->filled('user')
                                ? '(#' .
                                        request()->input('user') .
                                        ') ' .
                                        \App\Models\User::find(request()->input('user'))?->fullname() .
                                        ' - ' .
                                        \App\Models\User::find(request()->input('user'))?->email ??
                                    'کاربر #' . request()->input('user')
                                : '' }}"></x-user-selection-component>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="date_from">از تاریخ:</label>
                        <input type="text" name="date_from" class="form-control" id="date_from" data-jdp
                            value="{{ request()->input('date_from') }}">
                    </div>
                    <div class="col-lg-2 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="date_to">تا تاریخ:</label>
                        <input type="text" name="date_to" class="form-control" id="date_to" data-jdp
                            value="{{ request()->input('date_to') }}">
                    </div>
                    <div class="col-lg-2 col-md-6 col-12 mb-2">
                        <label class="form-label d-none d-lg-block">&nbsp;</label>
                        <div class="d-flex flex-wrap gap-1 justify-content-start">
                            <button class="btn btn-success btn-sm flex-fill" type="submit" style="min-width: 70px;">
                                <i class="fas fa-search me-1"></i>جستجو
                            </button>
                            <button class="btn btn-outline-secondary btn-sm flex-fill" type="button" id="clearFilters"
                                style="min-width: 70px;">
                                <i class="fas fa-times me-1"></i>پاک کردن
                            </button>
                            <button class="btn btn-outline-info btn-sm flex-fill" type="button" id="exportFiltered"
                                style="min-width: 60px;">
                                <i class="fas fa-download me-1"></i>اکسل
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters Row (Initially Hidden) -->
                <div class="row mb-3" id="advancedFilters" style="display: none;">

                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="quantity_min">حداقل مقدار:</label>
                        <input type="number" name="quantity_min" class="form-control" id="quantity_min" placeholder="0.00"
                            step="0.00000001" value="{{ request()->input('quantity_min') }}">
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="quantity_max">حداکثر مقدار:</label>
                        <input type="number" name="quantity_max" class="form-control" id="quantity_max" placeholder="0.00"
                            step="0.00000001" value="{{ request()->input('quantity_max') }}">
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="trade_value_min">حداقل ارزش معامله:</label>
                        <div class="input-group">
                            <input type="number" name="trade_value_min" class="form-control" id="trade_value_min"
                                placeholder="0.00" step="0.01" value="{{ request()->input('trade_value_min') }}">
                            <span class="input-group-text">USDT</span>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="trade_value_max">حداکثر ارزش معامله:</label>
                        <div class="input-group">
                            <input type="number" name="trade_value_max" class="form-control" id="trade_value_max"
                                placeholder="0.00" step="0.01" value="{{ request()->input('trade_value_max') }}">
                            <span class="input-group-text">USDT</span>
                        </div>
                    </div>
                </div>

                <!-- Filter Summary (Show active filters) -->
                @if (request()->hasAny([
                        'market',
                        'user',
                        'date_from',
                        'date_to',
                        'price_min',
                        'price_max',
                        'quantity_min',
                        'quantity_max',
                        'trade_value_min',
                        'trade_value_max',
                    ]))
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i>
                                <span class="me-2">فیلترهای فعال:</span>
                                <div class="d-flex flex-wrap gap-1">
                                    @if (request()->filled('market'))
                                        @php $selectedMarket = $markets->find(request()->input('market')) @endphp
                                        <span class="badge bg-primary">بازار:
                                            {{ $selectedMarket?->name ?? request()->input('market') }}</span>
                                    @endif
                                    @if (request()->filled('user'))
                                        @php
                                            $selectedUser = \App\Models\User::find(request()->input('user'));
                                        @endphp
                                        @if ($selectedUser)
                                            <span class="badge bg-primary">کاربر: (#{{ $selectedUser->id }})
                                                {{ $selectedUser->fullname() }} - {{ $selectedUser->email }}</span>
                                        @else
                                            <span class="badge bg-primary">کاربر: #{{ request()->input('user') }}</span>
                                        @endif
                                    @endif
                                    @if (request()->filled('date_from'))
                                        <span class="badge bg-primary">از: {{ request()->input('date_from') }}</span>
                                    @endif
                                    @if (request()->filled('date_to'))
                                        <span class="badge bg-primary">تا: {{ request()->input('date_to') }}</span>
                                    @endif
                                    @if (request()->filled('price_min'))
                                        <span class="badge bg-success">قیمت ≥ {{ request()->input('price_min') }}</span>
                                    @endif
                                    @if (request()->filled('price_max'))
                                        <span class="badge bg-success">قیمت ≤ {{ request()->input('price_max') }}</span>
                                    @endif
                                    @if (request()->filled('quantity_min'))
                                        <span class="badge bg-warning">مقدار ≥
                                            {{ request()->input('quantity_min') }}</span>
                                    @endif
                                    @if (request()->filled('quantity_max'))
                                        <span class="badge bg-warning">مقدار ≤
                                            {{ request()->input('quantity_max') }}</span>
                                    @endif
                                    @if (request()->filled('trade_value_min'))
                                        <span class="badge bg-info">ارزش ≥
                                            {{ request()->input('trade_value_min') }}</span>
                                    @endif
                                    @if (request()->filled('trade_value_max'))
                                        <span class="badge bg-info">ارزش ≤
                                            {{ request()->input('trade_value_max') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست معاملات اسپات</h5>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="text-nowrap">
                            @php
                                $currentParams = request()->except([
                                    'sortById',
                                    'sortByQuantity',
                                    'sortByTradeValue',
                                    'sortByCreatedAt',
                                ]);

                                $currentSortDirection = request()->input('sortById', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_trades.index', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
                                class="text-black">
                                ID
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th class="text-nowrap">بازار</th>
                        <th class="text-nowrap">
                            @php
                                $currentParams = request()->except([
                                    'sortById',
                                    'sortByQuantity',
                                    'sortByTradeValue',
                                    'sortByCreatedAt',
                                ]);
                                $currentSortDirection = request()->input('sortByQuantity', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_trades.index', array_merge($currentParams, ['sortByQuantity' => $newSortDirection])) }}"
                                class="text-black">
                                مقدار
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th class="text-nowrap">
                            @php
                                $currentParams = request()->except([
                                    'sortById',
                                    'sortByQuantity',
                                    'sortByTradeValue',
                                    'sortByCreatedAt',
                                ]);
                                $currentSortDirection = request()->input('sortByTradeValue', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_trades.index', array_merge($currentParams, ['sortByTradeValue' => $newSortDirection])) }}"
                                class="text-black">
                                ارزش معامله (USDT)
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th class="text-nowrap">کاربر Maker</th>
                        <th class="text-nowrap">کاربر Taker</th>
                        <th class="text-nowrap">کارمزد کل
                            <i class="fa-regular fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-custom-class="tooltip-dark" title="مجموع ارزش کارمزد maker و taker"></i>
                        </th>
                        <th class="text-nowrap">
                            @php
                                $currentParams = request()->except([
                                    'sortById',
                                    'sortByQuantity',
                                    'sortByTradeValue',
                                    'sortByCreatedAt',
                                ]);
                                $currentSortDirection = request()->input('sortByCreatedAt', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_trades.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                                class="text-black">
                                تاریخ
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th class="text-nowrap">وضعیت سفارش
                            <i class="fa-regular fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-custom-class="tooltip-dark" title="بر اساس وضعیت سفارش maker"></i>
                        </th>
                        <th class="text-nowrap">جزییات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if ($spotTrades->isEmpty())
                        <tr>
                            <td colspan="14" class="text-center">تراکنشی یافت نشد.</td>
                        </tr>
                    @else
                        @foreach ($spotTrades as $spotTrade)
                            <tr class="table-striped">
                                <td>{{ $spotTrade->id }}</td>
                                <td class="text-heading fw-medium">
                                    <img src="{{ asset($spotTrade->market->baseCurrency->coinLogo()) }}"
                                        class="rounded-circle" width="32px">
                                    <small class="ms-1">{{ $spotTrade->market->name }}</small>
                                </td>
                                <td>
                                    <span class="ms-1">{{ formatNumberTrimZeros($spotTrade->quantity) }}</span>
                                    <small>{{ $spotTrade->market->base_currency }}</small>
                                </td>
                                <td dir="ltr">{{ formatNumberTrimZeros($spotTrade->price * $spotTrade->quantity) }}
                                    <small>USDT</small>
                                </td>
                                <td>{{ $spotTrade->makerOrder->user->email }}
                                    <div class="mt-1">
                                        سمت: <span
                                            class="badge bg-{{ $spotTrade->makerSide === 'BUY' ? 'success' : 'danger' }}">
                                            {{ $spotTrade->makerSide }} </span>
                                    </div>

                                </td>
                                <td>{{ $spotTrade->takerOrder->user->email }}
                                    <div class="mt-1">
                                        سمت: <span
                                            class="badge bg-{{ $spotTrade->takerSide === 'BUY' ? 'success' : 'danger' }}">
                                            {{ $spotTrade->takerSide }} </span>
                                    </div>
                                </td>
                                <td class="text-info" dir="ltr">
                                    {{ formatNumberTrimZeros($spotTrade->total_commission_value) }}
                                    <small>USDT</small>
                                </td>
                                <td dir="ltr">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($spotTrade->created_at, '%Y/%m/%d H:i:s') }}<br>
                                    <small
                                        class="badge bg-label-secondary">{{ $spotTrade->created_at->format('Y/m/d') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-label-{{ $spotTrade->makerOrder->status->color() }}">
                                        {{ $spotTrade->makerOrder->status->label() }}
                                    </span>
                                </td>
                                <td>
                                    <a href="" class="btn btn-sm btn-icon" data-bs-toggle="modal"
                                        data-bs-target="#trade-{{ $spotTrade->id }}">
                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>
                                    @if(auth()->user()->hasRole('tech_developers'))
                                    <button type="button" class="btn btn-sm btn-icon btn-text-warning"
                                        data-bs-toggle="modal" data-bs-target="#note-trade-{{ $spotTrade->id }}"
                                        title="ثبت نوت">
                                        <i class="{{ $spotTrade->notes ? 'fa-solid' : 'fa-regular' }} fa-note-sticky fa-lg {{ $spotTrade->notes ? 'text-warning' : '' }}"></i>
                                    </button>
                                    @endif
                                    <div class="modal fade " id="trade-{{ $spotTrade->id }}" tabindex="-1"
                                        aria-modal="true" role="dialog">
                                        <div class="modal-dialog modal-xl" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header justify-content-between">

                                                    <div>
                                                        <h5>جزيیات معامله</h5>
                                                        <h6 class="modal-title font-number mb-2">شماره معامله
                                                            #{{ $spotTrade->id }}</h6>
                                                        <div class="d-flex gap-2 mb-2 align-items-baseline">
                                                            <span>شماره سفارش Maker --></span>
                                                            <h6 class="modal-title font-number">
                                                                #{{ $spotTrade->maker_order_id }}</h6>
                                                        </div>
                                                        <div class="d-flex gap-2 mb-2 align-items-baseline">
                                                            <span>شماره سفارش Taker --></span>
                                                            <h6 class="modal-title font-number">
                                                                #{{ $spotTrade->taker_order_id }}</h6>
                                                        </div>

                                                    </div>

                                                    <div class="d-flex flex-column ">


                                                        <div dir="ltr" class="text-end">
                                                            <span class="fw-bold">Maker: </span>
                                                            <span>{{ $spotTrade->makerOrder->user->email }}
                                                                ({{ $spotTrade->makerOrder->user->username }})
                                                            </span>

                                                            <span
                                                                class="badge bg-{{ $spotTrade->makerSide === 'BUY' ? 'success' : 'danger' }}">
                                                                {{ $spotTrade->makerSide }} </span>
                                                        </div>


                                                        <div dir="ltr" class="mt-2 text-end">
                                                            <span class="fw-bold">Taker: </span>
                                                            <span>{{ $spotTrade->takerOrder->user->email }}
                                                                ({{ $spotTrade->takerOrder->user->username }})
                                                            </span>

                                                            <span
                                                                class="badge bg-{{ $spotTrade->takerSide === 'BUY' ? 'success' : 'danger' }}">
                                                                {{ $spotTrade->takerSide }} </span>
                                                        </div>


                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <div class="d-flex flex-wrap gap-2 font-number">

                                                            <span
                                                                class="text-success">{{ formatNumber(($spotTrade->quantity / $spotTrade->makerOrder->quantity) * 100) }}
                                                                درصد از کل سفارش</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <h5 class="fw-bold text-black m-0">
                                                                {{ $spotTrade->market->name }}
                                                                <span
                                                                    class="text-primary">({{ $spotTrade->takerOrder->type }})</span>
                                                            </h5>

                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد Maker</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">

                                                            <span
                                                                class="text-black">{{ formatNumberTrimZeros($spotTrade->commission->maker_commission_amount) }}
                                                                {{ $spotTrade->commission->maker_commission_currency }}</span>

                                                            <small
                                                                class="me-2">({{ formatNumberTrimZeros($spotTrade->maker_commission_value) }}
                                                                USDT)</small>
                                                            <small
                                                                class="me-2">({{ formatNumberTrimZeros($spotTrade->commission->maker_commission_percentage) }}%)</small>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد Taker</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">

                                                            <span
                                                                class="text-black">{{ formatNumberTrimZeros($spotTrade->commission->taker_commission_amount) }}
                                                                {{ $spotTrade->commission->taker_commission_currency }}</span>

                                                            <small
                                                                class="me-2">({{ formatNumberTrimZeros($spotTrade->taker_commission_value) }}
                                                                USDT)</small>
                                                            <small
                                                                class="me-2">({{ formatNumberTrimZeros($spotTrade->commission->taker_commission_percentage) }}%)</small>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">مجموع کارمزد (USDT)</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            <span
                                                                class="text-info">{{ formatNumberTrimZeros($spotTrade->total_commission_value) }}
                                                                USDT</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary"
                                                        data-bs-dismiss="modal">بستن</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if(auth()->user()->hasRole('tech_developers'))
                                    {{-- Notes Modal --}}
                                    <div class="modal fade" id="note-trade-{{ $spotTrade->id }}" tabindex="-1" aria-modal="true" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">نوت معامله #{{ $spotTrade->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <textarea class="form-control spot-trade-notes-input" rows="5"
                                                        placeholder="نوت خود را اینجا بنویسید..."
                                                        data-id="{{ $spotTrade->id }}"
                                                        data-url="{{ route('admin.spot_trades.notes.update', $spotTrade->id) }}">{{ $spotTrade->notes }}</textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                                                    <button type="button" class="btn btn-primary save-spot-trade-note"
                                                        data-id="{{ $spotTrade->id }}">ذخیره</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                {{ $spotTrades->appends(request()->all())->links() }}
            </div>
        </div>
    </div>

@endsection

@section('vendor-script')
    @vite(['resources/assets/js/jalalidatepicker.js', 'resources/assets/js/forms-extras.js'])
    <script>
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Advanced Filter Toggle
            $('#toggleAdvancedFilter').click(function() {
                const advancedFilters = $('#advancedFilters, #tradeValueFilters');
                const button = $(this);
                const icon = button.find('i');

                if (advancedFilters.is(':visible')) {
                    advancedFilters.slideUp();
                    icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    button.find('span').text('نمایش فیلترهای پیشرفته');
                } else {
                    advancedFilters.slideDown();
                    icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    button.find('span').text('مخفی کردن فیلترهای پیشرفته');
                }
            });

            // Clear Filters
            $('#clearFilters').click(function() {
                // Clear all form inputs
                $('#filterForm')[0].reset();

                // Clear select2 if used
                $('#filterForm select').val('').trigger('change');

                // Redirect to clean URL
                window.location.href = '{{ route('admin.spot_trades.index') }}';
            });

            // Export Filtered Data
            $('#exportFiltered').click(function() {
                // Create a temporary form for POST request
                const form = $('<form>', {
                    'method': 'POST',
                    'action': '{{ route('admin.spot_trade.excel-export') }}',
                    'target': '_blank'
                });

                // Add CSRF token
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': '{{ csrf_token() }}'
                }));

                // Add all current filter values
                $('#filterForm').find('input, select').each(function() {
                    const input = $(this);
                    if (input.val() && input.attr('name')) {
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': input.attr('name'),
                            'value': input.val()
                        }));
                    }
                });

                // Submit the form
                form.appendTo('body').submit().remove();
            });

            // Auto-show advanced filters if any advanced filter is active
            @if (request()->hasAny(['price_min', 'price_max', 'quantity_min', 'quantity_max', 'trade_value_min', 'trade_value_max']))
                $('#toggleAdvancedFilter').click();
            @endif

            // Save spot trade note
            $(document).on('click', '.save-spot-trade-note', function () {
                const id = $(this).data('id');
                const textarea = $('.spot-trade-notes-input[data-id="' + id + '"]');
                const url = textarea.data('url');
                const notes = textarea.val();
                const btn = $(this);

                btn.prop('disabled', true);
                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { notes: notes },
                    success: function (res) {
                        Toastify({
                            text: res.message,
                            duration: 3000,
                            gravity: 'top', position: 'right',
                            style: { background: '#28C76F' }
                        }).showToast();
                        $('#note-trade-' + id).modal('hide');
                        // Update icon color
                        const noteBtn = $('[data-bs-target="#note-trade-' + id + '"] i');
                        if (notes.trim()) {
                            noteBtn.addClass('text-warning fa-solid').removeClass('fa-regular');
                        } else {
                            noteBtn.removeClass('text-warning fa-solid').addClass('fa-regular');
                        }
                    },
                    error: function () {
                        Toastify({
                            text: 'خطا در ذخیره نوت',
                            duration: 5000,
                            gravity: 'top', position: 'right',
                            style: { background: '#EA5455' }
                        }).showToast();
                    },
                    complete: function () { btn.prop('disabled', false); }
                });
            });
        });
    </script>

    <style>
        /* Custom responsive improvements */
        @media (max-width: 768px) {
            .btn-sm {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }

            .table-responsive {
                font-size: 0.85rem;
            }

            .form-label {
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
            }

            .card-header h5 {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .btn-sm {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
            }

            .table-responsive {
                font-size: 0.8rem;
            }

            .form-control,
            .form-select {
                font-size: 0.875rem;
            }
        }
    </style>
@endsection
