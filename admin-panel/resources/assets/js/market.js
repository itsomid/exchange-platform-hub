'use strict';

(function () {
    let cardColor, borderColor, headingColor, labelColor, shadeColor, barBgColor;
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



    // Exchange Price
    // --------------------------------------------------------------------
    const marketWeeklyChart = document.querySelector('#marketWeeklyChart');

    // Determine chart color based on price change percentage
    let chartColor = config.colors.success; // default green
    if (typeof window.priceChangePercentage !== 'undefined') {
        chartColor = window.priceChangePercentage >= 0 ? config.colors.success : config.colors.danger;
    }

    // Get market history data from the global variable (passed from blade template)
    let chartData = [46350, 42350, 46350, 45150]; // fallback data
    if (typeof window.marketHistoryData !== 'undefined' && window.marketHistoryData.length > 0) {
        chartData = window.marketHistoryData.map(item => parseFloat(item.close));
    }

    const exchangePriceConfig = {
        chart: {
            height: 120,
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
        colors: [chartColor],
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
                data: chartData
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
            enabled: true,
            custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                const value = series[seriesIndex][dataPointIndex];
                const timestamp = window.marketHistoryData && window.marketHistoryData[dataPointIndex]
                    ? window.marketHistoryData[dataPointIndex].timestamp
                    : 'N/A';

                return '<div class="custom-tooltip" style="' +
                    'background: ' + cardColor + '; ' +
                    'border: 1px solid ' + borderColor + '; ' +
                    'border-radius: 8px; ' +
                    'padding: 12px 16px; ' +
                    'box-shadow: 0 4px 20px rgba(0,0,0,0.15); ' +
                    'font-family: inherit; ' +
                    'min-width: 180px;' +
                    '">' +
                    '<div style="' +
                    'color: ' + headingColor + '; ' +
                    'font-weight: 600; ' +
                    'font-size: 14px; ' +
                    'margin-bottom: 8px; ' +
                    'display: flex; ' +
                    'align-items: center; ' +
                    'gap: 8px;' +
                    '">' +
                    '<span style="' +
                    'width: 8px; ' +
                    'height: 8px; ' +
                    'border-radius: 50%; ' +
                    'background: ' + (window.priceChangePercentage >= 0 ? config.colors.success : config.colors.danger) + '; ' +
                    'display: inline-block;' +
                    '"></span>' +
                    'قیمت' +
                    '</div>' +
                    '<div style="' +
                    'color: ' + headingColor + '; ' +
                    'font-size: 16px; ' +
                    'font-weight: 700; ' +
                    'margin-bottom: 6px; ' +
                    'font-family: \"Courier New\", monospace;' +
                    '">$' + value.toLocaleString() + '</div>' +
                    (timestamp !== 'N/A' ?
                        '<div style="' +
                        'color: ' + labelColor + '; ' +
                        'font-size: 12px; ' +
                        'margin-top: 4px;' +
                        '">' + new Date(timestamp).toLocaleDateString('fa-IR') + '</div>' : '') +
                    '</div>';
            },
            theme: shadeColor
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
    if (typeof marketWeeklyChart !== 'undefined' && marketWeeklyChart !== null) {
        const exchangePrice = new ApexCharts(marketWeeklyChart, exchangePriceConfig);
        exchangePrice.render();
    }
})();
