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

// Get the selected month element
    const selectedMonthEl = document.querySelector('#selectedMonth');
    const registrationEl = document.querySelector('#userRegistrationChart');

    if (!registrationEl) {
        console.error("Chart container not found!");
    }

// Parse initial data from attributes
    const totalRegistrationStr = registrationEl.getAttribute('data-totalRegistration') || '[]';
    const totalInActiveRegistrationStr = registrationEl.getAttribute('data-totalInActiveRegistration') || '[]';

    const totalRegistration = JSON.parse(totalRegistrationStr);
    const totalInActiveRegistration = JSON.parse(totalInActiveRegistrationStr);

// Initialize Persian Days
    const initialPersianDays = generatePersianDays(selectedMonthEl.innerText, 30);
    const maxValue = Math.max(...totalRegistration, ...totalInActiveRegistration); // Find the max value
    const tickAmount = Math.ceil(maxValue / 5); // Divide into 5 steps dynamically
// Declare chart instance globally
    let shipment;

// Function to initialize or re-create the chart
    function renderChart(registrationData, inactiveRegistrationData, persianDays) {
        if (shipment) {
            shipment.destroy(); // Destroy previous instance if exists
        }

        shipment = new ApexCharts(registrationEl, {
            series: [
                { name: 'ثبت نام', type: 'column', data: registrationData },
                { name: 'تایید نشده', type: 'line', data: inactiveRegistrationData }
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
                markers: { width: 8, height: 8, offsetX: -3 },
                height: 40,
                itemMargin: { horizontal: 10, vertical: 0 },
                fontSize: '14px',
                fontFamily: 'FarsiNumeral',
                fontWeight: 400,
                labels: { colors: headingColor, useSeriesColors: false },
                offsetY: 10
            },
            grid: { strokeDashArray: 8, borderColor },
            colors: [chartColors.line.series1, chartColors.line.series2],
            fill: { opacity: [1, 1] },
            plotOptions: {
                bar: { columnWidth: '30%', startingShape: 'rounded', endingShape: 'rounded', borderRadius: 4 }
            },
            dataLabels: { enabled: false },
            xaxis: {
                tickAmount: 30,
                categories: persianDays,
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
                tickAmount: tickAmount,
                min: 0,
                forceNiceScale: true,

                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px',
                        fontFamily: 'FarsiNumeral',
                        fontWeight: 400
                    },
                    formatter: (val) => Math.round(val) // اطمینان از نمایش اعداد صحیح
                }
            },
            responsive: [
                {
                    breakpoint: 1400,
                    options: {
                        chart: { height: 320 },
                        xaxis: { labels: { style: { fontSize: '10px' } } },
                        legend: { itemMargin: { vertical: 0, horizontal: 10 }, fontSize: '13px', offsetY: 12 }
                    }
                },
                {
                    breakpoint: 1025,
                    options: { chart: { height: 415 }, plotOptions: { bar: { columnWidth: '50%' } } }
                },
                {
                    breakpoint: 982,
                    options: { plotOptions: { bar: { columnWidth: '30%' } } }
                },
                {
                    breakpoint: 480,
                    options: { chart: { height: 250 }, legend: { offsetY: 7 } }
                }
            ]
        });

        shipment.render();
    }

// Initial chart render
    renderChart(totalRegistration, totalInActiveRegistration, initialPersianDays);

// Handle month selection change
    document.querySelector('#monthDropdown').addEventListener('click', async (event) => {
        const target = event.target;
        if (target.tagName === 'A') {
            const selectedMonth = target.getAttribute('data-month');
            document.querySelector('#selectedMonth').textContent = selectedMonth;

            try {
                // Fetch new data from Laravel
                const response = await fetch(`/admin/report/user-registration-report/month?month=${selectedMonth}`);
                const data = await response.json();

                if (!data.totalRegistrations || !data.totalInactiveRegistrations) {
                    console.error("Invalid response structure:", data);
                    return;
                }

                // Generate updated Persian days
                const updatedPersianDays = generatePersianDays(selectedMonth, 30);

                // Re-render chart with new data
                renderChart(
                    Object.values(data.totalRegistrations),
                    Object.values(data.totalInactiveRegistrations),
                    updatedPersianDays
                );
            } catch (error) {
                console.error("Error fetching data:", error);
            }
        }
    });

})();


