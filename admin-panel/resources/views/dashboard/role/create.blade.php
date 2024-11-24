@extends('dashboard.layout.master')
@section('title', 'افزودن نقش جدید')
@section('content')
    <div class="row">
        <div class="card">
            <div class="card-body">
                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">افزودن نقش جدید</h5>
                    <div class="card-title-elements ms-auto">
                        <a href="{{route('admin.role.index')}}" class="btn btn-warning">
                            <i class="fa fa-undo mx-2"></i>
                            بازگشت به لیست نقش ها
                        </a>
                    </div>
                </div>

                <form action="{{route('admin.role.store')}}" method="post" class="mt-5">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="persian_name">برچسب فارسی</label>
                                <input id="persian_name" type="text" class="form-control" name="persian_name" value="{{old('persian_name')}}" placeholder="برچسب فارسی نقش را وارد کنید" autocomplete="off">
                                @error('persian_name')
                                    <small class="text-danger">{{$message}}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">نام نقش</label>
                                <input id="name" type="text" class="form-control" name="name" value="{{old('name')}}" placeholder="نام نقش را وارد کنید" autocomplete="off">
                                @error('name')
                                    <small class="text-danger">{{$message}}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mt-5">
                        <div class="col-12">
                            <h5>مجوز ها</h5>
                        </div>
                        @foreach($permissions as $permission)
                            <div class="col-4">
                                <div class="form-check mt-3">
                                    <input
                                        name="permissions[]"
                                        class="form-check-input"
                                        type="checkbox"
                                        value="{{$permission->id}}"
                                        id="permission_{{$permission->id}}"/>

                                    <label
                                        class="form-check-label"
                                        title="{{$permission->description}}"
                                        for="permission_{{$permission->id}}">
                                        {{$permission->persian_name}}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                        @error('permissions') <small class="form-text text-danger">{{ $message }}</small>@enderror
                        <div class="col-12 text-center">
                            <button class="btn btn-primary my-5">
                                <i class="fa fa-save"></i>
                                <span class="mx-2">
                                    ذخیره
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
