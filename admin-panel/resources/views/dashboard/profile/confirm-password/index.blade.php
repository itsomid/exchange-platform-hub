@extends('dashboard.profile.layout.master')
@section('body')
    <div class="container">
        <div class="card">
            <div class="row justify-content-center">
                <div class="col-md-8 my-5 text-center">
                    <form action="{{route('password.confirm')}}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="password">  جهت فعالسازی پسورد خود را وارد کنید</label>
                            <input
                                id="password"
                                name="password"
                                class="form-control"
                                type="password"
                                placeholder="پسورد خود را وارد کنید">
                            @error('password')
                                <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                        <button class="btn btn-success my-3">فعاسازی</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


