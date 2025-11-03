@extends('dashboard.layout.master')
@section('title', 'مدیریت دارایی‌های مسدود شده')
@section('content')


    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">فیلترها</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.locked-balance.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">کاربر</label>
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
                    <div class="col-md-3">
                        <label class="form-label">نوع</label>
                        <select class="form-select" name="type">
                            <option value="">همه انواع</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">شناسه سفارش اسپات</label>
                        <input type="number" class="form-control" name="spot_order_id"
                            value="{{ request('spot_order_id') }}" placeholder="شناسه سفارش اسپات">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">توضیحات</label>
                        <input type="text" class="form-control" name="description" value="{{ request('description') }}"
                            placeholder="جستجو در توضیحات">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">وضعیت</label>
                        <select class="form-select" name="deleted">
                            <option value="">همه</option>
                            <option value="0" {{ request('deleted') === '0' ? 'selected' : '' }}>فعال</option>
                            <option value="1" {{ request('deleted') === '1' ? 'selected' : '' }}>حذف شده</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa-light fa-search me-1"></i>
                            جستجو
                        </button>
                        <a href="{{ route('admin.locked-balance.index') }}" class="btn btn-outline-secondary">
                            <i class="fa-light fa-refresh me-1"></i>
                            پاک کردن فیلترها
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">لیست دارایی‌های مسدود شده</h5>
            <span class="badge bg-primary">{{ $lockedBalances->total() }} رکورد</span>
        </div>
        <div class="card-body">
            @if ($lockedBalances->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>کاربر</th>
                                <th>کیف پول</th>
                                <th>مبلغ</th>
                                <th>نوع</th>
                                <th>مرتبط با</th>
                                <th>توضیحات</th>
                                <th>تاریخ ایجاد</th>
                                <th>وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lockedBalances as $lockedBalance)
                                <tr>
                                    <td>{{ $lockedBalance->id }}</td>
                                    <td>
                                        @if ($lockedBalance->wallet && $lockedBalance->wallet->user)
                                            <div class="d-flex flex-column">
                                                <a class="text-heading text-truncate">
                                                    <span
                                                        class="fw-medium">{{ $lockedBalance->wallet->user->email }}</span>
                                                </a>
                                                <small>{{ $lockedBalance->wallet->user->username }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">نامشخص</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($lockedBalance->wallet && $lockedBalance->wallet->currency)
                                            <a href="{{ route('admin.wallet.detail', ['user' => $lockedBalance->wallet->user_id, 'wallet' => $lockedBalance->wallet->id, 'type' => 'lockedBalanceDetails']) }}"
                                                class="text-decoration-none d-flex align-items-center">
                                                <img src="{{ asset($lockedBalance->wallet->currency->coinLogo()) }}"
                                                    class="rounded-circle img-fluid me-2" width="30">
                                                {{ $lockedBalance->wallet->currency->symbol }}
                                            </a>
                                        @else
                                            <span class="text-muted">نامشخص</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ formatNumberTrimZeros($lockedBalance->amount) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-{{ $lockedBalance->type->color() }}">
                                            {{ $lockedBalance->type->label() }}
                                        </span>

                                    </td>
                                    <td>
                                        @if ($lockedBalance->getRelatedEntityName())
                                            @if ($lockedBalance->getRelatedEntityUrl())
                                                <a href="{{ $lockedBalance->getRelatedEntityUrl() }}"
                                                    class="text-decoration-none">
                                                    {{ $lockedBalance->getRelatedEntityName() }}
                                                </a>
                                            @else
                                                {{ $lockedBalance->getRelatedEntityName() }}
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($lockedBalance->description)
                                            <span data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-custom-class="tooltip-dark"
                                                title="{{ $lockedBalance->description }}">
                                                {{ Str::limit($lockedBalance->description, 30) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            {{ \App\Helpers\DateFormatter::convertToPersianDate($lockedBalance->created_at, 'H:i:s %Y/%m/%d') }}<br>
                                            <small class="text-muted">
                                                {{ $lockedBalance->created_at->format('Y/m/d') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($lockedBalance->deleted_at)
                                            <span class="badge bg-danger mb-2">حذف شده</span><br>
                                            {{ \App\Helpers\DateFormatter::convertToPersianDate($lockedBalance->deleted_at, 'H:i:s %Y/%m/%d') }}<br>
                                            <small class="text-muted">
                                                {{ $lockedBalance->deleted_at->format('Y/m/d') }}</small>
                                        @else
                                            <span class="badge bg-success">فعال</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{-- {{ $lockedBalances->appends(request()->query())->links('dashboard.layout.pagination') }} --}}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fa-light fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">هیچ رکوردی یافت نشد</h5>
                    <p class="text-muted">لطفاً فیلترهای خود را تغییر دهید و دوباره تلاش کنید.</p>
                </div>
            @endif
        </div>
    </div>
@endsection


@section('vendor-script')
    <script>
        // Auto-submit form on select change for better UX
        document.querySelectorAll(
            'select[name="currency"], select[name="type"], select[name="deleted"], select[name="sort"]').forEach(
            function(select) {
                select.addEventListener('change', function() {
                    this.form.submit();
                });
            });
    </script>
    <script>
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
