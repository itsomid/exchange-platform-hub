@extends('dashboard.layout.master')
@section('title', 'مدیریت واریزی ها')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد واریزی ها</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{count($deposits)}}</h4>
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
                            <span>تعداد واریزی های امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$todayDepositsCount}}</h4>
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
                            <span>ارزش واریزی های امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{formatNumberTrimZeros($totalDepositsValue)}}</h4>
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
                            <span class="text-white">کاربران با بیشترین واریزی امروز</span>
                            <div class="d-flex align-items-baseline my-1">
                                <small class="text-white mx-2">مجموع: </small>
                                <h4 class="mb-0 me-2 text-primary">{{formatNumberTrimZeros($totalTopUsersDeposit)}}</h4>
                                <small class="text-primary">USDT</small>
                            </div>
                        </div>
                        <ul class="list-unstyled avatar-group d-flex my-0">
                            @if(count($topUsers))
                                @foreach($topUsers as $topUser)
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-html='true'
                                        data-bs-placement="top" class="avatar pull-up"
                                        title="<span class='fw-medium'>نام:</span>
                                                    {{ $topUser['user']->fullname()}}</span>
                                                    <br> <span class='fw-medium'>شناسه کاربری:</span>
                                                    <span class='fw-medium font-monospace'>({{ $topUser['user']->id }}#)</span>
                                                    <br> <span class='fw-medium'>نام کاربری:</span>
                                                    <span class='fw-medium font-monospace'>({{ $topUser['user']->username }})</span>
                                                    <br> <span class='fw-medium'>مجموع واریز:</span>
                                                    <span class='fw-medium font-monospace'>{{ formatNumberTrimZeros($topUser['totalDeposit']) }}$</span>
                                                    ">
                                        <div class="avatar me-2">
                                            @php
                                                // Define your color array
                                                $colors = ['primary', 'info', 'danger', 'warning','success'];

                                                // Get a random index from the array
                                                $randomIndex = array_rand($colors);

                                                // Retrieve the color using the random index
                                                $randomColor = $colors[$randomIndex];
                                            @endphp
                                            <span
                                                class="avatar-initial rounded-circle bg-label-{{$randomColor}}">{{$topUser['user']->avatar_user_name}}</span>
                                        </div>
                                        {{--                                        <img class="rounded-circle" src="{{ $topUser['user']->avatar_url ?? 'http://127.0.0.1:8000/images/avatars/male/2.png' }}">--}}
                                    </li>
                                @endforeach
                            @else
                                بدون واریز
                            @endif

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر</h5>
            </div>
            <form action="{{route('admin.deposit.index')}}" method="get">
                <div class="row">
                    <div class="col-md-3 mt-3">
                        <div class="form-group">
                            <label class="form-label" for="type">وضعیت واریز:</label>
                            <select name="status" class="form-control" id="type">
                                <option value="">همه</option>
                                @foreach(\App\Enums\DepositStatusEnum::cases() as $case)
                                    <option
                                        value="{{$case->value}}" {{request()->has('status') && request()->input('status') == $case->value ? 'selected' : "" }}>
                                        {{\App\Enums\DepositStatusEnum::TYPE_LABEL[$case->value]}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label" for="user">کاربر :</label>
                        <x-user-selection-component
                            input-name="user"
                            multiple="0"
                            selected="{{ request()->filled('user')?$deposits[0]->user : '' }}"
                            selected-label="{{ request()->filled('user')
                                ? '('.$deposits[0]->user->id.'#) '.$deposits[0]->user->fullname().' | '.$deposits[0]->user->email
                                : '' }}"

                        ></x-user-selection-component>
                    </div>
                    <div class="w-100"></div>
                    <div class="col-md-2">
                        <div class="form-group mt-3"><br>
                            <button class="btn btn-success text-white" type="submit">
                                <span>فیلتر</span><i class="fas fa-filter mx-3"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>



    <div class="card mb-4">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست واریزی ها</h5>
            </div>

        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>شناسه</th>
                    <th>کاربر</th>
                    <th>Coin</th>
                    <th>شبکه</th>
                    <th>
                        @php
                            $currentParams = request()->except('sortByAmount');
                            $newSortDirection = request()->input('sortByAmount') == 'asc' ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('admin.transaction.index', array_merge($currentParams, ['sortByAmount' => $newSortDirection])) }}"
                           class="text-black">
                            مقدار
                            @if( request()->input('sortByAmount') == 'asc')
                                <span>&uarr;</span>
                            @else
                                <span>&darr;</span>
                            @endif
                        </a>
                    </th>
                    <th>آدرس</th>
                    <th>(TxID) لینک تراکنش</th>
                    <th>
                        @php
                            $currentParams = request()->except('sortByCreatedAt');
                            $newSortDirection = request()->input('sortByCreatedAt') == 'asc' ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('admin.transaction.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                           class="text-black">
                            تاریخ و زمان
                            @if( request()->input('sortByCreatedAt') == 'asc')
                                <span>&uarr;</span>
                            @else
                                <span>&darr;</span>
                            @endif
                        </a>
                    </th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @if($deposits->isEmpty())
                    <tr>
                        <td colspan="11" class="text-center">واریزی یافت نشد.</td>
                    </tr>
                @else

                    @foreach($deposits as $deposit)
                        <tr>
                            <td>{{$deposit->id}}</td>

                            <td>
                                <div class="d-flex flex-column">
                                    <a href="" class="text-heading text-truncate">
                                        <span class="fw-medium">{{$deposit->user->email}}</span>
                                    </a>
                                    <small>{{$deposit->user->username}}</small>
                                </div>
                            </td>
                            <td class="text-heading fw-medium">
                                <img src="{{asset($deposit->currency->coinLogo())}}"
                                     class="rounded-circle img-fluid" width="30">
                                {{$deposit->currency_symbol}}
                            </td>
                            <td>{{$deposit->currency_chain}}</td>
                            <td class="font-number" dir="ltr">
                                <h6 class="mb-0">{{formatNumberTrimZeros($deposit->amount)}}</h6>
                            </td>

                            <td class="font-number">
                                <h6 class="mb-0">
                                    @if($deposit->address)
                                        {{$deposit->address}}
                                        @php
                                            $chain = $deposit->currency_chain; // Assuming $deposit->chain holds the blockchain type (e.g., 'BTC', 'ERC20')
                                            $address = $deposit->address;

                                            // Define node providers with their address URL patterns
                                            $nodeProviderLinks = [
                                                'BTC' => 'https://blockstream.info/address/{address}',
                                                'ERC20' => 'https://etherscan.io/address/{address}',
                                                'BEP20' => 'https://bscscan.com/address/{address}',
                                                'TRC20' => 'https://tronscan.org/#/address/{address}',
                                                'BSC' => 'https://bscscan.com/address/{address}',
                                                'DOGE' => 'https://blockcypher.com/doge/address/{address}',
                                            ];

                                            // Get the appropriate link for the chain type
                                            $targetLink = $nodeProviderLinks[$chain] ?? null;

                                            // Replace placeholder with the actual address
                                            if ($targetLink) {
                                                $targetLink = str_replace('{address}', $address, $targetLink);
                                            }
                                        @endphp

                                        @if($targetLink)
                                            <a href="{{ $targetLink }}" target="_blank">
                                                <i class="fa-regular fa-clone"></i>
                                            </a>
                                        @else
                                            <span>Link not available</span>
                                        @endif
                                    @else
                                        <span>بدون آدرس</span>
                                    @endif

                                </h6>
                            </td>

                            <td class="font-number">
                                @if($deposit->transaction_hash)
                                    {{ formatNumberTrimZeros($deposit->transaction_hash) }}
                                    @php
                                        $chain = $deposit->currency_chain;
                                        $transactionHash = $deposit->transaction_hash;

                                        // Define node providers with their URL patterns
                                        $nodeProviderLinks = [
                                            'BTC' => 'https://blockstream.info/tx/{hash}',
                                            'ERC20' => 'https://etherscan.io/tx/{hash}',
                                            'BEP20' => 'https://bscscan.com/tx/{hash}',
                                            'TRC20' => 'https://tronscan.org/#/transaction/{hash}',
                                            'BSC' => 'https://bscscan.com/tx/{hash}',
                                            'DOGE' => 'https://blockcypher.com/doge/tx/{hash}',
                                        ];

                                        // Get the appropriate link for the chain type
                                        $targetLink = $nodeProviderLinks[$chain] ?? null;

                                        // Replace placeholder with the actual transaction hash
                                        if ($targetLink) {
                                            $targetLink = str_replace('{hash}', $transactionHash, $targetLink);
                                        }
                                    @endphp

                                    @if($targetLink)
                                        <a href="{{ $targetLink }}" target="_blank">
                                            <i class="fa-regular fa-clone"></i>
                                        </a>
                                    @else
                                        <span>Link not available</span>
                                    @endif
                                @else
                                    <span>بدون هش تراکنش</span>
                                @endif

                            </td>
                            <td class="font-number">
                                {{\App\Helpers\DateFormatter::convertToPersianDate($deposit->created_at,'H:i:s %Y/%m/%d')}}
                            </td>

                            <td>
                                    <span
                                        class="badge bg-label-{{$deposit->status->color()}}">{{$deposit->status->label()}}</span>
                            </td>
                            <td>
                                @if($deposit->status === \App\Enums\DepositStatusEnum::CONFIRMED)
                                    <a  href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                            data-bs-target="#deposit-{{$deposit->id}}">
                                        <i class="fa-regular fa-eye fa-xl"></i>
                                    </a>
                                @endif

                                <div class="modal fade" id="deposit-{{$deposit->id}}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header" dir="ltr">
                                                <h5 class="modal-title font-number">Deposit
                                                    #{{$deposit->id}}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">

                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">شناسه تراکنش</h6>
                                                    <div class="d-flex flex-wrap gap-4 font-number">
                                                        Transaction #{{$deposit->transaction->id}}
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر قبل از واریز</h6>
                                                    <div class="d-flex  gap-4 align-items-center">
                                                        <small> {{$deposit->currency_symbol}}</small>
                                                        <span class="font-number">{{formatNumberTrimZeros($deposit->transaction->balance - $deposit->transaction->amount)}}</span>

                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر پس از واریز</h6>
                                                    <div class="d-flex  gap-4 align-items-center">
                                                        <small> {{$deposit->currency_symbol}}</small>
                                                        <span
                                                            class="font-number text-primary">{{formatNumberTrimZeros($deposit->transaction->balance)}}</span>
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات واریز</h6>
                                                    <div class="text-wrap font-number">
                                                        {{$deposit->description}}
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات تراکنش</h6>
                                                    <div class="text-wrap font-number">
                                                        {{$deposit->transaction->description}}
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات ادمین</h6>
                                                    <div class="text-wrap font-number">
                                                        {{$deposit->transaction->admin_description}}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-label-secondary"
                                                        data-bs-dismiss="modal">بستن
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                {{$deposits->links()}}
            </div>
        </div>
    </div>

@endsection
@section('vendor-script')
    @vite([])
@endsection
@section('vendor-style')
    @vite([])
@endsection
