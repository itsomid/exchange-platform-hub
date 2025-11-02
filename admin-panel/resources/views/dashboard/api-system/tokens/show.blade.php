@extends('dashboard.layout.master')

@section('title', 'جزئیات توکن API - ' . $system->name)

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">جزئیات توکن</h4>
                            <small class="text-muted">سیستم: {{ $system->name }}</small>
                        </div>
                        <div>
                            <a href="{{ route('admin.api-system.tokens.index', $system) }}" class="btn btn-secondary">
                                بازگشت <i class="fas fa-arrow-left ms-1"></i>
                            </a>
                        </div>
                    </div>

                </div>

                <div class="card-body">


                    @if (session('new_token'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>توجه:</strong> این توکن فقط یک بار نمایش داده می‌شود. لطفاً آن را کپی کنید:
                            <br><br>
                            <code class="bg-dark text-light p-2 d-block">{{ session('new_token') }}</code>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Token Information -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">اطلاعات توکن</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><strong>نام توکن:</strong></label>
                                                <p class="form-control-plaintext">{{ $token->name }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><strong>وضعیت:</strong></label>
                                                <p class="form-control-plaintext">
                                                    @if ($token->is_active)
                                                        <span class="badge bg-success">فعال</span>
                                                    @else
                                                        <span class="badge bg-danger">غیرفعال</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><strong>تاریخ ایجاد:</strong></label>
                                                <p class="form-control-plaintext">
                                                    {{ $token->created_at->format('Y/m/d H:i') }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><strong>آخرین استفاده:</strong></label>
                                                <p class="form-control-plaintext">
                                                    {{ $usageStats['last_used'] ? App\Helpers\DateFormatter::convertToPersianDate($usageStats['last_used'], 'H:i:s %Y/%m/%d') : 'هرگز' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($token->expires_at)
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>تاریخ انقضا:</strong></label>
                                                    <p class="form-control-plaintext">
                                                        {{ App\Helpers\DateFormatter::convertToPersianDate($token->expires_at, 'H:i:s %Y/%m/%d') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($token->description)
                                        <div class="mb-3">
                                            <label class="form-label"><strong>توضیحات:</strong></label>
                                            <p class="form-control-plaintext">{{ $token->description }}</p>
                                        </div>
                                    @endif

                                    @if ($token->scopes && is_array($token->scopes) && count($token->scopes) > 0)
                                        <div class="mb-3">
                                            <label class="form-label"><strong>دسترسی‌ها:</strong></label>
                                            <div class="mt-2">
                                                @foreach ($token->scopes as $scope)
                                                    <span class="badge bg-info me-1">{{ $scope }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Usage Statistics -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">آمار استفاده</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>کل درخواست‌ها:</span>
                                            <strong>{{ formatNumberTrimZeros($usageStats['total_requests']) }}</strong>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>30 روز گذشته:</span>
                                            <strong>{{ formatNumberTrimZeros($usageStats['requests_last_30_days']) }}</strong>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>درخواست‌های موفق:</span>
                                            <strong>{{ formatNumberTrimZeros($usageStats['successful_requests']) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Logs -->
                    @if ($recentLogs->count() > 0)
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">آخرین درخواست‌ها (30 روز گذشته)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>تاریخ</th>
                                                <th>متد</th>
                                                <th>مسیر</th>
                                                <th>وضعیت پاسخ</th>
                                                <th>IP کاربر</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($recentLogs as $log)
                                                <tr>
                                                    <td>{{ $log->requested_at ? \App\Helpers\DateFormatter::convertToPersianDate($log->requested_at, 'H:i:s %Y/%m/%d') : $log->created_at->format('Y/m/d H:i') }}
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">{{ $log->method ?? 'N/A' }}</span>
                                                    </td>
                                                    <td>{{ $log->endpoint ?? 'N/A' }}</td>
                                                    <td>
                                                        @if ($log->response_status >= 200 && $log->response_status < 300)
                                                            <span
                                                                class="badge bg-success">{{ $log->response_status }}</span>
                                                        @elseif($log->response_status >= 400)
                                                            <span
                                                                class="badge bg-danger">{{ $log->response_status }}</span>
                                                        @else
                                                            <span
                                                                class="badge bg-warning">{{ $log->response_status ?? 'N/A' }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $log->ip_address ?? 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">عملیات</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.api-system.tokens.edit', [$system, $token]) }}"
                                    class="btn btn-warning">
                                    <i class="fas fa-edit"></i> ویرایش توکن
                                </a>

                                <form method="POST"
                                    action="{{ route('admin.api-system.tokens.destroy', [$system, $token]) }}"
                                    class="d-inline" onsubmit="return confirm('آیا از حذف این توکن اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> حذف توکن
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
