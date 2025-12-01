<div class="card mb-6">
    <div class="card-body pt-12">
        <div class="user-avatar-section">
            <div class=" d-flex align-items-center flex-column">
                <img class="img-fluid rounded mb-4" src="{{ asset('images/avatars/avatar.webp') }}" height="120"
                    width="120" alt="User avatar" />
                <div class="user-info text-center">
                    <h5>{{ $user->fullname() }}</h5>
                </div>
            </div>
        </div>

        <h5 class="pb-4 border-bottom mb-4">جزئیات</h5>
        <div class="info-container">
            <ul class="list-unstyled mb-6">
                <li class="mt-2 d-flex justify-content-between">
                    <span class="h6">شناسه کاربری:</span>
                    <span class="font-number">#{{ $user->id }}</span>
                </li>
                <li class="mt-2 d-flex justify-content-between">
                    <span class="h6">نام کاربری:</span>
                    <span>{{ $user->username }}</span>
                </li>

                <li class="mt-2 d-flex justify-content-between">
                    <span class="h6">ایمیل:</span>
                    <span>{{ $user->email }}</span>
                </li>
                <li class="mt-2 d-flex justify-content-between ali">
                    <span class="h6">وضعیت پنل کاربری:</span>
                    <span
                        class="badge bg-label-{{ $user->status->color() }} align-self-baseline">{{ $user->status->label() }}</span>
                </li>
                <li class="mt-2 d-flex justify-content-between">
                    <span class="h6">شماره تماس:</span>
                    <span>{{ $user->mobile }}</span>
                </li>
                <li class="mt-2 d-flex justify-content-between">
                    <span class="h6">معرف:</span>
                    @if ($user->introducerReferral)
                        <a class="btn btn-primary font-number p-1" data-bs-html='true' data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-custom-class="tooltip-dark"
                            title="<span class='fw-medium'>نام:</span>
                                                    {{ $user->introducerReferral->user->fullname() }}</span>
                                                    <br> <span class='fw-medium'>شناسه کاربری:</span>
                                                    <span class='fw-medium font-monospace'>({{ $user->introducerReferral->user->id }}#)</span>">
                            <span>{{ $user->introducerReferral?->user->username }}</span>
                        </a>
                    @else
                        <span>بدون معرف</span>
                    @endif

                </li>
                <li class="mt-3 d-flex justify-content-between">
                    <span class="h6">محدودیت‌های حساب:</span>

                    @if ($user->activeFinancialBlocks->isEmpty())
                        <span class="badge bg-label-success align-self-baseline">بدون محدودیت</span>
                    @else
                        <div class="text-end">
                            @foreach ($user->activeFinancialBlocks as $block)
                                <span
                                    class="badge bg-label-danger ms-1 align-self-baseline">{{ $block->action->label() }}</span>
                            @endforeach
                        </div>
                    @endif
                </li>
                <li class="mt-2 d-flex justify-content-between">
                    <span class="h6">احراز هویت دو مرحله ای:</span>
                    <span
                        class="badge bg-label-{{ $user->twoFAStatus() ? 'success' : 'danger' }} align-self-baseline">{{ $user->twoFAStatus() ? 'فعال' : 'غیرفعال' }}</span>
                </li>
                <li class="mt-2 d-flex justify-content-between">
                    <span class="h6">تاریخ ایجاد حساب:</span>
                    <span>{{ \App\Helpers\DateFormatter::convertToPersianDate($user->created_at, '%d %B %Y - H:i:s') }}</span>

                </li>
                <li class="mt-2 d-flex justify-content-between">
                    <span class="h6">آخرین فعالیت:</span>
                    @if ($user->latestActiveToken)
                        {{ \App\Helpers\DateFormatter::convertToPersianDate($user->latestActiveToken->last_used_at, 'H:i:s %Y/%m/%d') }}
                    @else
                        <span>بدون فعالیت</span>
                    @endif
                </li>
                <li class="mt-2 d-flex justify-content-between">
                    <span class="h6">مکان:</span>
                    @if ($user->latestActiveToken)
                        @php
                            $locationData = $user->latestActiveToken->ip 
                                ? App\Helpers\LocationFinder::getLocationData($user->latestActiveToken->ip) 
                                : ['country' => 'نامشخص', 'city' => '-', 'country_code' => ''];
                        @endphp
                        <div class="d-flex align-items-center">
                   
                            <div class="text-end">
                                <div class="fw-semibold">@if ($locationData['country_code'])
                                <span class="fi fi-{{ $locationData['country_code'] }}" style="font-size: 0.8rem; margin-left: 8px;"></span>
                            @endif{{ $locationData['country'] }}</div>
                                <small class="text-muted">{{ $locationData['city'] }}</small>
                                <br>
                                <code class="text-primary" style="font-size: 0.75rem;">{{ $user->latestActiveToken->ip }}</code>
                            </div>
                        </div>
                    @else
                        <span>بدون فعالیت</span>
                    @endif
                </li>


            </ul>
            <div class="d-flex justify-content-center">
                @if ($user->status === \App\Enums\UserStatusEnum::INACTIVE)
                    <div class="text-center">
                        <form action="{{ route('admin.user.active-user', $user->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-success"
                                onclick="return confirm('آیا از فعال سازی کاربر مطمئن هستید؟')">
                                فعالسازی کاربر
                            </button>
                        </form>
                        <p class="mt-3 text-danger">کاربر ایمیل خود را تایید نکرده است</p>
                    </div>
                @else
                    <form action="{{ route('admin.user.toggle-status', $user->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button
                            class="btn btn-{{ $user->status === \App\Enums\UserStatusEnum::SUSPEND ? 'success' : 'danger' }}"
                            onclick="return confirm('آیا مطمئن هستید؟')">
                            {{ $user->status === \App\Enums\UserStatusEnum::SUSPEND ? 'فعالسازی کاربر' : 'تعلیق کاربر' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>


@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/css/flag-icons.min.css">
@endsection

@section('vendor-script')
    <script>
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection

