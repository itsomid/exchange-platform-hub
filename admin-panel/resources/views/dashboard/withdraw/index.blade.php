@extends('dashboard.layout.master')
@section('title', 'مدیریت برداشت ها')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>

                                تعداد برداشت ها
                                @if (request('status'))
                                    ({{ \App\Enums\WithdrawalStatusEnum::TYPE_LABEL[request('status')] }})
                                @else
                                    <span>(همه)</span>
                                @endif
                            </span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $withdraws->total() }}</h4>
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
                            <span>تعداد برداشت های امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $todayWithdrawalsCount }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded">
                            <i class="fa-light fa-money-bill-wave"></i>
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
                            <span>ارزش برداشت های امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ formatNumberTrimZeros($totalWithdrawalsValue) }}</h4>
                                <small>USDT</small>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded">
                            <i class="fa-light fa-money-bill-wave"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body bg-success">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-white">کاربران با بیشترین برداشت امروز</span>
                            <div class="d-flex align-items-baseline my-1">
                                <small class="text-white mx-2">مجموع: </small>
                                <h4 class="mb-0 me-2 text-primary">{{ formatNumber($totalTopUsersWithdrawals, 2) }}</h4>
                                <small class="text-primary">USDT</small>
                            </div>
                        </div>
                        <ul class="list-unstyled avatar-group d-flex my-0">
                            @if (count($topUsers))
                                @foreach ($topUsers as $topUser)
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-html='true'
                                        data-bs-placement="top" class="avatar pull-up"
                                        title="<span class='fw-medium'>نام:</span>
                                                    {{ $topUser['user']->fullname() }}</span>
                                                    <br> <span class='fw-medium'>شناسه کاربری:</span>
                                                    <span class='fw-medium font-monospace'>({{ $topUser['user']->id }}#)</span>
                                                    <br> <span class='fw-medium'>نام کاربری:</span>
                                                    <span class='fw-medium font-monospace'>({{ $topUser['user']->username }})</span>
                                                    <br> <span class='fw-medium'>مجموع برداشت:</span>
                                                    <span class='fw-medium font-monospace'>{{ formatNumberTrimZeros($topUser['totalWithdraw']) }}$</span>
                                                    ">
                                        <div class="avatar me-2">
                                            @php
                                                // Define your color array
                                                $colors = ['primary', 'info', 'danger', 'warning', 'success'];

                                                // Get a random index from the array
                                                $randomIndex = array_rand($colors);

                                                // Retrieve the color using the random index
                                                $randomColor = $colors[$randomIndex];
                                            @endphp
                                            <span
                                                class="avatar-initial rounded-circle bg-label-{{ $randomColor }}">{{ $topUser['user']->avatar_user_name }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            @else
                                بدون برداشت
                            @endif

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">خروجی اکسل</h5>
            <form class="row mt-3 d-flex align-items-end"
                action="{{ route('admin.withdrawal.excel-export', request()->query()) }}" method="POST">
                @csrf
                <div class="col-md-2 user_role">
                    <label class="form-label" for="UserRole">از آیدی :</label>
                    <input type="number" class="form-control" placeholder="آیدی کاربر">
                </div>
                <div class="col-md-2 user_role">
                    <label class="form-label" for="UserRole">تا آیدی :</label>
                    <input type="number" class="form-control" placeholder="آیدی کاربر">
                </div>
                <div class="col-md-2 mt-2">
                    <button class="btn btn-success class ">دانلود خروجی اکسل</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر</h5>
            </div>
            <form action="{{ route('admin.withdrawal.index') }}" method="get">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="type">وضعیت برداشت:</label>
                            <select name="status" class="form-control" id="type">
                                <option value="">همه</option>
                                @foreach (\App\Enums\WithdrawalStatusEnum::cases() as $case)
                                    <option value="{{ $case->value }}"
                                        {{ request()->has('status') && request()->input('status') == $case->value ? 'selected' : '' }}>
                                        {{ $case->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 ">
                        <label class="form-label" for="user">کاربر :</label>
                        <x-user-selection-component input-name="user" multiple="0"
                            selected="{{ request()->filled('user') ? $withdraws[0]->user->id : '' }}"
                            selected-label="{{ request()->filled('user')
                                ? '(' . $withdraws[0]->user->id . '#) ' . $withdraws[0]->user->fullname() . ' | ' . $withdraws[0]->user->email
                                : '' }}"></x-user-selection-component>
                    </div>
                    <div class="col-md-2 align-self-end">

                        <button class="btn btn-success text-white" type="submit">
                            <span>فیلتر</span><i class="fas fa-filter mx-3"></i>
                        </button>

                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست برداشت ها</h5>
            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            @php
                                $currentParams = request()->except('sortById');
                                $currentSortDirection = request()->input('sortById', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
                                class="text-black">
                                ID
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>کاربر</th>
                        <th>Coin</th>
                        <th>شبکه</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByAmount');
                                $newSortDirection = request()->input('sortByAmount') == 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortByAmount' => $newSortDirection])) }}"
                                class="text-black">
                                مقدار
                                @if (request()->input('sortByAmount') == 'asc')
                                    <span>&uarr;</span>
                                @else
                                    <span>&darr;</span>
                                @endif
                            </a>
                        </th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByTotalFee');
                                $currentSortDirection = request()->input('sortByTotalFee', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortByTotalFee' => $newSortDirection])) }}"
                                class="text-black">
                                کارمزد برداشت <br> (فی شبکه + صرافی)
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>

                        </th>
                        <th>ارزش</th>
                        <th>آدرس برداشت</th>
                        <th> هش تراکنش (TxID)</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByCreatedAt');
                                $currentSortDirection = request()->input('sortByCreatedAt', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                                class="text-black">
                                زمان درخواست
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByConfirmedAt');
                                $currentSortDirection = request()->input('sortByConfirmedAt', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortByConfirmedAt' => $newSortDirection])) }}"
                                class="text-black">
                                زمان تکمیل برداشت
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if ($withdraws->isEmpty())
                        <tr>
                            <td colspan="11" class="text-center">برداشتی یافت نشد.</td>
                        </tr>
                    @else
                        @foreach ($withdraws as $withdraw)
                            <tr>
                                <td>{{ $withdraw->id }}</td>

                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('admin.inquiry.user-details', ['user' => $withdraw->user]) }}"
                                            class="text-heading text-truncate">
                                            <span class="fw-medium">{{ $withdraw->user->email }}</span>
                                        </a>
                                        <small>{{ $withdraw->user->username }}</small>
                                    </div>
                                </td>
                                <td class="text-heading fw-medium">
                                    <img src="{{ asset($withdraw->currency->coinLogo()) }}"
                                        class="rounded-circle img-fluid" width="25">
                                    <small class="ms-1">{{ $withdraw->currency_symbol }}</small>

                                </td>
                                <td>
                                    <smal>{{ $withdraw->currencyChain->chain }}</smal>
                                </td>
                                <td class="font-number" dir="ltr">
                                    <h6 class="mb-0">{{ formatNumberTrimZeros($withdraw->amount) }}</h6>
                                </td>
                                <td class="font-number" dir="ltr">
                                    <h6 class="mb-0">{{ formatNumberTrimZeros($withdraw->total_fee) }}</h6>
                                </td>
                                <td dir="ltr">
                                    <h6 class="font-number text-heading mb-0">
                                        <span class="ms-1">{{ formatNumberTrimZeros($withdraw->usdt_value) }}</span>
                                        <small class="text-muted">USDT</small>
                                    </h6>
                                </td>
                                <td class="font-number">
                                    <h6 class="mb-0 d-flex">
                                        @if ($withdraw->explorer_address_url)
                                            <a href="javascript:void(0);" class="clipboard-btn mx-1"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#withdraw{{ $withdraw->address }}">
                                                <i class="fa-regular fa-clone"></i>
                                            </a>

                                            <input type="hidden" value="{{ $withdraw->address }}"
                                                id="withdraw{{ $withdraw->address }}" class="form-control text-left"
                                                aria-label="Username" readonly>

                                            <a href="{{ $withdraw->explorer_address_url }}" target="_blank">
                                                <small>{{ shorten_hash($withdraw->address) }}</small>
                                            </a>
                                        @else
                                            <small>N/A Address</small>
                                        @endif
                                    </h6>
                                </td>

                                <td class="font-number">
                                    <h6 class="mb-0">
                                        @if ($withdraw->explorer_tx_url && $withdraw->transaction_hash)
                                            <a href="javascript:void(0);" class="clipboard-btn mx-1"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#deposit{{ $withdraw->transaction_hash }}">
                                                <i class="fa-regular fa-clone"></i>
                                            </a>
                                            <input type="hidden" value="{{ $withdraw->transaction_hash }}"
                                                id="deposit{{ $withdraw->transaction_hash }}"
                                                class="form-control text-left" placeholder="کد معرف شما"
                                                aria-label="Username" readonly>
                                            <a href="{{ $withdraw->explorer_tx_url }}" target="_blank" class="me-1">
                                                <small>{{ shorten_hash($withdraw->transaction_hash) }}</small>
                                            </a>
                                        @else
                                            <small>N/A TxID</small>
                                        @endif
                                    </h6>

                                </td>
                                <td class="font-number">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($withdraw->created_at, 'H:i:s %Y/%m/%d') }}
                                </td>
                                <td class="font-number">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($withdraw->confirmed_at, 'H:i:s %Y/%m/%d') }}
                                </td>

                                <td>
                                    <span
                                        class="badge bg-label-{{ $withdraw->status->color() }}">{{ $withdraw->status->label() }}</span>
                                </td>
                                <td>

                                    <a href="#" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                        data-bs-target="#withdraw-modal-{{ $withdraw->id }}">
                                        <i class="fa-regular fa-eye fa-xl"></i>
                                    </a>

                                    <div class="modal fade" id="withdraw-modal-{{ $withdraw->id }}" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div
                                                class="modal-content border-3 border-{{ $withdraw->status === \App\Enums\WithdrawalStatusEnum::COMPLETED
                                                    ? 'success'
                                                    : ($withdraw->status === \App\Enums\WithdrawalStatusEnum::FAILED
                                                        ? 'danger'
                                                        : ($withdraw->status === \App\Enums\WithdrawalStatusEnum::PENDING
                                                            ? 'warning'
                                                            : 'info')) }}">
                                                <div class="modal-header" dir="ltr">
                                                    <h5 class="modal-title font-number">Withdraw #{{ $withdraw->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @include('dashboard.withdraw.withdrawal-detail-modal', [
                                                        'withdraw' => $withdraw,
                                                    ])
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary"
                                                        data-bs-dismiss="modal">بستن
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($withdraw->status === \App\Enums\WithdrawalStatusEnum::COMPLETED)
                                        <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                            data-bs-target="#withdraw-{{ $withdraw->id }}">
                                            <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                        </a>
                                        <div class="modal fade" id="withdraw-{{ $withdraw->id }}" tabindex="-1"
                                            aria-model="true" role="dialog">
                                            <div class="modal-dialog modal-xl" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title font-number" id="exampleModalLabel4">تراکنش
                                                            های
                                                            برداشت #{{ $withdraw->id }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="table-responsive text-nowrap">
                                                            <table class="table table-striped">
                                                                <thead>
                                                                    <tr>
                                                                        <th>شناسه</th>
                                                                        <th>نوع تراکنش</th>
                                                                        <th>رمز ارز</th>
                                                                        <th>مقدار</th>
                                                                        <th>مقدار موجودی</th>
                                                                        <th>توضیحات</th>
                                                                        <th>تاریخ و زمان</th>
                                                                        <th>وضعیت</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="table-border-bottom-0">
                                                                    @if ($withdraw->transactions->isEmpty())
                                                                        <tr>
                                                                            <td colspan="9" class="text-center">تراکنشی
                                                                                یافت
                                                                                نشد.
                                                                            </td>
                                                                        </tr>
                                                                    @else
                                                                        @foreach ($withdraw->transactions as $transaction)
                                                                            <tr>
                                                                                <td>{{ $transaction->id }}</td>
                                                                                <td class="text-heading fw-medium">
                                                                                    <div
                                                                                        class="d-flex justify-content-start align-items-center">
                                                                                        <div
                                                                                            class="trans-avatar-group d-flex align-items-center assigned-avatar">
                                                                                            <div class="avatar avatar-md ">
                                                                                                <img src="{{ asset($transaction->wallet->currency->coinLogo()) }}"
                                                                                                    class="rounded-circle">
                                                                                            </div>
                                                                                            <div class="avatar avatar-md">
                                                                                                <span
                                                                                                    class="avatar-initial rounded-circle bg-label-{{ $transaction->type->color() }}">
                                                                                                    <i
                                                                                                        class="fa-regular fa-{{ $transaction->type->icon() }} mx-3"></i>
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div
                                                                                            class="d-flex flex-column align-items-start">
                                                                                            <span
                                                                                                class="badge bg-label-{{ $transaction->type->color() }} ms-2">
                                                                                                {{ $transaction->type->label() }}
                                                                                            </span>
                                                                                            @if ($transaction->subtype->value != 'user_initiated')
                                                                                                <span
                                                                                                    class="badge bg-label-secondary ms-2 mt-2">
                                                                                                    {{ $transaction->subtype->label() }}
                                                                                                </span>
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>
                                                                                </td>


                                                                                <td>{{ $transaction->wallet->currency_symbol }}
                                                                                </td>
                                                                                <td class="font-number" dir="ltr">
                                                                                    <h6
                                                                                        class="mb-0 {{ $transaction->amount > 0 ? 'text-success' : 'text-danger' }}">
                                                                                        {{ formatNumberTrimZeros($transaction->amount) }}
                                                                                    </h6>
                                                                                </td>
                                                                                <td class="font-number">
                                                                                    <h6 class="mb-0">
                                                                                        {{ formatNumberTrimZeros($transaction->balance) }}
                                                                                    </h6>
                                                                                </td>

                                                                                <td class="font-number text-wrap">
                                                                                    @if ($transaction->admin_id)
                                                                                        {{ $transaction->admin->last_name }}
                                                                                    @endif
                                                                                    <span>{{ $transaction->description }}</span>

                                                                                </td>
                                                                                <td class="font-number">
                                                                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($transaction->created_at, 'H:i:s %Y/%m/%d') }}
                                                                                </td>

                                                                                <td>
                                                                                    <span
                                                                                        class="badge bg-label-{{ $transaction->status->color() }}">
                                                                                        {{ $transaction->status->label() }}
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    @endif
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <a type="button"
                                                            href="{{ route('admin.transaction.index', ['withdrawal_id' => $withdraw->id]) }}"
                                                            class="btn btn-primary">لیست تراکنش ها</a>
                                                        <button type="button"
                                                            class="btn btn-label-secondary waves-effect"
                                                            data-bs-dismiss="modal">بستن
                                                        </button>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($withdraw->status === \App\Enums\WithdrawalStatusEnum::PENDING)
                                        <a href="{{ route('admin.withdrawal.check-withdrawal', $withdraw->id) }}"
                                            class="btn btn-sm btn-icon btn-warning">
                                            <i class="fa-solid fa-rotate-right"></i>
                                        </a>
                                    @endif
                                    @if ($withdraw->status === \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL)
                                        <a href="{{ route('admin.withdrawal.confirm-withdrawal', ['withdraw' => $withdraw]) }}"
                                            class="btn btn-icon btn-success me-2">
                                            <i class="fa-regular fa-badge-check fa-lg"></i>
                                        </a>
                                        <a href="{{ route('admin.withdrawal.cancel-withdrawal', ['withdraw' => $withdraw]) }}"
                                            class="btn btn-icon btn-danger">
                                            <i class="fa-regular fa-xmark fa-lg"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                {{ $withdraws->appends(request()->all())->links() }}
            </div>
        </div>

    </div>

@endsection
@section('vendor-script')
    <script>
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Handle copy functionality
            $('.clipboard-btn').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                const icon = btn.find('i');
                const originalIcon = icon.attr('class');

                // Copy the text
                const targetId = btn.data('clipboard-target');
                const textToCopy = $(targetId).val();
                navigator.clipboard.writeText(textToCopy).then(() => {
                    // Change icon to tick
                    icon.removeClass(originalIcon).addClass('fa-solid fa-check');

                    // Change back to original icon after 2 seconds
                    setTimeout(() => {
                        icon.removeClass('fa-solid fa-check').addClass(originalIcon);
                    }, 2000);
                });
            });
        });
    </script>
@endsection
