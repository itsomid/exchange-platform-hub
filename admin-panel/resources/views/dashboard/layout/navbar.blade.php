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
        @if(!auth()->user()->two_factor_secret && app()->environment() == 'production')
            <div class="navbar-nav align-items-center">
                <a class="nav-link style-switcher-toggle hide-arrow" href="{{route('admin.profile.2fa.edit')}}">
                    ❗⛔ برای استفاده از پنل لطفا نسبت به فعالسازی تایید دو مرحله ای اقدام کنید ⛔❗
                </a>
            </div>
        @endif
        <ul class="navbar-nav flex-row align-items-center ms-auto">

            <!-- Style Switcher -->
            <!-- Notification -->
            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                   href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                   aria-expanded="false">
              <span class="">
                <i class="fa-regular fa-bell fa-xl"></i>
                  <span class="badge rounded-pill bg-danger text-white badge-notifications">{{$unreadCount}}</span>

              </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-0">
                    <li class="dropdown-menu-header border-bottom">
                        <div class="dropdown-header d-flex align-items-center py-3">
                            <h6 class="mb-0 me-auto">اعلان‌ها</h6>
                            <div class="d-flex align-items-center h6 mb-0">
                                <span class="badge bg-label-primary me-2">{{$unreadCount}} جدید </span>
                                <a href="{{route('admin.admin.notifications.mark-all-read')}}"
                                   class="btn btn-text-secondary rounded-pill btn-icon dropdown-notifications-all"
                                   data-bs-toggle="tooltip" data-bs-placement="top" title="تغییر همه به خوانده شده">
                                    <i class="fa-regular fa-envelope-open text-heading"></i>
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="dropdown-notifications-list scrollable-container">
                        <ul class="list-group list-group-flush">
                            @forelse($notifications as $notification)
                                <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <h6 class="small mb-1"><span
                                                        class="badge bg-label-primary">{{ \App\Enums\NotificationTypeEnum::getLabel($notification->type) }}</span>
                                            </h6>
                                            <small
                                                    class="mb-1 d-block text-body">{{ $notification->data['message'] }}</small>
                                            <small
                                                    class="text-muted">{{\App\Helpers\DateFormatter::ago($notification->created_at)}}</small>
                                        </div>
                                        <div class="flex-shrink-0 dropdown-notifications-actions">
                                            <a href="javascript:void(0)" class="dropdown-notifications-read">
                                                @if ($notification->read_at == null)
                                                    <span class="badge badge-dot"></span>
                                                @endif
                                            </a>
                                            <a href="javascript:void(0)" class="dropdown-notifications-archive">
                                                <span class="fa-regular fa-x"></span>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @empty

                                <div class="my-4 mx-auto">پیام تازه ای وجود ندارد</div>

                            @endforelse

                        </ul>
                    </li>
                    <li class="border-top">
                        <div class="d-grid p-4">
                            <a class="btn btn-primary btn-sm d-flex"
                               href="{{route('admin.admin.notifications.index')}}">
                                <small class="align-middle">مشاهده تمام اعلان ها</small>
                            </a>
                        </div>
                    </li>
                </ul>
            </li>
            <!--/ Notification -->
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
                                <span class="align-middle"> <i
                                            class="fa-regular fa-brightness-low  me-2"></i>روشن</span>
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
