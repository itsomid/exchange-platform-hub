@extends('dashboard.layout.master')
@section('title', 'مدیریت تراکنش ها')

@section('vendor-style')
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
        <div class="col-sm-12 col-xl-3">
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

        <div class="col-sm-12 col-xl-3">
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
        <div class="col-sm-12 col-xl-3">
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

        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد تراکنش های Referral</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $referralTransactionsCount }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-regular fa-user-tag"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fas fa-file-excel me-2 text-success"></i>
                خروجی اکسل
            </h5>
            <form id="excelExportForm" class="row mt-3 d-flex align-items-end">
                @csrf
                <div class="col-md-2 user_role">
                    <label class="form-label" for="from_id">
                        <i class="fas fa-arrow-up me-1 text-primary"></i>
                        از آیدی تراکنش: (اختیاری)
                    </label>
                    <input type="number" name="from_id" id="from_id" class="form-control" placeholder="مثلاً 1000">

                </div>
                <div class="col-md-2 user_role">
                    <label class="form-label" for="to_id">
                        <i class="fas fa-arrow-down me-1 text-danger"></i>
                        تا آیدی تراکنش: (اختیاری)
                    </label>
                    <input type="number" name="to_id" id="to_id" class="form-control" placeholder="مثلاً 1200">
                </div>
                <div class="col-md-5 mt-2">
                    <button type="submit" class="btn btn-success me-2" id="exportExcelBtn">
                        <i class="fas fa-download me-2"></i>
                        دانلود خروجی اکسل
                    </button>
                    <button type="button" class="btn btn-info" id="exportFilteredExcelBtn">
                        <i class="fas fa-filter me-2"></i>
                        خروجی با فیلترها
                    </button>
                </div>
                <div class="col-md-12 mt-3">
                    <div id="exportProgress" class="progress" style="display: none; height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                             role="progressbar"
                             style="width: 0%">
                            <span class="progress-text">در حال آماده سازی...</span>
                        </div>
                    </div>
                    <div id="exportMessage" class="alert mt-2" style="display: none;"></div>
                </div>
            </form>
        </div>
    </div>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="card-title header-elements mb-4">
                <h5 class="m-0 me-2 d-flex align-items-center">
                    <i class="fas fa-filter me-2 text-primary"></i>
                    فیلترهای پیشرفته
                </h5>
            </div>
            <form action="{{ route('admin.transaction.index') }}" method="get">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="type">
                                <i class="fas fa-exchange-alt me-1 text-info"></i>
                                نوع تراکنش:
                            </label>
                            <select name="type" class="form-select" id="type">
                                <option value="">همه انواع</option>
                                @foreach (\App\Enums\TransactionTypeEnum::cases() as $case)
                                    <option value="{{ $case->value }}"
                                        {{ request()->has('type') && request()->input('type') == $case->value ? 'selected' : '' }}>
                                        {{ $case->label() }} ({{ $case->value }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="subtype">
                                <i class="fas fa-tags me-1 text-warning"></i>
                                نوع زیر تراکنش:
                            </label>
                            <select name="subtype" class="form-select" id="subtype">
                                <option value="">همه انواع</option>
                                @foreach (\App\Enums\TransactionSubTypeEnum::cases() as $case)
                                    <option value="{{ $case->value }}"
                                        {{ request()->has('subtype') && request()->input('subtype') == $case->value ? 'selected' : '' }}>
                                        {{ $case->label() }} ({{ $case->value }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="currency">
                                <i class="fas fa-coins me-1 text-success"></i>
                                رمز ارز:
                            </label>
                            <select name="currency" class="form-select" id="currency">
                                <option value="">همه ارزها</option>
                                @foreach (\App\Models\Currency::all() as $currency)
                                    <option value="{{ $currency->symbol }}"
                                        {{ request()->has('currency') && request()->input('currency') == $currency->symbol ? 'selected' : '' }}>
                                        {{ $currency->name }} ({{ $currency->symbol }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="user">
                            <i class="fas fa-user me-1 text-primary"></i>
                            کاربر:
                        </label>
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
                </div>

                <!-- Transaction Value Range Filter -->
                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <div class="card border-0">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-3 d-flex align-items-center">
                                    <i class="fas fa-dollar-sign me-2 text-success"></i>
                                    فیلتر بازه ارزش تراکنش
                                    <small class="text-muted ms-2">(ارزش = مقدار × قیمت کوین)</small>
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold" for="transaction_value_min">
                                                <i class="fas fa-arrow-up me-1 text-success"></i>
                                                حداقل ارزش (دلار):
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" name="transaction_value_min" class="form-control"
                                                    id="transaction_value_min" placeholder="200" step="0.01"
                                                    min="0"
                                                    value="{{ request()->input('transaction_value_min') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold" for="transaction_value_max">
                                                <i class="fas fa-arrow-down me-1 text-danger"></i>
                                                حداکثر ارزش (دلار):
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" name="transaction_value_max" class="form-control"
                                                    id="transaction_value_max" placeholder="500" step="0.01"
                                                    min="0"
                                                    value="{{ request()->input('transaction_value_max') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-group w-100">
                                            <div class="d-grid gap-2 d-md-flex">
                                                <button class="btn btn-primary flex-fill" type="submit">
                                                    <i class="fas fa-search me-2"></i>
                                                    اعمال فیلتر
                                                </button>
                                                <a href="{{ route('admin.transaction.index') }}"
                                                    class="btn btn-outline-secondary flex-fill">
                                                    <i class="fas fa-times me-2"></i>
                                                    پاک کردن
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                                            @if ($transaction->subtype->value != 'user_initiated')
                                                <span class="badge bg-label-secondary ms-2 mt-2">
                                                    {{ $transaction->subtype->label() }}
                                                </span>
                                            @endif
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
        <div class="row mt-4">
            <div class="col-md-12">
                {{ $transactions->appends(request()->all())->links() }}
            </div>
        </div>
    </div>

@endsection

@section('vendor-script')
    <script>
        $(document).ready(function() {
            // Handle excel export with ID range
            $('#excelExportForm').on('submit', function(e) {
                e.preventDefault();
                exportExcel(false);
            });

            // Handle excel export with filters
            $('#exportFilteredExcelBtn').on('click', function(e) {
                e.preventDefault();
                exportExcel(true);
            });

            function exportExcel(useFilters) {
                const $form = $('#excelExportForm');
                const $progressBar = $('#exportProgress');
                const $progressBarInner = $progressBar.find('.progress-bar');
                const $progressText = $progressBar.find('.progress-text');
                const $message = $('#exportMessage');
                const $submitBtn = $('#exportExcelBtn');
                const $filterBtn = $('#exportFilteredExcelBtn');

                // Gather form data
                let formData = {
                    _token: $form.find('[name="_token"]').val()
                };

                // Add ID range if provided
                const fromId = $('#from_id').val();
                const toId = $('#to_id').val();

                if (fromId) formData.from_id = fromId;
                if (toId) formData.to_id = toId;

                // Add filters if requested
                if (useFilters) {
                    const filterForm = $('form[action="{{ route('admin.transaction.index') }}"]');

                    // Get all filter values
                    const type = filterForm.find('[name="type"]').val();
                    const subtype = filterForm.find('[name="subtype"]').val();
                    const currency = filterForm.find('[name="currency"]').val();
                    const user = filterForm.find('[name="user"]').val();
                    const transactionValueMin = filterForm.find('[name="transaction_value_min"]').val();
                    const transactionValueMax = filterForm.find('[name="transaction_value_max"]').val();
                    const sortById = '{{ request()->input("sortById") }}';
                    const sortByAmount = '{{ request()->input("sortByAmount") }}';
                    const sortByCreatedAt = '{{ request()->input("sortByCreatedAt") }}';

                    if (type) formData.type = type;
                    if (subtype) formData.subtype = subtype;
                    if (currency) formData.currency = currency;
                    if (user) formData.user = user;
                    if (transactionValueMin) formData.transaction_value_min = transactionValueMin;
                    if (transactionValueMax) formData.transaction_value_max = transactionValueMax;
                    if (sortById) formData.sortById = sortById;
                    if (sortByAmount) formData.sortByAmount = sortByAmount;
                    if (sortByCreatedAt) formData.sortByCreatedAt = sortByCreatedAt;
                }

                // Reset UI
                $message.hide();
                $progressBar.show();
                $progressBarInner.css('width', '0%').removeClass('bg-success bg-danger').addClass('bg-info');
                $progressText.text('در حال آماده سازی...');
                $submitBtn.prop('disabled', true);
                $filterBtn.prop('disabled', true);

                // Simulate progress
                let progress = 0;
                const progressInterval = setInterval(function() {
                    progress += 5;
                    if (progress <= 90) {
                        $progressBarInner.css('width', progress + '%');
                        $progressText.text('در حال پردازش... ' + progress + '%');
                    }
                }, 200);

                // Make AJAX request
                $.ajax({
                    url: '{{ route('admin.transaction.excel-export') }}',
                    type: 'POST',
                    data: formData,
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(blob, status, xhr) {
                        clearInterval(progressInterval);

                        // Complete progress
                        $progressBarInner.css('width', '100%').removeClass('bg-info').addClass('bg-success');
                        $progressText.text('دانلود موفق! ');

                        // Get filename from header or create default
                        let filename = 'transactions_' + new Date().getTime() + '.xlsx';
                        const disposition = xhr.getResponseHeader('Content-Disposition');
                        if (disposition && disposition.indexOf('filename=') !== -1) {
                            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                            if (matches != null && matches[1]) {
                                filename = matches[1].replace(/['"]/g, '');
                            }
                        }

                        // Create download link
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        // Show success message
                        $message.removeClass('alert-danger').addClass('alert-success')
                            .html('<i class="fas fa-check-circle me-2"></i>فایل اکسل با موفقیت دانلود شد!')
                            .show();

                        // Reset after 3 seconds
                        setTimeout(function() {
                            $progressBar.fadeOut();
                            $message.fadeOut();
                            $submitBtn.prop('disabled', false);
                            $filterBtn.prop('disabled', false);
                        }, 3000);
                    },
                    error: function(xhr) {
                        clearInterval(progressInterval);

                        let errorMsg = 'خطا در دانلود فایل!';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.status === 500) {
                            errorMsg = 'خطای سرور! لطفاً بازه کوچکتری انتخاب کنید.';
                        } else if (xhr.status === 422) {
                            errorMsg = 'داده‌های ورودی نامعتبر است!';
                        }

                        $progressBarInner.css('width', '100%').removeClass('bg-info').addClass('bg-danger');
                        $progressText.text('خطا!');

                        $message.removeClass('alert-success').addClass('alert-danger')
                            .html('<i class="fas fa-exclamation-circle me-2"></i>' + errorMsg)
                            .show();

                        setTimeout(function() {
                            $progressBar.fadeOut();
                            $submitBtn.prop('disabled', false);
                            $filterBtn.prop('disabled', false);
                        }, 3000);
                    }
                });
            }
        });
    </script>
@endsection