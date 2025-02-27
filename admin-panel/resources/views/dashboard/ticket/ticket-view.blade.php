@extends('dashboard.layout.master')
@section('title', 'تیکت')
@section('content')
    @php
        $ticketTypeEnum = App\Enums\TicketTypeEnum::fromModelClass($ticket->ticketable_type ?? 'unknown');
    @endphp
    <div class="row">
        <div class="col-lg-3 order-1 order-md-0">
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2">اطلاعات تیکت</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table card-table">
                            <tbody class="table-border-bottom-0">
                            <tr>
                                <td class="w-50 ps-0">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="me-2">
                                            <i class='ti ti-car ti-lg text-heading'></i>
                                        </div>
                                        <h6 class="mb-0 fw-normal">شماره تیکت</h6>
                                    </div>
                                </td>
                                <td class="text-end pe-0 text-nowrap">
                                    <h6 class="mb-0">{{$ticket->ticket_number}}</h6>
                                </td>
                            </tr>
                            <tr>
                                <td class="w-50 ps-0">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="me-2">
                                            <i class='ti ti-car ti-lg text-heading'></i>
                                        </div>
                                        <h6 class="mb-0 fw-normal">دسته بندی</h6>
                                    </div>
                                </td>
                                <td class="text-end pe-0 text-nowrap">
                                    <h6 class="mb-0">{{$ticketTypeEnum->label()}}</h6>
                                </td>
                            </tr>
                            <tr>
                                <td class="w-50 ps-0">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="me-2">
                                            <i class='ti ti-car ti-lg text-heading'></i>
                                        </div>
                                        <h6 class="mb-0 fw-normal">اولویت</h6>
                                    </div>
                                </td>
                                <td class="text-end pe-0 text-nowrap">
                                    <h6 class="mb-0">
                                        <span
                                            class="badge bg-label-{{$ticket->priority->color()}}">{{$ticket->priority->label()}}</span>
                                    </h6>
                                </td>
                            </tr>
                            <tr>
                                <td class="w-50 ps-0">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="me-2">
                                            <i class='ti ti-car ti-lg text-heading'></i>
                                        </div>
                                        <h6 class="mb-0 fw-normal">تاریخ ایجاد</h6>
                                    </div>
                                </td>
                                <td class="text-end pe-0 text-nowrap">
                                    <h6 class="mb-0">
                                        {{\App\Helpers\DateFormatter::convertToPersianDate($ticket->created_at,'%A, %d %B %Y')}}
                                    </h6>
                                </td>
                            </tr>
                            <tr>
                                <td class="w-50 ps-0">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="me-2">
                                            <i class='ti ti-car ti-lg text-heading'></i>
                                        </div>
                                        <h6 class="mb-0 fw-normal">وضعیت</h6>
                                    </div>
                                </td>
                                <td class="text-end pe-0 text-nowrap">
                                    <h6 class="mb-0">
                                        <span
                                            class="badge bg-label-{{$ticket->status->color()}}">{{$ticket->status->label()}}</span>
                                    </h6>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <x-user-details :user="$ticket->user"/>
        </div>
        <div class="col-xl-9 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-2">{{$ticket->subject}}</h5>
                    <div class="card-subtitle mb-5">
                        <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 my-2">

                            <li class="list-inline-item d-flex gap-2 align-items-center">
                                <i class="fa-regular fa-clock"></i>
                                <div class="text-body">ساخته شده:
                                    {{\App\Helpers\DateFormatter::convertToPersianDate($ticket->created_at,'%A, %d %B %Y')}}
                                    ({{\App\Helpers\DateFormatter::ago($ticket->created_at)}})
                                </div>
                            </li>
                        </ul>
                    </div>
                    <p class="card-text fw-medium mt-3">
                        {{$ticketReplies[count($ticketReplies)-1]->message}}
                    </p>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">

                    <form action="{{route('admin.ticket.replies.store',['ticket'=>$ticket])}}" method="POST">
                        @csrf
                        <x-tinymce-editor selector="message" :value="old('description')"/>

                        <div class="col-md p-6">
                            <small class="text-light fw-medium d-block">وضعیت</small>
                            <div class="form-check form-check-inline form-check-{{\App\Enums\TicketStatusEnum::WaitingForCustomer->color()}} mt-4">
                                <input class="form-check-input" type="radio" name="status" checked id="in_progress" value="{{\App\Enums\TicketStatusEnum::WaitingForCustomer}}">
                                <label class="form-check-label" for="in_progress">در انتظار پاسخ مشتری</label>
                            </div>
                            <div class="form-check form-check-inline form-check-{{\App\Enums\TicketStatusEnum::RESOLVED->color()}}">
                                <input class="form-check-input" type="radio" name="status" id="closed" value="{{\App\Enums\TicketStatusEnum::RESOLVED}}">
                                <label class="form-check-label" for="closed">حل شده</label>
                            </div>
                            <div class="form-check form-check-inline form-check-{{\App\Enums\TicketStatusEnum::CLOSED->color()}}">
                                <input class="form-check-input" type="radio" name="status" id="closed" value="{{\App\Enums\TicketStatusEnum::CLOSED}}">
                                <label class="form-check-label" for="closed">بسته شده</label>
                            </div>
                        </div>
                        <button class="btn btn-success mt-3">
                            <i class="fa-regular fa-reply me-2"></i>
                            پاسخ به تیکت
                        </button>
                    </form>

                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="card-title">مکالمات</h4>
                </div>
                @foreach($ticketReplies as $reply)
                    <div class="card-body border-bottom">

                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-2 align-self-start flex-shrink-0">
                                <img class="img-fluid rounded mb-4"
                                     src="{{ $reply->getRepliableAvatar() }}"
                                     alt="{{ $reply->getRepliableName() }} Avatar"/>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="d-flex mb-1">
                                    <h5 class="mb-0">{{ $reply->getRepliableName() }}</h5>
                                    <small
                                        class="align-self-baseline badge bg-label-{{App\Enums\TicketRepliableTypeEnum::fromModelClass($reply->repliable_type)->color()}} ms-3">{{ $reply->getRepliableRole() }}</small>
                                </div>

                                <small class="text-muted">
                                    <i class="fa-regular fa-clock"></i>
                                    {{\App\Helpers\DateFormatter::convertToPersianDate($reply->created_at,'%A, %d %B %Y')}}
                                    ({{\App\Helpers\DateFormatter::ago($reply->created_at)}})
                                </small>


                                <p class="mt-5">{!! $reply->message !!} </p>
                            </div>
                        </div>


                    </div>
                @endforeach

            </div>
        </div>

    </div>

@endsection
@section('vendor-script')
    <script>
        $(document).ready(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
    @vite([

            'resources/assets/vendor/libs/tinymce/tinymce.js'
          ])
@endsection
