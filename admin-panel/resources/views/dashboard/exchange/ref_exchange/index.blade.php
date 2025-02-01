@extends('dashboard.layout.master')
@section('title', 'مدیریت کدهای معرف')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">{{count($exchanges)}}</h5>
                            <small>تعداد صرافی های پشتیبانی شده</small>
                        </div>
                        <span class="badge bg-label-danger rounded-circle p-3">
                            <i class="fa-light fa-users fa-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">{{$activeExchange->name}}</h5>
                            <small>صرافی فعال</small>
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
                <h5 class="m-0 me-2">لیست صرافی ها</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>نام صرافی</th>
                        <th>slug</th>
                        <th>وضعیت</th>
                        <th>اولویت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($exchanges as $exchange)
                        <tr>
                            <td>
                                {{$exchange->id}}
                            </td>
                            <td>
                                {{$exchange->name}}
                            </td>
                            <td>
                                {{$exchange->slug}}
                            </td>
                            <td>
                                {{$exchange->is_active}}
                            </td>
                            <td>
                               {{$exchange->priority}}
                            </td>


                            <td>
                                <div class="d-flex align-items-center">

                                    <a class="text-secondary me-3"
                                       disabled="">
                                        <i class="fa-light fa-pen-to-square fa-lg"></i>
                                    </a>

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
