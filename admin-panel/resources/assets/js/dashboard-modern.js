/**
 * Modern Dashboard with AJAX functionality
 */

'use strict';

(function () {
    let depositChart = null;
    let withdrawalChart = null;
    let otcChart = null;

    // Load KPI Stats
    async function loadKPIStats() {
        try {
            const response = await fetch('/admin/dashboard/ajax/kpi-stats');
            const data = await response.json();

            const elements = {
                'total-users': data.totalUsers,
                'active-users': data.activeUsers,
                'today-registrations': `+${data.todayRegistrations} امروز`,
                'today-spot-volume': data.todaySpotVolume,
                'today-otc-volume': data.todayOTCVolume,
                'weekly-volume-display': data.weeklyTradingVolume,
                'pending-withdrawals': data.pendingWithdrawals
            };

            // Safely update elements
            Object.keys(elements).forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = elements[id];
                } else {
                    console.warn(`Element with id '${id}' not found`);
                }
            });
        } catch (error) {
            console.error('Error loading KPI stats:', error);
        }
    }

    // Load Weekly Volume when tab is clicked
    async function loadWeeklyVolume() {
        const element = document.getElementById('weekly-volume-display');
        if (!element) return;

        try {
            // Show loading
            element.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            
            const response = await fetch('/admin/dashboard/ajax/kpi-stats');
            const data = await response.json();
            
            element.textContent = data.weeklyTradingVolume;
        } catch (error) {
            console.error('Error loading weekly volume:', error);
            element.textContent = 'خطا در بارگذاری';
        }
    }

    // Load Financial Summary based on tab
    async function loadFinancialSummary(type = 'withdrawals') {
        const container = document.getElementById('financial-summary-content');
        
        if (!container) {
            console.error('Financial summary container not found');
            return;
        }
        
        // Show loading state
        container.innerHTML = `
            <div class="text-center py-5">
                <span class="spinner-border spinner-border-sm" role="status"></span>
                <span class="me-2">در حال بارگذاری...</span>
            </div>
        `;

        try {
            console.log('Fetching financial summary for type:', type);
            const response = await fetch(`/admin/dashboard/ajax/financial-summary?type=${type}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            const data = result.data;

            console.log('Received data:', data);

            if (!data || data.length === 0) {
                container.innerHTML = '<p class="text-center text-muted py-4">داده‌ای برای نمایش وجود ندارد</p>';
                return;
            }

            let html = '<ul class="p-0 m-0">';
            data.forEach(item => {
                // Use logo from backend
                const logoPath = item.logo || '/images/coins/default.png';
                
                html += `
                    <li class="mb-4 d-flex justify-content-between align-items-center pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <img src="${logoPath}" class="img-fluid rounded-circle me-3" width="45px" 
                                 onerror="this.src='/images/coins/default.png'" alt="${item.currency_symbol}">
                            <h6 class="mb-0">${item.currency_symbol}</h6>
                        </div>
                        <div class="d-flex align-items-center">
                `;

                // Special formatting for ref_purchases type
                if (type === 'ref_purchases' && item.total_filled_value) {
                    html += `
                        <div class="me-3 text-end">
                            <small class="text-muted d-block">USDT</small>
                            <h5 class="mb-0 font-number">${formatNumber(item.total_filled_value)}</h5>
                        </div>
                        <span class="me-3">=</span>
                    `;
                }

                html += `
                            <div class="text-end">
                                <small class="text-muted d-block">${item.currency_symbol}</small>
                                <h5 class="mb-0 font-number">${formatNumber(item.total_amount)}</h5>
                            </div>
                        </div>
                    </li>
                `;
            });
            html += '</ul>';
            
            container.innerHTML = html;
        } catch (error) {
            console.error('Error loading financial summary:', error);
            container.innerHTML = '<p class="text-center text-danger py-4">خطا در بارگذاری داده‌ها</p>';
        }
    }

    // Load Deposit/Withdrawal Charts
    async function loadDepositWithdrawCharts() {
        try {
            const response = await fetch('/admin/dashboard/ajax/deposit-withdraw-charts');
            const data = await response.json();

            document.getElementById('total-deposits-value').textContent = data.totalDepositsValue;
            document.getElementById('total-withdrawals-value').textContent = data.totalWithdrawalValue;

            // Create Deposit Chart
            renderDepositChart(data.deposits);
            
            // Create Withdrawal Chart
            renderWithdrawalChart(data.withdrawals);
        } catch (error) {
            console.error('Error loading charts:', error);
        }
    }

    // Load Trading Stats
    async function loadTradingStats() {
        try {
            const response = await fetch('/admin/dashboard/ajax/trading-stats');
            const data = await response.json();

            renderOTCChart(data.otcBuyLastWeek, data.otcSellLastWeek);
        } catch (error) {
            console.error('Error loading trading stats:', error);
        }
    }

    // Load Recent Activities
    window.loadRecentActivities = async function() {
        const tbody = document.getElementById('recent-activities-tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    <span class="me-2">در حال بارگذاری...</span>
                </td>
            </tr>
        `;

        try {
            const response = await fetch('/admin/dashboard/ajax/recent-activities');
            const result = await response.json();
            const activities = result.activities;

            if (activities.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">فعالیتی وجود ندارد</td></tr>';
                return;
            }

            let html = '';
            activities.forEach(activity => {
                const typeIcon = activity.type === 'deposit' 
                    ? '<i class="fa fa-arrow-down text-success"></i>' 
                    : '<i class="fa fa-arrow-up text-danger"></i>';
                
                const statusBadge = activity.status === 'completed' 
                    ? '<span class="badge bg-success">تکمیل شده</span>'
                    : activity.status === 'pending'
                    ? '<span class="badge bg-warning">در انتظار</span>'
                    : '<span class="badge bg-secondary">' + activity.status + '</span>';

                html += `
                    <tr>
                        <td>${typeIcon} ${activity.type === 'deposit' ? 'واریز' : 'برداشت'}</td>
                        <td>${activity.user}</td>
                        <td class="font-number">${activity.amount}</td>
                        <td>${activity.currency}</td>
                        <td>${activity.time}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        } catch (error) {
            console.error('Error loading recent activities:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">خطا در بارگذاری داده‌ها</td></tr>';
        }
    };

    // Load Top Trading Pairs
    async function loadTopTradingPairs() {
        const spotContainer = document.getElementById('top-spot-pairs');
        const otcContainer = document.getElementById('top-otc-pairs');
        
        try {
            const response = await fetch('/admin/dashboard/ajax/top-trading-pairs');
            const result = await response.json();
            
            // Render Spot Pairs
            if (spotContainer) {
                renderTradingPairs(spotContainer, result.spotPairs, 'primary');
            }
            
            // Render OTC Pairs
            if (otcContainer) {
                renderTradingPairs(otcContainer, result.otcPairs, 'success');
            }
        } catch (error) {
            console.error('Error loading top trading pairs:', error);
            if (spotContainer) {
                spotContainer.innerHTML = '<li class="text-center py-3 text-danger">خطا در بارگذاری داده‌ها</li>';
            }
            if (otcContainer) {
                otcContainer.innerHTML = '<li class="text-center py-3 text-danger">خطا در بارگذاری داده‌ها</li>';
            }
        }
    }

    // Helper function to render trading pairs
    function renderTradingPairs(container, pairs, badgeColor) {
        if (!pairs || pairs.length === 0) {
            container.innerHTML = '<li class="text-center py-3 text-muted">داده‌ای موجود نیست</li>';
            return;
        }

        let html = '';
        pairs.forEach((pair, index) => {
            html += `
                <li class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-${badgeColor} me-2">${index + 1}</span>
                        <strong>${pair.pair}</strong>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">حجم: ${pair.volume} <small>USDT</small></div>
                        <div class="text-muted small">معاملات: ${pair.trades}</div>
                    </div>
                </li>
            `;
        });
        
        container.innerHTML = html;
    }

    // Render Deposit Chart
    function renderDepositChart(data) {
        const chartEl = document.querySelector('#deposit-chart');
        if (!chartEl) {
            console.error('Deposit chart element not found');
            return;
        }

        // Ensure we have data
        if (!data || data.length === 0) {
            chartEl.innerHTML = '<p class="text-center text-muted">داده‌ای موجود نیست</p>';
            return;
        }

        const dateLabels = data.map(d => d.date_label);
        const gregorianDates = data.map(d => d.gregorian_date);
        const counts = data.map(d => parseInt(d.count) || 0);
        const values = data.map(d => parseFloat(d.value) || 0);

        const options = {
            series: [{
                name: 'تعداد واریز',
                data: counts
            }],
            chart: {
                height: 250,
                type: 'bar',
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '50%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val;
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ['#304758']
                }
            },
            colors: ['#28c76f'],
            xaxis: {
                categories: dateLabels,
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return Math.floor(val);
                    }
                }
            },
            tooltip: {
                enabled: true,
                custom: function({series, seriesIndex, dataPointIndex, w}) {
                    const count = counts[dataPointIndex];
                    const value = values[dataPointIndex];
                    const date = dateLabels[dataPointIndex];
                    const gregorianDate = gregorianDates[dataPointIndex];
                    
                    return '<div class="custom-tooltip">' +
                        '<div class="tooltip-title">' + date + '</div>' +
                        '<div class="tooltip-subtitle">' + gregorianDate + '</div>' +
                        '<div class="tooltip-content">' +
                        '<span>تعداد: <strong>' + count.toLocaleString() + '</strong></span><br>' +
                        '<span>مجموع: <strong>' + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' USDT</strong></span>' +
                        '</div>' +
                        '</div>';
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                padding: {
                    top: 0,
                    right: 10,
                    bottom: 0,
                    left: 10
                }
            }
        };

        if (depositChart) {
            depositChart.destroy();
        }
        depositChart = new ApexCharts(chartEl, options);
        depositChart.render();
    }

    // Render Withdrawal Chart
    function renderWithdrawalChart(data) {
        const chartEl = document.querySelector('#withdrawal-chart');
        if (!chartEl) {
            console.error('Withdrawal chart element not found');
            return;
        }

        // Ensure we have data
        if (!data || data.length === 0) {
            chartEl.innerHTML = '<p class="text-center text-muted">داده‌ای موجود نیست</p>';
            return;
        }

        const dateLabels = data.map(d => d.date_label);
        const gregorianDates = data.map(d => d.gregorian_date);
        const counts = data.map(d => parseInt(d.count) || 0);
        const values = data.map(d => parseFloat(d.value) || 0);

        const options = {
            series: [{
                name: 'تعداد برداشت',
                data: counts
            }],
            chart: {
                height: 250,
                type: 'bar',
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '50%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val;
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ['#304758']
                }
            },
            colors: ['#ea5455'],
            xaxis: {
                categories: dateLabels,
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return Math.floor(val);
                    }
                }
            },
            tooltip: {
                enabled: true,
                custom: function({series, seriesIndex, dataPointIndex, w}) {
                    const count = counts[dataPointIndex];
                    const value = values[dataPointIndex];
                    const date = dateLabels[dataPointIndex];
                    const gregorianDate = gregorianDates[dataPointIndex];
                    
                    return '<div class="custom-tooltip">' +
                        '<div class="tooltip-title">' + date + '</div>' +
                        '<div class="tooltip-subtitle">' + gregorianDate + '</div>' +
                        '<div class="tooltip-content">' +
                        '<span>تعداد: <strong>' + count.toLocaleString() + '</strong></span><br>' +
                        '<span>مجموع: <strong>' + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' USDT</strong></span>' +
                        '</div>' +
                        '</div>';
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                padding: {
                    top: 0,
                    right: 10,
                    bottom: 0,
                    left: 10
                }
            }
        };

        if (withdrawalChart) {
            withdrawalChart.destroy();
        }
        withdrawalChart = new ApexCharts(chartEl, options);
        withdrawalChart.render();
    }

    // Render OTC Trading Chart
    function renderOTCChart(buyCount, sellCount) {
        const chartEl = document.querySelector('#otc-trading-chart');
        if (!chartEl) {
            console.error('OTC chart element not found');
            return;
        }

        // Convert to numbers
        const buy = parseInt(buyCount) || 0;
        const sell = parseInt(sellCount) || 0;

        // Check if we have any data
        if (buy === 0 && sell === 0) {
            chartEl.innerHTML = '<p class="text-center text-muted">داده‌ای موجود نیست</p>';
            return;
        }

        const options = {
            series: [buy, sell],
            chart: {
                type: 'donut',
                height: 200
            },
            labels: ['خرید', 'فروش'],
            colors: ['#00cfe8', '#7367f0'],
            legend: {
                position: 'bottom'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: { show: true },
                            value: { 
                                show: true,
                                formatter: function(val) {
                                    return parseInt(val);
                                }
                            },
                            total: {
                                show: true,
                                label: 'کل',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            }
        };

        if (otcChart) {
            otcChart.destroy();
        }
        otcChart = new ApexCharts(chartEl, options);
        otcChart.render();
    }

    // Format number helper
    function formatNumber(num) {
        if (!num) return '0';
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 8
        });
    }

    // Tab change handler for financial summary
    document.addEventListener('DOMContentLoaded', function() {
        // Load initial data
        loadKPIStats();
        loadFinancialSummary('withdrawals');
        loadDepositWithdrawCharts();
        loadTradingStats();
        loadRecentActivities();
        loadTopTradingPairs();

        // Setup tab listeners - باید قبل از اینکه تب فعال شود هم کار کنه
        const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                const type = this.getAttribute('data-type');
                if (type) {
                    console.log('Loading financial summary for:', type);
                    loadFinancialSummary(type);
                }
            });
        });

        // Auto refresh every 60 seconds
        setInterval(() => {
            loadKPIStats();
            loadTopTradingPairs();
        }, 60000);
    });

})();
