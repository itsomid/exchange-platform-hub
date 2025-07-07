@extends('dashboard.layout.master')
@section('title', 'مدیریت سهام ها')
@section('content')

    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد سهام</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$totalStocks}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="fa-light fa-money-bill-wave fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد سهام های فعال </span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$activeStocks}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                          <i class="fa-regular fa-hand-holding-dollar fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <div class="card">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست سهام ها</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{route('admin.stock.create')}}" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i> تعریف سهام
                    </a>
                </div>

            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>نام سهام</th>
                    <th>ارزش</th>
                    <th>نوع سهام</th>
                    <th>کارمزد ابطال</th>
                    <th class="text-wrap w-25">توضیحات</th>
                    <th>
                        @php
                            $currentParams = request()->except('sortByCreatedAt');
                            $currentSortDirection = request()->input('sortByCreatedAt', 'asc');
                            $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('admin.transaction.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                           class="text-black">
                            تاریخ ایجاد
                            @if($currentSortDirection === 'asc')
                                <span><i class="fa-solid fa-arrow-up"></i></span>
                            @else
                                <span><i class="fa-solid fa-arrow-down"></i></span>
                            @endif
                        </a>
                    </th>

                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @if($stocks->isEmpty())
                    <tr>
                        <td colspan="9" class="text-center">سهامی یافت نشد.</td>
                    </tr>
                @else

                    @foreach($stocks as $stock)
                        <tr>
                            <td>{{$stock->id}}</td>
                            <td class="text-heading fw-medium">
                                {{$stock->name}}
                            </td>
                            <td>
                                <span class="font-number" dir="ltr">
                                    {{$stock->value}}
                                </span>
                            </td>
                            <td>
                                {{$stock->type->label()}}
                            </td>
                            <td dir="ltr">
                                {{$stock->cancellation_fee}}%
                            </td>
                            <td>
                                {{$stock->description}}
                            </td>
                            <td>
                                {{\App\Helpers\DateFormatter::convertToPersianDate($stock->created_at,'H:i:s %Y/%m/%d')}}
                            </td>
                            <td>
                                <span class="badge bg-label-{{$stock->status->color()}} rounded p-2">
                                    {{$stock->status->label()}}
                                </span>
                            </td>
                            <td>
                                <a href="{{route('admin.stock.edit', $stock->id)}}"
                                   class="btn btn-icon btn-text-secondary">
                                    <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                </a>
                                <a href="#" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                   data-bs-target="#stock-{{$stock->id}}">
                                    <i class="fa-regular fa-eye fa-lg"></i>
                                </a>
                                <div class="modal fade" id="stock-{{$stock->id}}" tabindex="-1"
                                     aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header" dir="ltr">
                                                <h5 class="modal-title font-number">Stock
                                                    #{{$stock->id}}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">نام سهام</h6>
                                                    <div class="d-flex  gap-4 align-items-center">
                                                        <div class="d-flex flex-column">
                                                            {{$stock->name}}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش سهام</h6>
                                                    <div class="text-wrap font-number">
                                                        {{$stock->value}} USDT
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">تعداد قراردادهای فعال این
                                                        سهم</h6>
                                                    <div class="d-flex  gap-4 align-items-center">
                                                        <span
                                                            class="font-number">{{$stock->contracts->where('contract_status', \App\Enums\StockContractStatusEnum::ACTIVE)->count()}}</span>
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">تعداد قراردادهای ابطال شده این
                                                        سهم</h6>
                                                    <div class="d-flex  gap-4 align-items-center">
                                                        <span
                                                            class="font-number">{{$stock->contracts->where('contract_status', \App\Enums\StockContractStatusEnum::CANCELED)->count()}}</span>
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">تعداد قراردادهای فروخته شده این
                                                        سهم</h6>
                                                    <div class="d-flex  gap-4 align-items-center">
                                                        <span
                                                            class="font-number">{{$stock->contracts->where('contract_status', \App\Enums\StockContractStatusEnum::SOLD)->count()}}</span>
                                                    </div>
                                                </div>

                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش قراردادهای فعال این سهم تا
                                                        کنون</h6>
                                                    <div class="text-wrap font-number">
                                                        {{$stock->contracts->where('contract_status', \App\Enums\StockContractStatusEnum::ACTIVE)->sum('total_value')}}
                                                        USDT
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش قراردادهای ابطال شده این سهم
                                                        تا کنون</h6>
                                                    <div class="text-wrap font-number">
                                                        {{$stock->contracts->where('contract_status', \App\Enums\StockContractStatusEnum::CANCELED)->sum('total_value')}}
                                                        USDT
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش قراردادهای فروخته شده این
                                                        سهم تا کنون</h6>
                                                    <div class="text-wrap font-number">
                                                        {{$stock->contracts->where('contract_status', \App\Enums\StockContractStatusEnum::SOLD)->sum('total_value')}}
                                                        USDT
                                                    </div>
                                                </div>
                                                <div
                                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات سهام</h6>
                                                    <div class="text-wrap w-60 text-end">
                                                        {{$stock->description}}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-label-secondary"
                                                        data-bs-dismiss="modal">بستن
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
        {{--        <div class="row mt-4">--}}
        {{--            <div class="col-md-12">--}}
        {{--                {{$stocks->appends(request()->all())->links()}}--}}
        {{--            </div>--}}
        {{--        </div>--}}
    </div>

@endsection
@section('vendor-script')
    @vite([
          ])
@endsection
@section('vendor-style')
    @vite([])
@endsection
