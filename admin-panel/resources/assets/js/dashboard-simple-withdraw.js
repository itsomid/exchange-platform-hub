'use strict';

(function () {
    let cardColor, borderColor, headingColor, labelColor,shadeColor, barBgColor;
    if (isDarkStyle) {
        cardColor = config.colors_dark.cardColor;
        labelColor = config.colors_dark.textMuted;
        borderColor = config.colors_dark.borderColor;
        headingColor = config.colors_dark.headingColor;
        shadeColor = 'dark';
        barBgColor = '#3d4157';

    } else {
        cardColor = config.colors.cardColor;
        labelColor = config.colors.textMuted;
        borderColor = config.colors.borderColor;
        headingColor = config.colors.headingColor;
        shadeColor = '';
        barBgColor = '#efeef0';
    }
// Average Daily Sales
// --------------------------------------------------------------------
    const withdrawEl = document.querySelector('#withdraw'),
        withdrawConfig = {
            chart: {
                height: 100,
                type: 'area',
                toolbar: {
                    show: false
                },
                sparkline: {
                    enabled: true
                }
            },
            markers: {
                colors: 'transparent',
                strokeColors: 'transparent'
            },
            grid: {
                show: false
            },
            colors: [config.colors.danger],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: shadeColor,
                    shadeIntensity: 0.8,
                    opacityFrom: 0.6,
                    opacityTo: 0.1
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                width: 2,
                curve: 'smooth'
            },
            series: [
                {
                    data: [12, 500, 233, 564,234,123,222]
                }
            ],
            xaxis: {
                show: true,
                lines: {
                    show: false
                },
                labels: {
                    show: false
                },
                stroke: {
                    width: 0
                },
                axisBorder: {
                    show: false
                }
            },
            yaxis: {
                stroke: {
                    width: 0
                },
                show: false
            },
            tooltip: {
                enabled: false
            },
            responsive: [
                {
                    breakpoint: 1387,
                    options: {
                        chart: {
                            height: 80
                        }
                    }
                },
                {
                    breakpoint: 1200,
                    options: {
                        chart: {
                            height: 123
                        }
                    }
                }
            ]
        };
    if (typeof withdrawEl !== undefined && withdrawEl !== null) {
        const withdraw = new ApexCharts(withdrawEl, withdrawConfig);
        withdraw.render();
    }


})();
