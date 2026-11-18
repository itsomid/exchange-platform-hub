@extends('dashboard.layout.master')

@section('title', 'تنظیمات امنیتی')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h4 class="mb-1">تنظیمات امنیتی</h4>
            <p class="mb-0 text-muted">اقدام‌های امنیتی روی نشست کاربران صرافی. کاربران آنلاین و نشست پرسنل پنل دست‌نخورده می‌مانند.</p>
        </div>
        <span class="badge bg-label-warning">بازه فعالیت: {{ $stats['idle_minutes'] }} دقیقه</span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">کاربران آنلاین</h6>
                            <h3 class="mb-0" data-stat="active-users">{{ number_format($stats['active_users']) }}</h3>
                            <small class="text-muted">فعال در {{ $stats['idle_minutes'] }} دقیقه اخیر</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">توکن‌های محفوظ</h6>
                            <h3 class="mb-0" data-stat="kept-tokens">{{ number_format($stats['kept_tokens']) }}</h3>
                            <small class="text-muted">متعلق به کاربران آنلاین</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">کاربران غیرفعال</h6>
                            <h3 class="mb-0" data-stat="idle-users">{{ number_format($stats['idle_users']) }}</h3>
                            <small class="text-muted">بدون فعالیت در {{ $stats['idle_minutes'] }} دقیقه اخیر</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-warning rounded">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">توکن‌های قابل حذف</h6>
                            <h3 class="mb-0" data-stat="revocable-tokens">{{ number_format($stats['revocable_tokens']) }}</h3>
                            <small class="text-muted">با تأیید، از پایگاه داده پاک می‌شوند</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-danger rounded">
                                <i class="fas fa-right-from-bracket"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="avatar avatar-md flex-shrink-0">
                    <div class="avatar-initial bg-label-danger rounded">
                        <i class="fas fa-shield-alt fa-lg"></i>
                    </div>
                </div>
                <div>
                    <h5 class="mb-1">خروج اجباری کاربران غیرفعال</h5>
                    <p class="mb-0 text-muted">
                        توکن کاربرانی که در {{ $stats['idle_minutes'] }} دقیقه اخیر فعالیتی نداشته‌اند منقضی و از پایگاه داده حذف می‌شود.
                        آن حساب‌ها برای ادامه باید دوباره وارد شوند. معیار فعالیت، زمان آخرین استفاده از توکن است.
                    </p>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <span class="badge bg-label-success mb-3">دست نمی‌خورد</span>
                        <ul class="mb-0 text-muted">
                            <li class="mb-2">کاربری که در {{ $stats['idle_minutes'] }} دقیقه اخیر با حسابش درخواستی زده است</li>
                            <li class="mb-2">کاربری که همین تازگی وارد شده و هنوز درخواستی ثبت نشده</li>
                            <li>نشست پرسنل همین پنل</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <span class="badge bg-label-danger mb-3">حذف می‌شود</span>
                        <ul class="mb-0 text-muted">
                            <li class="mb-2">همه توکن‌های کاربری که {{ $stats['idle_minutes'] }} دقیقه است فعالیتی نداشته</li>
                            <li class="mb-2">اگر کاربر چند دستگاه داشته باشد و هیچ‌کدام تازه استفاده نشده باشند، همه پاک می‌شوند</li>
                            <li>بعد از حذف، همان توکن دیگر معتبر نیست و ورود دوباره لازم است</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if ($stats['revocable_tokens'] > 0)
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#revokeIdleSessionsModal">
                    <i class="fas fa-right-from-bracket me-1"></i>
                    منقضی و حذف {{ number_format($stats['revocable_tokens']) }} توکن
                </button>
            @else
                <button type="button" class="btn btn-secondary" disabled>
                    <i class="fas fa-check me-1"></i>
                    نشست غیرفعالی برای حذف نیست
                </button>
            @endif
        </div>
    </div>

    @if ($stats['revocable_tokens'] > 0)
        <div class="modal fade" id="revokeIdleSessionsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تأیید خروج کاربران غیرفعال</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            این کار <strong>{{ number_format($stats['revocable_tokens']) }} توکن</strong>
                            متعلق به <strong>{{ number_format($stats['idle_users']) }} کاربر</strong>
                            را برای همیشه از پایگاه داده حذف می‌کند.
                        </p>
                        <div class="alert alert-warning mb-0" role="alert">
                            {{ number_format($stats['active_users']) }} کاربر آنلاین سر جایشان می‌مانند.
                            نشست پرسنل پنل تغییر نمی‌کند. این عمل قابل بازگشت نیست.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                        <form action="{{ route('admin.security-settings.revoke-idle-sessions') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-right-from-bracket me-1"></i>
                                حذف توکن‌ها
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
