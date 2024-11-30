@extends('dashboard.layout.master')
@section('title', 'ویرایش پروفایل')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-6" dir="ltr">

                <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center">
                    <div class="d-flex align-items-center">
                        <img src="{{$currency->coinLogo()}}" width="100" class="mx-5 my-3">
                    </div>
                    <div class="flex-grow-1 mt-3 mt-lg-5">
                        <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
                            <div >
                                <h4 class="mb-2 mt-lg-6 text-end">{{$currency->name}}</h4>

                            </div>
                            <a href="javascript:void(0)" class="btn btn-primary mb-1 waves-effect waves-light">
                                <i class="ti ti-user-check ti-xs me-2"></i>Connected
                            </a>
                        </div>
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
                        <a class="nav-link @if(request()->route()->getName() == 'admin.profile.password.edit') active @endif"
                           href="{{route('admin.profile.password.edit')}}">
                            <i class="fa-regular fa-chart-network fa-lg me-2"></i>
                            افزودن شبکه جدید برای کوین
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    @yield('currency-body')
@endsection

