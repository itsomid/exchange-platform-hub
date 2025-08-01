@extends('dashboard.layout.master')

@section('title', 'مدیریت کانتینرهای داکر')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">مدیریت کانتینرهای داکر</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title text-white">مدیریت کانتینر Exchange Listen</h5>
                                    <p class="card-text">این کانتینر مسئول گوش دادن به تغییرات قیمت از صرافی‌ها است.</p>
                                    <form action="{{ route('admin.docker.restart-exchange-listen') }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-light me-2"
                                            onclick="return confirm('آیا از ریستارت کردن کانتینر exchange-listen اطمینان دارید؟')">
                                            <i class="fas fa-redo me-1"></i> ریستارت کانتینر
                                        </button>
                                        <button type="button" class="btn btn-light" onclick="showExchangeListenLogs()">
                                            <i class="fas fa-file-alt me-1"></i> نمایش لاگ‌ها
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">وضعیت کانتینرها</h5>
                                    <p class="card-text">نمایش وضعیت فعلی تمام کانتینرهای داکر</p>
                                    <button type="button" class="btn btn-light me-2" onclick="refreshContainerStatus()">
                                        <i class="fas fa-sync-alt me-1"></i> بروزرسانی وضعیت
                                    </button>
                                    <button type="button" class="btn btn-outline-light" onclick="debugDockerPaths()">
                                        <i class="fas fa-bug me-1"></i> Debug مسیرها
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">وضعیت کانتینرها</h5>
                                </div>
                                <div class="card-body">
                                    <div id="container-status">
                                        <div class="text-center ">
                                            <div class="spinner-border" role="status">
                                                <span class="visually-hidden">در حال بارگذاری...</span>
                                            </div>
                                            <p class="mt-2">در حال دریافت وضعیت کانتینرها...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4" id="logs-section" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">لاگ‌های کانتینر Exchange Listen</h5>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="hideLogs()">
                                        <i class="fas fa-times me-1"></i> بستن
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="exchange-listen-logs">
                                        <div class="text-center">
                                            <div class="spinner-border" role="status">
                                                <span class="visually-hidden">در حال بارگذاری...</span>
                                            </div>
                                            <p class="mt-2">در حال دریافت لاگ‌ها...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function refreshContainerStatus() {
            const statusDiv = document.getElementById('container-status');
            statusDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">در حال بارگذاری...</span>
            </div>
            <p class="mt-2">در حال دریافت وضعیت کانتینرها...</p>
        </div>
    `;

            fetch('{{ route('admin.docker.container-status') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.innerHTML = `
                    <div class="table-responsive text-end" dir="ltr">
                        <pre class="bg-dark text-white p-3 rounded" style="font-size: 14px; max-height: 400px; overflow-y: auto;">${data.containers}</pre>
                    </div>
                `;
                    } else {
                        statusDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        خطا در دریافت وضعیت کانتینرها: ${data.error}
                    </div>
                `;
                    }
                })
                .catch(error => {
                    statusDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    خطا در ارتباط با سرور: ${error.message}
                </div>
            `;
                });
        }

        function showExchangeListenLogs() {
            const logsSection = document.getElementById('logs-section');
            const logsDiv = document.getElementById('exchange-listen-logs');

            // نمایش بخش لاگ‌ها
            logsSection.style.display = 'block';

            // اسکرول به بخش لاگ‌ها
            logsSection.scrollIntoView({
                behavior: 'smooth'
            });

            // نمایش loading
            logsDiv.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                    <p class="mt-2">در حال دریافت لاگ‌ها...</p>
                </div>
            `;

            // دریافت لاگ‌ها
            fetch('{{ route('admin.docker.exchange-listen-logs') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        logsDiv.innerHTML = `
                            <div class="table-responsive text-end" dir="ltr">
                                <pre class="bg-dark text-white p-3 rounded" style="font-size: 12px; max-height: 500px; overflow-y: auto;">${data.logs}</pre>
                            </div>
                        `;
                    } else {
                        logsDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                خطا در دریافت لاگ‌ها: ${data.error}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    logsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            خطا در ارتباط با سرور: ${error.message}
                        </div>
                    `;
                });
        }

        function hideLogs() {
            const logsSection = document.getElementById('logs-section');
            logsSection.style.display = 'none';
        }

        function debugDockerPaths() {
            fetch('{{ route('admin.docker.debug-paths') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let debugInfo = '<h6>اطلاعات Debug:</h6>';
                        debugInfo += '<pre class="bg-light p-3 rounded text-end ltr" style="font-size: 12px; max-height: 400px; overflow-y: auto;">';
                        debugInfo += JSON.stringify(data.debug, null, 2);
                        debugInfo += '</pre>';
                        
                        // نمایش در modal یا alert
                        const modalHtml = `
                            <div class="modal fade" id="debugModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Debug اطلاعات Docker</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body" dir="ltr">
                                            ${debugInfo}
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // حذف modal قبلی اگر وجود داشته باشد
                        const existingModal = document.getElementById('debugModal');
                        if (existingModal) {
                            existingModal.remove();
                        }
                        
                        // اضافه کردن modal جدید
                        document.body.insertAdjacentHTML('beforeend', modalHtml);
                        
                        // نمایش modal
                        const modal = new bootstrap.Modal(document.getElementById('debugModal'));
                        modal.show();
                    } else {
                        alert('خطا در دریافت اطلاعات debug: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('خطا در ارتباط با سرور: ' + error.message);
                });
        }

        // بارگذاری اولیه وضعیت کانتینرها
        document.addEventListener('DOMContentLoaded', function() {
            refreshContainerStatus();
        });
    </script>
@endsection
@section('vendor-style')
    <style>
        .bg-dark {
            background-color: #1a1a1a !important;
        }
    </style>
@endsection
