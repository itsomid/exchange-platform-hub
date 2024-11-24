@extends('dashboard.layout.master')
@section('title', 'مدیریت کدهای معرف')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">{{count($referralCodes)}}</h5>
                            <small>تعداد کدهای معرف</small>
                        </div>
                        <span class="badge bg-label-danger rounded-circle p-3">
                            <i class="fa-light fa-users fa-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">${{formatNumber($totalTransactionSum,2)}}</h5>
                            <small>مجموع دریافتی کاربران</small>
                        </div>
                        <span class="badge bg-label-success rounded-circle p-3">
                            <i class="fa-light fa-gift fa-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">{{$totalRegisteredUsers}}</h5>
                            <small>تعداد افراد ثبت نام شده با کد معرف</small>
                        </div>
                        <span class="badge bg-label-warning rounded-circle p-3">
                            <i class="fa-light fa-users fa-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <h5 class="card-title">فیلتر کدهای معرف</h5>
            <form class="row" action="{{route('admin.referral_code.index')}}">
                <div class="col-md-6 user_role">
                    <label class="form-label" for="code">کد:</label>
                    <input type="text" name="code" class="form-control" value="{{request()->input('code')}}"
                           placeholder="کد معرف را وارد کنید">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="user">کاربر :</label>
                    <x-user-selection-component
                        input-name="user"
                        multiple="0"
                        selected="{{ request()->filled('user')?$referralCodes[0]->user : '' }}"
                        selected-label="{{ request()->filled('user')
                        ? '('.$referralCodes[0]->user->id.'#) '.$referralCodes[0]->user->fullname().' | '.$referralCodes[0]->user->email
                        : '' }}"

                    ></x-user-selection-component>
                </div>
                <div class="col-md-12 text-right  mt-4">
                    <button class="btn btn-primary">
                        <i class="fa fa-search mx-2"></i>
                        جستجو
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست کدهای معرف</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{route('admin.referral_code.create')}}" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i>
                        افزودن کد معرف جدید
                    </a>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>کد دعوت</th>
                        <th>کاربر</th>
                        <th>سهم از کارمزد شما / دوستان</th>
                        <th>تعداد دوستان</th>
                        <th>تعداد معاملات</th>
                        <th>مجموع دریافتی</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($referralCodes as $referralCode)
                        <tr>
                            <td>
                                {{$referralCode->id}}
                            </td>
                            <td>
                                {{$referralCode->code}}
                            </td>
                            <td>

                                <div class="d-flex flex-column">
                                    <a href="" class="text-heading text-truncate">
                                        <span class="fw-medium">{{ $referralCode->user->email}}</span>
                                    </a>
                                    <small>{{ $referralCode->user->fullname()}}</small>
                                </div>
                            </td>
                            <td>
                                {{$referralCode->introducer_fee}}%/{{$referralCode->friend_fee}}%
                            </td>
                            <td>
                                <span class="me-2">{{count($referralCode->registeredUsers)}}</span>
                                <a href="">(مشاهده)</a>
                            </td>
                            <td>
                                {{count($referralCode->referralCodeUsage)}}
                            </td>
                            <td>
                                ${{formatNumber($referralCode->transactions_sum_amount,2)}}
                            </td>

                            <td>
                                <div class="d-flex align-items-center">

                                    <a class="text-secondary me-3"
                                       href="{{ route('admin.referral_code.edit', ['referral_code' => $referralCode->id]) }}">
                                        <i class="fa-light fa-pen-to-square fa-lg"></i>
                                    </a>
                                    <a class="text-secondary me-3" href="">

                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>
                                    <a class="text-secondary me-3" href="">

                                        <i class="fa-light fa-trash-alt fa-lg"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{--                @include('dashboard.layout.pagination', ['collection' => $regentCodes])--}}
        </div>
    </div>

@endsection
