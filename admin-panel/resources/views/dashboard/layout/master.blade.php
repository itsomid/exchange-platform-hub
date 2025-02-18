<!DOCTYPE html>
@php
    $configData['styleOpt'] = session('theme', 'light');
    $configData['contentLayout'] = 'wide'  ;//'wide','compact'
    $container = ($configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
    $contentLayout = (isset($container) ? (($container === 'container-xxl') ? "layout-compact" : "layout-wide") : "");
    $navbarType  = "layout-navbar-fixed" // "layout-navbar-fixed" : "";
@endphp
<html
    lang="en"
    class="{{$configData['styleOpt']}}-style {{$contentLayout}} {{$navbarType}} layout-menu-fixed"
    dir="rtl"
    data-theme="theme-default"
    data-assets-path="{{ asset('/assets') . '/' }}"
    data-template="vertical-menu-template-no-customizer-starter">
<head>
    <meta charset="utf-8"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"/>
    <title>
        پنل بیتکس روم -
        @yield('title')
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{asset('images/favicon-32x32.png')}}"/>
    <!-- core css -->

    @vite(['resources/assets/scss/admin/app'.($configData['styleOpt'] !== 'light' ? '-' . $configData['styleOpt'] : '').'.scss'])
    @vite(['resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss'])
    @yield('vendor-style')

</head>

<body>
<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <!-- Menu -->
        @include('dashboard.layout.sidebar')
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
            <!-- Navbar -->
            @include('dashboard.layout.navbar', ['container' => $container,])
            <!-- / Navbar -->

            <!-- Content wrapper -->
            <div class="content-wrapper">
                <!-- Content -->
                <div class="{{$container}} flex-grow-1 container-p-y" id="app">
                    @if(session()->has('success'))
                        <div class="alert alert-success" role="alert">
                            {{session('success')}}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @yield('content')
                </div>
                <!-- / Content -->

                <!-- Footer -->
                @include('dashboard.layout.footer')
                <!-- / Footer -->

                <div class="content-backdrop fade"></div>
            </div>
            <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
</div>
<!-- / Layout wrapper -->
<!-- Core JS -->
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
@include('dashboard.layout.sections.scripts')

@include('dashboard.layout.vendor.flash_message')

@if(session()->has('super_admin'))
    @include('dashboard.layout.login_as_admin')
@endif

</body>
</html>










