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



    // Support Tracker - Radial Bar Chart
    // --------------------------------------------------------------------
    const supportTrackerEl = document.querySelector('#supportTracker'),
        supportTrackerOptions = {
            series: [0],
            labels: ['تیکت های کامل شده'],
            chart: {
                height: 360,
                type: 'radialBar'
            },
            plotOptions: {
                radialBar: {
                    offsetY: 10,
                    startAngle: -140,
                    endAngle: 130,
                    hollow: {
                        size: '65%'
                    },
                    track: {
                        background: cardColor,
                        strokeWidth: '100%'
                    },
                    dataLabels: {
                        name: {
                            offsetY: -20,
                            color: labelColor,
                            fontSize: '13px',
                            fontWeight: '400',
                            fontFamily: 'FarsiNumeral'
                        },
                        value: {
                            offsetY: 10,
                            color: headingColor,
                            fontSize: '38px',
                            fontWeight: '400',
                            fontFamily: 'FarsiNumeral'
                        }
                    }
                }
            },
            colors: [config.colors.primary],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    shadeIntensity: 0.5,
                    gradientToColors: [config.colors.primary],
                    inverseColors: true,
                    opacityFrom: 1,
                    opacityTo: 0.6,
                    stops: [30, 70, 100]
                }
            },
            stroke: {
                dashArray: 10
            },
            grid: {
                padding: {
                    top: -20,
                    bottom: 5
                }
            },
            states: {
                hover: {
                    filter: {
                        type: 'none'
                    }
                },
                active: {
                    filter: {
                        type: 'none'
                    }
                }
            },
            responsive: [
                {
                    breakpoint: 1025,
                    options: {
                        chart: {
                            height: 330
                        }
                    }
                },
                {
                    breakpoint: 769,
                    options: {
                        chart: {
                            height: 280
                        }
                    }
                }
            ]
        };
    if (typeof supportTrackerEl !== undefined && supportTrackerEl !== null) {
        const supportTracker = new ApexCharts(supportTrackerEl, supportTrackerOptions);
        supportTracker.render();
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

    // Shipment statistics Chart
    // --------------------------------------------------------------------
    function generatePersianDays(month, daysInMonth) {
        const days = [];
        for (let i = 1; i <= daysInMonth; i++) {
            days.push(`${i} ${month}`);
        }
        return days;
    }
    const initialPersianDays = generatePersianDays('آذر', 30);
    const selectedMonthEl = document.querySelector('#selectedMonth');
    const monthDropdown = document.querySelector('#monthDropdown');
    const thisMonthSeriesData = Array.from({ length: 30 }, () => Math.floor(Math.random() * 100));

    const shipmentEl = document.querySelector('#shipmentStatisticsChart'),
        shipmentConfig = {

            series: [
                {
                    name: 'ثبت نام',
                    type: 'column',
                    data: thisMonthSeriesData // Replace with actual data
                },
                {
                    name: 'تایید شده',
                    type: 'line',
                    data: thisMonthSeriesData.map(val => val - 6) // Replace with actual data
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
                size: 5,
                colors: [config.colors.white],
                strokeColors: chartColors.line.series2,
                hover: { size: 6 },
                borderRadius: 4
            },
            stroke: {
                curve: 'smooth',
                width: [0, 3],
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
                tickAmount: 4,
                min: 0,
                max: 100,
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
    if (typeof shipmentEl !== undefined && shipmentEl !== null) {
        const shipment = new ApexCharts(shipmentEl, shipmentConfig);
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

    // OTC BUY LAst week Bar Chart
    // --------------------------------------------------------------------
    const OTCBuyLastWeekEl = document.querySelector('#OTCBuyLastWeek'),
        OTCBuyLastWeekConfig = {
            chart: {
                height: 200,
                parentHeightOffset: 0,
                type: 'bar',
                toolbar: {
                    show: false
                }
            },
            tooltip: {
                enabled: true
            },
            plotOptions: {
                bar: {
                    barHeight: '100%',
                    columnWidth: '30px',
                    startingShape: 'rounded',
                    endingShape: 'rounded',
                    borderRadius: 4,
                    colors: {
                        backgroundBarColors: [barBgColor, barBgColor, barBgColor, barBgColor, barBgColor, barBgColor, barBgColor],
                        backgroundBarRadius: 4
                    }
                }
            },
            colors: [config.colors.primary],
            grid: {
                show: false,
                padding: {
                    top: -30,
                    left: -16,
                    bottom: 0,
                    right: -6
                }
            },
            dataLabels: {
                enabled: false
            },

            series: [
                {
                    name: 'ثبت نام',
                    type: 'column',
                    data: [0, 0, 0, 0, 0, 0, 0]
                }
            ],
            legend: {
                show: false
            },
            xaxis: {
                categories: ['شنبه', 'یک‌شنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'],
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    show: true,
                    style: {
                        colors: labelColor,
                        fontSize: '13px',
                        fontFamily: 'FarsiNumeral',
                        fontWeight: 400
                    },
                },

            },
            yaxis: {
                labels: {
                    show: false
                }
            },
            responsive: [
                {
                    breakpoint: 1441,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '40%',
                                borderRadius: 4
                            }
                        }
                    }
                },
                {
                    breakpoint: 1368,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '48%'
                            }
                        }
                    }
                },
                {
                    breakpoint: 1200,
                    options: {
                        plotOptions: {
                            bar: {
                                borderRadius: 6,
                                columnWidth: '30%',
                                colors: {
                                    backgroundBarRadius: 6
                                }
                            }
                        }
                    }
                },
                {
                    breakpoint: 991,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '35%',
                                borderRadius: 6
                            }
                        }
                    }
                },
                {
                    breakpoint: 883,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '40%'
                            }
                        }
                    }
                },
                {
                    breakpoint: 768,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '25%'
                            }
                        }
                    }
                },
                {
                    breakpoint: 576,
                    options: {
                        plotOptions: {
                            bar: {
                                borderRadius: 9
                            },
                            colors: {
                                backgroundBarRadius: 9
                            }
                        }
                    }
                },
                {
                    breakpoint: 479,
                    options: {
                        plotOptions: {
                            bar: {
                                borderRadius: 4,
                                columnWidth: '35%'
                            },
                            colors: {
                                backgroundBarRadius: 4
                            }
                        },
                        grid: {
                            padding: {
                                right: -15,
                                left: -15
                            }
                        }
                    }
                },
                {
                    breakpoint: 376,
                    options: {
                        plotOptions: {
                            bar: {
                                borderRadius: 3
                            }
                        }
                    }
                }
            ]
        };
    if (typeof OTCBuyLastWeekEl !== undefined && OTCBuyLastWeekEl !== null) {
        const OTCBuyLastWeek = new ApexCharts(OTCBuyLastWeekEl, OTCBuyLastWeekConfig);
        OTCBuyLastWeek.render();
    }

    // OTC BUY LAst week Bar Chart
    // --------------------------------------------------------------------
    const OTCSellLastWeekEl = document.querySelector('#OTCSellLastWeek'),
        OTCSellLastWeekConfig = {
            chart: {
                height: 200,
                parentHeightOffset: 0,
                type: 'bar',
                toolbar: {
                    show: false
                }
            },
            tooltip: {
                enabled: true
            },
            plotOptions: {
                bar: {
                    barHeight: '100%',
                    columnWidth: '30px',
                    startingShape: 'rounded',
                    endingShape: 'rounded',
                    borderRadius: 4,
                    colors: {
                        backgroundBarColors: [barBgColor, barBgColor, barBgColor, barBgColor, barBgColor, barBgColor, barBgColor],
                        backgroundBarRadius: 4
                    }
                }
            },
            colors: [config.colors.primary],
            grid: {
                show: false,
                padding: {
                    top: -30,
                    left: -16,
                    bottom: 0,
                    right: -6
                }
            },
            dataLabels: {
                enabled: false
            },

            series: [
                {
                    name: 'ثبت نام',
                    type: 'column',
                    data: [0, 0, 0, 0, 0, 0, 0]
                }
            ],
            legend: {
                show: false
            },
            xaxis: {
                categories: ['شنبه', 'یک‌شنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'],
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    show: true,
                    style: {
                        colors: labelColor,
                        fontSize: '13px',
                        fontFamily: 'FarsiNumeral',
                        fontWeight: 400
                    },
                }
            },
            yaxis: {
                labels: {
                    show: false
                }
            },
            responsive: [
                {
                    breakpoint: 1441,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '40%',
                                borderRadius: 4
                            }
                        }
                    }
                },
                {
                    breakpoint: 1368,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '48%'
                            }
                        }
                    }
                },
                {
                    breakpoint: 1200,
                    options: {
                        plotOptions: {
                            bar: {
                                borderRadius: 6,
                                columnWidth: '30%',
                                colors: {
                                    backgroundBarRadius: 6
                                }
                            }
                        }
                    }
                },
                {
                    breakpoint: 991,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '35%',
                                borderRadius: 6
                            }
                        }
                    }
                },
                {
                    breakpoint: 883,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '40%'
                            }
                        }
                    }
                },
                {
                    breakpoint: 768,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '25%'
                            }
                        }
                    }
                },
                {
                    breakpoint: 576,
                    options: {
                        plotOptions: {
                            bar: {
                                borderRadius: 9
                            },
                            colors: {
                                backgroundBarRadius: 9
                            }
                        }
                    }
                },
                {
                    breakpoint: 479,
                    options: {
                        plotOptions: {
                            bar: {
                                borderRadius: 4,
                                columnWidth: '35%'
                            },
                            colors: {
                                backgroundBarRadius: 4
                            }
                        },
                        grid: {
                            padding: {
                                right: -15,
                                left: -15
                            }
                        }
                    }
                },
                {
                    breakpoint: 376,
                    options: {
                        plotOptions: {
                            bar: {
                                borderRadius: 3
                            }
                        }
                    }
                }
            ]
        };
    if (typeof OTCSellLastWeekEl !== undefined && OTCSellLastWeekEl !== null) {
        const OTCSellLastWeek = new ApexCharts(OTCSellLastWeekEl, OTCSellLastWeekConfig);
        OTCSellLastWeek.render();
    }
})();


