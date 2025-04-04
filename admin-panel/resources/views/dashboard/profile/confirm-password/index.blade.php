@extends('dashboard.profile.layout.master')
@section('profile-body')
    <div class="container">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <form action="{{route('password.confirm')}}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="password text-end">جهت

                                        @if(auth()->user()->two_factor_secret)
                                            <span class="text-danger">غیرفعالسازی</span>
                                        @else
                                            <span class="text-success">فعالسازی</span>
                                        @endif
                                    پسورد خود را وارد کنید</label>
                                <input
                                    id="password"
                                    name="password"
                                    class="form-control mt-2"
                                    type="password"
                                    placeholder="پسورد خود را وارد کنید">
                                @error('password')
                                <small class="text-danger">{{$message}}</small>
                                @enderror
                            </div>
                            <button class="btn btn-success my-3">فعالسازی</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection



