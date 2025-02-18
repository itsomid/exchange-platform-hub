{{--@vite(['resources/js/app.js'])--}}
@vite([
    'resources/assets/vendor/libs/jquery/jquery.js',
    'resources/assets/vendor/libs/popper/popper.js',
    'resources/assets/vendor/js/bootstrap.js',
    'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
    'resources/assets/vendor/libs/toastify/toastify.js',
    'resources/js/app.js'
])

@vite(['resources/assets/js/main.js'])
@yield('vendor-script')
@stack('scripts')
