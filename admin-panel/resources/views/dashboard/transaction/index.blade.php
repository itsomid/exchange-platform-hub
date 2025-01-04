@extends('dashboard.layout.master')
@section('title', 'مدیریت تراکنش ها')
@section('content')

    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left"><span>تعداد تراکنش ها</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{count($transactions)}}</h4>
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
                            <span>تعداد تراکنش های امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{count($transactions)}}</h4>
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
                        <div class="content-left"><span>تعداد تراکنش های Referral</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$referralTransactionsCount}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-regular fa-user-tag"></i>
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
                            <span class="text-white">کاربران با بیشترین تراکنش امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">?</h4>
                            </div>
                        </div>
                        <ul class="list-unstyled avatar-group d-flex my-0">
                            @foreach($transactions as $transaction)
                                {{--                                <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"--}}
                                {{--                                    title="{{$transaction->user->email}}" class="avatar pull-up">--}}
                                {{--                                    <img class="rounded-circle" src="http://127.0.0.1:8000/images/avatars/male/2.png" alt="Avatar">--}}
                                {{--                                </li>--}}
                            @endforeach
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
            <form action="{{route('admin.transaction.index')}}" method="get">
                <div class="row">
                    <div class="col-md-3 mt-3">
                        <div class="form-group">
                            <label class="form-label" for="type">نوع تراکنش:</label>
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
                    <div class="col-md-6 mt-3">
                        <label class="form-label" for="user">کاربر :</label>
                        <x-user-selection-component
                            input-name="user"
                            multiple="0"
                            selected=""
                            selected-label=""
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



    <div class="card">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست تراکنش ها</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{route('admin.wallet.increase-credit')}}" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i> افزایش اعتبار
                    </a>
                </div>

            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-striped">
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
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @if($transactions->isEmpty())
                    <tr>
                        <td colspan="9" class="text-center">تراکنشی یافت نشد.</td>
                    </tr>
                @else

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
                                                <span
                                                    class="avatar-initial rounded-circle bg-label-{{\App\Enums\TransactionTypeEnum::TYPE_COLOR[$transaction->type->value]}}">
                                                    <i class="fa-regular fa-{{\App\Enums\TransactionTypeEnum::TYPE_ICON[$transaction->type->value]}} mx-3"></i>
                                                </span>
                                        </div>
                                    </div>

                                    <span class="badge bg-label-{{\App\Enums\TransactionTypeEnum::TYPE_COLOR[$transaction->type->value]}} ms-2">
                                           {{\App\Enums\TransactionTypeEnum::TYPE_LABEL[$transaction->type->value]}}
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
                            <td class="font-number" dir="ltr">
                                <h6 class="mb-0 {{$transaction->amount > 0 ?'text-success': 'text-danger'}}">{{formatNumberTrimZeros($transaction->amount)}}</h6>
                            </td>
                            <td class="font-number">
                                <h6 class="mb-0">{{formatNumberTrimZeros($transaction->balance)}}</h6>
                            </td>
                            <td class="font-number">
                                @if($transaction->admin_id)
                                    {{$transaction->admin->last_name}}
                                @endif
                                {{$transaction->description}}
                            </td>
                            <td class="font-number">
                                {{\App\Helpers\DateFormatter::convertToPersianDate($transaction->created_at,'H:i:s %Y/%m/%d')}}
                            </td>

                            <td>
                                @if($transaction->deposit)
                                    <span
                                        class="badge bg-label-{{\App\Enums\DepositStatusEnum::TYPE_COLOR[$transaction->deposit->status->value]}} btn-sm">
                                                {{\App\Enums\DepositStatusEnum::TYPE_LABEL[$transaction->deposit->status->value]}}
                                            </span>
                                @endif
                            </td>
                            <td>
                                <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                   data-bs-target="#deposit-{{$transaction->id}}">
                                    <i class="fa-regular fa-eye fa-xl"></i>
                                </a>
                                <div class="modal fade" id="deposit-{{$transaction->id}}" tabindex="-1"
                                     aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header" dir="ltr">
                                                <h5 class="modal-title font-number">Transaction
                                                    #{{$transaction->id}}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">مشخصات کاربر</h6>
                                                    <div class="d-flex  gap-4 align-items-center">
                                                        <div class="d-flex flex-column">
                                                            <a href="" class="text-heading text-truncate">
                                                                <span class="fw-medium">{{$transaction->user->email}}</span>
                                                            </a>
                                                            <small>{{$transaction->user->username}}</small>
                                                            <small>{{$transaction->user->fullname()}}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر قبل از تراکنش</h6>
                                                    <div class="d-flex  gap-4 align-items-center">
                                                        <img
                                                            src="{{asset($transaction->wallet->currency->coinLogo())}}" width="30"/>
                                                        <span
                                                            class="font-number">{{formatNumberTrimZeros($transaction->balance - $transaction->amount)}}</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی کاربر پس از تراکنش</h6>
                                                    <div class="d-flex  gap-4 align-items-center">
                                                        <img src="{{asset($transaction->wallet->currency->coinLogo())}}" width="30"/>
                                                        <span
                                                            class="font-number text-primary">{{formatNumberTrimZeros($transaction->balance)}}</span>
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">ساخته شده توسط ادمین</h6>
                                                    <div class="text-wrap font-number">
                                                        @if($transaction->admin_id)
                                                            {{$transaction->admin->fullname()}} -
                                                            #{{$transaction->admin->id}}
                                                        @else
                                                            <span class="badge bg-label-danger">خیر</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات تراکنش</h6>
                                                    <div class="text-wrap font-number w-60">
                                                        {{$transaction->description}}
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات ادمین</h6>
                                                    <div class="text-wrap font-number">
                                                        {{$transaction->admin_description}}
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
                {{$transactions->appends(request()->all())->links()}}
            </div>
        </div>
    </div>

@endsection
@section('vendor-script')
    @vite([
          ])
@endsection
@section('vendor-style')
    @vite([])
@endsection
