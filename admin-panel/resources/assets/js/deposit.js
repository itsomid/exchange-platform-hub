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
    // Project Status - Line Chart
    // --------------------------------------------------------------------
    const projectStatusEl = document.querySelector('#projectStatusChart'),
        projectStatusConfig = {

            chart: {
                height: 320,
                type: 'area',
                stacked: false,
                parentHeightOffset: 0,
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            markers: {
                size: 5,
                colors: [config.colors.white],
                strokeColors: [config.colors.primary],
                hover: { size: 3 },
                borderRadius: 4
            },
            series: [
                {
                    name: 'مبلغ',
                    type: 'area',
                    data: [2000, 2000, 4000, 4000, 3050, 3050, 2000, 2000, 3050, 3050, 4700, 4700, 2750, 2750, 5700, 5700,2000,5700,3050,5700,5700,5700,4700,3050,5700,5700,5700,4700,5700,5700]
                },
                {
                    name: 'تعداد',
                    type: 'area',
                    data: [20, 20, 56, 40, 30, 30, 20, 20, 30, 30, 47, 47, 20, 20, 57, 57,57,20,20,20,30,20,20,20,20,20,20,20,20,20]
                }
            ],
            dataLabels: {
                enabled: false
            },
            grid: {
                show: true,
                padding: {
                    left: 10,
                    right: 10
                }
            },
            stroke: {
                width: 3,
                curve: 'straight'
            },
            colors: [config.colors.primary],
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.6,
                    opacityTo: 0.15,
                    stops: [0, 95, 100]
                }
            },
            xaxis: {
                labels: {
                    show: true,
                    style: {
                        colors: labelColor,
                        fontSize: '13px',
                        fontFamily: 'FarsiNumeral',
                        fontWeight: 400
                    },
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                lines: {
                    show: false
                }
            },
            legend: {
                show: true,
                position: 'bottom',

                height: 40,
                fontSize: '14px',
                fontFamily: 'FarsiNumeral',
                fontWeight: 400,
            },
            yaxis: {
                labels: {
                    show: true
                },
                min: 1000,
                max: 6000,
                tickAmount: 5
            },
            tooltip: {
                enabled: true
            }
        };
    if (typeof projectStatusEl !== undefined && projectStatusEl !== null) {
        const projectStatus = new ApexCharts(projectStatusEl, projectStatusConfig);
        projectStatus.render();
    }
})();
