@extends('dashboard.layout.master')
@section('title','ویرایش رمز عبور')
@section('content')

    <div class="card">
        <form action="{{route('admin.admin.password.update', ['admin' => $admin->id])}}" method="post">
            @csrf
            @method('PATCH')
            <div class="row card-body">
                <div class="col-6">
                    <div class="form-group mt-3">
                        <label for="password">کلمه عبور</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control"
                            placeholder="کلمه عبور جدید خود را وارد کنید"
                        >@error('password')<small class="text-danger">{{$message}}</small>@enderror
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group mt-3">
                        <label for="password_confirmation"> تکرار کلمه عبور</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="form-control"
                            placeholder="تکرار کلمه عبور جدید خود را وارد کنید"
                        >@error('password')<small class="text-danger">{{$message}}</small>@enderror
                    </div>
                </div>


                <div class="col-12 text-center">
                    <button class="btn btn-primary my-4">ثبت تغییرات</button>
                </div>
            </div>
        </form>
    </div>

@endsection
