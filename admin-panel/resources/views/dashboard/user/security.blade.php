@extends('dashboard.user.layout.master')
@section('title','ویرایش رمز عبور')
@section('user-body')



        <!-- Two-steps verification -->
        <div class="card mb-6">
            <div class="card-body">
                <h5 class="mb-6">ارسال لینک بازیابی رمز عبور به ایمیل کاربر</h5>

                <a href="{{route('admin.user.reset-password-email',['user'=>$user->id])}}" class="btn btn-primary mt-2">ارسال لینک بازیابی رمز عبور</a>
            </div>
        </div>

        <div class="card mb-6">
            <h5 class="card-header">دستگاه های اخیر کاربر</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="text-truncate">مرورگر</th>
                        <th class="text-truncate">دستگاه</th>
                        <th class="text-truncate">مکان</th>
                        <th class="text-truncate">آخرین فعالیت</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="text-truncate text-heading fw-medium">
                            <i class="fa-brands fa-windows me-2"></i>Chrome on Windows
                        </td>
                        <td class="text-truncate">HP Spectre 360</td>
                        <td class="text-truncate">Switzerland</td>
                        <td class="text-truncate">10, July 2021 20:07</td>
                    </tr>
                    <tr>
                        <td class="text-truncate text-heading fw-medium">
                            <i class="fa-brands fa-apple me-2"></i>Chrome on iPhone</td>
                        <td class="text-truncate">iPhone 12x</td>
                        <td class="text-truncate">Australia</td>
                        <td class="text-truncate">13, July 2021 10:10</td>
                    </tr>
                    <tr>
                        <td class="text-truncate text-heading fw-medium">
                            <i class="fa-brands fa-android me-2"></i>
                            Chrome on Android</td>
                        <td class="text-truncate">Oneplus 9 Pro</td>
                        <td class="text-truncate">Dubai</td>
                        <td class="text-truncate">14, July 2021 15:15</td>
                    </tr>

                    </tbody>
                </table>
            </div>
        </div>

@endsection
