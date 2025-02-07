/**
 * Dashboard Analytics
 */

'use strict';

(function () {
    let cardColor,borderColor, headingColor, labelColor,shadeColor,  barBgColor;
    if (isDarkStyle) {
        cardColor = config.colors_dark.cardColor;
        labelColor = config.colors_dark.textMuted;
        borderColor = config.colors_dark.borderColor;
        headingColor = config.colors_dark.headingColor;

        barBgColor = '#3d4157';

    } else {
        cardColor = config.colors.cardColor;
        labelColor = config.colors.textMuted;
        borderColor = config.colors.borderColor;
        headingColor = config.colors.headingColor;
        shadeColor = '';
        barBgColor = '#efeef0';
    }


    const chartColors = {
        donut: {
            series1: config.colors.success,
            series2: '#53D28C',
            series3: '#7EDDA9',
            series4: '#A9E9C5'
        },
        line: {
            series1: config.colors.warning,
            series2: config.colors.primary,
            series3: '#7367f029'
        }
    };


    // User Registration statistics Chart
    // --------------------------------------------------------------------
    function generatePersianDays(month, daysInMonth) {
        const days = [];
        for (let i = 1; i <= daysInMonth; i++) {
            days.push(`${i} ${month}`);
        }
        return days;
    }
    const selectedMonthEl = document.querySelector('#selectedMonth');

    const initialPersianDays = generatePersianDays(selectedMonthEl.innerText, 30);

    const monthDropdown = document.querySelector('#monthDropdown');

    const registrationEl = document.querySelector('#userRegistrationChart');
    const totalRegistrationStr = registrationEl.getAttribute('data-totalRegistration');
    const totalInActiveRegistrationStr = registrationEl.getAttribute('data-totalInActiveRegistration');
    const totalRegistration = JSON.parse(totalRegistrationStr);
    const totalInActiveRegistration = JSON.parse(totalInActiveRegistrationStr);

    console.log(totalRegistration)
    const   registrationChartConfig = {

            series: [
                {
                    name: 'ثبت نام',
                    type: 'column',
                    data: totalRegistration // Replace with actual data
                },
                {
                    name: 'تایید شده',
                    type: 'line',
                    data: totalInActiveRegistration // Replace with actual data
                }
            ],
            chart: {
                height: 320,
                type: 'line',
                stacked: false,
                parentHeightOffset: 0,
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            markers: {
                size: 3,
                colors: [config.colors.white],
                strokeColors: chartColors.line.series2,
                hover: { size: 4 },
                borderRadius: 4
            },
            stroke: {
                curve: 'smooth',
                width: [0, 2],
                lineCap: 'round'
            },
            legend: {
                show: true,
                position: 'bottom',
                markers: {
                    width: 8,
                    height: 8,
                    offsetX: -3
                },
                height: 40,
                itemMargin: {
                    horizontal: 10,
                    vertical: 0
                },
                fontSize: '14px',
                fontFamily: 'FarsiNumeral',
                fontWeight: 400,
                labels: {
                    colors: headingColor,
                    useSeriesColors: false
                },
                offsetY: 10
            },
            grid: {
                strokeDashArray: 8,
                borderColor
            },
            colors: [chartColors.line.series1, chartColors.line.series2],
            fill: {
                opacity: [1, 1]
            },
            plotOptions: {
                bar: {
                    columnWidth: '30%',
                    startingShape: 'rounded',
                    endingShape: 'rounded',
                    borderRadius: 4
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                tickAmount: 30,
                categories: initialPersianDays,
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '9px',
                        fontFamily: 'FarsiNumeral',
                        fontWeight: 400
                    }
                },
                axisBorder: { show: true },
                axisTicks: { show: true }
            },
            yaxis: {
                tickAmount: 5,
                min: 0,

                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px',
                        fontFamily: 'FarsiNumeral',
                        fontWeight: 400
                    },
                    formatter: function (val) {
                        return val ;
                    }
                }
            },
            responsive: [
                {
                    breakpoint: 1400,
                    options: {
                        chart: { height: 320 },
                        xaxis: { labels: { style: { fontSize: '10px' } } },
                        legend: {
                            itemMargin: {
                                vertical: 0,
                                horizontal: 10
                            },
                            fontSize: '13px',
                            offsetY: 12
                        }
                    }
                },
                {
                    breakpoint: 1025,
                    options: {
                        chart: { height: 415 },
                        plotOptions: { bar: { columnWidth: '50%' } }
                    }
                },
                {
                    breakpoint: 982,
                    options: { plotOptions: { bar: { columnWidth: '30%' } } }
                },
                {
                    breakpoint: 480,
                    options: {
                        chart: { height: 250 },
                        legend: { offsetY: 7 }
                    }
                }
            ]
        };
    if (typeof registrationEl !== undefined && registrationEl !== null) {
        const shipment = new ApexCharts(registrationEl, registrationChartConfig);
        shipment.render();
        monthDropdown.addEventListener('click', (event) => {
            const target = event.target;
            if (target.tagName === 'A') {
                const selectedMonth = target.getAttribute('data-month'); // Get the month from the dropdown
                selectedMonthEl.textContent = selectedMonth; // Update the button text

                // Determine the number of days in the selected month (example logic)
                const daysInMonth = selectedMonth === 'اسفند' ? 29 : 30; // Adjust for different months if necessary

                // Generate Persian days for the selected month
                const persianDays = generatePersianDays(selectedMonth, daysInMonth);

                // Update chart data (example random data, replace with actual data as needed)
                const newSeriesData = Array.from({ length: daysInMonth }, () => Math.floor(Math.random() * 100));

                // Update the chart
                shipment.updateOptions({
                    xaxis: {
                        categories: persianDays, // Update X-axis with Persian days

                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    series: [
                        {
                            name: 'ثبت نام',
                            type: 'column',
                            data: newSeriesData // Update data for selected month
                        },
                        {
                            name: 'تایید شده',
                            type: 'line',
                            data: newSeriesData.map(val => val - 6) // Example transformation
                        }
                    ]
                });
            }
        });
    }


})();


