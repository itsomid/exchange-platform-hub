<!DOCTYPE html>

<html
    lang="en"
    class="light-style customizer-hide"
    dir="rtl"
    data-theme="theme-default"
    data-template="horizontal-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport"  content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>
        @yield('title')
        |  بیتکس روم
    </title>

    @vite(['resources/assets/scss/admin/auth.scss'])
</head>
<body>
<!-- Content -->

    @yield('content')
<!-- / Content -->

<!-- Core JS -->
@vite(['resources/assets/js/main.js'])

</body>
</html>
