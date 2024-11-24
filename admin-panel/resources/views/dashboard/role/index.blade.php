@extends('dashboard.layout.master')
@section('title', 'لیست نقش ها')
@section('content')
    <div class="row">
        <div class="card">
            <div class="card-body">
                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">لیست نقش ها</h5>
                    @can('role.create')
                        <div class="card-title-elements ms-auto">
                            <a href="{{route('admin.role.create')}}" class="btn btn-primary">
                                <i class="fa fa-plus mx-2"></i>
                                افزودن نقش جدید
                            </a>
                        </div>
                    @endcan
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>برچسب فارسی</th>
                            <th>نام نقش</th>
                            <th>گارد</th>
                            <th>آخرین ویرایش</th>
                            @canany(['role.edit'])
                                <th>عملیات</th>
                            @endcanany

                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($roles as $role)
                            <tr>
                                <td class="w-50">{{$role->persian_name}}</td>
                                <td class="w-50">{{$role->name}}</td>
                                <td class="w-25">{{$role->guard_name}}</td>
                                <td class="w-25">{{\Morilog\Jalali\Jalalian::forge($role->updated_at)->format('%A, %d %B %Y')}}</td>
                                @canany(['role.edit'])
                                <td>
                                    <a href="{{route('admin.role.edit', ['role' => $role->id])}}" class="btn btn-info rounded">
                                        <i class="fa fa-pen mx-2"></i>                                ویرایش
                                    </a>
                                </td>
                                @endcanany
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
