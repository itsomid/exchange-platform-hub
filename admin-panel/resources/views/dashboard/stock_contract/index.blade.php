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
                            <span>تعداد قرارداد های فروخته شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $soldContracts }}</h4>
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
                            <span>تعداد قرارداد های ابطال شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $canceledContracts }}</h4>
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
                            <span>مبلغ فروخته شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($soldAmount) }}</h4>
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
                            <span>مبلغ ابطال شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($canceledAmount) }}</h4>
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
                            <span>مبلغ کارمزد ابطال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($cancellationFees) }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                          <i class="fa-regular fa-hand-holding-dollar fa-lg"></i>
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
{{--                    <form action="{{ route('admin.stock-contract.generate-missing-pdfs') }}" method="POST" class="d-inline me-2">--}}
{{--                        @csrf--}}
{{--                        <button type="submit" class="btn btn-warning" onclick="return confirm('آیا از ایجاد فایل‌های قرارداد گمشده اطمینان دارید؟')">--}}
{{--                            <i class="fa fa-refresh mx-2"></i> ایجاد PDF های گمشده--}}
{{--                        </button>--}}
{{--                    </form>--}}
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
                            @if($currentSortDirection === 'asc')
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
                @if($contracts->isEmpty())
                    <tr>
                        <td colspan="8" class="text-center">قراردادی یافت نشد.</td>
                    </tr>
                @else
                    @foreach($contracts as $contract)
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
                                @if($contract->contract_file && file_exists(storage_path('app/public/contracts/stock/' . $contract->contract_file)))
                                    <a href="{{ asset('storage/contracts/stock/' . $contract->contract_file) }}" target="_blank" class="fw-medium font-number">
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
                                <span class="font-number" dir="ltr">{{ number_format($contract->amount) }}</span>
                            </td>
                            <td>
                                <span class="font-number" dir="ltr">
                                    <h6 class="font-number text-heading mb-0">
                                        <span class="ms-1">{{ number_format($contract->total_value,2) }}</span>
                                        <small class="text-muted">USDT</small>
                                    </h6>
                                </span>
                            </td>
                            <td>
                                {{\App\Helpers\DateFormatter::convertToPersianDate($contract->created_at,'H:i:s - %d %B %Y')}}
                            </td>
                            <td>
                                <span class="badge bg-label-{{ $contract->contract_status->color() }} rounded p-2">
                                    {{ $contract->contract_status->label() }}
                                </span>
                            </td>
                            <td>
                                <a class="text-secondary me-1" href="{{ route('admin.stock-contract.show', $contract->id) }}">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a class="text-secondary me-1" href="{{ route('admin.stock-contract.edit', $contract->id) }}">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.stock-contract.destroy', $contract->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-link text-danger p-0 m-0" onclick="return confirm('آیا از حذف این قرارداد اطمینان دارید؟')">
                                        <i class="fa-light fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>

@endsection
