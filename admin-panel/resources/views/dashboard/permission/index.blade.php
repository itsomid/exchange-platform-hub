@extends('dashboard.layout.master')
@section('title', 'لیست مجوز ها')
@section('content')
    <div class="row">
        <div class="card">
            <div class="card-body">
                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">لیست مجوز ها</h5>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>برچسب فارسی</th>
                            <th>عنوان انگلیسی</th>
                            <th>گارد</th>
                            <th class="text-center">توضیحات</th>
                            @can('permission.edit')
                                <th class="text-center">ویرایش</th>
                            @endcan

                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($permissions as $permission)
                            <tr>
                                <td class="w-25">{{$permission->persian_name}}</td>
                                <td class="w-25">{{$permission->name}}</td>
                                <td class="w-25">{{$permission->guard_name}}</td>
                                <td class="w-50 text-center">
                                    <small title="{{$permission->description}}">
                                        {{str()->substr($permission->description, 0 , 50)}}
                                        @if(strlen($permission->description) > 50)
                                            ...
                                        @endif

                                    </small>
                                </td>
                                @can('permission.edit')
                                    <td>
                                        <a href="{{route('admin.permission.edit', ['permission' => $permission->id])}}" class="btn btn-info rounded btn-sm">
                                            <i class="fa fa-pen mx-2"></i>                                ویرایش
                                        </a>
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
