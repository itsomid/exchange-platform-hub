@extends('dashboard.layout.master')

@section('title', 'افزودن سیستم API جدید')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">افزودن سیستم API جدید</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.api-system.store') }}">
                        @csrf

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
                                                id="name" name="name" value="{{ old('name') }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="description">توضیحات</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                                rows="3">{{ old('description') }}</textarea>
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
                                                        value="{{ old('contact_email') }}">
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
                                                        value="{{ old('contact_phone') }}">
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
                                            <textarea class="form-control @error('allowed_ips') is-invalid @enderror" id="allowed_ips" name="allowed_ips"
                                                rows="3" placeholder="هر IP را در یک خط جداگانه وارد کنید. برای اجازه دسترسی از همه IP ها، خالی بگذارید.">{{ old('allowed_ips') }}</textarea>
                                            <small class="form-text text-muted">
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
                                                        value="{{ old('rate_limit_per_minute', 60) }}" min="1"
                                                        max="1000">
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
                                                        value="{{ old('rate_limit_per_hour', 1000) }}" min="1"
                                                        max="10000">
                                                    @error('rate_limit_per_hour')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>وضعیت سیستم</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="is_active"
                                                    name="is_active" value="1"
                                                    {{ old('is_active', true) ? 'checked' : '' }}>
                                                <label class="custom-control-label ms-1" for="is_active">
                                                    سیستم فعال باشد
                                                </label>
                                            </div>
                                        </div>

                                        <div class="alert alert-info">
                                            <small>
                                                <i class="fas fa-info-circle"></i>
                                                دسترسی‌ها از طریق توکن‌های API مدیریت می‌شوند. پس از ایجاد سیستم،
                                                توکن‌های مورد نیاز را ایجاد کنید.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-primary btn-block me-1">
                                            <i class="fas fa-save me-1"></i> ذخیره سیستم
                                        </button>
                                        <a href="{{ route('admin.api-system.index') }}"
                                            class="btn btn-secondary btn-block">
                                            <i class="fas fa-times me-1"></i> انصراف
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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
                    // Basic IP validation (you can make this more sophisticated)
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
    </script>
@endpush
