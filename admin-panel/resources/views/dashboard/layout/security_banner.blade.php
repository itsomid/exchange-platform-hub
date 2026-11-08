@if ($requiresTwoFactor || $passwordDeadline)
    {{-- Same container as the page content, so the banners line up with the navbar and the cards. --}}
    <div class="{{ $container ?? 'container-fluid' }} container-p-y pb-0">
        @if ($requiresTwoFactor)
            <div class="security-banner twofa-banner" role="alert">
                <div class="security-banner__icon">
                    <i class="fa-solid fa-shield-exclamation"></i>
                </div>
                <div class="security-banner__text">
                    <strong>احراز هویت دو مرحله‌ای (2FA) فعال نیست</strong>
                    <span>برای استفاده از پنل باید در ابتدا 2FA را فعال کنید. تا آن زمان فقط به پروفایل خود دسترسی دارید.</span>
                </div>
                @unless (request()->routeIs('admin.profile.2fa.edit'))
                    <a href="{{ route('admin.profile.2fa.edit') }}" class="btn btn-sm security-banner__btn">
                        <i class="fa-regular fa-lock-keyhole me-1"></i>
                        فعال‌سازی 2FA
                    </a>
                @endunless
            </div>
        @endif

        @if ($passwordDeadline)
            @php
                $remaining = now()->diff($passwordDeadline);
                $daysLeft = $passwordOverdue ? 0 : $remaining->days;
                $urgent = ! $passwordOverdue && $daysLeft < 1;
            @endphp
            <div @class([
                'security-banner password-banner',
                'security-banner--urgent' => $urgent,
                'security-banner--danger' => $passwordOverdue,
                'mt-3' => $requiresTwoFactor,
            ]) role="alert">
                <div class="security-banner__icon">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div class="security-banner__text">
                    @if ($passwordOverdue)
                        <strong>مهلت تغییر گذرواژه تمام شده است</strong>
                        <span>برای استفاده از پنل باید گذرواژه خود را تغییر دهید. تا آن زمان فقط به پروفایل خود دسترسی دارید.</span>
                    @else
                        <strong>گذرواژه شما باید تغییر کند</strong>
                        <span>برای استفاده از پنل باید در اسرع وقت گذرواژه کاربری خود را عوض کنید. پس از پایان مهلت، دسترسی شما به پنل بسته می‌شود.</span>
                    @endif
                </div>
                @unless ($passwordOverdue)
                    <div class="security-banner__counter" title="زمان باقی‌مانده تا بسته‌شدن پنل">
                        @if ($daysLeft >= 1)
                            <b>{{ $daysLeft }}</b> روز
                            @if ($remaining->h > 0)
                                و <b>{{ $remaining->h }}</b> ساعت
                            @endif
                        @else
                            <b>{{ $remaining->h }}</b> ساعت و <b>{{ $remaining->i }}</b> دقیقه
                        @endif
                        <small>مانده</small>
                    </div>
                @endunless
                @unless (request()->routeIs('admin.profile.password.edit'))
                    <a href="{{ route('admin.profile.password.edit') }}" class="btn btn-sm security-banner__btn">
                        <i class="fa-regular fa-lock-keyhole me-1"></i>
                        تغییر گذرواژه
                    </a>
                @endunless
            </div>
        @endif
    </div>

    <style>
        .security-banner {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .85rem 1.25rem;
            background: linear-gradient(90deg, #ffe58f 0%, #ffc53d 100%);
            border: 1px solid #f0b429;
            border-radius: .375rem;
            color: #5c3d00;
            box-shadow: 0 .125rem .5rem rgba(230, 168, 0, .3);
        }

        .security-banner--urgent {
            background: linear-gradient(90deg, #ffd8a8 0%, #ff922b 100%);
            border-color: #e8590c;
            color: #5f2a00;
            box-shadow: 0 .125rem .5rem rgba(232, 89, 12, .3);
        }

        .security-banner--danger {
            background: linear-gradient(90deg, #ffc9c9 0%, #fa5252 100%);
            border-color: #e03131;
            color: #4a0000;
            box-shadow: 0 .125rem .5rem rgba(224, 49, 49, .3);
        }

        .security-banner__icon {
            flex: 0 0 auto;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, .55);
            font-size: 1.2rem;
        }

        .security-banner__text {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            gap: .15rem;
            line-height: 1.6;
        }

        .security-banner__counter {
            flex: 0 0 auto;
            padding: .35rem .85rem;
            border-radius: .375rem;
            background: rgba(255, 255, 255, .6);
            text-align: center;
            line-height: 1.5;
            white-space: nowrap;
        }

        .security-banner__counter b {
            font-size: 1.15rem;
        }

        .security-banner__counter small {
            display: block;
            opacity: .75;
        }

        .security-banner__btn {
            flex: 0 0 auto;
            background: #3d2900;
            color: #fff;
            border: 0;
            white-space: nowrap;
        }

        .security-banner__btn:hover {
            background: #241800;
            color: #fff;
        }

        @media (max-width: 575.98px) {
            .security-banner {
                flex-wrap: wrap;
            }
        }
    </style>
@endif
