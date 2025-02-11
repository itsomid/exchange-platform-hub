<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="" class="app-brand-link layout-menu-toggle" draggable="false">
            <img src="{{asset('images/logo/logo.svg')}}" class="img-fluid w-50">
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
                <div>داشبورد</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">افراد و دپارتمان ها</span>
        </li>
        @can('admin.index')
            <li class="menu-item @if(request()->is('admin/admins')) active @endif">
                <a href="{{route('admin.admin.index')}}" class="menu-link">
                    {{--                    <i class=" tf-icons ti ti-users"></i>--}}

                    <i class="menu-icon fa-solid fa-user-tie-hair fa-lg"></i>
                    <div>مدیریت همکاران</div>
                </a>
            </li>
        @endcan

        @can('user.index')
            <li class="menu-item @if(request()->is('admin/users*') && !request()->is('admin/users/financial-status*')) active @endif">
                <a href="{{route('admin.user.index')}}" class="menu-link">
                    <i class="menu-icon fa-light fa-users fa-lg"></i>
                    <div>مدیریت کاربران</div>
                </a>
            </li>
        @endcan
        @can('user.index')
            <li class="menu-item @if(request()->is('admin/users/financial-status*')) active @endif">
                <a href="{{route('admin.user.financial-status')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-user-lock fa-lg"></i>
                    <div>کاربران مسدود شده</div>
                </a>
            </li>
        @endcan
        @can('user.index')
            <li class="menu-item @if(request()->is('admin/inquiry*')) active @endif">
                <a disabled="" href="{{route('admin.inquiry.index')}}" class="menu-link">
                    <i class="menu-icon fa-light fa-user-alt fa-lg"></i>
                    <div>استعلام کاربر</div>
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
                    <div>کدهای معرف</div>
                </a>
            </li>
        @endcan
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">مالی و خرید ها</span>
        </li>
        {{--        @can('order.index')--}}
        {{--            <li class="menu-item @if(request()->is('admin/exchange/market123123*')) active @endif">--}}
        {{--                <a href="{{route('admin.wallet.index')}}" class="menu-link">--}}
        {{--                    <i class="menu-icon fa-regular fa-wallet"></i>--}}
        {{--                    <div>مدیریت کیف پول ها</div>--}}
        {{--                </a>--}}
        {{--            </li>--}}
        {{--        @endcan--}}
        @can('transaction')
            <li class="menu-item @if(request()->is('admin/transactions*')) active @endif">
                <a href="{{route('admin.transaction.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-chart-candlestick"></i>
                    <div>لیست تراکنش ها</div>
                </a>
            </li>
        @endcan
        @can('otc_order')
            <li class="menu-item @if(request()->is('admin/otc*')) active @endif">
                <a href="{{route('admin.otc_orders.index')}}" class="menu-link">
                    <i class="menu-icon  fa-regular fa-swap"></i>

                    <div>معاملات OTC</div>
                </a>
            </li>
        @endcan
        @can('deposit')
            <li class="menu-item @if(request()->is('admin/deposits*')) active @endif">
                <a href="{{route('admin.deposit.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-square-arrow-down-left"></i>
                    <div>لیست واریزی ها</div>
                </a>
            </li>
        @endcan
        @can('withdrawal')
            <li class="menu-item @if(request()->is('admin/withdrawal*')) active @endif">
                <a href="{{route('admin.withdrawal.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-square-arrow-up-right"></i>
                    <div>لیست برداشت ها</div>
                </a>
            </li>
        @endcan

        @canany(['currency', 'market','ref-exchanges'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">مدیریت Exchange</span>
            </li>
            <li class="menu-item @if(request()->is('admin/exchange/currencies*')) active @endif">
                <a href="{{route('admin.currency.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-circle-dollar"></i>
                    <div>Currency</div>
                </a>
            </li>
            <li class="menu-item @if(request()->is('admin/exchange/market*')) active @endif">
                <a href="{{route('admin.market.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-display-chart-up-circle-dollar"></i>
                    <div>Market</div>
                </a>
            </li>
            <li class="menu-item @if(request()->is('admin/exchange/ref-exchanges*')) active @endif">
                <a href="{{route('admin.exchange.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-display-chart-up-circle-dollar"></i>
                    <div>مدیریت صرافی های مرجع</div>
                </a>
            </li>
        @endcanany
        @can(['support'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">بخش ارتباط با کاربر</span>
            </li>
            <li class="menu-item @if(request()->is('admin/tickets*')) active open @endif">
                <a  href="javascript:void(0);" class="menu-link menu-toggle">
                    <div class="d-inline-flex position-relative">
                        <i class="menu-icon  fa-regular fa-headset "></i>

                        <span class="badge rounded-pill bg-danger badge-dot badge-notifications badge-bling" ></span>
                    </div>

                    <div>مدیریت تیکت ها</div>

                </a>
                <ul class="menu-sub">
                    <li class="menu-item @if(request()->is('admin/tickets?status=pending*')) active @endif">
                        <a href="{{route('admin.role.index')}}" class="menu-link">
                            <i class="menu-icon fa-light fa-user fa-sm"></i>
                            <div>در انتظار پاسخ</div>
                        </a>
                    </li>
                    <li class="menu-item @if(request()->is('admin/tickets/*')) active @endif">
                        <a href="{{route('admin.permission.index')}}" class="menu-link">
                            <i class="menu-icon fa-light fa-key fa-sm"></i>
                            <div>پاسخ داده شده</div>
                        </a>
                    </li>
                    <li class="menu-item @if(request()->is('admin/tickets*')) active @endif">
                        <a href="{{route('admin.tickets.index')}}" class="menu-link">
                            <i class="menu-icon fa-light fa-ticket"></i>
                            <div>همه تیکت ها</div>
                        </a>
                    </li>
                </ul>
            </li>
        @endcanany
        @can(['wallet'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">مدیریت کیف پول‌ها</span>
            </li>
            <li class="menu-item @if(request()->is('admin/wallets')) active @endif">
                <a href="{{route('admin.wallet')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-wallet"></i>
                    <div>کیف پول های صرافی</div>
                </a>
            </li>
            <li class="menu-item @if(request()->is('admin/wallets/assets-gathering-to-hd-wallet*')) active @endif">
                <a href="{{route('admin.wallets.assets-gathering-to-hd-wallet.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-wallet"></i>
                    <div>تجمیع دارایی در HD Wallet</div>
                </a>
            </li>
        @endcanany
        @canany(['report'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">گزارشات</span>
            </li>
            <li class="menu-item @if(request()->is('admin/report/deposit*')) active @endif">
                <a href="{{route('admin.report.deposit')}}" class="menu-link">
                    <i class="menu-icon fa-solid fa-chart-line-up"></i>
                    <div>گزارش واریز</div>
                </a>
            </li>
            <li class="menu-item @if(request()->is('admin/report/withdrawal*')) active @endif">
                <a href="{{route('admin.report.withdrawal')}}" class="menu-link">
                    <i class="menu-icon fa-solid fa-chart-line-down"></i>

                    <div>گزارش برداشت</div>
                </a>
            </li>
            <li class="menu-item @if(request()->is('admin/report/user-registration-report*')) active @endif">
                <a href="{{route('admin.report.getUserRegistrationState')}}" class="menu-link">
                    <i class="menu-icon fa-solid fa-chart-user"></i>
                    <div>گزارش ثبت نام کاربران</div>
                </a>
            </li>

        @endcanany
        @canany(['setting.int.index', 'setting.ext.index'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">مدیریت سیستم</span>
            </li>

            <li class="menu-item @if(request()->is('admin/admins/notification*')) active @endif">
                <a href="{{route('admin.admin.notifications.index')}}" class="menu-link">
                    <i class="menu-icon fa-regular fa-bell"></i>
                    <div>اعلان های مدیریت</div>
                    @if($unreadCount > 0)
                        <div class="badge bg-danger rounded-pill ms-auto">{{$unreadCount}}</div>
                    @endif

                </a>
            </li>

            @can('roles.permissions')
                <li class="menu-item @if(request()->is(['admin/roles*','admin/permissions*'])) active open @endif">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon fa-regular fa-user-group fa-sm"></i>
                        <div>نقش ها و مجوزها</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item @if(request()->is('admin/roles*')) active @endif">
                            <a href="{{route('admin.role.index')}}" class="menu-link">
                                <i class="menu-icon fa-light fa-user fa-sm"></i>
                                <div>نقش ها</div>
                            </a>
                        </li>
                        <li class="menu-item @if(request()->is('admin/permissions*')) active @endif">
                            <a href="{{route('admin.permission.index')}}" class="menu-link">
                                <i class="menu-icon fa-light fa-key fa-sm"></i>
                                <div>مجوزها</div>
                            </a>
                        </li>
                    </ul>

                </li>
            @endcan
            <li class="menu-item @if(request()->is('admin/internal-settings*')) active open @endif">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon fa-regular fa-cog fa-sm"></i>
                    <div> پیکربندی سیستم</div>
                </a>
                <ul class="menu-sub">
                    @can('setting.int.index')
                        <li class="menu-item  @if(request()->is('admin/internal-settings*')) active @endif">
                            <a href="{{route('admin.internal.setting.index')}}" class="menu-link">
                                <div>تنظیمات داخلی</div>
                            </a>
                        </li>
                    @endcan
                    @can('setting.ext.index')
                        <li class="menu-item  @if(request()->is('admin/external-settings*')) active @endif">
                            <a href="{{route('admin.external-setting.index')}}" class="menu-link">
                                <div>تنظیمات خارجی</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcan
        @can('view-logs')
            <li class="menu-item @if(request()->route()->getName() == 'telescope') active @endif">
                <a href="{{route('telescope')}}" class="menu-link">
                    <i class="menu-icon fa-light fa-telescope fa-sm"></i>
                    <div> تلسکوپ</div>
                </a>
            </li>

            <li class="menu-item @if(request()->is('/pulse*')) active @endif">
                <a href="{{url('./pulse')}}" class="menu-link">
                    <i class="menu-icon fa-regular  fa-monitor-heart-rate fa-sm"></i>
                    <div> Pulse</div>
                </a>
            </li>

            <li class="menu-item @if(request()->is('/log-viewer*')) active @endif">
                <a href="{{url('./log-viewer')}}" class="menu-link">
                    <i class="menu-icon  fa-regular  fa-circle-exclamation fa-sm"></i>
                    <div> Logs and Errors</div>
                </a>
            </li>
        @endcan


    </ul>
</aside>


