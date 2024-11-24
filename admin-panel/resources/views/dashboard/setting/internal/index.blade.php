@extends('dashboard.layout.master')
@section('title', 'تنظیمات و پیکربندی')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title header-elements">
                            <h5 class="m-0 me-2">تنظیمات و پیکربندی </h5>
                        </div>
                            <form action="{{route('admin.setting.int.update-permissions')}}" method="post">
                                @csrf
                                <button class="btn btn-success w-100">
                                    <i class="fa fa-refresh mx-2"></i>
                                    بروزرسانی مجوزها
                                </button>
                            </form>
                            <h6 class="text-center my-3">آخرین مجوزهای اضافه شده:</h6>
                             @foreach($last3permissions as $last3permission)
                                <p>{{$last3permission->name}}</p>
                             @endforeach

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
