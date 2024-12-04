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
                                <h4 class="mb-0 me-2">{{$markets->count()}}</h4>
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
                                <h4 class="mb-0 me-2">{{$markets->where('is_active')->count()}}</h4>
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
                                <h4 class="mb-0 me-2">{{$markets->where('is_active', false)->count()}}</h4>
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
                    <label class="form-label" for="status">وضعیت بازار :</label>
                    <select id="status" name="type" class="form-select text-capitalize mb-md-0 ">
                        <option value="" {{ request('is_active') == '' ? 'selected' : '' }}>همه</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>فعال</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>غیرفعال</option>


                    </select>
                </div>
            </form>
        </div>
    </div>



    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست بازارها</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{route('admin.market.create')}}" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i>
                        افزودن  بازار جدید
                    </a>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>کوین</th>
                        <th>آخرین قیمت</th>
                        <th>قیمت صرافی</th>
                        <th>حداقل مقدار معامله</th>
                        <th>جداکثر مقدار معامله</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($markets as $market)

                        <tr>
                            <td class="">{{$market->id}}</td>
                            <td class="text-heading fw-medium">
                                <div class="d-flex justify-content-start align-items-center">
                                    <div class="avatar-group d-flex align-items-center assigned-avatar">

                                        <div class="avatar avatar-md">
                                            <img src="{{asset($market->quoteCurrency->coinLogo())}}" class="rounded-circle ">
                                        </div>
                                        <div class="avatar avatar-md " >
                                            <img src="{{asset($market->baseCurrency->coinLogo())}}" class="rounded-circle  ">
                                        </div>
                                    </div>
{{--                                    <img src="{{asset($market->baseCurrency->coinLogo())}}" class="img-fluid me-3"--}}
{{--                                         width="50px">--}}
                                    <div class="ms-3">{{$market->base_currency}}/{{$market->quote_currency}}</div>
                                </div>

                            </td>

                            <td class="">
                                <span class="font-number text-heading h5">{{formatNumber($market->price)}}</span>
                            </td>
                            <td class="font-number text-heading fw-medium">
                                <span>{{formatNumber($market->exchange_price)}}</span>
                            </td>
                            <td class="font-number">
                                {{formatNumber($market->min_trade_amount,8)}}
                            </td>
                            <td class="font-number">
                                {{formatNumber($market->max_trade_amount,2)}}
                            </td>
                            <td>
                                <span class="badge bg-label-{{$market->is_active?'success':'danger'}} me-1">
                                    {{$market->is_active?'فعال':'غیرفعال'}}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a class="text-secondary me-3"
                                       href="{{ route('admin.market.edit', ['market' => $market->id]) }}">
                                        <i class="fa-light fa-pen-to-square fa-lg"></i>
                                    </a>
                                    <a class="text-secondary me-3" href="">
                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
