@extends('dashboard.layout.master')
@section('title', ' نشست های فعال ' . $admin->fullname())
@section('content')

    @php
        $expiredCount = $admin->sessions->filter(fn($s) => $s->isExpired())->count();
        $activeCount = $admin->sessions->count() - $expiredCount;
    @endphp

    <div class="card">
        <div class="card-body">

            <div class="card-title header-elements">
                <div>
                    <h5 class="m-0 me-2">
                        <i class="fa fa-clock text-primary me-2"></i>
                        لیست نشست های فعال
                    </h5>
                    <small class="text-muted d-block mt-1">
                        مدیریت {{ $admin->fullname() }} -
                        <span class="text-success fw-semibold">{{ $activeCount }} فعال</span>
                        @if ($expiredCount > 0)
                            <span class="text-danger fw-semibold me-2">، {{ $expiredCount }} منقضی</span>
                        @endif
                    </small>
                </div>
                @can('session.destroy')
                    <div class="card-title-elements ms-auto gap-2">
                        @if ($expiredCount > 0)
                            <form action="{{ route('admin.session.destroy-expired', ['admin' => $admin]) }}" method="POST"
                                style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-warning">
                                    <i class="fa fa-broom mx-2"></i>
                                    حذف منقضی شده‌ها ({{ $expiredCount }})
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('admin.session.purge', ['admin' => $admin]) }}" method="POST"
                            style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger"
                                onclick="return confirm('آیا از حذف همه نشست‌ها اطمینان دارید؟')">
                                <i class="fa fa-skull mx-2"></i>
                                حذف همه ی نشست ها
                            </button>
                        </form>
                    </div>
                @endcan
            </div>

            @if ($admin->sessions->count() > 0)
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th><i class="fa fa-globe me-1"></i> IP</th>
                                <th><i class="fa fa-desktop me-1"></i> سیستم</th>
                                <th><i class="fa fa-browser me-1"></i> مرورگر</th>
                                <th><i class="fa fa-clock me-1"></i> آخرین فعالیت</th>
                                <th><i class="fa fa-hourglass-end me-1"></i> وضعیت</th>
                                @can('session.destroy')
                                    <th><i class="fa fa-cog me-1"></i> عملیات</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($admin->sessions as $session)
                                @php
                                    $isCurrentSession = $session->id === $currentSessionId;
                                    $isExpired = $session->isExpired();
                                @endphp
                                <tr
                                    class="{{ $isCurrentSession ? 'table-primary' : ($isExpired ? 'table-secondary opacity-75' : '') }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <code class="text-dark">{{ $session->ip_address }}</code>
                                            @if ($isCurrentSession)
                                                <span class="badge bg-primary ms-2">
                                                    <i class="fa fa-user me-1"></i>
                                                    نشست فعلی
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-flex align-items-center">
                                            @if ($session->is_desktop())
                                                <i class="fa fa-laptop text-info me-2"></i>
                                            @else
                                                <i class="fa fa-mobile-alt text-success me-2"></i>
                                            @endif
                                            {{ $session->platform() ?: 'نامشخص' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">
                                            {{ $session->browser() ?: 'نامشخص' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="">
                                            <i class="fa fa-clock me-1"></i>
                                            {{ \App\Helpers\DateFormatter::convertUnixTimeToPersianDate($session->last_activity, 0, 'H:i:s %Y/%m/%d') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($isExpired)
                                            <div class="d-flex flex-column">
                                                <span class="badge bg-label-danger mb-1">
                                                    <i class="fa fa-times-circle me-1"></i>
                                                    منقضی شده
                                                </span>
                                                <small class="">
                                                    انقضا:
                                                    {{ \App\Helpers\DateFormatter::convertUnixTimeToPersianDate($session->last_activity, (int) config('session.lifetime'), 'H:i:s %Y/%m/%d') }}
                                                </small>
                                            </div>
                                        @else
                                            <div class="d-flex flex-column">
                                                <span class="badge bg-label-success mb-1">
                                                    <i class="fa fa-check-circle me-1"></i>
                                                    فعال
                                                </span>
                                                <small class="">
                                                    انقضا:
                                                    {{ \App\Helpers\DateFormatter::convertUnixTimeToPersianDate($session->last_activity, (int) config('session.lifetime'), 'H:i:s %Y/%m/%d') }}
                                                </small>
                                            </div>
                                        @endif
                                    </td>
                                    @can('session.destroy')
                                        <td>
                                            @if ($isCurrentSession)
                                                <button class="btn btn-outline-secondary btn-sm" disabled
                                                    title="نمی‌توانید نشست فعلی خود را حذف کنید">
                                                    <i class="fa fa-ban mx-1"></i>
                                                    غیرقابل حذف
                                                </button>
                                            @else
                                                <form
                                                    action="{{ route('admin.session.destroy', ['admin' => $admin, 'session' => $session->id]) }}"
                                                    method="post" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline-danger btn-sm"
                                                        onclick="return confirm('آیا از حذف این نشست اطمینان دارید؟')">
                                                        <i class="fa fa-sign-out-alt mx-1"></i>
                                                        حذف
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fa fa-inbox fa-4x text-muted"></i>
                    </div>
                    <h5 class="text-muted">هیچ نشست فعالی وجود ندارد</h5>
                    <p class="text-muted mb-0">این ادمین هیچ نشست فعال یا منقضی ندارد.</p>
                </div>
            @endif
        </div>
    </div>

    <style>
        .table-primary {
            --bs-table-bg: rgba(var(--bs-primary-rgb), 0.1);
            border-right: 3px solid var(--bs-primary);
        }

        .table-secondary {
            background: rgba(0, 0, 0, 0.02);
        }

        .opacity-75 {
            opacity: 0.75;
        }
    </style>

@endsection
