@php($themePreference = \App\Support\AdminTheme::preference())
@php($pageTitle = trim($__env->yieldContent('title', 'پنل مدیریت')))
@php($admin = auth()->user())
<nav
    class="layout-navbar {{ $container }} navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme admin-topbar"
    id="layout-navbar">
    <div class="admin-topbar__start">
        <a class="admin-topbar__icon layout-menu-toggle d-xl-none" href="javascript:void(0)" aria-label="باز کردن منو">
            <i class="fa-solid fa-bars"></i>
        </a>
        <div class="admin-topbar__title">
            <span class="admin-topbar__mark" aria-hidden="true"></span>
            <div class="admin-topbar__title-copy">
                <span class="admin-topbar__kicker">بیتکس روم</span>
                <p class="admin-topbar__heading" title="{{ $pageTitle }}">{{ $pageTitle }}</p>
            </div>
        </div>
    </div>

    <div class="navbar-nav-right admin-topbar__end" id="navbar-collapse">
        <ul class="navbar-nav admin-topbar__actions">
            @unless($panelLocked)
                <li class="nav-item dropdown-notifications navbar-dropdown dropdown">
                    <a class="admin-topbar__icon nav-link dropdown-toggle hide-arrow"
                       href="javascript:void(0);"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="outside"
                       aria-expanded="false"
                       aria-label="اعلان‌ها">
                        <i class="fa-regular fa-bell"></i>
                        @if($unreadCount > 0)
                            <span class="admin-topbar__count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end admin-topbar__menu admin-topbar__menu--notes">
                        <li class="admin-topbar__note-head">
                            <div class="admin-topbar__note-head-copy">
                                <strong>اعلان‌ها</strong>
                                @if($unreadCount > 0)
                                    <span>{{ $unreadCount }} خوانده‌نشده</span>
                                @else
                                    <span>مورد جدیدی نیست</span>
                                @endif
                            </div>
                            @if($unreadCount > 0)
                                <a href="{{ route('admin.admin.notifications.mark-all-read') }}"
                                   class="admin-topbar__icon admin-topbar__icon--ghost dropdown-notifications-all"
                                   title="تغییر همه به خوانده شده">
                                    <i class="fa-regular fa-envelope-open"></i>
                                </a>
                            @endif
                        </li>
                        <li class="dropdown-notifications-list scrollable-container">
                            <ul class="admin-topbar__notes">
                                @forelse($notifications as $notification)
                                    <li class="dropdown-notifications-item admin-topbar__note">
                                        <div class="admin-topbar__note-body">
                                            <div class="admin-topbar__note-top">
                                                <span class="admin-topbar__note-type">{{ \App\Enums\NotificationTypeEnum::getLabel($notification->type) }}</span>
                                                <time class="admin-topbar__note-time">{{ \App\Helpers\DateFormatter::ago($notification->created_at) }}</time>
                                            </div>
                                            <p class="admin-topbar__note-text">{{ $notification->data['message'] ?? '' }}</p>
                                        </div>
                                        <div class="dropdown-notifications-actions admin-topbar__note-actions">
                                            <a href="javascript:void(0)" class="dropdown-notifications-read" title="خوانده شد">
                                                @if($notification->read_at == null)
                                                    <span class="admin-topbar__dot"></span>
                                                @endif
                                            </a>
                                            <a href="javascript:void(0)" class="dropdown-notifications-archive" title="بستن">
                                                <i class="fa-regular fa-xmark"></i>
                                            </a>
                                        </div>
                                    </li>
                                @empty
                                    <li class="admin-topbar__empty">
                                        <i class="fa-regular fa-bell-slash"></i>
                                        <span>پیام تازه‌ای وجود ندارد</span>
                                    </li>
                                @endforelse
                            </ul>
                        </li>
                        <li class="admin-topbar__note-foot">
                            <a class="admin-topbar__menu-link" href="{{ route('admin.admin.notifications.index') }}">
                                <i class="fa-regular fa-list"></i>
                                <span>مشاهده تمام اعلان‌ها</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endunless

            <li class="nav-item dropdown-style-switcher dropdown">
                <a class="admin-topbar__icon nav-link dropdown-toggle hide-arrow"
                   href="javascript:void(0);"
                   data-bs-toggle="dropdown"
                   aria-label="تغییر پوسته">
                    <i @class([
                        'fa-regular',
                        'fa-sun' => $themePreference === \App\Support\AdminTheme::LIGHT,
                        'fa-moon' => $themePreference === \App\Support\AdminTheme::DARK,
                        'fa-desktop' => $themePreference === \App\Support\AdminTheme::SYSTEM,
                    ])></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-styles admin-topbar__menu admin-topbar__menu--theme">
                    <li>
                        <form action="{{ route('admin.set-theme') }}" method="POST">
                            @csrf
                            <input type="hidden" name="theme" value="light">
                            <button type="submit" @class(['dropdown-item', 'admin-topbar__menu-link', 'is-active' => $themePreference === \App\Support\AdminTheme::LIGHT]) data-theme="light">
                                <i class="fa-regular fa-sun"></i>
                                <span>روشن</span>
                            </button>
                        </form>
                    </li>
                    <li>
                        <form action="{{ route('admin.set-theme') }}" method="POST">
                            @csrf
                            <input type="hidden" name="theme" value="dark">
                            <button type="submit" @class(['dropdown-item', 'admin-topbar__menu-link', 'is-active' => $themePreference === \App\Support\AdminTheme::DARK]) data-theme="dark">
                                <i class="fa-regular fa-moon"></i>
                                <span>تاریک</span>
                            </button>
                        </form>
                    </li>
                    <li>
                        <form action="{{ route('admin.set-theme') }}" method="POST">
                            @csrf
                            <input type="hidden" name="theme" value="system">
                            <button type="submit" @class(['dropdown-item', 'admin-topbar__menu-link', 'is-active' => $themePreference === \App\Support\AdminTheme::SYSTEM]) data-theme="system">
                                <i class="fa-regular fa-desktop"></i>
                                <span>سیستم</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </li>

            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="admin-topbar__user nav-link dropdown-toggle hide-arrow"
                   href="javascript:void(0);"
                   data-bs-toggle="dropdown"
                   aria-label="حساب کاربری">
                    <span class="admin-topbar__avatar-wrap">
                        <img src="{{ $admin->avatar() }}" alt="" data-admin-avatar>
                    </span>
                    <span class="admin-topbar__user-meta">
                        <span class="admin-topbar__user-name">{{ $admin->fullname() }}</span>
                        <span class="admin-topbar__user-sub">{{ $admin->mobile }}</span>
                    </span>
                    <i class="fa-regular fa-chevron-down admin-topbar__chevron"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end admin-topbar__menu admin-topbar__menu--user">
                    <li class="admin-topbar__menu-head">
                        <span class="admin-topbar__avatar-wrap">
                            <img src="{{ $admin->avatar() }}" alt="" data-admin-avatar>
                        </span>
                        <span class="admin-topbar__user-meta">
                            <span class="admin-topbar__user-name">{{ $admin->fullname() }}</span>
                            <span class="admin-topbar__user-sub">{{ $admin->mobile }}</span>
                        </span>
                    </li>
                    <li>
                        <a class="dropdown-item admin-topbar__menu-link" href="{{ route('admin.profile.edit') }}">
                            <i class="fa-regular fa-user"></i>
                            <span>پروفایل</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item admin-topbar__menu-link" href="javascript:void(0);"
                           data-bs-toggle="modal" data-bs-target="#changeAvatarModal">
                            <i class="fa-regular fa-camera"></i>
                            <span>تغییر آواتار</span>
                        </a>
                    </li>
                    <li class="admin-topbar__menu-sep" aria-hidden="true"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="post" class="admin-topbar__menu-form">
                            @csrf
                            <button type="submit" class="dropdown-item admin-topbar__menu-link is-danger">
                                <i class="fa-regular fa-arrow-right-from-bracket"></i>
                                <span>خروج از سیستم</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
