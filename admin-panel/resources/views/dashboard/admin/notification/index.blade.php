@extends('dashboard.layout.master')
@section('title', 'اعلان های مدیریت')
@section('content')

    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد اعلان ها</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$notifications->count()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-users"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>اعلان های ادمین</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$notifications->count()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-solid fa-user-check"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست اعلان ها مدیریت</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>کاربر</th>
                        <th>نوع اعلان</th>
                        <th>متن پیام</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($notifications as $notification)

                        <tr>
                            <td>
                                {{\App\Helpers\DateFormatter::convertToPersianDate($notification->created_at,'H:i:s %Y/%m/%d')}}
                            </td>
                            <td>
                                {{ $notification->notifiable_type === 'App\Models\Admin' ? 'Admin' : 'Other' }}
                            </td>
                            <td>
                                <span
                                    class="badge bg-label-primary">{{ \App\Enums\NotificationType::getLabel($notification->type) }}</span>
                            </td>
                            <td>{{ $notification->data['message'] }}</td>
                            <td>
                                @if ($notification->read_at == null)
                                    <span class="text-primary">خوانده نشده</span>
                                @else
                                    <span class="text-success">خوانده شده</span>
                                @endif
                            </td>
                            <td>
                                @if ($notification->read_at == null)
                                    <form action="{{ route('admin.admin.notifications.mark-read', $notification->id) }}"
                                          method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-primary">Mark as Read</button>

                                    </form>
                                @else
                                    <span class="text-success">خوانده شده</span>
                                @endif
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
