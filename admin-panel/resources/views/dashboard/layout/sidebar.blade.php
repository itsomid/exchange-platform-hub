<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="" class="app-brand-link layout-menu-toggle" draggable="false">
            <img  src="{{asset('images/logo/logo.svg')}}" class="img-fluid w-50" >
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            {{--            <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>--}}
            <i class="fa-solid fa-scrubber d-none d-xl-block align-middle "></i>
            {{--            <i class="fa-solid fa-scrubber"></i>--}}
            <i class="fa-light fa-xmark d-block d-xl-none ti-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <li class="menu-item @if(request()->is('admin')) active @endif">
            <a href="{{route('admin.dashboard')}}" class="menu-link">
                {{--                    <i class=" tf-icons ti ti-users"></i>--}}
                <i class="menu-icon  fa-regular fa-chart-pie-simple fa-sm"></i>
                <div data-i18n="Page 1">داشبورد</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">افراد و دپارتمان ها</span>
        </li>
        @can('admin.index')
            <li class="menu-item @if(request()->is('admin/admin*')) active @endif">
                <a href="{{route('admin.admin.index')}}" class="menu-link">
                    {{--                    <i class=" tf-icons ti ti-users"></i>--}}

                    <i class="menu-icon fa-solid fa-user-tie-hair fa-lg"></i>
                    <div >مدیریت همکاران</div>
                </a>
            </li>
        @endcan

        @can('user.index')
            <li class="menu-item @if(request()->is('admin/users*')) active @endif">
                <a href="{{route('admin.user.index')}}" class="menu-link">
                    <i class="menu-icon fa-light fa-users fa-lg"></i>
                    <div >مدیریت کاربران</div>
                </a>
            </li>
        @endcan
        @can('user.index')
            <li class="menu-item @if(request()->is('admin/inquiry*')) active @endif">
                <a disabled="" href="{{route('admin.inquiry.index')}}" class="menu-link">
                    <i class="menu-icon fa-light fa-screen-users fa-lg"></i>
                    <div >استعلام کاربر</div>
                </a>
            </li>
        @endcan


        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">بخش مدیریت فروش</span>
        </li>
        @can('referral_code.index')
            <li class="menu-item @if(request()->is('admin/referral-codes*')) active @endif">
                <a href="{{route('admin.referral_code.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-user-tag fa-sm"></i>
                    <div data-i18n="Page 1">کدهای معرف</div>
                </a>
            </li>
        @endcan
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">مالی و خرید ها</span>
        </li>
        @can('order.index')
            <li class="menu-item @if(request()->is('admin/orders*')) active @endif">
                <a href="" class="menu-link">
                    <i class="menu-icon fa-regular fa-chart-candlestick"></i>
                    <div data-i18n="Page 1">لیست سفارشها</div>
                </a>
            </li>
        @endcan
        @can('transaction.index')
            <li class="menu-item @if(request()->is('admin/transaction*')) active @endif">
                <a href="" class="menu-link">
                    <i class="menu-icon fa-regular fa-money-from-bracket"></i>
                    <div data-i18n="Page 1">لیست تراکنش ها</div>
                </a>
            </li>
        @endcan
        @canany(['setting.int.index', 'setting.ext.index'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">مدیریت Exchange</span>
            </li>
            <li class="menu-item @if(request()->is('admin/exchange/currencies*')) active @endif">
                <a href="{{route('admin.currency.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-circle-dollar"></i>
                    <div data-i18n="Page 1">Currency</div>
                </a>
            </li>
            <li class="menu-item @if(request()->is('admin/transaction*')) active @endif">
                <a href="" class="menu-link">
                    <i class="menu-icon fa-regular fa-display-chart-up-circle-dollar"></i>
                    <div data-i18n="Page 1">Market</div>
                </a>
            </li>
        @endcanany
        @canany(['setting.int.index', 'setting.ext.index'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">مدیریت سیستم</span>
            </li>

            @can('role.index')
                <li class="menu-item @if(request()->is(['admin/roles*','admin/permissions*'])) active open @endif">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon fa-regular fa-user-group fa-sm"></i>
                        <div>نقش ها و مجوزها</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item @if(request()->is('admin/roles*')) active @endif">
                            <a href="{{route('admin.role.index')}}" class="menu-link">
                                <i class="menu-icon fa-light fa-user fa-sm"></i>
                                <div data-i18n="Page 1"> نقش ها</div>
                            </a>
                        </li>
                        <li class="menu-item @if(request()->is('admin/permissions*')) active @endif">
                            <a href="{{route('admin.permission.index')}}" class="menu-link">
                                <i class="menu-icon fa-light fa-key fa-sm"></i>
                                <div data-i18n="Page 1"> مجوزها</div>
                            </a>
                        </li>
                    </ul>

                </li>
            @endcan
            <li class="menu-item @if(request()->is('admin/setting*')) active @endif">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon fa-regular fa-cog fa-sm"></i>
                    <div> پیکربندی سیستم</div>
                </a>
                <ul class="menu-sub">
                    @can('setting.int.index')
                        <li class="menu-item">
                            <a href="{{route('admin.internal.setting.index')}}" class="menu-link">
                                <div>تنظیمات داخلی</div>
                            </a>
                        </li>
                    @endcan
                    @can('setting.ext.index')
                        <li class="menu-item">
                            <a href="{{route('admin.external-setting.index')}}" class="menu-link">
                                <div>تنظیمات خارجی</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
            <li class="menu-item @if(request()->route()->getName() == 'telescope') active @endif">
                <a href="{{route('telescope')}}" class="menu-link">
                    <i class="menu-icon fa-light fa-telescope fa-sm"></i>
                    <div data-i18n="Page 1"> تلسکوپ</div>
                </a>
            </li>

            <li class="menu-item @if(request()->is('/pulse*')) active @endif">
                <a href="{{url('./pulse')}}" class="menu-link">
                    <i class="menu-icon fa-regular  fa-monitor-heart-rate fa-sm"></i>
                    <div data-i18n="Page 1"> Pulse</div>
                </a>
            </li>

            <li class="menu-item @if(request()->is('/log-viewer*')) active @endif">
                <a href="{{url('./log-viewer')}}" class="menu-link">
                    <i class="menu-icon  fa-regular  fa-circle-exclamation fa-sm"></i>
                    <div data-i18n="Page 1"> Logs and Errors</div>
                </a>
            </li>
        @endcan


    </ul>
</aside>
