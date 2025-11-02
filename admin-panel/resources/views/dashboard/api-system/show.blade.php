@extends('dashboard.layout.master')

@section('title', 'جزئیات سیستم API - ' . $system->name)

@section('content')

    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">{{ $system->name }}</h4>

                    </div>
                    <div>
                        <span class="badge bg-{{ $system->is_active ? 'success' : 'secondary' }} badge-lg">
                            {{ $system->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <!-- System Information -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>اطلاعات سیستم</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>شناسه:</strong></td>
                                            <td>{{ $system->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>نام:</strong></td>
                                            <td>{{ $system->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>ایمیل تماس:</strong></td>
                                            <td>{{ $system->contact_email ?? 'تعریف نشده' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>شماره تماس:</strong></td>
                                            <td>{{ $system->contact_phone ?? 'تعریف نشده' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>تاریخ ایجاد:</strong></td>
                                            <td>{{ App\Helpers\DateFormatter::convertToPersianDate($system->created_at,'H:i:s %Y/%m/%d') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>آخرین بروزرسانی:</strong></td>
                                            <td>{{ App\Helpers\DateFormatter::convertToPersianDate($system->updated_at,'H:i:s %Y/%m/%d') }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>محدودیت در دقیقه:</strong></td>
                                            <td>{{ $system->rate_limit_per_minute ?? 'نامحدود' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>محدودیت در ساعت:</strong></td>
                                            <td>{{ $system->rate_limit_per_hour ?? 'نامحدود' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>آخرین درخواست:</strong></td>
                                            <td>
                                                @if ($system->last_used_at)
                                                    {{ $system->last_used_at->diffForHumans() }}
                                                @else
                                                    هیچ درخواستی
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>تعداد کل درخواست‌ها:</strong></td>
                                            <td>{{ formatNumberTrimZeros($statistics['total_requests']) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>درخواست‌های موفق:</strong></td>
                                            <td>{{ formatNumberTrimZeros($statistics['successful_requests']) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>میانگین زمان پاسخ:</strong></td>
                                            <td>{{ $statistics['avg_response_time'] ? round($statistics['avg_response_time'], 2) . ' ms' : 'N/A' }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if ($system->description)
                                <div class="mt-3">
                                    <strong>توضیحات:</strong>
                                    <p class="mt-2">{{ $system->description }}</p>
                                </div>
                            @endif

                            @if ($system->allowed_ips)
                                <div class="mt-3">
                                    <strong>IP های مجاز:</strong>
                                    <div class="mt-2">
                                        @if (is_array($system->allowed_ips))
                                            @foreach ($system->allowed_ips as $ip)
                                                @if (trim($ip))
                                                    <span class="badge bg-info me-1">{{ trim($ip) }}</span>
                                                @endif
                                            @endforeach
                                        @else
                                            @foreach (explode("\n", $system->allowed_ips) as $ip)
                                                @if (trim($ip))
                                                    <span class="badge bg-info">{{ trim($ip) }}</span>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- API Tokens -->
                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>توکن‌های API</h5>
                            <a href="{{ route('admin.api-system.tokens.create', $system) }}"
                                class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> افزودن توکن
                            </a>
                        </div>
                        <div class="card-body">
                            @if ($system->tokens->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>نام</th>
                                                <th>توکن</th>
                                                <th>وضعیت</th>
                                                <th>دسترسی‌ها</th>
                                                <th>آخرین استفاده</th>
                                                <th>عملیات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($system->tokens as $token)
                                                <tr>
                                                    <td>{{ $token->name }}</td>
                                                    <td>
                                                        <code class="token-display" data-token="{{ $token->token }}">
                                                            {{ Str::mask($token->token, '*', 8, -8) }}
                                                        </code>
                                                        <button class="btn btn-sm btn-link p-0 "
                                                            onclick="toggleToken(this)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-link p-0 ms-1"
                                                            onclick="copyToken(this, '{{ $token->token }}')">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge bg-{{ $token->is_active ? 'success' : 'secondary' }}">
                                                            {{ $token->is_active ? 'فعال' : 'غیرفعال' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if ($token->scopes)
                                                            @foreach (is_array($token->scopes) ? $token->scopes : json_decode($token->scopes, true) as $scope)
                                                                <span
                                                                    class="badge bg-info me-1 mb-1">{{ $scope }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">همه دسترسی‌ها</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($token->last_used_at)
                                                            {{ $token->last_used_at->diffForHumans() }}
                                                        @else
                                                            <span class="text-muted">استفاده نشده</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('admin.api-system.tokens.show', [$system, $token]) }}"
                                                                class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="{{ route('admin.api-system.tokens.edit', [$system, $token]) }}"
                                                                class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-key fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">هیچ توکنی تعریف نشده</h6>
                                    <p class="text-muted">برای استفاده از API، حداقل یک توکن ایجاد کنید.</p>
                                    <a href="{{ route('admin.api-system.tokens.create', $system) }}"
                                        class="btn btn-primary">
                                        <i class="fas fa-plus"></i> ایجاد اولین توکن
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                <!-- Actions and Quick Stats -->
                <div class="col-md-4">
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5>عملیات سریع</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.api-system.edit', $system) }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-1"></i> ویرایش سیستم
                                </a>

                                <form method="POST" action="{{ route('admin.api-system.toggle-status', $system) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="btn btn-{{ $system->is_active ? 'secondary' : 'success' }} w-100">
                                        <i class="fas fa-{{ $system->is_active ? 'pause' : 'play' }} me-1"></i>
                                        {{ $system->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}
                                    </button>
                                </form>

                                <a href="{{ route('admin.api-system.tokens.create', $system) }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> افزودن توکن جدید
                                </a>

                                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                    <i class="fas fa-trash me-1"></i> حذف سیستم
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>آمار</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-right">
                                        <h4 class="text-primary">{{ $statistics['total_tokens'] }}</h4>
                                        <small class="text-muted">توکن‌ها</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success">{{ $statistics['active_tokens'] }}</h4>
                                    <small class="text-muted">توکن فعال</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-12">
                                    <h4 class="text-secondary">{{ formatNumberTrimZeros($statistics['requests_today']) }}
                                    </h4>
                                    <small class="text-muted">درخواست امروز</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>فعالیت اخیر</h5>
                        </div>
                        <div class="card-body">
                            @if ($system->requestLogs->count() > 0)
                                <div class="timeline">
                                    @foreach ($system->requestLogs as $log)
                                        <div class="timeline-item">
                                            <div
                                                class="timeline-marker bg-{{ $log->status_code < 400 ? 'success' : 'danger' }}">
                                            </div>
                                            <div class="timeline-content">
                                                <h6 class="timeline-title">{{ $log->endpoint }}</h6>
                                                <p class="timeline-text">
                                                    <small>
                                                        {{ $log->method }} - {{ $log->status_code }}
                                                        ({{ $log->response_time }}ms)
                                                    </small>
                                                </p>
                                                <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted text-center">هیچ فعالیتی یافت نشد</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأیید حذف</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>آیا از حذف سیستم API "{{ $system->name }}" اطمینان دارید؟</p>
                    <p class="text-danger">
                        <strong>توجه:</strong> این عمل غیرقابل بازگشت است و تمام توکن‌ها و تاریخچه مربوط به این
                        سیستم حذف خواهد شد.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                    <form method="POST" action="{{ route('admin.api-system.destroy', $system) }}"
                        style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">حذف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-marker {
            position: absolute;
            left: -35px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: -31px;
            top: 15px;
            width: 2px;
            height: calc(100% + 5px);
            background-color: #dee2e6;
        }

        .token-display {
            cursor: pointer;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function toggleToken(button) {
            const codeElement = button.previousElementSibling;
            const icon = button.querySelector('i');
            const fullToken = codeElement.dataset.token;
            const maskedToken = fullToken.replace(/^(.{8}).*(.{8})$/, '$1' + '*'.repeat(fullToken.length - 16) + '$2');

            if (codeElement.textContent.trim() === fullToken) {
                codeElement.textContent = maskedToken;
                icon.className = 'fas fa-eye ms-2';
            } else {
                codeElement.textContent = fullToken;
                icon.className = 'fas fa-eye-slash ms-2';
            }
        }

        function copyToken(button, token) {
            navigator.clipboard.writeText(token).then(function() {
                AnimatedTooltip.showSuccess(button, 'کپی شد!');
            }).catch(function(err) {
                AnimatedTooltip.showError(button, 'خطا در کپی کردن!');
            });
        }

        function confirmDelete() {
            $('#deleteModal').modal('show');
        }
    </script>
@endpush
