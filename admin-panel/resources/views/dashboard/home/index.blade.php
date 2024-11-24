@extends('dashboard.layout.master')
@section('title', 'پیشخوان')
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <!-- Support Tracker -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between pb-0">
                            <div class="card-title mb-0">
                                <h5 class="mb-0">Support Tracker</h5>
                                <small class="text-muted">Last 7 Days</small>
                            </div>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" id="supportTrackerMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="ti ti-dots-vertical ti-sm text-muted"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="supportTrackerMenu">
                                    <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                    <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                    <div class="mt-lg-4 mt-lg-2 mb-lg-4 mb-2 pt-1">
                                        <h1 class="mb-0">164</h1>
                                        <p class="mb-0">Total Tickets</p>
                                    </div>
                                    <ul class="p-0 m-0">
                                        <li class="d-flex gap-3 align-items-center mb-lg-3 pt-2 pb-1">
                                            <div class="badge rounded bg-label-primary p-1"><i class="ti ti-ticket ti-sm"></i></div>
                                            <div>
                                                <h6 class="mb-0 text-nowrap">New Tickets</h6>
                                                <small class="text-muted">142</small>
                                            </div>
                                        </li>
                                        <li class="d-flex gap-3 align-items-center mb-lg-3 pb-1">
                                            <div class="badge rounded bg-label-info p-1"><i class="ti ti-circle-check ti-sm"></i></div>
                                            <div>
                                                <h6 class="mb-0 text-nowrap">Open Tickets</h6>
                                                <small class="text-muted">28</small>
                                            </div>
                                        </li>
                                        <li class="d-flex gap-3 align-items-center pb-1">
                                            <div class="badge rounded bg-label-warning p-1"><i class="ti ti-clock ti-sm"></i></div>
                                            <div>
                                                <h6 class="mb-0 text-nowrap">Response Time</h6>
                                                <small class="text-muted">1 Day</small>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-12 col-sm-8 col-md-12 col-lg-8">
                                    <div id="supportTracker"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/ Support Tracker -->
            </div>
        </div>
    </div>
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/css/apex-charts.css',
            'resources/assets/vendor/js/apexcharts.js'])


@endsection

@section('scripts')
    <script type="module">
        window.ApexCharts = ApexCharts; // return apex chart

        var options = {
            series: [67],
            chart: {
                height: 350,
                type: 'radialBar',
                offsetY: -10
            },
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 135,
                    dataLabels: {
                        name: {
                            fontSize: '16px',
                            color: undefined,
                            offsetY: 120
                        },
                        value: {
                            offsetY: 76,
                            fontSize: '22px',
                            color: undefined,
                            formatter: function (val) {
                                return val + "%";
                            }
                        }
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    shadeIntensity: 0.15,
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 50, 65, 91]
                },
            },
            stroke: {
                dashArray: 4
            },
            labels: ['Median Ratio'],
        };

        var chart = new ApexCharts(document.querySelector("#supportTracker"), options);
        chart.render();
    </script>
@endsection
