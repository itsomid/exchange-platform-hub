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
                            <h5 class="mb-1">{{ $tickets->total() }}</h5>
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
                            <h5 class="mb-1">{{ $pendingTicketCount }}</h5>
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
                            <h5 class="mb-1">{{ $answeredTicketCount }}</h5>
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
                            <h5 class="mb-1">{{ $closedTicketCount }}</h5>
                        </div>
                        <span class="badge bg-label-danger rounded-circle p-3">
                            <i class="fa-light fa-tickets fa-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <div class="card-title header-elements mb-0">
                <h5 class="m-0 me-2 text-primary">
                    <i class="fas fa-filter me-2"></i>فیلتر تیکت‌ها
                </h5>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.tickets.index') }}" method="get">
                <!-- First Row: Type, Status, Subject, Message -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="type">
                                <i class="fas fa-tag me-1 text-muted"></i>نوع تیکت:
                            </label>
                            <select name="type" class="form-select" id="type">
                                <option value="">همه انواع</option>
                                @foreach (\App\Enums\TicketTypeEnum::cases() as $case)
                                    <option value="{{ $case->value }}"
                                        {{ request()->has('type') && request()->input('type') == $case->value ? 'selected' : '' }}>
                                        {{ $case->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="status">
                                <i class="fas fa-info-circle me-1 text-muted"></i>وضعیت:
                            </label>
                            <select name="status" class="form-select" id="status">
                                <option value="">همه وضعیت‌ها</option>
                                @foreach (\App\Enums\TicketStatusEnum::cases() as $case)
                                    <option value="{{ $case->value }}"
                                        {{ request()->has('status') && request()->input('status') == $case->value ? 'selected' : '' }}>
                                        {{ $case->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="subject">
                                <i class="fas fa-heading me-1 text-muted"></i>موضوع:
                            </label>
                            <input type="text" class="form-control" id="subject" name="subject"
                                value="{{ request('subject') }}" placeholder="جستجو در موضوع تیکت...">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="message">
                                <i class="fas fa-comment me-1 text-muted"></i>متن پیام:
                            </label>
                            <input type="text" class="form-control" id="message" name="message"
                                value="{{ request('message') }}" placeholder="جستجو در متن پیام‌ها...">
                        </div>
                    </div>
                </div>

                <!-- Second Row: User Selection and Filter Button -->
                <div class="row g-3 align-items-end">
                    <div class="col-lg-8 col-md-8">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="user">
                                <i class="fas fa-user me-1 text-muted"></i>کاربر:
                            </label>
                            <x-user-selection-component input-name="user" multiple="0"
                                selected="{{ request()->filled('user') ? $tickets[0]->user->id ?? '' : '' }}"
                                selected-label="{{ request()->filled('user') && isset($tickets[0]) && $tickets[0]->user
                                    ? '(' . $tickets[0]->user->id . '#) ' . $tickets[0]->user->fullname() . ' | ' . $tickets[0]->user->email
                                    : '' }}"></x-user-selection-component>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary px-4" type="submit">
                                <i class="fas fa-search me-2"></i>اعمال فیلتر
                            </button>
                            @if (request()->hasAny(['type', 'status', 'subject', 'message', 'user']))
                                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary px-3">
                                    <i class="fas fa-times me-1"></i>پاک کردن
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
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
                        @foreach ($tickets as $ticket)
                            @php
                                $ticketTypeEnum = App\Enums\TicketTypeEnum::fromModelClass(
                                    $ticket->ticketable_type ?? 'unknown',
                                );
                            @endphp
                            <tr>
                                <td>
                                    {{ $ticket->ticket_number }}
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="" class="text-heading text-truncate">
                                            <span class="fw-medium">{{ $ticket->user->email }}</span>
                                        </a>
                                        <small>{{ $ticket->user->username }}</small>
                                        <small>{{ $ticket->user->fullname() }}</small>
                                    </div>
                                </td>
                                <td>
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($ticket->created_at, 'H:i %Y/%m/%d') }}
                                </td>
                                <td>{{ $ticket->subject }}</td>
                                <td>
                                    <span
                                        class="badge bg-label-{{ $ticketTypeEnum->color() }}">{{ $ticketTypeEnum->label() }}</span>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-label-{{ $ticket->priority->color() }}">{{ $ticket->priority->label() }}</span>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-label-{{ $ticket->status->color() }}">{{ $ticket->status->label() }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('admin.ticket.replies.index', ['ticket' => $ticket]) }}"
                                            class="btn btn-icon btn-text-secondary">
                                            <i class="fa-light fa-eye fa-lg"></i>
                                        </a>
                                        <form action="{{ route('admin.tickets.destroy', ['ticket' => $ticket]) }}"
                                            method="post">
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
                    {{ $tickets->appends(request()->all())->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
