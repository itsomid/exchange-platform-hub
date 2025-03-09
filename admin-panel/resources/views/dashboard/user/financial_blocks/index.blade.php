@extends('dashboard.layout.master')
@section('title', 'مدیریت کاربران')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد کاربران محدود شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{count($blockedUsers)}}</h4>

                            </div>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="fa-light fa-user-lock fa-lg"></i>

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
                            <span>تعداد کاربران بلاک از برداشت</span>
                            <div class="d-flex align-items-center my-1">

                                <h4 class="mb-0 me-2">{{$withdrawBlockedUsers}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded ">
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
                            <span>تعداد کاربران بلاک از واریز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$depositBlockedUsers}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="fa-regular fa-money-from-bracket"></i>
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
                            <span>تعداد کاربران بلاک از معامله</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$tradeBlockedUsers}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                         <i class="fa-solid fa-money-bill-transfer"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">خروجی اکسل</h5>
            <form class="row mt-3 d-flex align-items-end justify-content-between">
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
            <form class="row" action="{{route('admin.user.financial-status')}}" method="get">
                <div class="col-md-4 user_role">
                    <label class="form-label" for="search">جستجو متن :</label>
                    <input id="search" type="text" name="search_key" value="{{request('search_key')}}" placeholder="ایمیل٫ شناسه کاربری٫ شماره تلفن٫..."
                           class="form-control">
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
                <h5 class="m-0 me-2">لیست کاربران بلاک شده</h5>

                <div class="card-title-elements ms-auto">
                    <a href="{{route('admin.user.financial-block.create-mass-block')}}" class="btn btn-danger">
                        <i class="fa-regular fa-ban mx-2"></i>
                        مسدود سازی گروهی کاربران
                    </a>

                </div>
            </div>

            @if(count($blockedUsers))

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
                                <a href="{{ route('admin.user.financial-status', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
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
                            <th>وضعیت اکانت</th>
                            <th>وضعیت حساب</th>
                            <th>آخرین فعالیت</th>
                            <th>عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">


                        @foreach($blockedUsers as $user)
                            <tr>
                                <td>
                                    {{$user->id}}
                                </td>

                                <td>

                                    <div class="d-flex flex-column">
                                        <a href="" class="text-heading text-truncate">
                                            <span class="fw-medium">{{$user->email}}</span>
                                        </a>
                                        <small>{{$user->username}}</small>
                                    </div>
                                </td>
                                <td>
                                    {{$user->fullname()}}
                                </td>


                                <td>
                                  <span
                                      class="badge bg-label-{{$user->status->color()}} align-self-baseline">{{$user->status->label()}}</span>

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
                                </td>

                                <td>
                                    فعالیتی نداشته است
                                </td>
                                <td >
                                    <form action="{{route('admin.user.financial-block.deleteBlock',['user'=>$user,'financialBlock'=>$block->id])}}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-icon btn-danger">
                                            <i class="fa-light fa-trash-alt fa-lg"></i>
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center h4 mt-5">فرد مورد نظر در لیست بلاکی ها نیست 🙄</p>
            @endif
        </div>
        <div class="row justify-content-center">
            {{$blockedUsers->links()}}
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
