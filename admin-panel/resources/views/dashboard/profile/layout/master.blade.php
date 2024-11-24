@extends('dashboard.layout.master')
@section('title', 'ویرایش پروفایل')
@section('content')
    <div class="row">
        <!-- User Sidebar -->
        <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
            <!-- User Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="user-avatar-section">
                        <div class="d-flex align-items-center flex-column">
                            <img class="img-fluid rounded mb-3 pt-1 mt-4"
                                 src="{{auth()->user()->avatar()}}"
                                 height="100"
                                 width="100"
                                 alt="User avatar"/>
                            <div class="user-info text-center">
                                <h4 class="mb-2">{{auth()->user()->fullname()}}</h4>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 small text-uppercase text-muted">جزئیات</p>
                    <div class="info-container">
                        <ul class="list-unstyled">
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="fw-semibold ">شماره تماس:</span>
                                <span>{{auth()->user()->mobile}}</span>
                            </li>
                            <li class="mt-3 d-flex justify-content-between">
                                <span class="fw-semibold ">ایمیل:</span>
                                <span>{{auth()->user()->email}}</span>
                            </li>
                            <li class="mt-3 d-flex justify-content-between">
                                <span class="fw-semibold">نقش:</span>
                                @foreach(auth()->user()->roles()->get() as $role)
                                    <span
                                        class="badge bg-label-primary bg-glow "> {{$role->persian_name}} </span>
                                @endforeach
                            </li>
                            <li class="mt-3 d-flex justify-content-between">
                                <span class="fw-semibold">احراز هویت دو مرحله ای:</span>
                                <span class="badge bg-label-{{auth('admin')->user()->twoFAStatus()?'success':'danger'}}">{{auth('admin')->user()->twoFAStatus()?'فعال':'غیرفعال'}}</span>
                            </li>
                            <li class="mt-3 d-flex justify-content-between">
                                <span class="fw-semibold">وضعیت:</span>
                                <span class="badge bg-label-{{auth('admin')->user()->status()?'success':'danger'}}">{{auth('admin')->user()->status()?'فعال':'غیرفعال'}}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /User Card -->
        </div>
        <!--/ User Sidebar -->

        <!-- User Content -->
        <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
            <!-- User Pills -->
            <ul class="nav nav-pills flex-column flex-md-row mb-4">
                <li class="nav-item">
                    <a class="nav-link @if(request()->route()->getName() == 'admin.profile.edit') active @endif"
                       href="{{route('admin.profile.edit')}}">
                        <i class="ti ti-user-check ti-xs me-1"></i>
                        اطلاعات کاربری
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->route()->getName() == 'admin.profile.password.edit') active @endif"
                       href="{{route('admin.profile.password.edit')}}">
                        <i class="ti ti-lock ti-xs me-1"></i>
                        ویرایش گذرواژه
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->route()->getName() == 'admin.profile.2fa.edit') active @endif"
                       href="{{route('admin.profile.2fa.edit')}}">
                        <i class="ti ti-currency-dollar ti-xs me-1"></i>
                        احراز هویت دو مرحله ای
                    </a>
                </li>
            </ul>
            <!--/ User Pills -->
            @yield('profile-body')
        </div>
        <!--/ User Content -->
    </div>
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/clipboard/clipboard.js',
            'resources/assets/js/extended-ui-misc-clipboardjs.js'])
@endsection
