@extends('dashboard.layout.master')
@section('title', 'مدیریت تیکت ها')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <small>همه تیکت ها</small>
                            <h5 class="mb-1">{{$tickets->total()}}</h5>
                        </div>
                        <span class="badge bg-label-primary rounded-circle p-3">
                            <i class="fa-light fa-tickets fa-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <small>درانتظار پاسخ</small>
                            <h5 class="mb-1">{{$pendingTicketCount}}</h5>
                        </div>
                        <span class="badge bg-label-warning rounded-circle p-3">
                        <i class="fa-light fa-tickets fa-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <small>پاسخ داده شده</small>
                            <h5 class="mb-1">{{$answeredTicketCount}}</h5>
                        </div>
                        <span class="badge bg-label-success rounded-circle p-3">
                            <i class="fa-light fa-tickets fa-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <small>بسته شده</small>
                            <h5 class="mb-1">{{$closedTicketCount}}</h5>
                        </div>
                        <span class="badge bg-label-danger rounded-circle p-3">
                              <i class="fa-light fa-tickets fa-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card">

        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست تیکت ها</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>شماره</th>
                        <th>کاربر</th>
                        <th>تاریخ</th>
                        <th>موضوع</th>
                        <th>دسته بندی</th>
                        <th>اولویت</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($tickets as $ticket)
                        @php
                             $ticketTypeEnum = App\Enums\TicketTypeEnum::fromModelClass($ticket->ticketable_type ?? 'unknown');
                        @endphp
                        <tr>
                            <td>
                                {{$ticket->ticket_number}}
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <a href="" class="text-heading text-truncate">
                                        <span class="fw-medium">{{$ticket->user->email}}</span>
                                    </a>
                                    <small>{{$ticket->user->username}}</small>
                                    <small>{{$ticket->user->fullname()}}</small>
                                </div>
                            </td>
                            <td>
                                {{\App\Helpers\DateFormatter::convertToPersianDate($ticket->created_at,'H:i %Y/%m/%d')}}
                            </td>
                            <td>{{$ticket->subject}}</td>
                            <td>
                                <span
                                    class="badge bg-label-{{$ticketTypeEnum->color()}}">{{$ticketTypeEnum->label()}}</span>
                            </td>
                            <td>
                                  <span
                                      class="badge bg-label-{{$ticket->priority->color()}}">{{$ticket->priority->label()}}</span>
                            </td>
                            <td>
                                 <span
                                     class="badge bg-label-{{$ticket->status->color()}}">{{$ticket->status->label()}}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{route('admin.ticket.replies.index',['ticket'=>$ticket])}}" class="btn btn-icon btn-text-secondary">
                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>
                                    <form action="{{route('admin.tickets.destroy',['ticket' => $ticket])}}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-icon btn-text-danger">
                                            <i class="fa-light fa-trash-alt fa-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    {{$tickets->appends(request()->all())->links()}}
                </div>
            </div>
        </div>
    </div>

@endsection
