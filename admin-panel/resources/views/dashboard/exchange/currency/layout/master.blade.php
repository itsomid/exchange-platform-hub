@extends('dashboard.layout.master')
@section('title', 'ویرایش پروفایل')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-6" dir="ltr">

                <div class="d-flex flex-column flex-lg-row text-sm-start text-center">
                    <div class="d-flex align-items-center">
                        <img src="{{$currency->coinLogo()}}" width="100" class="mx-5 my-3">
                        <h4 class="mb-2 mt-lg-6 text-end">{{$currency->name}}</h4>
                    </div>
                    <div class="d-flex flex-column align-items-end justify-content-center flex-grow-1 ms-5">
                        @if(count($currency->chains))
                        <a class="btn btn-{{count($currency->chains)?'success':'danger'}} ">

                                دارای شبکه فعال
                        </a>
                        @else
                            <a class="btn btn-{{count($currency->chains)?'success':'danger'}}">
                                بدون شبکه فعال
                            </a>
                            <div class="alert alert-warning mt-2" role="alert">
                                بعد از ایجاد کوین نسبت به ساخت شبکه آن اقدام کنید
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="nav-align-top">
                <ul class="nav nav-pills flex-column flex-md-row mb-4">
                    <li class="nav-item">
                        <a class="nav-link @if(request()->route()->getName() == 'admin.currency.edit') active @endif"
                           href="{{route('admin.currency.edit',['currency'=>$currency])}}">
                            <i class="fa-brands fa-bitcoin fa-lg me-2"></i>
                            اطلاعات کوین
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->route()->getName() == 'admin.currency.chains.edit') active @endif"
                           href="{{route('admin.currency.chains.edit',['currency'=>$currency])}}">
                            <i class="fa-regular fa-network-wired fa-lg me-2"></i>
                            اطلاعات شبکه های کوین
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->route()->getName() == 'admin.currency.chains.create') active @endif"
                           href="{{route('admin.currency.chains.create',['currency'=>$currency])}}">
                            <i class="fa-regular fa-chart-network fa-lg me-2"></i>
                            افزودن شبکه جدید برای کوین
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->route()->getName() == 'admin.currency.nodeprovider.edit') active @endif"
                           href="{{route('admin.currency.nodeprovider.edit',['currency'=>$currency])}}">
                            <i class="fa-regular fa-share-nodes fa-lg me-2"></i>
                            NodeProvider Configuration
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    @yield('currency-body')
@endsection

