@extends('dashboard.layout.master')
@section('title', 'استعلام کاربر')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">استعلام کاربر</h5>
                    <form action="{{ route('admin.inquiry.submit') }}"
                        class="row mt-3 d-flex align-items-end justify-content-between" method="post">
                        @csrf
                        <div class="col-md-4 user_role">
                            <label class="form-label" for="email">ایمیل یا شناسه کاربر:</label>
                            <div class="input-group">
                                <input type="text" id="email" name="email" class="form-control"
                                    placeholder="ایمیل یا #شناسه کاربری" value="{{ old('email') }}">
                                <button type="submit" class="btn btn-primary text-white">
                                    <span class="mx-2">جستجو</span>
                                    <i class="fa-regular fa-search"></i>
                                </button>
                            </div>

                        </div>
                        <div class="col-md-12 mt-2">
                            <div class="alert alert-info mb-0">
                                <div class="fw-semibold mb-2">نکات جستجو</div>
                                <div class="small">
                                    <div class="mb-1">برای جستجو با شناسه، مقدار را با # شروع کنید؛ مثال: <span
                                            class="badge bg-secondary">#42</span></div>
                                    <div class="mb-1">ایمیل: ابتدا تطابق دقیق بررسی می‌شود؛ در صورت نبود، ایمیل‌های
                                        تاییدشده در اولویت نمایش هستند.</div>
                                    <div>می‌توانید بخشی از ایمیل را وارد کنید؛ مثال: <span
                                            class="badge bg-secondary">shervin</span></div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
