@extends('dashboard.layout.master')
@section('title', 'مدیریت کاربران')
@section('content')
    @can('admin.index.statistic_boxes')
        <div class="row g-4 mb-4">
            <div class="col-sm-12 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>تعداد همکاران</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{$admins->count()}}</h4>
                                </div>
                            </div>
                            <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-users"></i>
                        </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>همکاران فعال</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{$admins->where('is_active', 1)->count()}}</h4>
                                </div>
                            </div>
                            <span class="badge bg-label-success rounded p-2">
                            <i class="fa-solid fa-user-check"></i>
                        </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>همکاران غیر فعال</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{$admins->where('is_active', 0)->count()}}</h4>
                                </div>
                            </div>
                            <span class="badge bg-label-warning rounded p-2">
                                <i class="fa-solid fa-user-xmark"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">جست و جو</h5>
            <form class="row mt-3 d-flex align-items-end justify-content-between"
                  action="{{route('admin.admin.index')}}" method="get">

                <div class="col-md-10 user_role">
                    <label class="form-label" for="key">نام یا شماره تماس یا ایمیل یا ID</label>
                    <input type="text" class="form-control" name="key" placeholder="دنبال چی میگردی؟">
                </div>
                <div class="col-md-2 mt-2 text-center">
                    <button class="btn btn-success">
                        <i class="fa-solid fa-magnifying-glass mx-2"></i>
                        جست و جو
                    </button>
                </div>
            </form>
        </div>
    </div>



    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست پرسنل</h5>
                @can('admin.create')
                    <div class="card-title-elements ms-auto">
                        <a href="{{route('admin.admin.create')}}" class="btn btn-primary">
                            <i class="fa fa-plus mx-2"></i>
                            افزودن همکار جدید
                        </a>
                    </div>
                @endcan
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>آواتار</th>
                        <th>نام</th>
                        @can('admin.index.table.mobile')
                            <th>شماره تماس</th>
                        @endcan
                        @can('admin.index.table.email')
                            <th>آدرس ایمیل</th>
                        @endcan
                        <th>نقش</th>


                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($admins as $admin)

                        <tr>
                            <td>{{$admin->id}}</td>
                            <td>
                                <img src="{{$admin->avatar()}}" class="img-fluid" width="50px">
                            </td>

                            <td>
                                {{$admin->fullname()}}
                            </td>
                            @can('admin.index.table.mobile')
                                <td>
                                    <a href="tel:{{$admin->mobile}}">{{$admin->mobile}}</a>
                                </td>
                            @endcan
                            @can('admin.index.table.email')
                                <td>
                                    {{$admin->email}}
                                </td>
                            @endcan
                            <td>
                                <div>
                                    @foreach($admin->roles()->get() as $role)
                                        <span
                                            class="badge bg-label-primary mx-1"> {{$role->persian_name}} </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                    <span class="badge bg-label-{{$admin->status()?'success':'danger'}} me-1">
                                        {{$admin->status()?'فعال':'غیرفعال'}}
                                    </span>
                            </td>

                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        @can('admin.edit')
                                            <a class="dropdown-item"
                                               href="{{route('admin.admin.edit', ['admin' => $admin->id])}}">
                                                <i class="fa-regular fa-user-pen mx-1"></i>
                                                ویرایش کاربر
                                            </a>
                                        @endcan
                                        @can('admin.edit')
                                            <a class="dropdown-item"
                                               href="{{route('admin.admin.password.edit', ['admin' => $admin->id])}}">
                                                <i class="fa-regular fa-key-skeleton mx-1"></i>
                                                ویرایش پسورد
                                            </a>
                                        @endcan
                                        @can('role.admin.edit')
                                            <a class="dropdown-item"
                                               href="{{route('admin.role.user.edit', ['admin' => $admin->id])}}">
                                                <i class="fa-regular fa-key mx-1"></i>
                                                ویرایش نقش کاربر
                                            </a>
                                        @endcan
                                        @can('admin.login-as-admin')
                                            <a class="dropdown-item"
                                               href="{{route('admin.admin.login_as_admin', ['admin' => $admin->id])}}">
                                                <i class="far fa-sign-in mx-1"></i>
                                                ورود به پنل
                                            </a>
                                        @endcan
                                        @can('session.index')
                                            <a class="dropdown-item"
                                               href="{{route('admin.session.index', ['admin' => $admin->id])}}">
                                                <i class="fa-regular fa-desktop mx-1"></i>
                                                نشست های فعال
                                            </a>
                                        @endcan
                                        @can('admin.toggle')
                                            <a class="dropdown-item"
                                               href="{{route('admin.admin.toggle', ['admin' => $admin->id])}}">
                                                @if($admin->is_active)
                                                    <i class="fa-regular fa-user-slash mx-1"></i>
                                                    مسدود سازی
                                                @else
                                                    <i class="fa fa-undo mx-1"></i>
                                                    فعالسازی مجدد
                                                @endif
                                            </a>
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
