@extends('dashboard.layout.master')
@section('title', 'مدیریت قراردادهای سهام')
@section('content')

    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد کل قرارداد ها</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalContracts }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-file-contract fa-lg"></i>
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
                            <span>ارزش قراردادهای فعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ formatNumberTrimZeros($activeAmount) }}
                                    <small class="text-muted fw-medium">USDT</small>
                                </h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-coins fa-lg"></i>
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
                            <span>تعداد قرارداد های فروخته شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $soldContracts }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-box fa-lg"></i>
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
                            <span>مبلغ قراردادهای فروخته شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ formatNumberTrimZeros($soldAmount) }}
                                    <small class="text-muted fw-medium">USDT</small>
                                </h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-sack-dollar fa-lg"></i>
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
                            <span>تعداد قرارداد های ابطال شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $canceledContracts }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-file-circle-xmark fa-lg"></i>
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
                            <span>مبلغ قراردادهای ابطال شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ formatNumberTrimZeros($canceledAmount) }}
                                    <small class="text-muted fw-medium">USDT</small>
                                </h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-coins fa-lg"></i>
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
                            <span> کارمزد دریافتی ابطال (فروخته و کنسل شده)</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ formatNumberTrimZeros($cancellationSoldFees) }}
                                    <small class="text-muted fw-medium">USDT</small>
                                </h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-receipt fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست قراردادها</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{ route('admin.stock-contract.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i> ایجاد قرارداد جدید
                    </a>
                </div>

            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>کاربر</th>
                        <th>شماره قرارداد</th>
                        <th>نوع سهام</th>
                        <th>تعداد</th>
                        <th>ارزش قرارداد</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByCreatedAt');
                                $currentSortDirection = request()->input('sortByCreatedAt', 'asc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.stock-contract.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                                class="text-black">
                                تاریخ ایجاد
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>

                        <th>وضعیت قرارداد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if ($contracts->isEmpty())
                        <tr>
                            <td colspan="8" class="text-center">قراردادی یافت نشد.</td>
                        </tr>
                    @else
                        @foreach ($contracts as $contract)
                            <tr>
                                <td>{{ $contract->id }}</td>
                                <td>
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="avatar-wrapper">
                                            <div class="avatar avatar-sm me-3">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ substr($contract->user->username ?? 'کاربر', 0, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <h6 class="mb-0">{{ $contract->user->fullname() ?? 'کاربر' }}</h6>
                                            <small class="text-muted">{{ $contract->user->email ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($contract->contract_file)
                                        <a href="{{ \App\Data\FileStoragePaths::CONTRACT_DOWNLOAD_URL($contract->contract_file) }}"
                                            target="_blank" class="fw-medium font-number">
                                            <i class="fa-thin fa-file-certificate fa-lg"></i>
                                            {{ $contract->contract_number }}
                                        </a>
                                    @else
                                        <span class="fw-medium font-number text-muted">
                                            <i class="fa-thin fa-file-certificate fa-lg"></i>
                                            {{ $contract->contract_number }}
                                            <small class="text-danger">(PDF موجود نیست)</small>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $contract->stock->name ?? 'نامشخص' }}</span>
                                </td>
                                <td>
                                    <span class="font-number"
                                        dir="ltr">{{ formatNumberTrimZeros($contract->amount) }}</span>
                                </td>
                                <td>
                                    <span class="font-number" dir="ltr">
                                        <h6 class="font-number text-heading mb-0">
                                            <span
                                                class="ms-1">{{ formatNumberTrimZeros($contract->total_value, 2) }}</span>
                                            <small class="text-muted">USDT</small>
                                        </h6>
                                    </span>
                                </td>
                                <td>
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($contract->created_at, 'H:i:s - %d %B %Y') }}
                                </td>
                                <td>
                                    <span class="badge bg-label-{{ $contract->contract_status->color() }} rounded p-2">
                                        {{ $contract->contract_status->label() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="d-flex gap-2 pb-2 ">
                                            <a class="text-secondary"
                                                href="{{ route('admin.stock-contract.show', $contract->id) }}"
                                                title="مشاهده">
                                                <i class="fa fa-eye fa-lg"></i>
                                            </a>
                                            <a class="text-secondary"
                                                href="{{ route('admin.stock-contract.edit', $contract->id) }}"
                                                title="ویرایش">
                                                <i class="fa fa-edit fa-lg"></i>
                                            </a>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-link m-0 p-0 text-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#stock-contract-{{ $contract->id }}" title="تراکنش‌ها">
                                                <i class="fa-light fa-memo-circle-info fa-lg"></i>
                                            </button>
                                            <form action="{{ route('admin.stock-contract.destroy', $contract->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link text-danger p-0 m-0"
                                                    onclick="return confirm('آیا از حذف این قرارداد اطمینان دارید؟')"
                                                    title="حذف">
                                                    <i class="fa-light fa-trash-alt fa-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <x-transaction-modal modal-id="stock-contract-{{ $contract->id }}"
                                        title="تراکنش های قرارداد #{{ $contract->id }}" :user="$contract->user"
                                        :transactions="$contract->transactions" route-name="admin.transaction.index"
                                        route-param="stock_contract_id" :route-param-value="$contract->id" />
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        @if ($contracts->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $contracts->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>

@endsection
