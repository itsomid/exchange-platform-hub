@extends('dashboard.layout.master')

@section('title', 'ویرایش سیستم API - ' . $system->name)

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title mb-0">ویرایش سیستم API</h4>

                    <a href="{{ route('admin.api-system.show', $system) }}" class="btn btn-secondary btn-block">
                        بازگشت <i class="fas fa-arrow-left ms-1"></i>
                    </a>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.api-system.update', $system) }}">
                        @csrf
                        @method('PATCH')

                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>اطلاعات پایه</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="name">نام سیستم <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name" value="{{ old('name', $system->name) }}"
                                                required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="description">توضیحات</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                                rows="3">{{ old('description', $system->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_email">ایمیل تماس</label>
                                                    <input type="email"
                                                        class="form-control @error('contact_email') is-invalid @enderror"
                                                        id="contact_email" name="contact_email"
                                                        value="{{ old('contact_email', $system->contact_email) }}">
                                                    @error('contact_email')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_phone">شماره تماس</label>
                                                    <input type="text"
                                                        class="form-control @error('contact_phone') is-invalid @enderror"
                                                        id="contact_phone" name="contact_phone"
                                                        value="{{ old('contact_phone', $system->contact_phone) }}">
                                                    @error('contact_phone')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Settings -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>تنظیمات امنیتی</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="allowed_ips">IP های مجاز</label>
                                            <textarea dir="ltr" class="mb-2 form-control  @error('allowed_ips') is-invalid @enderror" id="allowed_ips"
                                                name="allowed_ips" rows="3"
                                                placeholder="هر IP را در یک خط جداگانه وارد کنید. برای اجازه دسترسی از همه IP ها، خالی بگذارید.">{{ old('allowed_ips', is_array($system->allowed_ips) ? implode("\n", $system->allowed_ips) : $system->allowed_ips) }}</textarea>
                                            <small class="form-text text-muted">
                                                هر IP را در یک خط جداگانه وارد کنید. برای اجازه دسترسی از همه IP ها،
                                                خالی بگذارید.
                                                مثال: 192.168.1.1 یا 192.168.1.0/24 برای محدوده IP
                                            </small>
                                            @error('allowed_ips')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="rate_limit_per_minute">محدودیت درخواست در دقیقه</label>
                                                    <input type="number"
                                                        class="form-control @error('rate_limit_per_minute') is-invalid @enderror"
                                                        id="rate_limit_per_minute" name="rate_limit_per_minute"
                                                        value="{{ old('rate_limit_per_minute', $system->rate_limit_per_minute) }}"
                                                        min="1" max="1000">
                                                    @error('rate_limit_per_minute')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="rate_limit_per_hour">محدودیت درخواست در ساعت</label>
                                                    <input type="number"
                                                        class="form-control @error('rate_limit_per_hour') is-invalid @enderror"
                                                        id="rate_limit_per_hour" name="rate_limit_per_hour"
                                                        value="{{ old('rate_limit_per_hour', $system->rate_limit_per_hour) }}"
                                                        min="1" max="10000">
                                                    @error('rate_limit_per_hour')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- System Statistics (Read-only) -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>آمار سیستم</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-primary">{{ $system->tokens()->count() }}</h4>
                                                    <small class="text-muted">توکن‌ها</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-success">
                                                        {{ $system->tokens()->where('is_active', true)->count() }}
                                                    </h4>
                                                    <small class="text-muted">توکن فعال</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="text-center">
                                                    <h4 class="text-warning">
                                                        {{ $system->requestLogs()->whereDate('created_at', today())->count() }}
                                                    </h4>
                                                    <small class="text-muted">درخواست امروز</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status and Actions -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>وضعیت سیستم</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" class="form-check-input" id="is_active"
                                                    name="is_active" value="1"
                                                    {{ old('is_active', $system->is_active) == 1 ? 'checked' : '' }}>
                                                <label class="form-check-label ms-2" for="is_active">
                                                    سیستم فعال باشد
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                غیرفعال کردن سیستم، تمام درخواست‌های API را مسدود می‌کند.
                                            </small>
                                        </div>

                                        <hr>

                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle"></i> اطلاعات سیستم</h6>
                                            <ul class="mb-0">
                                                <li><strong>تاریخ ایجاد:</strong>
                                                    {{ App\Helpers\DateFormatter::convertToPersianDate($system->created_at, 'H:i:s %Y/%m/%d') }}
                                                </li>
                                                <li><strong>آخرین بروزرسانی:</strong>
                                                    {{ App\Helpers\DateFormatter::convertToPersianDate($system->updated_at, 'H:i:s %Y/%m/%d') }}
                                                </li>
                                                @if ($system->last_used_at)
                                                    <li><strong>آخرین درخواست:</strong>
                                                        {{ $system->last_used_at->diffForHumans() }}</li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-primary btn-block me-2">
                                            <i class="fas fa-save me-1"></i> ذخیره تغییرات
                                        </button>

                                        <hr>
                                        <a href="{{ route('admin.api-system.tokens.index', $system) }}"
                                            class="btn btn-info btn-block me-2">
                                            <i class="fas fa-key"></i> مدیریت توکن‌ها
                                        </a>
                                    </div>
                                </div>

                                <!-- Danger Zone -->
                                <div class="card mt-3 border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> منطقه خطر</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mt-3">عملیات‌های خطرناک که غیرقابل بازگشت هستند.</p>

                                        <button type="button" class="btn btn-outline-danger btn-sm btn-block me-2"
                                            onclick="resetAllTokens()">
                                            <i class="fas fa-sync me-1"></i> بازنشانی همه توکن‌ها
                                        </button>

                                        <button type="button" class="btn btn-outline-danger btn-sm btn-block me-2"
                                            onclick="clearAllLogs()">
                                            <i class="fas fa-trash me-1"></i> پاک کردن تاریخچه
                                        </button>

                                        <button type="button" class="btn btn-danger btn-sm btn-block"
                                            onclick="confirmDelete()">
                                            <i class="fas fa-trash-alt me-1"></i> حذف سیستم
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Confirmation Modals -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأیید حذف سیستم</h5>
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

    <div class="modal fade" id="resetTokensModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأیید بازنشانی توکن‌ها</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>آیا از بازنشانی همه توکن‌های این سیستم اطمینان دارید؟</p>
                    <p class="text-warning">
                        <strong>توجه:</strong> تمام توکن‌های فعلی غیرفعال شده و توکن‌های جدید تولید خواهند شد.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-warning" onclick="performResetTokens()">بازنشانی</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="clearLogsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأیید پاک کردن تاریخچه</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>آیا از پاک کردن تمام تاریخچه درخواست‌های این سیستم اطمینان دارید؟</p>
                    <p class="text-warning">
                        <strong>توجه:</strong> این عمل غیرقابل بازگشت است.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-warning" onclick="performClearLogs()">پاک کردن</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Auto-calculate hourly limit based on per-minute limit
            $('#rate_limit_per_minute').on('input', function() {
                const perMinute = parseInt($(this).val()) || 0;
                const perHour = perMinute * 60;
                $('#rate_limit_per_hour').val(perHour);
            });

            // Validate IP addresses
            $('#allowed_ips').on('blur', function() {
                const ips = $(this).val().split('\n').filter(ip => ip.trim() !== '');
                const invalidIps = [];

                ips.forEach(ip => {
                    ip = ip.trim();
                    // Basic IP validation
                    const ipRegex = /^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$/;
                    if (!ipRegex.test(ip)) {
                        invalidIps.push(ip);
                    }
                });

                if (invalidIps.length > 0) {
                    alert('IP های نامعتبر: ' + invalidIps.join(', '));
                }
            });
        });

        function confirmDelete() {
            $('#deleteModal').modal('show');
        }

        function resetAllTokens() {
            $('#resetTokensModal').modal('show');
        }

        function clearAllLogs() {
            $('#clearLogsModal').modal('show');
        }

        function performResetTokens() {
            // This would typically make an AJAX call to reset tokens
            alert('این قابلیت در نسخه آینده پیاده‌سازی خواهد شد.');
            $('#resetTokensModal').modal('hide');
        }

        function performClearLogs() {
            // This would typically make an AJAX call to clear logs
            alert('این قابلیت در نسخه آینده پیاده‌سازی خواهد شد.');
            $('#clearLogsModal').modal('hide');
        }
    </script>
@endpush
