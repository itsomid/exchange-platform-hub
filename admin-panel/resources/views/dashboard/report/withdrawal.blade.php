@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')
    <div class="card">
        <form action="{{route('')}}" method="get"  class="row mt-5" >
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label for="created_at">از تاریخ</label>
                        <input required
                               type="text"
                               name="from_date"
                               class="form-control"
                               autocomplete="off"
                               data-jdp
                               placeholder="جهت درج تاریخ کلیک کنید">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label for="created_at">تا تاریخ</label>
                        <input required
                               type="text"
                               name="to_date"
                               class="form-control"
                               autocomplete="off"
                               data-jdp
                               placeholder="جهت درج تاریخ کلیک کنید">
                    </div>
                </div>
                <div class="col-md-2">
                    <label for=""> </label>
                    <button class="btn btn-success w-100 ">دریافت گزارش</button>
                </div>
            </div>
        </form>
    </div>

    <div class="row g-6 mt-3">
        <div class="col-lg-12">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0 card-title">نمودار واریزی ها (یک ماه اخیر)</h5>
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill text-muted border-0 p-2 me-n1" type="button"
                                id="projectStatusId" data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                            <i class="fa-regular fa-grip-dots-vertical text-muted"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="projectStatusId">
                            <a class="dropdown-item" href="javascript:void(0);">مشاهده با تفکیک تاریخ</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="badge rounded bg-label-primary p-2 me-3 rounded">
                            <i class="fa-solid fa-money-from-bracket"></i>
                        </div>
                        <div class="d-flex justify-content-between w-100 gap-2 align-items-center">
                            <div class="me-2">
                                <h6 class="mb-0">$4,3742</h6>
                                <small class="text-body">مجموع واریزی های یک ماه اخیر</small>
                            </div>
                            <h6 class="mb-0 text-success">+10.2%</h6>
                        </div>
                    </div>
                    <div id="withdrawal-chart"></div>
                    <div class="d-flex justify-content-between mb-4">
                        <h6 class="mb-0">تعداد واریزی ها</h6>
                        <div class="d-flex">
                            <p class="mb-0 me-4">$756.26</p>
                            <p class="mb-0 text-danger">-139.34</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-0">مجموع واریزی ها</h6>
                        <div class="d-flex">
                            <p class="mb-0 me-4">$2,207.03</p>
                            <p class="mb-0 text-success">+576.24</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('vendor-script')
    @vite([
            'resources/assets/vendor/libs/apex-charts/apexcharts.js',
            'resources/assets/js/jalalidatepicker.js',
            'resources/assets/vendor/libs/cleavejs/cleave.js',
            'resources/assets/js/config.js',
            'resources/assets/js/withdrawal.js',
         ])
@endsection

@section('vendor-style')
    @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
])
@endsection
