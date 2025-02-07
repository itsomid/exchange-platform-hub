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


