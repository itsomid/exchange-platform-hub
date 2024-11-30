@extends('dashboard.layout.master')
@section('title', 'ویرایش پروفایل')
@section('content')
    <div class="row">
        <!-- User Card -->
        <div class="col-lg-4 order-1 order-md-0">

            <div class="card mb-6">
                <div class="card-body pt-12">
                    <div class="user-avatar-section">
                        <div class=" d-flex align-items-center flex-column">
                            <img class="img-fluid rounded mb-4" src="{{ asset('images/avatars/avatar.webp') }}"
                                 height="120" width="120" alt="User avatar"/>
                            <div class="user-info text-center">
                                <h5>{{$user->fullname()}}</h5>
                            </div>
                        </div>
                    </div>

                    <h5 class="pb-4 border-bottom mb-4">جزئیات</h5>
                    <div class="info-container">
                        <ul class="list-unstyled mb-6">
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">نام کاربری:</span>
                                <span>{{ $user->username}}</span>
                            </li>

                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">ایمیل:</span>
                                <span>{{$user->email}}</span>
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">وضعیت حساب:</span>
                                <span>{{$user->status}}</span>
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">شماره تماس:</span>
                                <span>{{$user->mobile}}</span>
                            </li>     <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">معرف:</span>
                                <span>{{$user->introducerReferral?->user->username}}</span>
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">وضعیت حساب</span>

                                @if($user->activeFinancialBlocks->isEmpty())
                                    <span class="badge bg-label-success align-self-baseline">بدون محدودیت</span>
                                @else
                                    <div class="text-end">
                                    @foreach($user->activeFinancialBlocks as $block)
                                        <span class="badge bg-label-danger ms-1 align-self-baseline">    {{\App\Enums\UserFinancialBlockAction::TYPE_LABEL[$block->action] }}</span>
                                    @endforeach
                                    </div>
                                @endif
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">احراز هویت دو مرحله ای:</span>
                                <span class="text-{{auth('admin')->user()->twoFAStatus()?'success':'danger'}}">{{auth('admin')->user()->twoFAStatus()?'فعال':'غیرفعال'}}</span>
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">تاریح ایجاد حساب:</span>
                                <span>{{\App\Helpers\DateFormatter::convertToPersianDate($user->created_at,'H:i:s %Y-%m-%d')}}</span>

                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">کشور:</span>
                                <span>{{$user->country}}</span>
                            </li>
                        </ul>
                        <div class="d-flex justify-content-center">

                            <a href="javascript:;" class="btn btn-label-danger suspend-user">تعلیق کاربر</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /User Card -->
        <!-- User Content -->
        <div class="col-lg-8 order-0 order-md-1">
            <!-- User Pills -->
            <ul class="nav nav-pills flex-column flex-md-row mb-4">
                <li class="nav-item">
                    <a class="nav-link @if(request()->route()->getName() == 'admin.user.edit') active @endif"
                       href="{{route('admin.user.edit',['user' => $user->id])}}">
                        <i class="fa-light fa-user-alt me-2"></i>
                        اطلاعات کاربری
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->route()->getName() == 'admin.user.security') active @endif"
                       href="{{route('admin.user.security',['user' => $user->id])}}">
                        <i class="fa-regular fa-shield-halved me-2"></i>
                        امنیت
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->route()->getName() == 'admin.user.financial-block.getBlocks') active @endif"
                       href="{{route('admin.user.financial-block.getBlocks',['user' => $user->id])}}">
                        <i class="fa-regular fa-file-chart-column me-2"></i>
                        دسترسی مالی
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link @if(request()->route()->getName() == 'admin.user.password.edit') active @endif"
                       href="{{route('admin.user.password.edit',['user' => $user->id])}}">
                        <i class="fa-regular fa-lock-alt me-2"></i>
                        ویرایش گذرواژه
                    </a>
                </li>

            </ul>
            <!--/ User Pills -->
            @yield('user-body')
        </div>
        <!--/ User Content -->
    </div>
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/clipboard/clipboard.js',
            'resources/assets/js/extended-ui-misc-clipboardjs.js'])
@endsection
