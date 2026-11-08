@extends('dashboard.profile.layout.master')
@section('profile-body')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('password.confirm') }}" method="POST" style="max-width: 440px">
                @csrf
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-regular fa-lock text-primary"></i>
                    <h6 class="mb-0">تأیید رمز عبور</h6>
                </div>
                <label class="form-label" for="password">برای ادامه، رمز عبور خود را وارد کنید</label>
                <input id="password" name="password" class="form-control" type="password" autofocus
                       autocomplete="current-password" placeholder="رمز عبور">
                @error('password')
                <small class="text-danger">{{ $message }}</small>
                @enderror
                <button class="btn btn-primary w-100 mt-3">تأیید</button>
            </form>
        </div>
    </div>
@endsection
