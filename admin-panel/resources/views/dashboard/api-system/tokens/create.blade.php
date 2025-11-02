@extends('dashboard.layout.master')

@section('title', 'افزودن توکن جدید - ' . $system->name)

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">افزودن توکن جدید</h4>

                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.api-system.tokens.store', $system) }}">
                        @csrf

                        <div class="row">
                            <!-- Token Information -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>اطلاعات توکن</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="name">نام توکن <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name" value="{{ old('name') }}" required
                                                placeholder="مثال: Production API Token">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="description">توضیحات</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                                rows="3" placeholder="توضیحات مربوط به استفاده از این توکن">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="expires_at">تاریخ انقضا</label>
                                                    <input type="datetime-local"
                                                        class="form-control @error('expires_at') is-invalid @enderror"
                                                        id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                                                    <small class="form-text text-muted">
                                                        خالی بگذارید تا توکن بدون انقضا باشد
                                                    </small>
                                                    @error('expires_at')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="usage_limit">محدودیت استفاده</label>
                                                    <input type="number"
                                                        class="form-control @error('usage_limit') is-invalid @enderror"
                                                        id="usage_limit" name="usage_limit" value="{{ old('usage_limit') }}"
                                                        min="0" placeholder="0 = بدون محدودیت">
                                                    <small class="form-text text-muted">
                                                        تعداد درخواست‌های مجاز (0 = بدون محدودیت)
                                                    </small>
                                                    @error('usage_limit')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Permissions -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>دسترسی‌ها و مجوزها</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>راهنما:</strong> دسترسی‌هایی که انتخاب می‌کنید، تعیین می‌کند این
                                            توکن به چه endpoint هایی دسترسی دارد.
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>دسترسی‌های کاربر</h6>
                                                @foreach ($availableScopes as $scope => $description)
                                                    @if (str_starts_with($scope, 'user_'))
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="scope_{{ $scope }}" name="scopes[]"
                                                                value="{{ $scope }}"
                                                                {{ in_array($scope, old('scopes', [])) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="scope_{{ $scope }}">
                                                                <strong>{{ $description }}</strong>

                                                            </label>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>

                                            <div class="col-md-6">
                                                <h6>دسترسی‌های تراکنش</h6>
                                                @foreach ($availableScopes as $scope => $description)
                                                    @if (str_starts_with($scope, 'transaction_'))
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="scope_{{ $scope }}" name="scopes[]"
                                                                value="{{ $scope }}"
                                                                {{ in_array($scope, old('scopes', [])) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="scope_{{ $scope }}">
                                                                <strong>{{ $description }}</strong>

                                                            </label>
                                                        </div>
                                                    @endif
                                                @endforeach

                                                <h6 class="mt-3">دسترسی‌های سهام</h6>
                                                @foreach ($availableScopes as $scope => $description)
                                                    @if (str_starts_with($scope, 'stock_'))
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="scope_{{ $scope }}" name="scopes[]"
                                                                value="{{ $scope }}"
                                                                {{ in_array($scope, old('scopes', [])) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="scope_{{ $scope }}">
                                                                <strong>{{ $description }}</strong>

                                                            </label>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="select_all_scopes">
                                                <label class="form-check-label" for="select_all_scopes">
                                                    <strong>انتخاب همه دسترسی‌ها</strong>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Settings and Actions -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>تنظیمات توکن</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="is_active"
                                                    name="is_active" value="1"
                                                    {{ old('is_active', true) ? 'checked' : '' }}>
                                                <label class="custom-control-label ms-1" for="is_active">
                                                    توکن فعال باشد
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                توکن‌های غیرفعال قابل استفاده نیستند.
                                            </small>
                                        </div>

                                        <div class="form-group mt-2">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="log_requests"
                                                    name="log_requests" value="1"
                                                    {{ old('log_requests', true) ? 'checked' : '' }}>
                                                <label class="custom-control-label ms-1" for="log_requests">
                                                    ثبت درخواست‌ها
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                تمام درخواست‌های این توکن ثبت شوند.
                                            </small>
                                        </div>

                                        <hr>

                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-exclamation-triangle"></i> نکات امنیتی</h6>
                                            <ul class="mb-0 small">
                                                <li>توکن تولید شده را در مکان امنی نگهداری کنید</li>
                                                <li>توکن را در کد منبع commit نکنید</li>
                                                <li>در صورت فاش شدن، فوراً توکن را غیرفعال کنید</li>
                                                <li>دسترسی‌های لازم را به حداقل برسانید</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- System Information -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>اطلاعات سیستم</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center">
                                            <h6>{{ $system->name }}</h6>
                                            <p class="text-muted">{{ $system->description }}</p>

                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <h5 class="text-primary">{{ $system->tokens()->count() }}</h5>
                                                    <small class="text-muted">توکن‌های موجود</small>
                                                </div>
                                                <div class="col-6">
                                                    <h5 class="text-success">
                                                        {{ $system->tokens()->where('is_active', true)->count() }}</h5>
                                                    <small class="text-muted">توکن‌های فعال</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-plus me-1"></i> ایجاد توکن
                                        </button>
                                        <a href="{{ route('admin.api-system.tokens.index', $system) }}"
                                            class="btn btn-secondary btn-block ms-2">
                                            <i class="fas fa-arrow-left me-1"></i> بازگشت
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
            // Select all scopes functionality
            $('#select_all_scopes').change(function() {
                const isChecked = $(this).is(':checked');
                $('input[name="scopes[]"]').prop('checked', isChecked);
            });

            // Update select all checkbox when individual scopes change
            $('input[name="scopes[]"]').change(function() {
                const totalScopes = $('input[name="scopes[]"]').length;
                const checkedScopes = $('input[name="scopes[]"]:checked').length;

                $('#select_all_scopes').prop('checked', totalScopes === checkedScopes);
            });

            // Set minimum date for expiration to current date
            const now = new Date();
            const minDate = now.toISOString().slice(0, 16);
            $('#expires_at').attr('min', minDate);

            // Auto-generate token name based on system name
            if (!$('#name').val()) {
                const systemName = '{{ $system->name }}';
                const tokenCount = {{ $system->tokens()->count() + 1 }};
                $('#name').val(systemName + ' Token #' + tokenCount);
            }

            // Validate form before submission
            $('form').submit(function(e) {
                const selectedScopes = $('input[name="scopes[]"]:checked').length;

                if (selectedScopes === 0) {
                    e.preventDefault();
                    alert('لطفاً حداقل یک دسترسی انتخاب کنید.');
                    return false;
                }

                // Show confirmation for token creation
                const confirmation = confirm(
                    'آیا از ایجاد این توکن اطمینان دارید؟\n\nتوکن تولید شده تنها یک بار نمایش داده می‌شود.'
                );
                if (!confirmation) {
                    e.preventDefault();
                    return false;
                }
            });

            // Usage limit validation
            $('#usage_limit').on('input', function() {
                const value = parseInt($(this).val());
                if (value < 0) {
                    $(this).val(0);
                }
            });

            // Expiration date validation
            $('#expires_at').on('change', function() {
                const selectedDate = new Date($(this).val());
                const now = new Date();

                if (selectedDate <= now) {
                    alert('تاریخ انقضا باید در آینده باشد.');
                    $(this).val('');
                }
            });
        });

        // Show scope descriptions on hover
        $('.form-check-label').hover(
            function() {
                $(this).find('small').addClass('text-info');
            },
            function() {
                $(this).find('small').removeClass('text-info');
            }
        );
    </script>
@endpush
