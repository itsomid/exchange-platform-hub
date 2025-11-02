@extends('dashboard.layout.master')

@section('title', 'مدیریت سیستم‌های API')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">سیستم‌های API</h4>
                    <a href="{{ route('admin.api-system.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> افزودن سیستم جدید
                    </a>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" name="search" class="form-control"
                                    placeholder="جستجو در نام یا توضیحات..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-control">
                                    <option value="">همه وضعیت‌ها</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال
                                    </option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                        غیرفعال</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-search me-1"></i> جستجو
                                </button>
                                <a href="{{ route('admin.api-system.statistics') }}" class="btn btn-info ms-2">
                                    <i class="fas fa-chart-bar me-1"></i> آمار کلی
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">کل سیستم‌ها</h6>
                                            <h3>{{ $systems->total() }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-server fa-2x"></i>
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
                                            <h6 class="card-title">سیستم‌های فعال</h6>
                                            <h3>{{ $systems->where('is_active', true)->count() }}</h3>
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
                                            <h6 class="card-title">سیستم‌های غیرفعال</h6>
                                            <h3>{{ $systems->where('is_active', false)->count() }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-pause-circle fa-2x"></i>
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
                                            <h6 class="card-title">درخواست‌های امروز</h6>
                                            <h3 id="today-requests">-</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-exchange-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- API Systems Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>شناسه</th>
                                    <th>نام سیستم</th>
                                    <th>توضیحات</th>
                                    <th>وضعیت</th>
                                    <th>تعداد توکن‌ها</th>
                                    <th>آخرین درخواست</th>
                                    <th>تاریخ ایجاد</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($systems as $system)
                                    <tr>
                                        <td>{{ $system->id }}</td>
                                        <td>
                                            <strong>{{ $system->name }}</strong>
                                            @if ($system->contact_email)
                                                <br><small class="text-muted">{{ $system->contact_email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span title="{{ $system->description }}">
                                                {{ Str::limit($system->description, 50) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $system->is_active ? 'success' : 'secondary' }}">
                                                {{ $system->is_active ? 'فعال' : 'غیرفعال' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ $system->tokens_count ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($system->last_used_at)
                                                <span title="{{ $system->last_used_at }}">
                                                    {{ $system->last_used_at->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-muted">هیچ درخواستی</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span title="{{ $system->created_at }}">
                                                {{ App\Helpers\DateFormatter::convertToPersianDate($system->created_at,'H:i:s %Y/%m/%d') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.api-system.show', $system) }}"
                                                    class="btn btn-sm btn-info" title="مشاهده جزئیات">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.api-system.edit', $system) }}"
                                                    class="btn btn-sm btn-warning" title="ویرایش">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST"
                                                    action="{{ route('admin.api-system.toggle-status', $system) }}"
                                                    style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-{{ $system->is_active ? 'secondary' : 'success' }}"
                                                        title="{{ $system->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}"
                                                        style="border-radius: 0">
                                                        <i class="fas fa-{{ $system->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="confirmDelete({{ $system->id }})" title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-server fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">هیچ سیستم API‌ای یافت نشد</h5>
                                                <p class="text-muted">برای شروع، یک سیستم API جدید ایجاد کنید.</p>
                                                <a href="{{ route('admin.api-system.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> افزودن سیستم جدید
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($systems->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $systems->appends(request()->query())->links() }}
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
                    <h5 class="modal-title">تأیید حذف</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>آیا از حذف این سیستم API اطمینان دارید؟</p>
                    <p class="text-danger">
                        <strong>توجه:</strong> این عمل غیرقابل بازگشت است و تمام توکن‌ها و تاریخچه مربوط به این سیستم حذف
                        خواهد شد.
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
@endsection

@push('scripts')
    <script>
        function confirmDelete(systemId) {
            const form = document.getElementById('deleteForm');
            form.action = `/admin/api-systems/${systemId}`;
            $('#deleteModal').modal('show');
        }

        // Load today's requests count
        $(document).ready(function() {
            // You can implement an AJAX call here to load real-time statistics
            // For now, we'll just show a placeholder
            $('#today-requests').text('{{ $systems->sum('requests_count') ?? 0 }}');
        });
    </script>
@endpush
