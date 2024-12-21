@extends('dashboard.layout.master')
@section('title', 'مدیریت تراکنش ها')
@section('content')
    <div class="row my-3 align-stretch">
        <div class="col-md-3">
            <div class="card shadow-none bg-primary  h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h6 class="card-title text-white my-0">تعداد تراکنش ها</h6>
                    <h6 class="card-text text-white">
                        {{count($transactions)}}
                    </h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-none bg-secondary  h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h6 class="card-title  text-white my-0">تعداد تراکنش های Referral</h6>
                    <h6 class="card-text text-white">
                        {{count($transactions)}}
                    </h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-none bg-success h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h6 class="card-title text-white my-0">کاربران با بیشترین واریز امروز</h6>
                    <ul class="list-unstyled avatar-group d-flex my-0">
                        @foreach($transactions as $transaction)
                            <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                title="{{$transaction->user}}" class="avatar pull-up">
                                <img class="rounded-circle"
                                     src="https://lh3.googleusercontent.com/e6PBGAIgp4UBlNKZYqXl0LU1hM_j2YHgY-aJDHzvTMe0R7_dJ9WkwKYIymQNHlyQqhHgM0hY2cx8G7rXrYug6KFWAfEbFuvo6aI2he2HjTdok_4O87r0C4mGeAj3=e365-rw-v0-w580"
                                     alt="Avatar">
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="card">
            <div class="card-body">
                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">فیلتر</h5>
                </div>
                <form action="{{route('admin.transaction.index')}}" method="get">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group mt-3">
                                <label for="type">نوع تراکنش:</label>
                                <select name="type" class="form-control" id="type">
                                    <option value=" ">همه</option>
                                    @foreach(\App\Enums\TransactionTypeEnum::cases() as $case)
                                        <option
                                            value="{{$case->name}}" {{request()->has('type') && request()->input('type') == $case->name ? 'selected' : "" }}>
                                            {{\App\Enums\TransactionTypeEnum::TYPE_LABEL[$case->value]}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group mt-3">
                                <label for="selectUser">کاربر مصرف کننده :</label>
                                <select name="user"
                                        id="selectUser"
                                        class="select2 form-control"
                                        data-selected="[{{request()->has('user') ? request()->input('user') : '' }} ]"
                                        src="{{route('admin.users.select.index')}}">
                                </select>
                            </div>
                        </div>
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
    </div>

    <div class="row mt-5">
        <div class="card">
            <div class="card-body">
                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">لیست تراکنش ها</h5>
                    <div class="card-title-elements ms-auto"><a href="http://127.0.0.1:8000/admin/admins/create" class="btn btn-primary">
                            <i class="fa fa-plus mx-2"></i> افزودن همکار جدید </a>
                    </div>

                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>نوع تراکنش</th>
                            <th>کاربر</th>
                            <th>رمز ارز</th>
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
                            <th>مقدار موجودی</th>
                            <th>توضیحات</th>
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
                            <th>عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{$transaction->id}}</td>
                                <td class="text-heading fw-medium">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="trans-avatar-group d-flex align-items-center assigned-avatar">

                                            <div class="avatar avatar-md ">
                                                <img src="{{asset($transaction->wallet->currency->coinLogo())}}"
                                                     class="rounded-circle  ">
                                            </div>
                                            <div class="avatar avatar-md">
                                                <span class="avatar-initial rounded-circle bg-label-{{\App\Enums\TransactionTypeEnum::TYPE_COLOR[$transaction->type->value]}}">
                                                    <i class="fa-regular fa-{{\App\Enums\TransactionTypeEnum::TYPE_ICON[$transaction->type->value]}} mx-3"></i>
                                                </span>
                                            </div>
                                        </div>

                                        <span
                                            class="badge bg-label-{{\App\Enums\TransactionTypeEnum::TYPE_COLOR[$transaction->type->value]}} ms-2">
                                           {{$transaction->type->value}}
                                        </span>
                                    </div>

                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="" class="text-heading text-truncate">
                                            <span class="fw-medium">{{$transaction->user->email}}</span>
                                        </a>
                                        <small>{{$transaction->user->username}}</small>
                                    </div>
                                </td>

                                <td>{{$transaction->wallet->currency_symbol}}</td>
                                <td class="font-number">
                                    <h6>{{formatNumber($transaction->amount)}}</h6>
                                </td>
                                <td class="font-number">
                                    <h6>{{formatNumber($transaction->balance)}}</h6>
                                </td>
                                <td>
                                    {{$transaction->description}}
                                </td>
                                <td class="font-number">
                                    {{\App\Helpers\DateFormatter::convertToPersianDate($transaction->created_at,'H:i:s %Y/%m/%d')}}
                                </td>

                                <td>
                                    @if($transaction->deposit)
                                        <a href="" class="btn btn-{{\App\Enums\DepositStatusEnum::TYPE_COLOR[$transaction->deposit->deposit_type->value]}} btn-sm">
                                            {{\App\Enums\DepositStatusEnum::TYPE_LABEL[$transaction->deposit->deposit_type->value]}}
                                        </a>
                                    @endif

                                </td>
                            </tr>

                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            {{--            {{$transactions->appends()->links()}}--}}
        </div>
    </div>
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js',
            'resources/assets/vendor/js/forms-selects.js',
            'resources/assets/js/select-select-user.js',
          ])
@endsection
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection
