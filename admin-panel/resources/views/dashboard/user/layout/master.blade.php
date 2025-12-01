@extends('dashboard.layout.master')
@section('title', 'ویرایش پروفایل')
@section('content')
    <div class="row">
        <!-- User Card -->
        <div class="col-lg-3 order-1 order-md-0">

            <x-user-details :user="$user" />
        </div>
        <!-- /User Card -->
        <!-- User Content -->
        <div class="col-lg-9 order-0 order-md-1">
            <!-- User Pills -->
            <ul class="nav nav-pills flex-column flex-md-row mb-4">
                <li class="nav-item">
                    <a class="nav-link @if (request()->route()->getName() == 'admin.user.edit') active @endif"
                        href="{{ route('admin.user.edit', ['user' => $user->id]) }}">
                        <i class="fa-light fa-user-alt me-2"></i>
                        اطلاعات کاربری
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if (request()->route()->getName() == 'admin.user.security') active @endif"
                        href="{{ route('admin.user.security', ['user' => $user->id]) }}">
                        <i class="fa-regular fa-shield-halved me-2"></i>
                        امنیت
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if (request()->route()->getName() == 'admin.user.financial-block.getBlocks') active @endif"
                        href="{{ route('admin.user.financial-block.getBlocks', ['user' => $user->id]) }}">
                        <i class="fa-regular fa-file-chart-column me-2"></i>
                        محدودیت های مالی
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link @if (request()->route()->getName() == 'admin.user.password.edit') active @endif"
                        href="{{ route('admin.user.password.edit', ['user' => $user->id]) }}">
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
    @vite(['resources/assets/vendor/libs/clipboard/clipboard.js', 'resources/assets/js/extended-ui-misc-clipboardjs.js'])
@endsection
