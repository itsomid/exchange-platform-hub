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
                                <h4 class="mb-0 me-2">{{$notifications->total()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-users"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر</h5>
            </div>
            <div class="d-flex justify-content-between">
                <form action="{{route('admin.admin.notifications.index')}}" method="get" class="mt-3 d-flex align-items-end">


                    <div class="form-group me-3">
                        <label class="form-label" for="type">نوع اعلان:</label>
                        <select name="type" class="form-control" id="type">
                            <option value="">همه</option>
                            @foreach(\App\Enums\NotificationTypeEnum::cases() as $case)
                                <option
                                    value="{{$case->value}}" {{request()->has('type') && request()->input('type') === $case->value ? 'selected' : "" }}>
                                    {{\App\Enums\NotificationTypeEnum::getLabel($case->value)}}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="form-group">
                        <button class="btn btn-success text-white">
                            <span>فیلتر</span><i class="fas fa-filter mx-3"></i>
                        </button>
                    </div>

                </form>
                <form action="{{ route('admin.admin.admin.notifications.destroyAll') }}" method="POST"
                      onsubmit="return confirm('مطمینی میخوای همه اعلان هارو پاک کنی؟');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="type" value="{{ request('type') }}">
                    <!-- Add other filter fields as hidden inputs if needed -->
                    <button type="submit" class="btn btn-danger">پاک کردن اعلان ها</button>
                </form>
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
                                <span
                                    class="badge bg-label-primary">{{ \App\Enums\NotificationTypeEnum::getLabel($notification->type) }}</span>
                            </td>
                            <td class="text-wrap">{{ $notification->data['message'] }}</td>
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
            <div class="row mt-4">
                <div class="col-md-12">
                    {{$notifications->appends(request()->all())->links()}}
                </div>
            </div>
        </div>
    </div>

@endsection
