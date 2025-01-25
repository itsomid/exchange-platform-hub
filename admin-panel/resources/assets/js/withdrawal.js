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
    // Project Status - Line Chart
    // --------------------------------------------------------------------
    const withdrawalChartEl = document.querySelector('#withdrawalAmountChart');
    if (!withdrawalChartEl) return;

    // Extract JSON from data attributes
    const chartDataStr = withdrawalChartEl.getAttribute('data-chartdata');
    const chartDatesStr = withdrawalChartEl.getAttribute('data-dates');

    if (!chartDataStr || !chartDatesStr) return;

    const chartData = JSON.parse(chartDataStr);
    const chartDates = JSON.parse(chartDatesStr);
    const realDataArray = Object.values(chartData);
    // [The rest is the same as above: define colors, config, etc.]

    const withdrawalChartOptions = {
        chart: {
            height: 350,
            type: 'line',
            stacked: false,
            parentHeightOffset: 0,
            toolbar: {show: false},
            zoom: {enabled: false}
        },
        markers: {
            size: [3, 0],
            colors: [config.colors.white],
            strokeColors: [config.colors.primary],
            hover: {size: 3},
            borderRadius: 4
        },
        stroke: {
            width: [3, 0],
            curve: 'straight'
        },
        dataLabels: {
            enabled: false,         // turn on data labels in general
            enabledOnSeries: [0]   // only show them on the second series
        },
        series: [
            {
                name: 'مقدار',
                type: 'area',
                data: Object.values(chartData).map(item => parseFloat(item.total_amount))
            },
            {
                name: 'تعداد',
                data: Object.values(chartData).map(() => 0),
                showInLegend: false // hide from the legend
            },
        ],
        grid: {
            show: true,
            padding: {
                left: 10,
                right: 30
            }
        },
        xaxis: {
            categories: chartDates,
            labels: {
                rotate: -90,
                rotateAlways: false,
                style: {
                    colors: labelColor,  // can be a single color or an array
                    fontSize: '12px',
                    fontFamily: 'FarsiNumeral', // if needed
                    fontWeight: 400
                }
            },
        },
        yaxis: {
            tickAmount: 5,
            labels: {
                formatter: function(val, index) {
                    return val.toFixed(2);
                }
            },


        },

        colors: [config.colors.primary, config.colors.warning],

        tooltip: {
            style: {
                fontSize: '12px',
                fontFamily: 'FarsiNumeral', // if needed
            },
            y: {
                formatter: function (val, { seriesIndex, dataPointIndex }) {
                    if (seriesIndex === 0) {
                        // Series 0 -> مقدار; just show the value
                        return parseFloat(val).toLocaleString();
                    } else {
                        // Series 1 -> تعداد
                        const realTransactions = realDataArray[dataPointIndex].total_transactions;
                        return parseInt(realTransactions, 10).toLocaleString();
                    }
                }
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
    };

    const chart = new ApexCharts(withdrawalChartEl, withdrawalChartOptions);
    chart.render();
})();
