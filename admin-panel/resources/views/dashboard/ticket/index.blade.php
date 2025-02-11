@extends('dashboard.layout.master')
@section('title', 'مدیریت کدهای معرف')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1"></h5>
                            <small>همه تیکت ها</small>
                        </div>
                        <span class="badge bg-label-danger rounded-circle p-3">
                            <i class="fa-light fa-users fa-xl"></i>
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
                            <h5 class="mb-1"></h5>
                            <small>درانتظار پاسخ</small>
                        </div>
                        <span class="badge bg-label-success rounded-circle p-3">
                            <i class="fa-light fa-gift fa-xl"></i>
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
                            <h5 class="mb-1"></h5>
                            <small>پاسخ داده شده</small>
                        </div>
                        <span class="badge bg-label-success rounded-circle p-3">
                            <i class="fa-light fa-gift fa-xl"></i>
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
                            <h5 class="mb-1"></h5>
                            <small>بسته شده</small>
                        </div>
                        <span class="badge bg-label-success rounded-circle p-3">
                            <i class="fa-light fa-gift fa-xl"></i>
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
                                {{$ticket->created_at}}
                            </td>
                            <td>
                                {{$ticket->subject}}
                            </td>
                            <td>
                                <span
                                    class="badge bg-label-{{$ticket->ticketable_type->color()}}">{{$ticket->ticketable_type->label()}}</span>
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

                                    <a href="" class="text-secondary me-3">
                                        <i class="fa-light fa-pen-to-square fa-lg"></i>
                                    </a>
                                    <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                       data-bs-target="#deposit-{{$ticket->id}}">
                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>
                                    <form action="{{route('admin.tickets.destroy',['ticket'=>$ticket])}}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-icon btn-danger ">
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
            {{--                @include('dashboard.layout.pagination', ['collection' => $regentCodes])--}}
        </div>
    </div>

@endsection
