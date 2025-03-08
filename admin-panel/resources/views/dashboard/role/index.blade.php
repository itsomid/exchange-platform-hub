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
                            <th>عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($roles as $role)
                            <tr>
                                <td class="w-50">{{$role->persian_name}}</td>
                                <td class="w-50">{{$role->name}}</td>
                                <td class="w-25">{{$role->guard_name}}</td>
                                <td class="w-25">{{\Morilog\Jalali\Jalalian::forge($role->updated_at)->format('%A, %d %B %Y')}}</td>
                                <td>
                                    <a href="{{route('admin.role.edit', ['role' => $role->id])}}" class="">
                                        <i class="fa fa-pen mx-2"></i>
                                    </a>
                                    <a  class="btn btn-link p-0 text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{$role->id}}">
                                        <i class="fa fa-trash mx-2"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Modals placed outside the table but within the card-body -->
                @foreach($roles as $role)
                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal{{$role->id}}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">حذف نقش</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    آیا از حذف نقش "{{$role->persian_name}}" اطمینان دارید؟
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                                    <form action="{{route('admin.role.destroy', $role)}}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">حذف</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
