@extends('dashboard.layout.master')
@section('title', 'تنظیمات و پیکربندی خارجی')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.setting.ext.update-ref-address')}}" method="post">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label for="ref_address">آدرس سرویس reference را وارد کنید:</label>
                                    <input class="form-control" dir="ltr" id="ref_address" type="text" name="ref_address" value="{{$ref_address}}" placeholder="http://127.0.0.1:8002/api">
                                    @error('ref_address')<small class="text-danger">{{$message}}</small>@enderror
                                </div>
                            </div>
                            <div class="col-md-2 ">
                                <button class="btn btn-primary w-100">
                                    <i class="fa fa-refresh mx-2"></i>
                                    ثبت تغییر
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

    </div>
@endsection
