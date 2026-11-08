<!DOCTYPE html>
@php
    $themePreference = \App\Support\AdminTheme::preference();
    $configData['contentLayout'] = 'wide'; //'wide','compact'
    $container = $configData['contentLayout'] === 'compact' ? 'container-xxl' : 'container-fluid';
    $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';
    $navbarType = 'layout-navbar-fixed'; // "layout-navbar-fixed" : "";
@endphp
<html lang="en"
    @class([
        'light-style' => $themePreference === \App\Support\AdminTheme::LIGHT,
        'dark-style' => $themePreference === \App\Support\AdminTheme::DARK,
        $contentLayout,
        $navbarType,
        'layout-menu-fixed',
    ])
    dir="rtl" data-theme="theme-default" data-theme-preference="{{ $themePreference }}"
    data-assets-path="{{ asset('/assets') . '/' }}"
    data-template="vertical-menu-template-no-customizer-starter">

<head>
    <meta charset="utf-8" />
    <script>
        (function () {
            var preference = @json($themePreference);
            var media = window.matchMedia('(prefers-color-scheme: dark)');

            function resolved() {
                if (preference === 'dark') return 'dark';
                if (preference === 'light') return 'light';
                return media.matches ? 'dark' : 'light';
            }

            function apply() {
                var mode = resolved();
                var root = document.documentElement;
                root.classList.remove('light-style', 'dark-style');
                root.classList.add(mode + '-style');
                root.setAttribute('data-resolved-theme', mode);
                root.style.colorScheme = mode;
            }

            apply();

            if (preference === 'system' && media.addEventListener) {
                media.addEventListener('change', apply);
            }
        })();
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>
        بیتکس روم -
        @yield('title')
    </title>

    <!-- Favicon -->
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/favicon.ico') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon/android-chrome-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/favicon/android-chrome-512x512.png') }}">
    <link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}">
    <!-- core css -->

    <x-admin-theme-style
        light="resources/assets/scss/admin/app.scss"
        dark="resources/assets/scss/admin/app-dark.scss"
    />
    <link rel="stylesheet" href="{{ asset('css/admin-topbar.css') }}?v={{ filemtime(public_path('css/admin-topbar.css')) }}">
    @vite(['resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss'])
    @yield('vendor-style')

    {{-- Reverb public config: browser connects here. Set REVERB_PUBLIC_* in .env per environment. --}}
    <script>
        window.__reverbConfig = {
            key: '{{ config("broadcasting.connections.reverb.key") }}',
            host: '{{ config("broadcasting.connections.reverb.public.host") }}',
            port: '{{ config("broadcasting.connections.reverb.public.port") }}',
            scheme: '{{ config("broadcasting.connections.reverb.public.scheme") }}',
        };
    </script>

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
                @include('dashboard.layout.navbar', ['container' => $container])
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    @include('dashboard.layout.security_banner')
                    <!-- Content -->
                    <div class="{{ $container }} flex-grow-1 container-p-y" id="app">
                        @if (session()->has('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
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
    @include('dashboard.layout.avatar_modal')
    <!-- Core JS -->
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    @include('dashboard.layout.sections.scripts')

    @include('dashboard.layout.vendor.flash_message')

    @if (session()->has('super_admin'))
        @include('dashboard.layout.login_as_admin')
    @endif

</body>

</html>