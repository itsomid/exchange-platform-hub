@extends('dashboard.layout.master')
@section('title', 'مدیریت تراکنش ها')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
    <style>
        .table-responsive {
            overflow-x: auto;
            position: relative;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .sticky-column {
            position: sticky;
            left: 0;
            background-color: #fff !important;
            z-index: 1;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1) !important;

        }
        
        .table thead .sticky-column {
            background-color: #fff !important;
            z-index: 2;
        }

        /* When a Bootstrap modal is open, ensure sticky cells don't participate in stacking */
        body.modal-open .sticky-column {
            z-index: auto !important;
            box-shadow: none !important;
        }
        
        .table tbody tr:hover .sticky-column {
            background-color: #f8f9fa !important;
        }
       
    </style>
@endsection

@section('content')

    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد تراکنش ها</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $transactions->total() }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="fa-light fa-money-bill-wave fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد کارمزدهای برداشت </span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $withdrawalFeeTransactionsCount }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-regular fa-hand-holding-dollar fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد کارمزدهای معاملات OTC</span>
                            <div class="d-flex align-items-center my-1">

                                <h4 class="mb-0 me-2">{{ $OTCFeeTransactionsCount }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-regular fa-chart-bar fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر پیشرفته تراکنش ها</h5>
                <div class="card-title-elements ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="toggleAdvancedFilter">
                        <i class="fas fa-chevron-down me-1"></i> نمایش فیلترهای پیشرفته
                    </button>
                </div>
            </div>
            <form action="{{ route('admin.transaction.index') }}" method="get" id="filterForm">
                <!-- Basic Filters Row -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="type">نوع تراکنش:</label>
                        <select name="type" class="form-select" id="type">
                            <option value="">همه انواع</option>
                            @foreach (\App\Enums\TransactionTypeEnum::cases() as $case)
                                <option value="{{ $case->value }}"
                                    {{ request()->input('type') == $case->value ? 'selected' : '' }}>
                                    {{ $case->label() }} ({{ $case->value }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="subtype">نوع زیر تراکنش:</label>
                        <select name="subtype" class="form-select" id="subtype">
                            <option value="">همه انواع</option>
                            @foreach (\App\Enums\TransactionSubTypeEnum::cases() as $case)
                                <option value="{{ $case->value }}"
                                    {{ request()->input('subtype') == $case->value ? 'selected' : '' }}>
                                    {{ $case->label() }} ({{ $case->value }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="currency">رمز ارز:</label>
                        <select name="currency" class="form-select" id="currency">
                            <option value="">همه ارزها</option>
                            @foreach (\App\Models\Currency::all() as $currency)
                                <option value="{{ $currency->symbol }}"
                                    {{ request()->input('currency') == $currency->symbol ? 'selected' : '' }}>
                                    {{ $currency->name }} ({{ $currency->symbol }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <!-- User and Buttons Row -->
                <div class="row mb-3">
                    <div class="col-lg-6 col-md-8 col-sm-12 mb-2">
                        <label class="form-label" for="user">کاربر:</label>
                        <x-user-selection-component input-name="user" multiple="0"
                            selected="{{ request()->filled('user') && $transactions->isNotEmpty() && $transactions[0]->user ? $transactions[0]->user->id : '' }}"
                            selected-label="{{ request()->filled('user') && $transactions->isNotEmpty() && $transactions[0]->user
                                ? '(' .
                                    $transactions[0]->user->id .
                                    '#) ' .
                                    $transactions[0]->user->fullname() .
                                    ' | ' .
                                    $transactions[0]->user->email
                                : '' }}"></x-user-selection-component>
                    </div>
                    <div class="col-lg-6 col-md-4 col-sm-12 mb-2 d-flex align-items-end">
                        <div class="d-flex flex-wrap gap-1">
                            <button class="btn btn-success btn-sm" type="submit">
                                <i class="fas fa-search me-1"></i> اعمال فیلتر
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters">
                                <i class="fas fa-times me-1"></i> حذف فیلترها
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" id="exportFiltered">
                                <i class="fas fa-file-excel me-1"></i> خروجی اکسل
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Advanced Filters (hidden by default) -->
                <div class="row mb-3" id="advancedFilters" style="display: none;">
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="transaction_value_min">حداقل ارزش تراکنش (دلار):</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="transaction_value_min" class="form-control"
                                id="transaction_value_min" placeholder="200" step="0.01" min="0"
                                value="{{ request()->input('transaction_value_min') }}">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="transaction_value_max">حداکثر ارزش تراکنش (دلار):</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="transaction_value_max" class="form-control"
                                id="transaction_value_max" placeholder="500" step="0.01" min="0"
                                value="{{ request()->input('transaction_value_max') }}">
                        </div>
                    </div>
                </div>
                <!-- Active filter summary -->
                @if (request()->hasAny(['type', 'subtype', 'currency', 'user', 'transaction_value_min', 'transaction_value_max']))
                    <div class="alert alert-info d-flex align-items-center flex-wrap">
                        <i class="fas fa-info-circle me-2"></i>
                        <span class="me-2">فیلترهای فعال:</span>
                        <div class="d-flex flex-wrap gap-1">
                            @if (request()->filled('type'))
                                <span class="badge bg-primary">نوع: {{ request()->input('type') }}</span>
                            @endif
                            @if (request()->filled('subtype'))
                                <span class="badge bg-primary">زیر نوع: {{ request()->input('subtype') }}</span>
                            @endif
                            @if (request()->filled('currency'))
                                <span class="badge bg-primary">ارز: {{ request()->input('currency') }}</span>
                            @endif
                            @if (request()->filled('user'))
                                <span class="badge bg-primary">کاربر: {{ request()->input('user') }}</span>
                            @endif
                            @if (request()->filled('transaction_value_min'))
                                <span class="badge bg-primary">حداقل ارزش: ${{ request()->input('transaction_value_min') }}</span>
                            @endif
                            @if (request()->filled('transaction_value_max'))
                                <span class="badge bg-primary">حداکثر ارزش: ${{ request()->input('transaction_value_max') }}</span>
                            @endif
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>



    <div class="card">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست تراکنش ها</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{ route('admin.wallet.increase-credit') }}" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i> افزایش اعتبار
                    </a>
                </div>

            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            @php
                                $currentParams = request()->except('sortById');
                                $currentSortDirection = request()->input('sortById', 'asc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.transaction.index', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
                                class="text-black">
                                ID
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>نوع تراکنش</th>
                        <th>کاربر</th>
                        <th>رمز ارز</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByAmount');
                                $currentSortDirection = request()->input('sortByAmount', 'asc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.transaction.index', array_merge($currentParams, ['sortByAmount' => $newSortDirection])) }}"
                                class="text-black">
                                مقدار
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>
                            مقدار موجودی
                            <br>
                            <small>(قبل از تراکنش)</small>
                        </th>
                        <th class="text-wrap w-25">توضیحات</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByCreatedAt');
                                $currentSortDirection = request()->input('sortByCreatedAt', 'asc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.transaction.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                                class="text-black">
                                زمان
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>

                        <th>وضعیت</th>
                        <th class="sticky-column">عملیات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if ($transactions->isEmpty())
                        <tr>
                            <td colspan="9" class="text-center">تراکنشی یافت نشد.</td>
                        </tr>
                    @else
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->id }}</td>
                                <td class="text-heading fw-medium">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="trans-avatar-group d-flex align-items-center assigned-avatar">
                                            <div class="avatar avatar-md ">
                                                <img src="{{ asset($transaction->wallet->currency->coinLogo()) }}"
                                                    class="rounded-circle">
                                            </div>
                                            <div class="avatar avatar-md">
                                                <span
                                                    class="avatar-initial rounded-circle bg-label-{{ $transaction->type->color() }}">
                                                    <i class="fa-regular fa-{{ $transaction->type->icon() }} mx-3"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column align-items-start">
                                            <span class="badge bg-label-{{ $transaction->type->color() }} ms-2">
                                                {{ $transaction->type->label() }}
                                            </span>
                                            
                                                <span class="badge bg-label-secondary ms-2 mt-2">
                                                    {{ $transaction->subtype->label() }}
                                                </span>
                                          
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <a class="text-heading text-truncate" target="_blank"
                                           href="{{ route('admin.inquiry.user-details', [$transaction->user]) }}">
                                            <span class="fw-medium">{{ $transaction->user->email }}</span>
                                        </a>
                                        <small>{{ $transaction->user->username }}</small>
                                    </div>
                                </td>

                                <td>{{ $transaction->wallet->currency_symbol }}</td>
                                <td class="font-number" dir="ltr">
                                    <h6 class="mb-0 {{ $transaction->amount > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ formatNumberTrimZeros($transaction->amount) }}</h6>
                                </td>
                                <td class="font-number">
                                    <h6 class="mb-0">{{ formatNumberTrimZeros($transaction->balance) }}</h6>
                                </td>

                                <td class="font-number text-wrap">

                                    <span>{{ $transaction->description }}</span>

                                </td>
                                <td class="font-number">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($transaction->created_at, 'H:i:s %Y/%m/%d') }}<br>
                                    <small class="text-muted ">{{ $transaction->created_at->format('Y/m/d') }}</small>
                                </td>

                                <td>
                                    <span class="badge bg-label-{{ $transaction->status->color() }}">
                                        {{ $transaction->status->label() }}
                                    </span>
                                </td>
                                <td class="sticky-column">
                                    <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                        data-bs-target="#transaction-{{ $transaction->id }}">
                                        <i class="fa-regular fa-eye fa-xl"></i>
                                    </a>
                                    @if(auth()->user()->hasRole('tech_developers'))
                                    <button type="button" class="btn btn-icon btn-text-warning"
                                        data-bs-toggle="modal" data-bs-target="#note-transaction-{{ $transaction->id }}"
                                        title="ثبت نوت">
                                        <i class="{{ $transaction->notes ? 'fa-solid' : 'fa-regular' }} fa-note-sticky fa-xl {{ $transaction->notes ? 'text-warning' : '' }}"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                         
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        @foreach ($transactions as $transaction)
             <x-transaction-details-modal :transaction="$transaction" />
        @endforeach
        @if(auth()->user()->hasRole('tech_developers'))
        @foreach ($transactions as $transaction)
        <div class="modal fade" id="note-transaction-{{ $transaction->id }}" tabindex="-1" aria-modal="true" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">نوت تراکنش #{{ $transaction->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <textarea class="form-control transaction-notes-input" rows="5"
                            placeholder="نوت خود را اینجا بنویسید..."
                            data-id="{{ $transaction->id }}"
                            data-url="{{ route('admin.transaction.notes.update', $transaction->id) }}">{{ $transaction->notes }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="button" class="btn btn-primary save-transaction-note"
                            data-id="{{ $transaction->id }}">ذخیره</button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        @endif
        <div class="row mt-4">
            <div class="col-md-12">
                {{ $transactions->appends(request()->all())->links() }}
            </div>
        </div>
    </div>

@endsection

@section('vendor-script')
    <script>
        $(document).ready(function () {
            // Toggle advanced filters
            $('#toggleAdvancedFilter').on('click', function () {
                const $section = $('#advancedFilters');
                const $icon = $(this).find('i');
                if ($section.is(':visible')) {
                    $section.slideUp();
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    $(this).html('<i class="fas fa-chevron-down me-1"></i> نمایش فیلترهای پیشرفته');
                } else {
                    $section.slideDown();
                    $(this).html('<i class="fas fa-chevron-up me-1"></i> پنهان کردن فیلترهای پیشرفته');
                }
            });

            // Clear filters
            $('#clearFilters').on('click', function () {
                window.location.href = '{{ route('admin.transaction.index') }}';
            });

            // Export with current filters
            $('#exportFiltered').on('click', function () {
                const params = new URLSearchParams(window.location.search);
                window.location.href = '{{ route('admin.transaction.excel-export') }}?' + params.toString();
            });

            // Auto-show advanced section if any advanced input has a value
            const advancedInputs = ['transaction_value_min', 'transaction_value_max'];
            const hasAdvancedFilter = advancedInputs.some(name => {
                const val = $('[name="' + name + '"]').val();
                return val && val.trim() !== '';
            });
            if (hasAdvancedFilter) {
                $('#advancedFilters').show();
                $('#toggleAdvancedFilter').html('<i class="fas fa-chevron-up me-1"></i> پنهان کردن فیلترهای پیشرفته');
            }

            // Save transaction note
            $(document).on('click', '.save-transaction-note', function () {
                const id = $(this).data('id');
                const textarea = $('.transaction-notes-input[data-id="' + id + '"]');
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
                        $('#note-transaction-' + id).modal('hide');
                        const noteBtn = $('[data-bs-target="#note-transaction-' + id + '"] i');
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
@endsection