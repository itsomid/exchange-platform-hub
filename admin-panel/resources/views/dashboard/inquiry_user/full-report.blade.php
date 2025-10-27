@extends('dashboard.layout.master')
@section('title', 'استعلام کاربر')
@section('content')

    <div class="row">
        <div class="col-lg-3 order-1 order-md-0">
            <x-user-details :user="$user" />

        </div>
        <div class="col-lg-9 order-0 order-md-1">

            <div class="card">
                <div class="card-body card-widget-separator">
                    <div class="row gy-4 gy-sm-1">
                        <div class="col-sm-6 col-lg-3">
                            <div
                                class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
                                <div>
                                    <h4 class="mb-0">{{ count($walletsWithAssetsValues) }}</h4>
                                    <p class="mb-0">تعداد کیف پول</p>
                                </div>
                                <div class="avatar me-sm-6">
                                    <span class="avatar-initial rounded bg-label-secondary text-heading">
                                        <i class="fa-light fa-wallet"></i>
                                    </span>
                                </div>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-6">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div
                                class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
                                <div>
                                    <h4 class="mb-0">{{ formatNumber($totalAssetsValue) }}
                                        <small class="text-muted">USDT</small>
                                    </h4>
                                    <p class="mb-0">ارزش کل موجودی ها</p>
                                </div>
                                <div class="avatar me-lg-6">
                                    <span class="avatar-initial rounded bg-label-secondary text-heading">
                                        <i class="fa-regular fa-dollar"></i>
                                    </span>
                                </div>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div
                                class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
                                <div>
                                    <h4 dir="ltr"
                                        class="mb-0 {{ $yesterdayProfitLoss['value'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <small class="text-muted">USDT</small>
                                        {{ formatNumber($yesterdayProfitLoss['value']) }}
                                    </h4>
                                    <p class="mb-0">سود و زیان دیروز</p>
                                    <small
                                        class="{{ $yesterdayProfitLoss['value'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ formatNumber($yesterdayProfitLoss['percentage']) }}%
                                    </small>
                                </div>
                                <div class="avatar me-sm-6">
                                    <span class="avatar-initial rounded bg-label-secondary text-heading">
                                        <i class="fa-regular fa-chart-line-up"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">{{ formatNumber($totalAvailableAssetsValue) }}
                                        <small class="text-muted">USDT</small>
                                    </h4>
                                    <p class="mb-0">موجودی در دسترس</p>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-success text-heading">
                                        <i class="fa-regular fa-dollar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card  mt-4">

                <div class="table-responsive text-nowrap">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>نام ارز</th>
                                <th>موجودی</th>
                                <th>در دسترس</th>
                                <th>ارزش(تتر)</th>
                                <th>درصد از کل</th>
                                <th>آدرس واریز</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($walletsWithAssetsValues as $wallet)
                                <tr>
                                    <td>
                                        <div class="d-flex">
                                            <div class="avatar me-2">
                                                <img src="{{ $wallet->currency->coinLogo() }}" class="img-fluid"
                                                    width="50px">
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span
                                                    class="fw-medium text-black">{{ $wallet->currency->persian_name }}</span>
                                                <small>{{ $wallet->currency->name }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-black font-number">
                                        {{ formatNumberTrimZeros($wallet->balance) }}</td>
                                    <td class="font-number">
                                        {{ formatNumberTrimZeros(bcsub($wallet->balance, $wallet->locked_balance, 8)) }}
                                        <br>
                                        @if ($wallet->locked_balance > 0)
                                            <small class="text-danger">
                                                {{ formatNumberTrimZeros($wallet->locked_balance) }}
                                                <i class="fa-regular fa-lock"></i>
                                            </small>
                                        @endif
                                    </td>
                                    <td class="font-number">{{ formatNumberTrimZeros($wallet->assetValue) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="position-relative d-inline-block"
                                                style="width: 45px; height: 45px;">
                                                <svg class="position-absolute top-0 start-0" width="45" height="45"
                                                    viewBox="0 0 36 36">
                                                    <circle cx="18" cy="18" r="16" fill="none"
                                                        stroke="#e9ecef" stroke-width="4"></circle>
                                                    <circle cx="18" cy="18" r="16" fill="none"
                                                        stroke="#6f38d4" stroke-width="4" stroke-dasharray="100"
                                                        stroke-dashoffset="{{ $totalAssetsValue > 0 ? 100 - formatNumber(($wallet->assetValue / $totalAssetsValue) * 100) : 100 }}"
                                                        transform="rotate(-90 18 18)"></circle>
                                                </svg>
                                                <span
                                                    class="position-absolute top-50 start-50 translate-middle font-number fw-bold"
                                                    style="font-size: 0.6rem;">
                                                    {{ $totalAssetsValue > 0 ? formatNumber(($wallet->assetValue / $totalAssetsValue) * 100) : 0 }}%
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <th>
                                        @if ($wallet->walletChains->isNotEmpty())
                                            @foreach ($wallet->walletChains as $walletChain)
                                                @if ($walletChain->address)
                                                    <a href="{{ $walletChain->explorer_address_url }}" target="_blank"
                                                        class="my-1">
                                                        <i class="fa-regular fa-clone me-1"></i>
                                                        <span class="font-number">{{ shorten_hash($walletChain->address) }}
                                                            ({{ $walletChain->currency_chain }})
                                                        </span>
                                                    </a><br>
                                                @else
                                                    <div class="d-flex align-items-center">
                                                        <span class="text-danger me-2"> آدرس شبکه
                                                            ({{ $walletChain->currency_chain }}) ست نشده است</span>
                                                        <button type="button"
                                                            class="btn btn-success btn-xs generate-address-btn"
                                                            data-user-id="{{ $user->id }}"
                                                            data-currency="{{ $wallet->currency_symbol }}"
                                                            data-chain="{{ $walletChain->currency_chain }}"
                                                            data-wallet-chain-id="{{ $walletChain->id }}">
                                                            <i class="fa-solid fa-plus me-1"></i>تولید آدرس
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @else
                                            <div class="d-flex align-items-center">
                                                <span class="text-danger me-2">N/A Wallet Chain</span>
                                                <form method="POST" action="{{ route('admin.wallet.create-chains') }}"
                                                    class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                    <input type="hidden" name="currency_symbol"
                                                        value="{{ $wallet->currency_symbol }}">
                                                    <button type="submit" class="btn btn-primary btn-xs">
                                                        <i class="fa-solid fa-plus me-1"></i>ایجاد WalletChain
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </th>
                                    <td>
                                        <a href="{{ route('admin.wallet.refresh', ['user' => $user->id, 'wallet' => $wallet->id]) }}"
                                            class="btn btn-primary btn-xs me-2">
                                            <i class="fa-solid fa-rotate-right me-1"></i>چک واریز</a>
                                        <a class="btn btn-link p-0 text-secondary me-2"
                                            href="{{ route('admin.wallet.detail', ['user' => $user->id, 'wallet' => $wallet->id, 'type' => 'deposit']) }}"><i
                                                class="fa-light fa-eye fa-lg"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">

        <div class="col">
            <div class="card mb-6">
                <div class="card-header px-0 pt-0 pb-3">
                    <div class="nav-align-top">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#form-tabs-otc" aria-controls="form-tabs-personal" role="tab"
                                    aria-selected="true">
                                    <span class="ti ti-user ti-lg d-sm-none"></span>
                                    <span class="d-none d-sm-block">آخرین معاملات کاربر</span>
                                    <span
                                        class="badge badge-center rounded-pill bg-{{ $totalOtcOrdersCount ? 'success' : 'danger' }} bg-glow ms-2">{{ $totalOtcOrdersCount }}</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#form-tabs-withdrawal" aria-controls="form-tabs-account"
                                    role="tab" aria-selected="false">
                                    <span class="ti ti-user-cog ti-lg d-sm-none"></span>
                                    <span class="d-none d-sm-block">برداشت های اخیر کاربر</span>
                                    <span
                                        class="badge badge-center rounded-pill bg-{{ $totalWithdrawsCount ? 'success' : 'danger' }} bg-glow ms-2">{{ $totalWithdrawsCount }}</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#form-tabs-deposit" aria-controls="form-tabs-social" role="tab"
                                    aria-selected="false">
                                    <span class="ti ti-link ti-lg d-sm-none"></span>
                                    <span class="d-none d-sm-block">واریز های اخیر کاربر</span>
                                    <span
                                        class="badge badge-center rounded-pill bg-{{ $totalDepositsCount ? 'success' : 'danger' }} bg-glow ms-2">{{ $totalDepositsCount }}</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#form-tabs-stock" aria-controls="form-tabs-social" role="tab"
                                    aria-selected="false">
                                    <span class="ti ti-link ti-lg d-sm-none"></span>
                                    <span class="d-none d-sm-block">آخرین معاملات سهام کاربر</span>
                                    <span
                                        class="badge badge-center rounded-pill bg-{{ $totalStockOrdersCount ? 'success' : 'danger' }} bg-glow ms-2">{{ $totalStockOrdersCount }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>


                <div class="tab-content p-0">
                    <div class="tab-pane fade active show" id="form-tabs-otc" role="tabpanel">

                        @if ($otcOrders->isEmpty())
                            <p class="text-center h4">معامله ای یافت نشد🙄</p>
                        @else
                            <div class="ms-5 mb-3">
                                <a class="btn btn-sm btn-primary"
                                    href="{{ route('admin.otc_orders.index', ['user' => $user->id]) }}">مشاهده تمام معامله
                                    ها</a>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>زمان</th>
                                            <th>بازار</th>
                                            <th>نوع معامله</th>
                                            <th>مقدار</th>
                                            <th>قیمت</th>
                                            <th>ارزش</th>
                                            <th>کارمزد</th>
                                            <th>دریافتی</th>
                                            <th>وضعیت</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach ($otcOrders as $order)
                                            <tr>
                                                <td class="font-number">
                                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($order->created_at, 'H:i:s %Y/%m/%d') }}
                                                </td>
                                                <td class="text-heading fw-medium">
                                                    <img src="{{ asset($order->market->baseCurrency->coinLogo()) }}"
                                                        class="rounded-circle" width="32px">
                                                    {{ $order->market->name }}
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-label-{{ $order->type->color() }}">{{ $order->type->label() }}</span>
                                                </td>

                                                <td class="font-number" dir="ltr">
                                                    <span
                                                        class="ms-2">{{ formatNumberTrimZeros($order->quantity) }}</span>
                                                    <small>{{ $order->market->baseCurrency->symbol }}</small>
                                                </td>
                                                <td class="font-number" dir="ltr">
                                                    <span class="ms-2">{{ formatNumberTrimZeros($order->price) }}</span>
                                                    <small>{{ $order->market->quoteCurrency->symbol }}</small>
                                                </td>
                                                <td class="font-number" dir="ltr">
                                                    {{ formatNumberTrimZeros($order->price * $order->quantity) }}
                                                    <small>USDT</small>
                                                </td>
                                                <td class="font-number" dir="ltr">
                                                    {{ formatNumberTrimZeros($order->fee) }}

                                                    <small>{{ $order->type === \App\Enums\OTCOrderTypeEnum::BUY ? $order->market->baseCurrency->symbol : $order->market->quoteCurrency->symbol }}</small>
                                                </td>
                                                <td class="font-number" dir="ltr">
                                                    @if ($order->type === \App\Enums\OTCOrderTypeEnum::BUY)
                                                        {{ formatNumberTrimZeros($order->quantity - $order->fee) }}
                                                        <small>{{ $order->market->baseCurrency->symbol }}</small>
                                                    @else
                                                        {{ formatNumberTrimZeros($order->price * $order->quantity - $order->fee) }}
                                                        <small>{{ $order->market->quoteCurrency->symbol }}</small>
                                                    @endif
                                                </td>

                                                <td>
                                                    <span
                                                        class="badge bg-{{ $order->status->color() }}">{{ $order->status->label() }}</span>
                                                </td>
                                                <td>
                                                    <a href="" class="btn btn-icon btn-text-secondary"
                                                        data-bs-toggle="modal" data-bs-target="#otc-{{ $order->id }}">
                                                        <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                                    </a>
                                                    @if ($order->status === \App\Enums\OTCOrderStatusEnum::CANCELED)
                                                        <a href="#" class="btn btn-icon btn-text-secondary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#otc-description-{{ $order->id }}">
                                                            <i class="fa-regular fa-eye fa-xl"></i>
                                                        </a>
                                                    @endif
                                                    @include('dashboard.otc_order.otc-description-modal', [
                                                        'order' => $order,
                                                    ])
                                                    <x-transaction-modal modal-id="otc-{{ $order->id }}"
                                                        title="تراکنش های معامله #{{ $order->id }}" :user="$order->user"
                                                        :transactions="$order->transactions" route-name="admin.transaction.index"
                                                        route-param="otc_order_id" :route-param-value="$order->id" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                    </div>

                    <div class="tab-pane fade" id="form-tabs-withdrawal" role="tabpanel">
                        @if ($withdraws->isEmpty())
                            <p class="text-center h4">برداشتی یافت نشد🙄</p>
                        @else
                            <div class="ms-5 mb-3">
                                <a class="btn btn-sm btn-primary"
                                    href="{{ route('admin.withdrawal.index', ['user' => $user->id]) }}">مشاهده تمام برداشت
                                    ها</a>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>زمان درخواست</th>
                                            <th>Coin</th>
                                            <th>شبکه</th>
                                            <th>مقدار</th>
                                            <th>کارمزد برداشت <br> (فی صرافی مرجع + بیتکس روم)</th>
                                            <th>آدرس</th>
                                            <th>(TxID) لینک تراکنش</th>
                                            <th>زمان تکمیل</th>
                                            <th>وضعیت</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">

                                        @foreach ($withdraws as $withdraw)
                                            <tr>
                                                <td class="font-number">
                                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($withdraw->created_at, 'H:i:s %Y/%m/%d') }}
                                                </td>
                                                <td class="text-heading fw-medium">
                                                    <img src="{{ asset($withdraw->currency->coinLogo()) }}"
                                                        class="rounded-circle img-fluid" width="30">
                                                    {{ $withdraw->currency_symbol }}
                                                </td>
                                                <td>{{ $withdraw->currencyChain->chain_name }}</td>
                                                <td class="font-number" dir="ltr">
                                                    <h6 class="mb-0">{{ formatNumberTrimZeros($withdraw->amount) }}</h6>
                                                </td>
                                                <td class="font-number" dir="ltr">
                                                    <h6 class="mb-0">{{ formatNumberTrimZeros($withdraw->total_fee) }}
                                                    </h6>
                                                </td>
                                                <td class="font-number">
                                                    <h6 class="mb-0">
                                                        @if ($withdraw->explorer_address_url)
                                                            <a href="{{ $withdraw->explorer_address_url }}"
                                                                target="_blank" class="me-1">
                                                                <i class="fa-regular fa-clone"></i>
                                                            </a>
                                                            <small>{{ shorten_hash($withdraw->address) }}</small>
                                                        @else
                                                            <span>N/A Address</span>
                                                        @endif
                                                    </h6>
                                                </td>

                                                <td class="font-number">
                                                    <h6 class="mb-0">
                                                        @if ($withdraw->explorer_tx_url && $withdraw->transaction_hash)
                                                            <a href="{{ $withdraw->explorer_tx_url }}" target="_blank"
                                                                class="me-1">
                                                                <i class="fa-regular fa-clone"></i>
                                                            </a>
                                                            <small>{{ shorten_hash($withdraw->transaction_hash) }}</small>
                                                        @else
                                                            <span>N/A TxID</span>
                                                        @endif
                                                    </h6>
                                                </td>

                                                <td class="font-number">
                                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($withdraw->created_at, 'H:i:s %Y/%m/%d') }}
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-label-{{ $withdraw->status->color() }}">{{ $withdraw->status->label() }}</span>
                                                </td>
                                                <td>

                                                    @if (in_array($withdraw->status, [
                                                            \App\Enums\WithdrawalStatusEnum::COMPLETED,
                                                            \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL,
                                                            \App\Enums\WithdrawalStatusEnum::PENDING,
                                                            \App\Enums\WithdrawalStatusEnum::FAILED,
                                                        ]))
                                                        <a href="#" class="btn btn-icon btn-text-secondary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#withdraw-modal-{{ $withdraw->id }}">
                                                            <i class="fa-regular fa-eye fa-xl"></i>
                                                        </a>

                                                        <div class="modal fade" id="withdraw-modal-{{ $withdraw->id }}"
                                                            tabindex="-1" aria-hidden="true">
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
                                                                        <h5 class="modal-title font-number">Withdraw
                                                                            #{{ $withdraw->id }}</h5>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        @include(
                                                                            'dashboard.withdraw.withdrawal-detail-modal',
                                                                            ['withdraw' => $withdraw]
                                                                        )
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button"
                                                                            class="btn btn-label-secondary"
                                                                            data-bs-dismiss="modal">بستن
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if ($withdraw->status === \App\Enums\WithdrawalStatusEnum::COMPLETED)
                                                        <a href="" class="btn btn-icon btn-text-secondary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#withdraw-{{ $withdraw->id }}">
                                                            <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                                        </a>
                                                        <x-transaction-modal :modal-id="'withdraw-' . $withdraw->id" :title="'تراکنش های برداشت #' . $withdraw->id"
                                                            :transactions="$withdraw->transactions" route-name="admin.transaction.index"
                                                            :user="$withdraw->user" route-param="withdrawal_id"
                                                            :route-param-value="$withdraw->id" />
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

                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane fade" id="form-tabs-deposit" role="tabpanel">
                        @if ($deposits->isEmpty())
                            <p class="text-center h4">واریزی یافت نشد🙄</p>
                        @else
                            <div class="ms-5 mb-3">
                                <a class="btn btn-sm btn-primary"
                                    href="{{ route('admin.deposit.index', ['user' => $user->id]) }}">مشاهده تمام
                                    واریزها</a>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>شناسه</th>
                                            <th>Coin</th>
                                            <th>شبکه</th>
                                            <th>مقدار</th>
                                            <th>آدرس</th>
                                            <th>(TxID) لینک تراکنش</th>
                                            <th>زمان</th>
                                            <th>وضعیت</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">

                                        @foreach ($deposits as $deposit)
                                            <tr>
                                                <td>{{ $deposit->id }}</td>

                                                <td class="text-heading fw-medium">
                                                    @if ($deposit->currency)
                                                        <img src="{{ asset($deposit->currency->coinLogo()) }}"
                                                            class="rounded-circle img-fluid" width="30">
                                                        {{ $deposit->currency_symbol }}
                                                    @else
                                                        <span class="text-muted">{{ $deposit->currency_symbol }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $deposit->currencyChain->chain_name }}</td>
                                                <td class="font-number" dir="ltr">
                                                    <h6 class="mb-0">{{ formatNumberTrimZeros($deposit->amount) }}</h6>
                                                </td>

                                                <td class="font-number">
                                                    <h6 class="mb-0">
                                                        @if ($deposit->explorer_address_url)
                                                            <a href="{{ $deposit->explorer_address_url }}"
                                                                target="_blank" class="me-1">
                                                                <i class="fa-regular fa-clone"></i>
                                                            </a>
                                                            <small>{{ shorten_hash($deposit->address) }}</small>
                                                        @else
                                                            <span>N/A Address</span>
                                                        @endif
                                                    </h6>
                                                </td>

                                                <td class="font-number">

                                                    <h6 class="mb-0">
                                                        @if ($deposit->explorer_tx_url && $deposit->transaction_hash)
                                                            <a href="{{ $deposit->explorer_tx_url }}" target="_blank"
                                                                class="me-1">
                                                                <i class="fa-regular fa-clone"></i>
                                                            </a>
                                                            <small>{{ shorten_hash($deposit->transaction_hash) }}</small>
                                                        @else
                                                            <span>N/A TxID</span>
                                                        @endif
                                                    </h6>

                                                </td>
                                                <td class="font-number">
                                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($deposit->created_at, 'H:i:s %Y/%m/%d') }}
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-label-{{ $deposit->status->color() }}">{{ $deposit->status->label() }}</span>
                                                </td>
                                                <td>
                                                    @if ($deposit->status === \App\Enums\DepositStatusEnum::CONFIRMED)
                                                        <a href="" class="btn btn-icon btn-text-secondary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deposit-{{ $deposit->id }}">
                                                            <i class="fa-regular fa-eye fa-xl"></i>
                                                        </a>
                                                        @include(
                                                            'dashboard.deposits.deposit-detail-modal',
                                                            [
                                                                'deposit' => $deposit,
                                                            ]
                                                        )
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <!-- Stock Contracts Tab -->
                    <div class="tab-pane fade" id="form-tabs-stock" role="tabpanel">
                        @if ($stockContracts->isEmpty())
                            <p class="text-center h4">قرارداد سهامی یافت نشد🙄</p>
                        @else
                            <div class="ms-5 mb-3">
                                <a class="btn btn-sm btn-primary"
                                    href="{{ route('admin.stock-contract.index', ['user' => $user->id]) }}">مشاهده تمام
                                    قراردادها</a>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>شماره قرارداد</th>
                                            <th>نوع سهام</th>
                                            <th>تعداد</th>
                                            <th>ارزش قرارداد</th>
                                            <th>تاریخ ایجاد</th>
                                            <th>وضعیت قرارداد</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach ($stockContracts as $contract)
                                            <tr>
                                                <td>{{ $contract->id }}</td>
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
                                                    <span
                                                        class="fw-medium">{{ $contract->stock->name ?? 'نامشخص' }}</span>
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
                                                    <span
                                                        class="badge bg-label-{{ $contract->contract_status->color() }} rounded p-2">
                                                        {{ $contract->contract_status->label() }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a class="text-secondary me-1"
                                                        href="{{ route('admin.stock-contract.show', $contract->id) }}">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a class="text-secondary me-1"
                                                        href="{{ route('admin.stock-contract.edit', $contract->id) }}">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-link p-0 m-0 me-1"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#stock-contract-{{ $contract->id }}">
                                                        <i class="fa-light fa-memo-circle-info"></i>
                                                    </button>
                                                    <x-transaction-modal :modal-id="'stock-contract-' . $contract->id" :title="'تراکنش های قرارداد #' . $contract->id"
                                                        :user="$contract->user" :transactions="$contract->transactions"
                                                        route-name="admin.transaction.index"
                                                        route-param="stock_contract_id" :route-param-value="$contract->id" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>



            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            const tabPanes = document.querySelectorAll('.tab-pane');
            const storageKey = 'inquiry_user_active_tab';

            // بازیابی آخرین تب فعال از localStorage
            const savedTab = localStorage.getItem(storageKey);

            if (savedTab) {
                // حذف کلاس active از همه تب‌ها و پنل‌ها
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                });

                tabPanes.forEach(pane => {
                    pane.classList.remove('active', 'show');
                });

                // فعال کردن تب ذخیره شده
                const savedButton = document.querySelector(`[data-bs-target="${savedTab}"]`);
                const savedPane = document.querySelector(savedTab);

                if (savedButton && savedPane) {
                    savedButton.classList.add('active');
                    savedButton.setAttribute('aria-selected', 'true');
                    savedPane.classList.add('active', 'show');
                }
            }

            // ذخیره تب فعال هنگام کلیک
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const target = this.getAttribute('data-bs-target');
                    localStorage.setItem(storageKey, target);
                });
            });

            // Handle generate address button clicks
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('generate-address-btn') || e.target.closest(
                        '.generate-address-btn')) {
                    const button = e.target.classList.contains('generate-address-btn') ? e.target : e.target
                        .closest('.generate-address-btn');

                    const userId = button.getAttribute('data-user-id');
                    const currency = button.getAttribute('data-currency');
                    const chain = button.getAttribute('data-chain');
                    const walletChainId = button.getAttribute('data-wallet-chain-id');

                    // Disable button and show loading state
                    button.disabled = true;
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>در حال تولید...';

                    // Make AJAX request to generate address
                    fetch('{{ route('admin.wallet.generate-address') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                user_id: userId,
                                currency: currency,
                                chain: chain
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message

                                // Reload the page to show the new address
                                location.reload();
                            } else {

                                // Re-enable button
                                button.disabled = false;
                                button.innerHTML = originalText;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);


                            // Re-enable button
                            button.disabled = false;
                            button.innerHTML = originalText;
                        });
                }
            });
        });
    </script>
@endpush
