{{-- @vite(['resources/js/app.js']) --}}
@vite(['resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'resources/assets/vendor/libs/toastify/toastify.js', 'resources/js/app.js'])

@vite(['resources/assets/js/main.js'])
@vite(['resources/assets/js/animated-tooltip.js'])
@yield('vendor-script')
@stack('scripts')
