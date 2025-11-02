@extends('dashboard.layout.master')

@section('title', 'مدیریت توکن‌های API - ' . $system->name)

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">مدیریت توکن‌های API</h4>
                            <small class="text-muted">سیستم: {{ $system->name }}</small>
                        </div>
                        <a href="{{ route('admin.api-system.tokens.create', $system) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> افزودن توکن جدید
                        </a>
                    </div>


                </div>

                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $tokens->total() }}</h4>
                                            <p class="mb-0">کل توکن‌ها</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-key fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $tokens->where('is_active', true)->count() }}</h4>
                                            <p class="mb-0">توکن‌های فعال</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $tokens->where('is_active', false)->count() }}</h4>
                                            <p class="mb-0">توکن‌های غیرفعال</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-times-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $tokens->where('expires_at', '>', now())->count() }}</h4>
                                            <p class="mb-0">توکن‌های معتبر</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.api-system.tokens.index', $system) }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="search">جستجو</label>
                                            <input type="text" class="form-control" id="search" name="search"
                                                value="{{ request('search') }}" placeholder="نام، توضیحات یا scope">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="status">وضعیت</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="">همه</option>
                                                <option value="active"
                                                    {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                                                <option value="inactive"
                                                    {{ request('status') == 'inactive' ? 'selected' : '' }}>غیرفعال
                                                </option>
                                                <option value="expired"
                                                    {{ request('status') == 'expired' ? 'selected' : '' }}>منقضی شده
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="scope">دسترسی</label>
                                            <select class="form-control" id="scope" name="scope">
                                                s <option value="">همه</option>
                                                @foreach ($availableScopes as $scopeKey => $scopeLabel)
                                                    <option value="{{ $scopeKey }}"
                                                        {{ request('scope') == $scopeKey ? 'selected' : '' }}>
                                                        {{ $scopeLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="sort">مرتب‌سازی</label>
                                            <select class="form-control" id="sort" name="sort">
                                                <option value="created_at_desc"
                                                    {{ request('sort') == 'created_at_desc' ? 'selected' : '' }}>
                                                    جدیدترین</option>
                                                <option value="created_at_asc"
                                                    {{ request('sort') == 'created_at_asc' ? 'selected' : '' }}>
                                                    قدیمی‌ترین</option>
                                                <option value="name_asc"
                                                    {{ request('sort') == 'name_asc' ? 'selected' : '' }}>نام (الف-ی)
                                                </option>
                                                <option value="name_desc"
                                                    {{ request('sort') == 'name_desc' ? 'selected' : '' }}>نام (ی-الف)
                                                </option>
                                                <option value="last_used_desc"
                                                    {{ request('sort') == 'last_used_desc' ? 'selected' : '' }}>آخرین
                                                    استفاده</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-primary btn-block ">
                                                    <i class="fas fa-search me-1"></i> جستجو
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tokens Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>نام توکن</th>
                                    <th>توکن</th>
                                    <th>دسترسی‌ها</th>
                                    <th>وضعیت</th>
                                    <th>تاریخ انقضا</th>
                                    <th>آخرین استفاده</th>
                                    <th>تعداد استفاده</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tokens as $token)
                                    <tr>
                                        <td>
                                            <strong>{{ $token->name }}</strong>
                                            @if ($token->description)
                                                <br><small
                                                    class="text-muted">{{ Str::limit($token->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <code class="token-display" data-token="{{ $token->token }}">
                                                    {{ Str::mask($token->token, '*', 8, -8) }}
                                                </code>
                                                <button class="btn btn-sm btn-outline-secondary ms-2"
                                                    onclick="toggleToken(this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary ms-1"
                                                    onclick="copyToken('{{ $token->token }}', this)">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($token->scopes)
                                                @foreach (is_array($token->scopes) ? $token->scopes : json_decode($token->scopes, true) as $scope)
                                                    <span class="badge bg-info me-1 mb-1">{{ $scope }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">بدون محدودیت</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($token->is_active)
                                                @if ($token->expires_at && $token->expires_at < now())
                                                    <span class="badge bg-warning">منقضی شده</span>
                                                @else
                                                    <span class="badge bg-success">فعال</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">غیرفعال</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($token->expires_at)
                                                {{ $token->expires_at->format('Y/m/d H:i') }}
                                                <br><small
                                                    class="text-muted">{{ $token->expires_at->diffForHumans() }}</small>
                                            @else
                                                <span class="text-muted">بدون انقضا</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($token->last_used_at)
                                                {{ $token->last_used_at->format('Y/m/d H:i') }}
                                                <br><small
                                                    class="text-muted">{{ $token->last_used_at->diffForHumans() }}</small>
                                            @else
                                                <span class="text-muted">استفاده نشده</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-primary">{{ formatNumberTrimZeros($token->usage_count) }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.api-system.tokens.show', [$system, $token]) }}"
                                                    class="btn btn-sm btn-info" title="مشاهده">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.api-system.tokens.edit', [$system, $token]) }}"
                                                    class="btn btn-sm btn-warning" title="ویرایش">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button
                                                    class="btn btn-sm btn-{{ $token->is_active ? 'secondary' : 'success' }}"
                                                    onclick="toggleStatus({{ $token->id }})"
                                                    title="{{ $token->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}">
                                                    <i class="fas fa-{{ $token->is_active ? 'pause' : 'play' }} me-1"></i>
                                                </button>
                                                <button class="btn btn-sm btn-primary"
                                                    onclick="regenerateToken({{ $token->id }})"
                                                    title="تولید مجدد توکن">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger"
                                                    onclick="confirmDelete({{ $token->id }}, '{{ $token->name }}')"
                                                    title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-key fa-3x mb-3"></i>
                                                <h5>هیچ توکنی یافت نشد</h5>
                                                <p>برای شروع، یک توکن جدید ایجاد کنید.</p>
                                                <a href="{{ route('admin.api-system.tokens.create', $system) }}"
                                                    class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> افزودن توکن جدید
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($tokens->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $tokens->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>


    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأیید حذف توکن</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>آیا از حذف توکن "<span id="tokenName"></span>" اطمینان دارید؟</p>
                    <p class="text-danger">
                        <strong>توجه:</strong> این عمل غیرقابل بازگشت است و تمام درخواست‌های مربوط به این توکن متوقف خواهند
                        شد.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">حذف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Regenerate Token Modal -->
    <div class="modal fade" id="regenerateModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأیید تولید مجدد توکن</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>آیا از تولید مجدد این توکن اطمینان دارید؟</p>
                    <p class="text-warning">
                        <strong>توجه:</strong> توکن فعلی غیرفعال شده و توکن جدید تولید خواهد شد. تمام سیستم‌هایی که از توکن
                        فعلی استفاده می‌کنند باید بروزرسانی شوند.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                    <form id="regenerateForm" method="POST" style="display: inline;">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-warning">تولید مجدد</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleToken(button) {
            const tokenDisplay = button.parentElement.querySelector('.token-display');
            const icon = button.querySelector('i');
            const fullToken = tokenDisplay.dataset.token;

            if (icon.classList.contains('fa-eye')) {
                tokenDisplay.textContent = fullToken;
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                tokenDisplay.textContent = fullToken.substring(0, 8) + '*'.repeat(fullToken.length - 16) + fullToken
                    .substring(fullToken.length - 8);
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function copyToken(token, buttonElement) {
            navigator.clipboard.writeText(token).then(function() {
                // Use the new AnimatedTooltip component
                AnimatedTooltip.showSuccess(buttonElement, 'کپی شد!');
            }).catch(function(err) {
                console.error('خطا در کپی کردن: ', err);
                AnimatedTooltip.showError(buttonElement, 'خطا در کپی!');
            });
        }

        function toggleStatus(tokenId) {
            fetch(`{{ route('admin.api-system.tokens.index', $system) }}/${tokenId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('خطا در تغییر وضعیت توکن');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('خطا در تغییر وضعیت توکن');
                });
        }

        function confirmDelete(tokenId, tokenName) {
            document.getElementById('tokenName').textContent = tokenName;
            document.getElementById('deleteForm').action =
                `{{ route('admin.api-system.tokens.index', $system) }}/${tokenId}`;
            $('#deleteModal').modal('show');
        }

        function regenerateToken(tokenId) {
            document.getElementById('regenerateForm').action =
                `{{ route('admin.api-system.tokens.index', $system) }}/${tokenId}/regenerate`;
            $('#regenerateModal').modal('show');
        }

        // Auto-refresh usage stats every 30 seconds
        setInterval(function() {
            // This would typically make an AJAX call to refresh usage statistics
            // Implementation depends on your specific requirements
        }, 30000);
    </script>
@endpush
