@extends('dashboard.layout.master')
@section('title', 'مدیریت کوین ها')
@section('content')

        <div class="row g-4 mb-4">
            <div class="col-sm-12 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>تعداد کوین</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{$currencies->count()}}</h4>
                                </div>
                            </div>
                            <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-users"></i>
                        </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>کوین های فعال</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{$currencies->where('is_active')->count()}}</h4>
                                </div>
                            </div>
                            <span class="badge bg-label-success rounded p-2">
                            <i class="fa-solid fa-user-check"></i>
                        </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>کوین های غیر فعال</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{$currencies->where('is_active', false)->count()}}</h4>
                                </div>
                            </div>
                            <span class="badge bg-label-warning rounded p-2">
                                <i class="fa-solid fa-user-xmark"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">جست و جو</h5>

            <form class="row mt-3 d-flex align-items-end justify-content-between"
                  action="{{route('admin.currency.index')}}" method="get">

                <div class="col-md-4 user_status ">
                    <label class="form-label" for="status">شبکه :</label>
                    <select id="status" name="type" class="form-select text-capitalize mb-md-0 ">
                        <option value="" {{ request('type') == '' ? 'selected' : '' }}>همه</option>
                        <option value="ERC20" {{ request('type') == 'ERC20' ? 'selected' : '' }}>ERC20</option>
                        <option value="BEP20" {{ request('type') == 'BEP20' ? 'selected' : '' }}>BEP20</option>
                    </select>
                </div>
            </form>
        </div>
    </div>



    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست کوین ها</h5>
                    <div class="card-title-elements ms-auto">
                        <a href="{{route('admin.currency.create')}}" class="btn btn-primary">
                            <i class="fa fa-plus mx-2"></i>
                            افزودن کوین جدید
                        </a>
                    </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>آواتار</th>
                        <th>نام</th>
                        <th>سیمبول</th>
                        <th>کد</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($currencies as $currency)

                        <tr>
                            <td>{{$currency->id}}</td>
                            <td>
                                <img src="{{asset($currency->coinLogo())}}" class="img-fluid" width="50px">
                            </td>

                            <td>
                                {{$currency->name}}
                            </td>
                            <td>
                                {{$currency->symbol}}
                            </td>
                            <td>
                                {{$currency->code}}
                            </td>
                            <td>
                                <span class="badge bg-label-{{$currency->status()?'success':'danger'}} me-1">
                                        {{$currency->status()?'فعال':'غیرفعال'}}
                                </span>

                            </td>
                            <td>
                                <div class="d-flex align-items-center">

                                    <a class="text-secondary me-3"
                                       href="{{ route('admin.currency.edit', ['currency' => $currency->id]) }}">
                                        <i class="fa-light fa-pen-to-square fa-lg"></i>
                                    </a>
                                    <a class="text-secondary me-3" href="">
                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>

                                </div>
                            </td>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
