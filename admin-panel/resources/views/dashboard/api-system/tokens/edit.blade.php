@extends('dashboard.layout.master')
@section('title', 'ویرایش توکن')

@section('content')

    <div class="row">
        <div class="col-12">


            <!-- Main Content -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5>ویرایش توکن {{ $token->name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.api-system.tokens.update', [$system, $token]) }}" method="POST">
                        @csrf
                        @method('PATCH')

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
                                                id="name" name="name" value="{{ old('name', $token->name) }}"
                                                required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="description">توضیحات</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                                rows="3">{{ old('description', $token->description) }}</textarea>
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
                                                        id="expires_at" name="expires_at"
                                                        value="{{ old('expires_at', $token->expires_at ? $token->expires_at->format('Y-m-d\TH:i') : '') }}">
                                                    <small class="form-text text-muted">
                                                        اگر خالی باشد، توکن هیچ‌گاه منقضی نمی‌شود.
                                                    </small>
                                                    @error('expires_at')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Scopes -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>دسترسی‌های توکن</h5>
                                        <small class="text-muted">دسترسی‌هایی که این توکن می‌تواند استفاده کند را انتخاب
                                            کنید.</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>دسترسی‌های کاربر</h6>
                                                @foreach ($availableScopes as $scope => $description)
                                                    @if (in_array($scope, ['user_balance', 'user_inquiry']))
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="scope_{{ $scope }}" name="scopes[]"
                                                                value="{{ $scope }}"
                                                                {{ in_array($scope, old('scopes', is_array($token->scopes) ? $token->scopes : [])) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="scope_{{ $scope }}">
                                                                <strong>{{ $description }}</strong>

                                                            </label>
                                                        </div>
                                                    @endif
                                                @endforeach

                                                <h6 class="mt-3">دسترسی‌های تراکنش</h6>
                                                @foreach ($availableScopes as $scope => $description)
                                                    @if ($scope === 'transaction_history')
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="scope_{{ $scope }}" name="scopes[]"
                                                                value="{{ $scope }}"
                                                                {{ in_array($scope, old('scopes', is_array($token->scopes) ? $token->scopes : [])) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="scope_{{ $scope }}">
                                                                <strong>{{ $description }}</strong>
                                                            </label>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>

                                            <div class="col-md-6">
                                                <h6>دسترسی‌های سهام</h6>
                                                @foreach ($availableScopes as $scope => $description)
                                                    @if ($scope === 'stock_purchase')
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="scope_{{ $scope }}" name="scopes[]"
                                                                value="{{ $scope }}"
                                                                {{ in_array($scope, old('scopes', is_array($token->scopes) ? $token->scopes : [])) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="scope_{{ $scope }}">
                                                                <strong>{{ $description }}</strong>
                                                            </label>
                                                        </div>
                                                    @endif
                                                @endforeach
                                                <h6>دسترسی های مالی</h6>
                                                @foreach ($availableScopes as $scope => $description)
                                                    @if (in_array($scope, ['user_credit_increase']))
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="scope_{{ $scope }}" name="scopes[]"
                                                                value="{{ $scope }}"
                                                                {{ in_array($scope, old('scopes', is_array($token->scopes) ? $token->scopes : [])) ? 'checked' : '' }}>
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
                                                    {{ old('is_active', $token->is_active) ? 'checked' : '' }}>
                                                <label class="custom-control-label ms-1" for="is_active">
                                                    توکن فعال باشد
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                توکن‌های غیرفعال قابل استفاده نیستند.
                                            </small>
                                        </div>

                                        <div class="form-group mt-3">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="log_requests"
                                                    name="log_requests" value="1"
                                                    {{ old('log_requests', $token->log_requests) ? 'checked' : '' }}>
                                                <label class="custom-control-label ms-1" for="log_requests">
                                                    ثبت درخواست‌ها
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                تمام درخواست‌های این توکن ثبت شود.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Token Info -->
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
                                                        {{ $system->tokens()->where('is_active', true)->count() }}
                                                    </h5>
                                                    <small class="text-muted">توکن‌های فعال</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Token Statistics -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>آمار توکن</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center">
                                            <div class="row text-center">
                                                <div class="col-12 mb-2">
                                                    <small class="text-muted">تعداد استفاده</small>
                                                    <h5 class="text-info">{{ $token->usage_count ?? 0 }}</h5>

                                                </div>
                                                @if ($token->expires_at)
                                                    <div class="col-12 mb-2">
                                                        <small class="text-muted">تاریخ انقضا</small>
                                                        <h6 class="text-warning">
                                                            {{ App\Helpers\DateFormatter::convertToPersianDate($token->expires_at, 'H:i:s %Y/%m/%d') }}
                                                        </h6>

                                                    </div>
                                                @endif
                                                <div class="col-12">
                                                    <small class="text-muted">تاریخ ایجاد</small>
                                                    <h6 class="text-muted">
                                                        {{ App\Helpers\DateFormatter::convertToPersianDate($token->created_at, 'H:i:s %Y/%m/%d') }}
                                                    </h6>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-save me-1"></i> ذخیره تغییرات
                                        </button>
                                        <a href="{{ route('admin.api-system.tokens.index', [$system]) }}"
                                            class="btn btn-secondary btn-block ms-2">
                                            بازگشت <i class="fas fa-arrow-left ms-1"></i>
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

            // Set initial state of select all checkbox
            const totalScopes = $('input[name="scopes[]"]').length;
            const checkedScopes = $('input[name="scopes[]"]:checked').length;
            $('#select_all_scopes').prop('checked', totalScopes === checkedScopes);

            // Set minimum date for expiration to current date
            const now = new Date();
            const minDate = now.toISOString().slice(0, 16);
            $('#expires_at').attr('min', minDate);

            // Validate form before submission
            $('form').submit(function(e) {
                const selectedScopes = $('input[name="scopes[]"]:checked').length;

                if (selectedScopes === 0) {
                    e.preventDefault();
                    alert('لطفاً حداقل یک دسترسی انتخاب کنید.');
                    return false;
                }

                // Show confirmation for token update
                const confirmation = confirm('آیا از ویرایش این توکن اطمینان دارید؟');
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
