@extends('dashboard.user.layout.master')
@section('title', 'ویرایش رمز عبور')
@section('user-body')

    <!-- Two-steps verification -->
    <div class="card mb-6">
        <div class="card-header">
            <h5 class="mb-6"><i class="fa fa-firewall"></i>
                تنظیمات امنیتی کاربر
            </h5>
        </div>
        <div class="card-body d-flex">

            <a href="{{ route('admin.user.reset-password-email', ['user' => $user->id]) }}" class="btn btn-primary me-3">
                ارسال لینک بازیابی رمز عبور
            </a>

            <form action="{{ route('admin.users.disable-user-two-factor', ['user' => $user->id]) }}" method="post">
                @csrf
                <button class="btn btn-warning" @if (!$user->twoFAStatus()) disabled @endif>
                    غیرفعال سازی ورود دومرحله ایی
                    @if (!$user->twoFAStatus())
                        <small class="mx-2">(ورود دو مرحله ایی غیرفعال است)</small>
                    @endif

                </button>
            </form>

        </div>
    </div>

    <!-- User Recent Devices -->
    <div class="card mb-6">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-shield-alt me-2"></i>
                دستگاه‌های اخیر کاربر
            </h5>
            <span class="badge bg-label-primary">{{ $tokens->total() }} دستگاه</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">وضعیت</th>
                        <th>مرورگر</th>
                        <th>نوع دستگاه</th>
                        <th>مکان</th>
                        <th>نام نشست</th>
                        <th>آدرس IP</th>
                        <th>آخرین فعالیت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tokens as $token)
                        <tr>
                            <td class="text-center">
                                @if ($token->is_active)
                                    <span class="badge rounded-pill bg-success">
                                        <i class="fas fa-check"></i>
                                    </span>
                                @else
                                    <span class="badge rounded-pill bg-danger">
                                        <i class="fas fa-times"></i>
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3">
                                        <span class="avatar-initial rounded bg-label-secondary">
                                            <i class="fa-brands <x-os-fa-icon :platform="$token->platform_name"></x-os-fa-icon>"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-sm">{{ $token->browser_name }}</h6>
                                        <small class="text-muted">{{ $token->platform_name }} {{ $token->platform_version }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-label-info">
                                    <i class="fas fa-{{ $token->device_type === 'دسکتاپ' ? 'desktop' : ($token->device_type === 'تبلت' ? 'tablet' : 'mobile') }} me-1"></i>
                                    {{ $token->device_type }}
                                </span>
                                @if ($token->device_model)
                                    <br><small class="text-muted">{{ $token->device_model }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex">
                                    @if ($token->location_country_code)
                                        <span class="fi fi-{{ $token->location_country_code }}" style="font-size: 1rem; margin-left: 8px;"></span>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $token->location_country }}</div>
                                        <small class="text-muted">{{ $token->location_city }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-label-primary">{{ $token->name }}</span>
                            </td>
                            <td>
                                <code class="text-primary">{{ $token->ip ?? 'N/A' }}</code>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($token->last_used_at, 'H:i %Y/%m/%d') }}
                                </small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    {{ $tokens->appends(request()->all())->links() }}

@endsection
@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/css/flag-icons.min.css">
@endsection
