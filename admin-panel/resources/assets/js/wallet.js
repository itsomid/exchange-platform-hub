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


    // wallet chart - Line Chart
    // --------------------------------------------------------------------
    const projectStatusEl = document.querySelector('#projectStatusChart'),
        projectStatusConfig = {
            chart: {
                height: 230,
                type: 'area',
                toolbar: false
            },
            markers: {
                strokeColor: 'transparent'
            },
            series: [
                {
                    data: [2000, 2000, 4000, 4000, 3050, 3050, 2000,]
                }
            ],
            dataLabels: {
                enabled: false
            },
            grid: {
                show: false,
                padding: {
                    left: -10,
                    right: -5
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
                    show: false
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
            yaxis: {
                labels: {
                    show: false
                },
                min: 1000,
                max: 6000,
                tickAmount: 5
            },
            tooltip: {
                enabled: false
            }
        };
    if (typeof projectStatusEl !== undefined && projectStatusEl !== null) {
        const projectStatus = new ApexCharts(projectStatusEl, projectStatusConfig);
        projectStatus.render();
    }
})();
