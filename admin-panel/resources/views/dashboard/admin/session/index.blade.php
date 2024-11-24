@extends('dashboard.layout.master')
@section('title', ' نشست های فعال ' . $admin->fullname())
@section('content')
    <div class="row">
        <div class="card">
            <div class="card-body">

                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">لیست نشست های فعال</h5>
                    @can('session.destroy')
                        <div class="card-title-elements ms-auto">
                            <a href="{{route('admin.session.purge', ['admin' => $admin])}}" class="btn btn-danger">
                                <i class="fa fa-skull mx-2"></i>
                                حذف همه ی نشست ها
                            </a>
                        </div>
                    @endcan
                </div>

                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>ip</th>
                            <th>سیستم</th>
                            <th>مرورگر</th>
                            <th>تاریخ انقضا</th>
                            @can('session.destroy')
                                <th>logout</th>
                            @endcan
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach($admin->sessions as $session)
                                <tr>
                                    <td>{{$session->ip_address}}</td>
                                    <td>{{$session->platform()}}</td>
                                    <td>{{$session->browser()}}</td>
                                    <td>
                                        @if($session->isExpired())
                                            <span class="badge bg-label-danger me-1}}">
                                                {{$session->expires_at()}}
                                            </span>
                                        @else
                                            <span class="badge bg-label-success me-1}}">
                                                {{$session->expires_at()}}
                                            </span>
                                        @endif

                                    </td>
                                    @can('session.destroy')
                                        <td>
                                            <form action="{{route('admin.session.destroy', ['admin' => $admin, 'session' => $session->id])}}"
                                                  method="post">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm ">
                                                    <i class="fa fa-trash mx-2"></i>
                                                    حذف نشست
                                                </button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
