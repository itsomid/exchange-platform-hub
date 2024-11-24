<nav
    class="layout-navbar {{$container}} navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            {{--            <i class="ti ti-menu-2 ti-sm"></i>--}}
            <i class="fa-solid fa-bars"></i>

        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <div class="navbar-nav align-items-center">
            <a class="nav-link style-switcher-toggle hide-arrow" href="javascript:void(0);">
                <i class="ti ti-sm"></i>
            </a>
        </div>

        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- Style Switcher -->
            <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i @class(['fa-light', 'fa-xl' ,'fa-moon-stars' => session('theme') === 'dark','fa-sun-bright' => session('theme','light') === 'light'])></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                    <li>
                        <form action="{{route('admin.set-theme')}}" method="POST">
                            @csrf
                            <input type="hidden" name="theme" value="light">
                            <button type="submit" class="dropdown-item">
                                <span class="align-middle"> <i class="fa-regular fa-brightness-low  me-2"></i>روشن</span>
                            </button>
                        </form>
                    </li>
                    <li>
                        <form action="{{route('admin.set-theme')}}" method="POST">
                            @csrf
                            <input type="hidden" name="theme" value="dark">
                            <button type="submit" class="dropdown-item" data-theme="dark">
                                <span class="align-middle">
                                    <i class="fa-regular fa-moon-stars fa-lg me-2"></i>
                                    تاریک</span>
                            </button>
                        </form>

                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                            <span class="align-middle"><i class="fa-light fa-desktop fa-lg me-2"></i>سیستم</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ Style Switcher -->
            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{auth()->user()->avatar()}}" alt class="h-auto rounded-circle"/>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="{{auth()->user()->avatar()}}" alt class="h-auto rounded-circle"/>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block">{{auth()->user()->fullname()}}</span>
                                    <small class="text-muted">{{auth()->user()->mobile}}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex justify-content-between" href="{{route('admin.profile.edit')}}">
                            <span class="align-middle">پروفایل</span>
                            <i class="ti ti-user-check me-2 ti-sm"></i>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item p-0">
                            <form action="{{route('logout')}}" method="post" class="w-100">@csrf
                                <button type="submit" class="btn d-flex w-100 justify-content-between text-danger">
                                    <span class="align-middle">خروج از سیستم</span>
                                    <i class="ti ti-logout me-2 ti-sm"></i>
                                </button>
                            </form>
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
