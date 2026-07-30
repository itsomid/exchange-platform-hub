@extends('dashboard.layout.master')
@section('title', 'مدیریت کاربران')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>همه ی کاربران</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$users->total()}}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-light fa-users fa-lg"></i>
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
                            <span>کاربران تایید شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$activeUsersCount}}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                           <i class="fa-regular fa-user-check fa-xl"></i>
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
                            <span>کاربران تایید نشده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$inActiveUsersCount}}</h4>
                                <p class="text-danger mb-0"></p>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                           <i class="fa-regular fa-user-clock fa-lg"></i>
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
                            <span>کاربران آنلاین</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$onlineUserCount}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-light fa-user-alt fa-lg"></i>

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
                            <span>مشتری های فعال (حداقل یک تراکنش در ماه)</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$usersHasTransactionCount}}</h4>
                                <p class="text-danger mb-0"></p>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                           <i class="fa-regular fa-user-clock fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>


    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">خروجی اکسل</h5>
            <form class="row mt-3 d-flex align-items-end justify-content-between"
                  action="{{route('admin.user.excel-export',request()->query())}}" method="POST">
                @csrf
                <div class="col-md-4 user_role">
                    <label class="form-label" for="UserRole">از آیدی :</label>
                    <input type="number" class="form-control" placeholder="آیدی کاربر">
                </div>
                <div class="col-md-4 user_role">
                    <label class="form-label" for="UserRole">تا آیدی :</label>
                    <input type="number" class="form-control" placeholder="آیدی کاربر">
                </div>
                <div class="col-md-2 mt-2">
                    <button class="btn btn-success class ">دانلود خروجی اکسل</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body border-bottom">
            <h5 class="card-title">فیلتر کاربر</h5>
            <form class="row" action="{{route('admin.user.index')}}" method="get">
                <div class="col-md-4 user_role">
                    <label class="form-label" for="search">جستجو متن :</label>
                    <input id="search" type="text" name="search_key" placeholder="ایمیل٫ شناسه کاربری٫..."
                           value="{{request()->input('search_key')}}" class="form-control">
                </div>

                <div class="col-md-4 user_status ">
                    <label class="form-label" for="status">وضعیت کاربری :</label>
                    <select id="status" name="status"
                            class="form-select text-capitalize mb-md-0 ">
                        <option value="" {{ request('status') == '' ? 'selected' : '' }}>همه</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غیر فعال
                        </option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>مسدود شده
                        </option>
                    </select>
                </div>


                <div class="col-md-4 user_status ">
                    <label class="form-label" for="support_description">توضیحات پشتیبان :</label>
                    <select id="support_description" name="support_description"
                            class="form-select text-capitalize mb-md-0 ">
                        <option value="">همه</option>
                        @foreach($supportDescriptions as $key=>$desc)
                            <option
                                value="{{$desc['support_description']}}" {{request()->input('support_description') === $desc['support_description'] ? 'selected' : null}}>{{$desc['support_description']}}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 mt-2">
                    <button type="submit" class="btn btn-primary mt-2 text-white">
                        <span class="mx-2">جستجو</span>
                        <i class="fa-regular fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست کاربران</h5>
                @can('user.create')
                    <div class="card-title-elements ms-auto">
                        <a href="{{route('admin.user.create')}}" class="btn btn-primary">
                            <i class="fa fa-plus mx-2"></i>
                            افزودن کاربر جدید
                        </a>
                    </div>
                @endcan
            </div>

            @if(count($users))

                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>

                        <tr>
                            <th>
                                @php
                                    $currentParams = request()->except('sortById');
                                    $currentSortDirection = request()->input('sortById', 'asc');
                                    $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                                @endphp
                                <a href="{{ route('admin.user.index', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
                                   class="text-black">
                                    ID
                                    @if($currentSortDirection === 'asc')
                                        <span><i class="fa-solid fa-arrow-up"></i></span>
                                    @else
                                        <span><i class="fa-solid fa-arrow-down"></i></span>
                                    @endif
                                </a>
                            </th>

                            <th>نام کاربری</th>
                            <th>نام</th>
                            <th>کد معرف ثبت نامی</th>
                            <th>وضعیت حساب</th>
                            <th>آخرین فعالیت
                                <br>وضعیت حساب
                            </th>
                            <th>
                                @php
                                    $currentParams = request()->except('sortByCreatedAt');
                                    $currentSortDirection = request()->input('sortByCreatedAt', 'asc');
                                    $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                                @endphp
                                <a href="{{ route('admin.user.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                                   class="text-black">
                                    تاریخ ثبت نام
                                    @if($currentSortDirection === 'asc')
                                        <span><i class="fa-solid fa-arrow-up"></i></span>
                                    @else
                                        <span><i class="fa-solid fa-arrow-down"></i></span>
                                    @endif
                                </a>
                            </th>
                            <th>عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">


                        @foreach($users as $user)
                            <tr>
                                <td>
                                    {{$user->id}}
                                </td>

                                <td>
                                    <div class="d-flex">

                                        <div class="avatar me-2 avatar-{{ $user->activity_status }}">
                                            <span
                                                class="avatar-initial rounded-circle bg-label-{{ $user->avatar_status }}">
                                            {{ trim($user->avatar_name) !== '' ?$user->avatar_name: $user->avatar_user_name }}
                                            </span>
                                        </div>

                                        <div class="d-flex flex-column">
                                            <a href="{{route('admin.inquiry.user-details',['user'=>$user])}}"
                                               class="text-heading text-truncate">
                                                <span class="fw-medium">{{$user->email}}</span>
                                            </a>
                                            <small>{{$user->username}}</small>
                                        </div>
                                    </div>

                                </td>
                                <td>
                                    {{$user->fullname()}}
                                </td>
                                <td>

                                    @if($user->introducerReferral)
                                        <a class="btn btn-primary font-number p-1" data-bs-html='true'
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           data-bs-custom-class="tooltip-dark"
                                           title="<span class='fw-medium'>نام:</span>
                                                    {{ $user->introducerReferral->user->fullname()}}</span>
                                                    <br> <span class='fw-medium'>شناسه کاربری:</span>
                                                    <span class='fw-medium font-monospace'>({{ $user->introducerReferral->user->id }}#)</span>"
                                        >
                                            {{ $user->introducerReferral->code}}
                                        </a>
                                    @endif
                                </td>


                                <td>
                                    @if($user->activeFinancialBlocks->isEmpty())
                                        <span class="badge bg-label-success">بدون محدودیت</span>
                                    @else
                                        @foreach($user->activeFinancialBlocks as $block)
                                            <div class="badge bg-label-danger me-2">
                                                <p class="mb-1">{{$block->action->label()}}</p>
                                                <span>{{\App\Helpers\DateFormatter::timeUntilInPersian($block->restricted_until)}}</span>
                                            </div>
                                        @endforeach
                                    @endif
                                    <span
                                        class="badge bg-label-{{$user->status->color()}} align-self-baseline ms-2">{{$user->status->label()}}</span>
                                </td>
                                <td class="font-number">

                                    @if($user->latestActiveToken)
                                        <span>{{\App\Helpers\DateFormatter::convertToPersianDate($user->latestActiveToken->last_used_at,'H:i:s %Y/%m/%d')}}</span>
                                    @else
                                        <span>بدون فعالیت</span>
                                    @endif
                                </td>
                                <td>
                                    <span>{{\App\Helpers\DateFormatter::convertToPersianDate($user->created_at,'H:i:s %d %B %Y')}}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a class="btn btn-outline-secondary text-dark"
                                               href="{{route('admin.inquiry.user-details',['user'=>$user])}}">
                                                <i class="fa-light fa-eye"></i>
                                            </a>
                                            <a class="btn btn-outline-secondary text-dark"
                                               href="{{route('admin.wallet.index',['user'=>$user->id])}}">
                                                <i class="fa-regular fa-wallet"></i>
                                            </a>
                                            @can('user.edit-note')
                                                <a class="btn {{$user->support_description ? 'btn-primary' :'btn-outline-secondary text-dark'}}"
                                                   href="#"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#noteModal{{$user->id}}">
                                                    <i class="fa-regular fa-user-pen"></i>
                                                </a>
                                            @endcan
                                        </div>

                                        <div class="dropdown mx-3">

                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                    data-bs-toggle="dropdown">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @can('user.edit')
                                                    <a class="dropdown-item"
                                                       href="{{route('admin.user.edit', ['user'=>$user->id])}}">
                                                        <i class="fa-light fa-pen"></i>
                                                        ویرایش کاربر
                                                    </a>
                                                @endcan
                                                <a class="dropdown-item"
                                                   href="{{route('admin.user.password.edit', ['user'=>$user->id])}}">
                                                    <i class="fa-regular fa-unlock"></i>
                                                    تغییر رمز عبور
                                                </a>
                                                <a class="dropdown-item"
                                                   href="{{route('admin.user.financial-block.getBlocks', ['user'=>$user->id])}}">
                                                    <i class="fa-regular fa-unlock"></i>
                                                    محدودیت های مالی
                                                </a>
                                                <a class="dropdown-item"
                                                   href="{{route('admin.user.security', ['user'=>$user->id])}}">
                                                    <i class="fa-regular fa-shield-halved "></i>
                                                    تنظیمات امنیتی کاربر
                                                </a>
                                                @can('user.login-as-customer')
                                                    <a class="dropdown-item"
                                                       href="{{ route('admin.user.login-as-user', $user->id) }}"
                                                       target="_blank"
                                                       rel="noopener noreferrer">
                                                        <i class="fa-light fa-right-to-bracket"></i>
                                                        ورود به عنوان کاربر
                                                    </a>
                                                @endcan

                                            </div>
                                        </div>
                                        @can('user.edit-note')
                                            <note-modal
                                                :id="'noteModal' + {{$user->id}}"
                                                :url="'{{route('api.user.update-note', ['user'=>$user->id])}}'"
                                                :support_description="'{{$user->support_description}}'"
                                            ></note-modal>
                                        @endcan
                                    </div>

                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center h4 mt-5">کاربری موجود نیست🙄</p>
            @endif
        </div>
        <div class="row justify-content-center">
            {{$users->appends(request()->all())->links()}}
        </div>
    </div>

@endsection

@section('vendor-script')
    <script>
        $(document).ready(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection

