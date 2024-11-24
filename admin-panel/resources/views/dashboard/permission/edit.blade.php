@extends('dashboard.layout.master')
@section('title', 'ویرایش مجوز '. $permission->name)
@section('content')
    <div class="row">
        <div class="card">
            <div class="card-body">
                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">ویرایش مجوز {{$permission->persian_name}}</h5>
                </div>
                <form action="{{route('admin.permission.update', ['permission' => $permission->id])}}" method="post">
                    @csrf
                    @method('PATCH')
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="name">عنوان</label>
                            <input type="text" id="name" class="form-control" value="{{$permission->name}}" disabled>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="persian_name">عنوان فارسی</label>
                            <input type="text" id="persian_name" class="form-control" value="{{$permission->persian_name}}" name="persian_name">
                        </div>
                        <div class="col-md-12 mt-3 form-group">
                            <label for="description">توضیحات</label>
                            <textarea id="description" name="description" class="form-control">{{$permission->description}}</textarea>
                        </div>
                        <div class="col-12 text-center">
                            <button class="btn btn-primary my-3">
                                <i class="fa fa-save mx-3"></i>
                                ثبت تغییرات
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
