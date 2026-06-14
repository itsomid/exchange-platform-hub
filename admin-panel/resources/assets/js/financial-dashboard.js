/**
 * Financial Dashboard - Trade Stats
 */

'use strict';

(function () {
    let refreshInterval = null;
    let currentVolumeStartDate = null;
    let currentVolumeEndDate = null;
    let currentTradeCountDate = null;
    let currentRevenueStartDate = null;
    let currentRevenueEndDate = null;
    let currentCashFlowStartDate = null;
    let currentCashFlowEndDate = null;
    let currentExpenseStartDate = null;
    let currentExpenseEndDate = null;
    let currentPnlStartDate = null;
    let currentPnlEndDate = null;
    let pnlSpreadData = [];
    let currentStockStartDate = null;
    let currentStockEndDate = null;
    let currentStockSearch = null;
    let stockSortField = 'created_at';
    let stockSortDir = 'desc';
    let stockCurrentPage = 1;
    async function loadTradeStats() {
        const btn = document.getElementById('btn-refresh-trades');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> بارگذاری...';
        }

        // Show skeleton loading
        showSkeletonLoading();

        try {
            const params = new URLSearchParams();
            if (currentVolumeStartDate) params.append('start_date', currentVolumeStartDate);
            if (currentVolumeEndDate) params.append('end_date', currentVolumeEndDate);
            if (currentTradeCountDate) params.append('trade_date', currentTradeCountDate);

            const response = await fetch(`/admin/financial-dashboard/ajax/trade-stats?${params.toString()}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Update Volume Cards
            updateElement('today-trade-volume', data.todayTradeVolume);
            updateElement('today-spot-volume-card', data.todaySpotVolume);
            updateElement('today-otc-volume-card', data.todayOTCVolume);

            updateElement('alltime-trade-volume', data.allTimeTradeVolume);
            updateElement('alltime-spot-volume', data.allTimeSpotVolume);
            updateElement('alltime-otc-volume', data.allTimeOTCVolume);

            // Update Count Cards
            updateElement('today-trade-count', data.todayTradeCount);
            updateElement('today-spot-count', data.todaySpotCount);
            updateElement('today-otc-count', data.todayOTCCount);

            updateElement('alltime-trade-count', data.allTimeTradeCount);
            updateElement('alltime-spot-count', data.allTimeSpotCount);
            updateElement('alltime-otc-count', data.allTimeOTCCount);

            // Update last refresh time
            updateLastRefreshTime();
        } catch (error) {
            console.error('Error loading trade stats:', error);
            showErrorState();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh me-1"></i> بروزرسانی';
            }
        }
    }

    // Show skeleton loading in KPI cards
    function showSkeletonLoading() {
        const ids = [
            'today-trade-volume', 'alltime-trade-volume',
            'today-trade-count', 'alltime-trade-count'
        ];

        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.innerHTML = '<div class="placeholder-glow"><span class="placeholder col-8 rounded"></span></div>';
            }
        });

        const subIds = [
            'today-spot-volume-card', 'today-otc-volume-card',
            'alltime-spot-volume', 'alltime-otc-volume',
            'today-spot-count', 'today-otc-count',
            'alltime-spot-count', 'alltime-otc-count'
        ];

        subIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.innerHTML = '<span class="placeholder col-4 placeholder-sm rounded"></span>';
            }
        });
    }

    // Show error state with retry
    function showErrorState() {
        const ids = [
            'today-trade-volume', 'alltime-trade-volume',
            'today-trade-count', 'alltime-trade-count'
        ];

        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.innerHTML = '<span class="text-danger cursor-pointer" onclick="loadTradeStats()" title="کلیک برای تلاش مجدد"><i class="fa fa-exclamation-triangle me-1"></i>خطا</span>';
            }
        });
    }

    // Update element safely
    function updateElement(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    // Update last refresh time
    function updateLastRefreshTime() {
        const el = document.getElementById('last-update-time');
        if (el) {
            const now = new Date();
            const time = now.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            el.innerHTML = `<i class="fa fa-clock me-1"></i> آخرین بروزرسانی: ${time}`;
        }
    }

    // Volume filter functions
    window.applyVolumeFilter = function () {
        const startDate = document.getElementById('volume-start-date').value;
        const endDate = document.getElementById('volume-end-date').value;

        if (startDate) {
            currentVolumeStartDate = startDate;
            currentVolumeEndDate = endDate || null;

            // Update label
            const label = document.getElementById('volume-today-label');
            const badge = document.getElementById('volume-range-badge');
            if (label) label.textContent = 'حجم معاملات';
            if (badge) {
                badge.textContent = endDate ? `${startDate} تا ${endDate}` : `از ${startDate}`;
                badge.style.display = 'inline-block';
            }
        }

        loadTradeStats();
    };

    window.resetVolumeFilter = function () {
        currentVolumeStartDate = null;
        currentVolumeEndDate = null;

        document.getElementById('volume-start-date').value = '';
        document.getElementById('volume-end-date').value = '';

        const label = document.getElementById('volume-today-label');
        const badge = document.getElementById('volume-range-badge');
        if (label) label.textContent = 'حجم معاملات امروز';
        if (badge) badge.style.display = 'none';

        loadTradeStats();
    };

    // Count filter functions
    window.applyCountFilter = function () {
        const tradeDate = document.getElementById('trade-count-date').value;

        if (tradeDate) {
            currentTradeCountDate = tradeDate;

            const label = document.getElementById('count-today-label');
            const badge = document.getElementById('count-date-badge');
            if (label) label.textContent = 'تعداد معاملات';
            if (badge) {
                badge.textContent = tradeDate;
                badge.style.display = 'inline-block';
            }
        }

        loadTradeStats();
    };

    window.resetCountFilter = function () {
        currentTradeCountDate = null;

        document.getElementById('trade-count-date').value = '';

        const label = document.getElementById('count-today-label');
        const badge = document.getElementById('count-date-badge');
        if (label) label.textContent = 'تعداد معاملات امروز';
        if (badge) badge.style.display = 'none';

        loadTradeStats();
    };

    // ====== Revenue Stats ======
    async function loadRevenueStats() {
        const btn = document.getElementById('btn-refresh-revenue');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> بارگذاری...';
        }

        showRevenueSkeletonLoading();

        try {
            const params = new URLSearchParams();
            if (currentRevenueStartDate) params.append('start_date', currentRevenueStartDate);
            if (currentRevenueEndDate) params.append('end_date', currentRevenueEndDate);

            const response = await fetch(`/admin/financial-dashboard/ajax/revenue-stats?${params.toString()}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            updateElement('today-commission', data.todayCommission);
            updateElement('today-spot-commission', data.todaySpotCommission);
            updateElement('today-otc-commission', data.todayOTCCommission);

            updateElement('alltime-commission', data.allTimeCommission);
            updateElement('alltime-spot-commission', data.allTimeSpotCommission);
            updateElement('alltime-otc-commission', data.allTimeOTCCommission);

            updateLastRefreshTime();
        } catch (error) {
            console.error('Error loading revenue stats:', error);
            showRevenueErrorState();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh me-1"></i> بروزرسانی';
            }
        }
    }

    function showRevenueSkeletonLoading() {
        ['today-commission', 'alltime-commission'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<div class="placeholder-glow"><span class="placeholder col-8 rounded"></span></div>';
        });

        ['today-spot-commission', 'today-otc-commission', 'alltime-spot-commission', 'alltime-otc-commission'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="placeholder col-4 placeholder-sm rounded"></span>';
        });
    }

    function showRevenueErrorState() {
        ['today-commission', 'alltime-commission'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="text-danger cursor-pointer" onclick="loadRevenueStats()" title="کلیک برای تلاش مجدد"><i class="fa fa-exclamation-triangle me-1"></i>خطا</span>';
        });
    }

    // Revenue filter functions
    window.applyRevenueFilter = function () {
        const startDate = document.getElementById('revenue-start-date').value;
        const endDate = document.getElementById('revenue-end-date').value;

        if (startDate) {
            currentRevenueStartDate = startDate;
            currentRevenueEndDate = endDate || null;

            const label = document.getElementById('revenue-today-label');
            const badge = document.getElementById('revenue-range-badge');
            if (label) label.textContent = 'کارمزد دریافتی';
            if (badge) {
                badge.textContent = endDate ? `${startDate} تا ${endDate}` : `از ${startDate}`;
                badge.style.display = 'inline-block';
            }
        }

        loadRevenueStats();
    };

    window.resetRevenueFilter = function () {
        currentRevenueStartDate = null;
        currentRevenueEndDate = null;

        document.getElementById('revenue-start-date').value = '';
        document.getElementById('revenue-end-date').value = '';

        const label = document.getElementById('revenue-today-label');
        const badge = document.getElementById('revenue-range-badge');
        if (label) label.textContent = 'کارمزد دریافتی امروز';
        if (badge) badge.style.display = 'none';

        loadRevenueStats();
    };

    // Expose globally for onclick handlers
    window.loadTradeStats = loadTradeStats;
    window.loadRevenueStats = loadRevenueStats;
    window.loadAssetStats = loadAssetStats;
    window.loadLiabilityStats = loadLiabilityStats;
    window.filterLiabilityTable = filterLiabilityTable;
    window.sortLiabilityTable = sortLiabilityTable;
    window.loadCashFlowStats = loadCashFlowStats;
    window.applyCashFlowFilter = applyCashFlowFilter;
    window.resetCashFlowFilter = resetCashFlowFilter;
    window.filterCashFlowTable = filterCashFlowTable;
    window.sortCashFlowTable = sortCashFlowTable;
    window.loadExpenseStats = loadExpenseStats;
    window.applyExpenseFilter = applyExpenseFilter;
    window.resetExpenseFilter = resetExpenseFilter;
    window.filterExpenseTable = filterExpenseTable;
    window.sortExpenseTable = sortExpenseTable;
    window.loadProfitLossStats = loadProfitLossStats;
    window.applyPnlFilter = applyPnlFilter;
    window.resetPnlFilter = resetPnlFilter;
    window.filterPnlTable = filterPnlTable;
    window.sortPnlTable = sortPnlTable;
    window.loadStockPurchaseStats = loadStockPurchaseStats;
    window.applyStockFilter = applyStockFilter;
    window.resetStockFilter = resetStockFilter;
    window.handleStockSearch = handleStockSearch;
    window.sortStockTable = sortStockTable;
    window.goToStockPage = goToStockPage;
    window.exportStockPurchases = exportStockPurchases;

    // ====== Asset Stats ======
    async function loadAssetStats() {
        const btn = document.getElementById('btn-refresh-assets');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> بارگذاری...';
        }

        showAssetSkeletonLoading();

        try {
            const response = await fetch('/admin/financial-dashboard/ajax/asset-stats');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            updateElement('total-exchange-balance', data.totalExchangeBalance);
            updateElement('total-hot-wallet-balance', data.totalHotWalletBalance);
            updateElement('total-user-liabilities', data.totalUserLiabilities);

            // Net assets with color coding
            const netEl = document.getElementById('net-exchange-assets');
            const netIcon = document.getElementById('net-assets-icon');
            if (netEl) {
                netEl.textContent = data.netExchangeAssets;
                if (data.isNetNegative) {
                    netEl.classList.add('text-danger');
                    netEl.classList.remove('text-success');
                    if (netIcon) {
                        netIcon.classList.remove('bg-label-primary');
                        netIcon.classList.add('bg-label-danger');
                    }
                } else {
                    netEl.classList.add('text-success');
                    netEl.classList.remove('text-danger');
                    if (netIcon) {
                        netIcon.classList.remove('bg-label-danger');
                        netIcon.classList.add('bg-label-primary');
                    }
                }
            }

            updateLastRefreshTime();
        } catch (error) {
            console.error('Error loading asset stats:', error);
            showAssetErrorState();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh me-1"></i> بروزرسانی';
            }
        }
    }

    function showAssetSkeletonLoading() {
        ['total-exchange-balance', 'total-hot-wallet-balance', 'net-exchange-assets'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<div class="placeholder-glow"><span class="placeholder col-8 rounded"></span></div>';
        });

        const liabEl = document.getElementById('total-user-liabilities');
        if (liabEl) liabEl.innerHTML = '<span class="placeholder col-4 placeholder-sm rounded"></span>';
    }

    function showAssetErrorState() {
        ['total-exchange-balance', 'total-hot-wallet-balance', 'net-exchange-assets'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="text-danger cursor-pointer" onclick="loadAssetStats()" title="کلیک برای تلاش مجدد"><i class="fa fa-exclamation-triangle me-1"></i>خطا</span>';
        });
    }

    // ====== Liability Stats ======
    let liabilityData = [];
    let liabilitySortField = 'userBalanceUsdt';
    let liabilitySortAsc = false;

    async function loadLiabilityStats() {
        const btn = document.getElementById('btn-refresh-liabilities');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> بارگذاری...';
        }

        showLiabilitySkeletonLoading();

        try {
            const response = await fetch('/admin/financial-dashboard/ajax/liability-stats');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            updateElement('total-debt-usdt', data.totalDebtUsdt);

            const countEl = document.getElementById('liability-currency-count');
            if (countEl) {
                countEl.innerHTML = `<i class="fa fa-coins me-1"></i>${data.breakdown.length} ارز فعال`;
            }

            liabilityData = data.breakdown;
            renderLiabilityTable();

            updateLastRefreshTime();
        } catch (error) {
            console.error('Error loading liability stats:', error);
            showLiabilityErrorState();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh me-1"></i> بروزرسانی';
            }
        }
    }

    function renderLiabilityTable() {
        const tbody = document.getElementById('liability-table-body');
        if (!tbody) return;

        const searchTerm = (document.getElementById('liability-search')?.value || '').toLowerCase();
        let filtered = liabilityData;

        if (searchTerm) {
            filtered = liabilityData.filter(item =>
                item.symbol.toLowerCase().includes(searchTerm) ||
                (item.name && item.name.toLowerCase().includes(searchTerm))
            );
        }

        // Sort
        filtered.sort((a, b) => {
            let valA = a[liabilitySortField];
            let valB = b[liabilitySortField];

            if (typeof valA === 'string') {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return liabilitySortAsc ? -1 : 1;
            if (valA > valB) return liabilitySortAsc ? 1 : -1;
            return 0;
        });

        const noResults = document.getElementById('liability-no-results');

        if (filtered.length === 0) {
            tbody.innerHTML = '';
            if (noResults) noResults.classList.remove('d-none');
            return;
        }

        if (noResults) noResults.classList.add('d-none');

        tbody.innerHTML = filtered.map(item => {
            const diffClass = item.isNegative ? 'text-danger fw-semibold' : 'text-success';
            const diffIcon = item.isNegative ? '<i class="fa fa-arrow-down me-1"></i>' : '<i class="fa fa-arrow-up me-1"></i>';

            return `<tr>
                <td>
                    <span class="fw-semibold">${escapeHtml(item.symbol)}</span>
                    <small class="text-muted d-block">${escapeHtml(item.name)}</small>
                </td>
                <td class="text-end">
                    <span class="font-number">${formatNumber(item.userBalanceUsdt)}</span>
                    <small class="text-muted d-block font-number">${escapeHtml(item.userBalance)} ${escapeHtml(item.symbol)}</small>
                </td>
                <td class="text-end">
                    <span class="font-number">${formatNumber(item.exchangeBalanceUsdt)}</span>
                    <small class="text-muted d-block font-number">${escapeHtml(item.exchangeBalance)} ${escapeHtml(item.symbol)}</small>
                </td>
                <td class="text-end">
                    <span class="${diffClass} font-number">${diffIcon}${formatNumber(Math.abs(item.differenceUsdt))}</span>
                    <small class="text-muted d-block font-number">${escapeHtml(item.difference)} ${escapeHtml(item.symbol)}</small>
                </td>
            </tr>`;
        }).join('');

        // Update sort icons
        document.querySelectorAll('[id^="sort-icon-"]').forEach(icon => {
            icon.className = 'fa fa-sort text-muted ms-1';
        });
        const activeIcon = document.getElementById(`sort-icon-${liabilitySortField}`);
        if (activeIcon) {
            activeIcon.className = liabilitySortAsc
                ? 'fa fa-sort-up text-primary ms-1'
                : 'fa fa-sort-down text-primary ms-1';
        }
    }

    function sortLiabilityTable(field) {
        if (liabilitySortField === field) {
            liabilitySortAsc = !liabilitySortAsc;
        } else {
            liabilitySortField = field;
            liabilitySortAsc = field === 'symbol';
        }
        renderLiabilityTable();
    }

    function filterLiabilityTable() {
        renderLiabilityTable();
    }

    function formatNumber(num) {
        return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showLiabilitySkeletonLoading() {
        const el = document.getElementById('total-debt-usdt');
        if (el) el.innerHTML = '<div class="placeholder-glow"><span class="placeholder col-8 rounded"></span></div>';

        const countEl = document.getElementById('liability-currency-count');
        if (countEl) countEl.innerHTML = '<span class="placeholder col-4 placeholder-sm rounded"></span>';

        const tbody = document.getElementById('liability-table-body');
        if (tbody) {
            let rows = '';
            for (let i = 0; i < 5; i++) {
                rows += `<tr>
                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                </tr>`;
            }
            tbody.innerHTML = rows;
        }
    }

    function showLiabilityErrorState() {
        const el = document.getElementById('total-debt-usdt');
        if (el) el.innerHTML = '<span class="text-danger cursor-pointer" onclick="loadLiabilityStats()" title="کلیک برای تلاش مجدد"><i class="fa fa-exclamation-triangle me-1"></i>خطا</span>';

        const tbody = document.getElementById('liability-table-body');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4 cursor-pointer" onclick="loadLiabilityStats()">
                <i class="fa fa-exclamation-triangle me-2"></i>خطا در بارگذاری - کلیک برای تلاش مجدد
            </td></tr>`;
        }
    }

    // ====== Cash Flow Stats ======
    let cashFlowData = [];
    let cashFlowSortField = 'depositUsdt';
    let cashFlowSortAsc = false;

    async function loadCashFlowStats() {
        const btn = document.getElementById('btn-refresh-cashflow');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> بارگذاری...';
        }

        showCashFlowSkeletonLoading();

        try {
            const params = new URLSearchParams();
            if (currentCashFlowStartDate) params.append('start_date', currentCashFlowStartDate);
            if (currentCashFlowEndDate) params.append('end_date', currentCashFlowEndDate);

            const response = await fetch(`/admin/financial-dashboard/ajax/cash-flow-stats?${params.toString()}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            updateElement('total-deposit-usdt', data.totalDepositUsdt);
            updateElement('total-withdrawal-usdt', data.totalWithdrawalUsdt);

            // Net cash flow with color coding
            const netEl = document.getElementById('net-cashflow-usdt');
            const netIcon = document.getElementById('net-cashflow-icon');
            if (netEl) {
                netEl.textContent = data.netCashFlowUsdt;
                if (data.isNetNegative) {
                    netEl.classList.add('text-danger');
                    netEl.classList.remove('text-success');
                    if (netIcon) {
                        netIcon.classList.remove('bg-label-primary');
                        netIcon.classList.add('bg-label-danger');
                    }
                } else {
                    netEl.classList.add('text-success');
                    netEl.classList.remove('text-danger');
                    if (netIcon) {
                        netIcon.classList.remove('bg-label-danger');
                        netIcon.classList.add('bg-label-primary');
                    }
                }
            }

            const countEl = document.getElementById('cashflow-currency-count');
            if (countEl) {
                countEl.innerHTML = `<i class="fa fa-coins me-1"></i>${data.currencyCount} ارز`;
            }

            // Calculate total counts for deposit/withdrawal cards
            let totalDepCount = 0, totalWthCount = 0;
            data.breakdown.forEach(item => {
                totalDepCount += item.depositCount;
                totalWthCount += item.withdrawalCount;
            });

            const depCountEl = document.getElementById('cashflow-deposit-count');
            if (depCountEl) depCountEl.innerHTML = `<i class="fa fa-hashtag me-1"></i>${totalDepCount.toLocaleString('en-US')} تراکنش`;

            const wthCountEl = document.getElementById('cashflow-withdrawal-count');
            if (wthCountEl) wthCountEl.innerHTML = `<i class="fa fa-hashtag me-1"></i>${totalWthCount.toLocaleString('en-US')} تراکنش`;

            cashFlowData = data.breakdown;
            renderCashFlowTable();

            updateLastRefreshTime();
        } catch (error) {
            console.error('Error loading cash flow stats:', error);
            showCashFlowErrorState();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh me-1"></i> بروزرسانی';
            }
        }
    }

    function renderCashFlowTable() {
        const tbody = document.getElementById('cashflow-table-body');
        if (!tbody) return;

        const searchTerm = (document.getElementById('cashflow-search')?.value || '').toLowerCase();
        let filtered = cashFlowData;

        if (searchTerm) {
            filtered = cashFlowData.filter(item =>
                item.symbol.toLowerCase().includes(searchTerm) ||
                (item.name && item.name.toLowerCase().includes(searchTerm))
            );
        }

        // Sort
        filtered.sort((a, b) => {
            let valA = a[cashFlowSortField];
            let valB = b[cashFlowSortField];

            if (typeof valA === 'string') {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return cashFlowSortAsc ? -1 : 1;
            if (valA > valB) return cashFlowSortAsc ? 1 : -1;
            return 0;
        });

        const noResults = document.getElementById('cashflow-no-results');

        if (filtered.length === 0) {
            tbody.innerHTML = '';
            if (noResults) noResults.classList.remove('d-none');
            return;
        }

        if (noResults) noResults.classList.add('d-none');

        tbody.innerHTML = filtered.map(item => {
            const netClass = item.isNetNegative ? 'text-danger fw-semibold' : 'text-success';
            const netIcon = item.isNetNegative ? '<i class="fa fa-arrow-down me-1"></i>' : '<i class="fa fa-arrow-up me-1"></i>';

            return `<tr>
                <td>
                    <span class="fw-semibold">${escapeHtml(item.symbol)}</span>
                    <small class="text-muted d-block">${escapeHtml(item.name)}</small>
                </td>
                <td class="text-end">
                    <span class="font-number text-success">${formatNumber(item.depositUsdt)}</span>
                    <small class="text-muted d-block font-number">${escapeHtml(item.depositAmount)} ${escapeHtml(item.symbol)} <span class="badge bg-label-secondary rounded-pill">${item.depositCount}</span></small>
                </td>
                <td class="text-end">
                    <span class="font-number text-danger">${formatNumber(item.withdrawalUsdt)}</span>
                    <small class="text-muted d-block font-number">${escapeHtml(item.withdrawalAmount)} ${escapeHtml(item.symbol)} <span class="badge bg-label-secondary rounded-pill">${item.withdrawalCount}</span></small>
                </td>
                <td class="text-end">
                    <span class="${netClass} font-number">${netIcon}${formatNumber(Math.abs(item.netUsdt))}</span>
                    <small class="text-muted d-block font-number">${escapeHtml(item.netAmount)} ${escapeHtml(item.symbol)}</small>
                </td>
            </tr>`;
        }).join('');

        // Update sort icons
        document.querySelectorAll('[id^="cf-sort-icon-"]').forEach(icon => {
            icon.className = 'fa fa-sort text-muted ms-1';
        });
        const activeIcon = document.getElementById(`cf-sort-icon-${cashFlowSortField}`);
        if (activeIcon) {
            activeIcon.className = cashFlowSortAsc
                ? 'fa fa-sort-up text-primary ms-1'
                : 'fa fa-sort-down text-primary ms-1';
        }
    }

    function sortCashFlowTable(field) {
        if (cashFlowSortField === field) {
            cashFlowSortAsc = !cashFlowSortAsc;
        } else {
            cashFlowSortField = field;
            cashFlowSortAsc = field === 'symbol';
        }
        renderCashFlowTable();
    }

    function filterCashFlowTable() {
        renderCashFlowTable();
    }

    function applyCashFlowFilter() {
        const startDate = document.getElementById('cashflow-start-date').value;
        const endDate = document.getElementById('cashflow-end-date').value;

        if (startDate) {
            currentCashFlowStartDate = startDate;
            currentCashFlowEndDate = endDate || null;

            const badge = document.getElementById('cashflow-range-badge');
            if (badge) {
                badge.textContent = endDate ? `${startDate} تا ${endDate}` : `از ${startDate}`;
                badge.style.display = 'inline-block';
            }

            // Update card labels
            updateElement('cashflow-deposit-label', 'کل واریزی‌ها');
            updateElement('cashflow-withdrawal-label', 'کل برداشت‌ها');
            updateElement('cashflow-net-label', 'خالص جریان نقدینگی');
        }

        loadCashFlowStats();
    }

    function resetCashFlowFilter() {
        currentCashFlowStartDate = null;
        currentCashFlowEndDate = null;

        document.getElementById('cashflow-start-date').value = '';
        document.getElementById('cashflow-end-date').value = '';

        const badge = document.getElementById('cashflow-range-badge');
        if (badge) badge.style.display = 'none';

        updateElement('cashflow-deposit-label', 'کل واریزی‌ها امروز');
        updateElement('cashflow-withdrawal-label', 'کل برداشت‌ها امروز');
        updateElement('cashflow-net-label', 'خالص جریان نقدینگی امروز');

        loadCashFlowStats();
    }

    function showCashFlowSkeletonLoading() {
        ['total-deposit-usdt', 'total-withdrawal-usdt', 'net-cashflow-usdt'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<div class="placeholder-glow"><span class="placeholder col-8 rounded"></span></div>';
        });

        ['cashflow-deposit-count', 'cashflow-withdrawal-count', 'cashflow-currency-count'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="placeholder col-4 placeholder-sm rounded"></span>';
        });

        const tbody = document.getElementById('cashflow-table-body');
        if (tbody) {
            let rows = '';
            for (let i = 0; i < 5; i++) {
                rows += `<tr>
                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                </tr>`;
            }
            tbody.innerHTML = rows;
        }
    }

    function showCashFlowErrorState() {
        ['total-deposit-usdt', 'total-withdrawal-usdt', 'net-cashflow-usdt'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="text-danger cursor-pointer" onclick="loadCashFlowStats()" title="کلیک برای تلاش مجدد"><i class="fa fa-exclamation-triangle me-1"></i>خطا</span>';
        });

        const tbody = document.getElementById('cashflow-table-body');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4 cursor-pointer" onclick="loadCashFlowStats()">
                <i class="fa fa-exclamation-triangle me-2"></i>خطا در بارگذاری - کلیک برای تلاش مجدد
            </td></tr>`;
        }
    }

    // ====== Expense Stats ======
    let expenseData = [];
    let expenseSortField = 'totalUsdt';
    let expenseSortAsc = false;
    let expensePieChart = null;

    async function loadExpenseStats() {
        const btn = document.getElementById('btn-refresh-expenses');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> بارگذاری...';
        }

        showExpenseSkeletonLoading();

        try {
            const params = new URLSearchParams();
            if (currentExpenseStartDate) params.append('start_date', currentExpenseStartDate);
            if (currentExpenseEndDate) params.append('end_date', currentExpenseEndDate);

            const response = await fetch(`/admin/financial-dashboard/ajax/expense-stats?${params.toString()}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            updateElement('total-fees-usdt', data.totalFees);
            updateElement('total-transfer-fees', data.totalTransferFees);
            updateElement('total-network-fees', data.totalNetworkFees);
            updateElement('total-ref-exchange-fees', data.totalRefExchangeFees);

            const countEl = document.getElementById('expense-currency-count');
            if (countEl) {
                countEl.innerHTML = `<i class="fa fa-coins me-1"></i>${data.currencyCount} ارز`;
            }

            // Render pie chart
            renderExpensePieChart(data.totalTransferFeesRaw, data.totalNetworkFeesRaw, data.totalRefExchangeFeesRaw);

            expenseData = data.breakdown;
            renderExpenseTable();

            updateLastRefreshTime();
        } catch (error) {
            console.error('Error loading expense stats:', error);
            showExpenseErrorState();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh me-1"></i> بروزرسانی';
            }
        }
    }

    function renderExpensePieChart(transferFees, networkFees, refExchangeFees) {
        const chartEl = document.getElementById('expense-pie-chart');
        if (!chartEl) return;

        // Check if ApexCharts is available
        if (typeof ApexCharts === 'undefined') {
            chartEl.innerHTML = '<div class="text-center text-muted py-4"><i class="fa fa-chart-pie me-1"></i>نمودار در دسترس نیست</div>';
            return;
        }

        const total = transferFees + networkFees + refExchangeFees;

        if (total === 0) {
            chartEl.innerHTML = '<div class="text-center text-muted py-4"><i class="fa fa-info-circle me-1"></i>داده‌ای برای نمایش وجود ندارد</div>';
            if (expensePieChart) {
                expensePieChart.destroy();
                expensePieChart = null;
            }
            return;
        }

        const options = {
            series: [transferFees, networkFees, refExchangeFees],
            chart: {
                type: 'donut',
                height: 280,
                fontFamily: 'inherit',
            },
            labels: ['فی انتقال', 'فی شبکه', 'فی صرافی مرجع'],
            colors: ['#03c3ec', '#ff3e1d', '#696cff'],
            legend: {
                position: 'bottom',
                fontFamily: 'inherit',
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val.toFixed(1) + '%';
                },
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatNumber(val) + ' USDT';
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            name: { show: true },
                            value: {
                                show: true,
                                formatter: function (val) {
                                    return formatNumber(val) + ' USDT';
                                }
                            },
                            total: {
                                show: true,
                                label: 'مجموع',
                                formatter: function (w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return formatNumber(total) + ' USDT';
                                }
                            }
                        }
                    }
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: { height: 250 },
                    legend: { position: 'bottom' }
                }
            }]
        };

        if (expensePieChart) {
            expensePieChart.updateOptions(options);
        } else {
            chartEl.innerHTML = '';
            expensePieChart = new ApexCharts(chartEl, options);
            expensePieChart.render();
        }
    }

    function renderExpenseTable() {
        const tbody = document.getElementById('expense-table-body');
        if (!tbody) return;

        const searchTerm = (document.getElementById('expense-search')?.value || '').toLowerCase();
        let filtered = expenseData;

        if (searchTerm) {
            filtered = expenseData.filter(item =>
                item.symbol.toLowerCase().includes(searchTerm) ||
                (item.name && item.name.toLowerCase().includes(searchTerm))
            );
        }

        // Sort
        filtered.sort((a, b) => {
            let valA = a[expenseSortField];
            let valB = b[expenseSortField];

            if (typeof valA === 'string') {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return expenseSortAsc ? -1 : 1;
            if (valA > valB) return expenseSortAsc ? 1 : -1;
            return 0;
        });

        const noResults = document.getElementById('expense-no-results');

        if (filtered.length === 0) {
            tbody.innerHTML = '';
            if (noResults) noResults.classList.remove('d-none');
            return;
        }

        if (noResults) noResults.classList.add('d-none');

        tbody.innerHTML = filtered.map(item => {
            return `<tr>
                <td>
                    <span class="fw-semibold">${escapeHtml(item.symbol)}</span>
                    <small class="text-muted d-block">${escapeHtml(item.name)}</small>
                </td>
                <td class="text-end">
                    <span class="font-number ${item.transferFeesUsdt > 0 ? 'text-info' : 'text-muted'}">${formatNumber(item.transferFeesUsdt)}</span>
                </td>
                <td class="text-end">
                    <span class="font-number ${item.networkFeesUsdt > 0 ? 'text-danger' : 'text-muted'}">${formatNumber(item.networkFeesUsdt)}</span>
                </td>
                <td class="text-end">
                    <span class="font-number ${item.refExchangeFeesUsdt > 0 ? 'text-primary' : 'text-muted'}">${formatNumber(item.refExchangeFeesUsdt)}</span>
                </td>
                <td class="text-end">
                    <span class="font-number fw-semibold">${formatNumber(item.totalUsdt)}</span>
                </td>
            </tr>`;
        }).join('');

        // Update sort icons
        document.querySelectorAll('[id^="exp-sort-icon-"]').forEach(icon => {
            icon.className = 'fa fa-sort text-muted ms-1';
        });
        const activeIcon = document.getElementById(`exp-sort-icon-${expenseSortField}`);
        if (activeIcon) {
            activeIcon.className = expenseSortAsc
                ? 'fa fa-sort-up text-primary ms-1'
                : 'fa fa-sort-down text-primary ms-1';
        }
    }

    function sortExpenseTable(field) {
        if (expenseSortField === field) {
            expenseSortAsc = !expenseSortAsc;
        } else {
            expenseSortField = field;
            expenseSortAsc = field === 'symbol';
        }
        renderExpenseTable();
    }

    function filterExpenseTable() {
        renderExpenseTable();
    }

    function applyExpenseFilter() {
        const startDate = document.getElementById('expense-start-date').value;
        const endDate = document.getElementById('expense-end-date').value;

        if (startDate) {
            currentExpenseStartDate = startDate;
            currentExpenseEndDate = endDate || null;

            const badge = document.getElementById('expense-range-badge');
            if (badge) {
                badge.textContent = endDate ? `${startDate} تا ${endDate}` : `از ${startDate}`;
                badge.style.display = 'inline-block';
            }

            updateElement('expense-total-label', 'کل هزینه‌ها');
        }

        loadExpenseStats();
    }

    function resetExpenseFilter() {
        currentExpenseStartDate = null;
        currentExpenseEndDate = null;

        document.getElementById('expense-start-date').value = '';
        document.getElementById('expense-end-date').value = '';

        const badge = document.getElementById('expense-range-badge');
        if (badge) badge.style.display = 'none';

        updateElement('expense-total-label', 'کل هزینه‌ها امروز');

        loadExpenseStats();
    }

    function showExpenseSkeletonLoading() {
        const el = document.getElementById('total-fees-usdt');
        if (el) el.innerHTML = '<div class="placeholder-glow"><span class="placeholder col-8 rounded"></span></div>';

        ['total-transfer-fees', 'total-network-fees', 'total-ref-exchange-fees'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span>';
        });

        const countEl = document.getElementById('expense-currency-count');
        if (countEl) countEl.innerHTML = '<span class="placeholder col-4 placeholder-sm rounded"></span>';

        const tbody = document.getElementById('expense-table-body');
        if (tbody) {
            let rows = '';
            for (let i = 0; i < 5; i++) {
                rows += `<tr>
                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                </tr>`;
            }
            tbody.innerHTML = rows;
        }
    }

    function showExpenseErrorState() {
        ['total-fees-usdt', 'total-transfer-fees', 'total-network-fees', 'total-ref-exchange-fees'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="text-danger cursor-pointer" onclick="loadExpenseStats()" title="کلیک برای تلاش مجدد"><i class="fa fa-exclamation-triangle me-1"></i>خطا</span>';
        });

        const tbody = document.getElementById('expense-table-body');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4 cursor-pointer" onclick="loadExpenseStats()">
                <i class="fa fa-exclamation-triangle me-2"></i>خطا در بارگذاری - کلیک برای تلاش مجدد
            </td></tr>`;
        }
    }

    // ====== Profit & Loss Stats ======
    let pnlSortField = 'spreadUsdt';
    let pnlSortAsc = false;

    async function loadProfitLossStats() {
        const btn = document.getElementById('btn-refresh-pnl');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> بارگذاری...';
        }

        showPnlSkeletonLoading();

        try {
            const params = new URLSearchParams();
            if (currentPnlStartDate) params.append('start_date', currentPnlStartDate);
            if (currentPnlEndDate) params.append('end_date', currentPnlEndDate);

            const response = await fetch(`/admin/financial-dashboard/ajax/profit-loss-stats?${params.toString()}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const data = await response.json();

            // Trade Spread
            updateElement('total-spread-usdt', data.totalSpread);

            // Total P/L with color
            const pnlEl = document.getElementById('total-profit-loss');
            if (pnlEl) {
                const prefix = data.isProfitNegative ? '-' : '+';
                pnlEl.innerHTML = `<span class="${data.isProfitNegative ? 'text-danger' : 'text-success'}">${prefix}${data.totalProfitLoss}</span>`;
            }

            const pnlIconBg = document.getElementById('pnl-icon-bg');
            if (pnlIconBg) {
                pnlIconBg.className = `avatar-initial rounded-circle ${data.isProfitNegative ? 'bg-label-danger' : 'bg-label-success'}`;
            }

            // Revenue badge
            const revBadge = document.getElementById('pnl-revenue-badge');
            if (revBadge) {
                revBadge.innerHTML = `درآمد: ${data.totalRevenue} USDT`;
                revBadge.className = 'badge bg-label-secondary ms-2';
            }

            // Net Profit Margin
            const marginEl = document.getElementById('net-profit-margin');
            if (marginEl) {
                const sign = data.isMarginNegative ? '-' : '';
                marginEl.innerHTML = `<span class="${data.isMarginNegative ? 'text-danger' : 'text-success'}">${sign}${data.netProfitMargin}%</span>`;
            }

            const marginIconBg = document.getElementById('margin-icon-bg');
            if (marginIconBg) {
                marginIconBg.className = `avatar-initial rounded-circle ${data.isMarginNegative ? 'bg-label-danger' : 'bg-label-primary'}`;
            }

            // Summary items
            updateElement('pnl-total-revenue', data.totalRevenue);
            updateElement('pnl-total-expenses', data.totalExpenses);
            updateElement('pnl-total-commission', data.totalCommission);

            // Currency count badge
            const countEl = document.getElementById('pnl-currency-count');
            if (countEl) countEl.textContent = `${data.currencyCount} ارز`;

            // Store and render table
            pnlSpreadData = data.spreadBreakdown || [];
            renderPnlTable();

            updateLastRefreshTime();
        } catch (error) {
            console.error('Error loading P&L stats:', error);
            showPnlErrorState();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh me-1"></i> بروزرسانی';
            }
        }
    }

    function renderPnlTable() {
        const tbody = document.getElementById('pnl-table-body');
        if (!tbody) return;

        const searchTerm = (document.getElementById('pnl-search')?.value || '').toLowerCase();
        let filtered = pnlSpreadData;

        if (searchTerm) {
            filtered = pnlSpreadData.filter(item =>
                item.symbol.toLowerCase().includes(searchTerm) ||
                (item.name && item.name.toLowerCase().includes(searchTerm))
            );
        }

        // Sort
        filtered.sort((a, b) => {
            let valA = a[pnlSortField];
            let valB = b[pnlSortField];

            if (typeof valA === 'string') {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return pnlSortAsc ? -1 : 1;
            if (valA > valB) return pnlSortAsc ? 1 : -1;
            return 0;
        });

        const noResults = document.getElementById('pnl-no-results');

        if (filtered.length === 0) {
            tbody.innerHTML = '';
            if (noResults) noResults.classList.remove('d-none');
            return;
        }

        if (noResults) noResults.classList.add('d-none');

        tbody.innerHTML = filtered.map(item => {
            const spreadClass = item.spreadUsdt < 0 ? 'text-danger' : 'text-success';
            const spreadIcon = item.spreadUsdt < 0 ? '<i class="fa fa-arrow-down me-1"></i>' : '<i class="fa fa-arrow-up me-1"></i>';
            const pctClass = item.avgSpreadPct < 0 ? 'text-danger' : 'text-success';

            return `<tr>
                <td>
                    <span class="fw-semibold">${escapeHtml(item.symbol)}</span>
                    <small class="text-muted d-block">${escapeHtml(item.name)}</small>
                </td>
                <td class="text-end">
                    <span class="font-number">${item.tradeCount.toLocaleString('en-US')}</span>
                    <small class="text-muted d-block">
                        <span class="text-success">${item.buyCount} خرید</span> /
                        <span class="text-danger">${item.sellCount} فروش</span>
                    </small>
                </td>
                <td class="text-end">
                    <span class="font-number">${formatNumber(item.volumeUsdt)}</span>
                </td>
                <td class="text-end">
                    <span class="${spreadClass} font-number fw-semibold">${spreadIcon}${formatNumber(Math.abs(item.spreadUsdt))}</span>
                </td>
                <td class="text-end">
                    <span class="${pctClass} font-number fw-semibold">${item.avgSpreadPct}%</span>
                </td>
            </tr>`;
        }).join('');

        // Update sort icons
        document.querySelectorAll('[id^="pnl-sort-icon-"]').forEach(icon => {
            icon.className = 'fa fa-sort text-muted ms-1';
        });
        const activeIcon = document.getElementById(`pnl-sort-icon-${pnlSortField}`);
        if (activeIcon) {
            activeIcon.className = pnlSortAsc
                ? 'fa fa-sort-up text-primary ms-1'
                : 'fa fa-sort-down text-primary ms-1';
        }
    }

    function sortPnlTable(field) {
        if (pnlSortField === field) {
            pnlSortAsc = !pnlSortAsc;
        } else {
            pnlSortField = field;
            pnlSortAsc = field === 'symbol';
        }
        renderPnlTable();
    }

    function filterPnlTable() {
        renderPnlTable();
    }

    function applyPnlFilter() {
        const startDate = document.getElementById('pnl-start-date').value;
        const endDate = document.getElementById('pnl-end-date').value;

        if (startDate) {
            currentPnlStartDate = startDate;
            currentPnlEndDate = endDate || null;

            const badge = document.getElementById('pnl-range-badge');
            if (badge) {
                badge.textContent = endDate ? `${startDate} تا ${endDate}` : `از ${startDate}`;
                badge.style.display = 'inline-block';
            }

            updateElement('pnl-spread-label', 'سود اسپرد معاملات');
            updateElement('pnl-total-label', 'سود/زیان خالص');
            updateElement('pnl-margin-label', 'حاشیه سود خالص');
        }

        loadProfitLossStats();
    }

    function resetPnlFilter() {
        currentPnlStartDate = null;
        currentPnlEndDate = null;

        document.getElementById('pnl-start-date').value = '';
        document.getElementById('pnl-end-date').value = '';

        const badge = document.getElementById('pnl-range-badge');
        if (badge) badge.style.display = 'none';

        updateElement('pnl-spread-label', 'سود اسپرد معاملات امروز');
        updateElement('pnl-total-label', 'سود/زیان خالص امروز');
        updateElement('pnl-margin-label', 'حاشیه سود خالص امروز');

        loadProfitLossStats();
    }

    function showPnlSkeletonLoading() {
        ['total-spread-usdt', 'total-profit-loss', 'net-profit-margin'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span>';
        });

        ['pnl-total-revenue', 'pnl-total-expenses', 'pnl-total-commission'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span>';
        });

        const revBadge = document.getElementById('pnl-revenue-badge');
        if (revBadge) revBadge.innerHTML = '<span class="placeholder-glow"><span class="placeholder col-6 placeholder-sm rounded"></span></span>';

        const countEl = document.getElementById('pnl-currency-count');
        if (countEl) countEl.innerHTML = '<span class="placeholder col-4 placeholder-sm rounded"></span>';

        const tbody = document.getElementById('pnl-table-body');
        if (tbody) {
            let rows = '';
            for (let i = 0; i < 5; i++) {
                rows += `<tr>
                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                </tr>`;
            }
            tbody.innerHTML = rows;
        }
    }

    function showPnlErrorState() {
        ['total-spread-usdt', 'total-profit-loss', 'net-profit-margin'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="text-danger cursor-pointer" onclick="loadProfitLossStats()" title="کلیک برای تلاش مجدد"><i class="fa fa-exclamation-triangle me-1"></i>خطا</span>';
        });

        ['pnl-total-revenue', 'pnl-total-expenses', 'pnl-total-commission'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="text-danger">--</span>';
        });

        const tbody = document.getElementById('pnl-table-body');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4 cursor-pointer" onclick="loadProfitLossStats()">
                <i class="fa fa-exclamation-triangle me-2"></i>خطا در بارگذاری - کلیک برای تلاش مجدد
            </td></tr>`;
        }
    }

    // ====== Stock Purchase Stats ======
    let stockSearchTimeout = null;

    async function loadStockPurchaseStats(page) {
        if (page !== undefined) stockCurrentPage = page;

        const btn = document.getElementById('btn-refresh-stock');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> بارگذاری...';
        }

        showStockSkeletonLoading();

        try {
            const params = new URLSearchParams();
            params.append('page', stockCurrentPage);
            params.append('sort', stockSortField);
            params.append('direction', stockSortDir);
            if (currentStockStartDate) params.append('start_date', currentStockStartDate);
            if (currentStockEndDate) params.append('end_date', currentStockEndDate);
            if (currentStockSearch) params.append('search', currentStockSearch);

            const response = await fetch(`/admin/financial-dashboard/ajax/stock-purchase-stats?${params.toString()}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const data = await response.json();

            // Update KPI cards
            updateElement('stock-total-contracts', data.totalContracts);
            updateElement('stock-active-contracts', data.activeContracts);
            updateElement('stock-total-amount', data.totalAmount);
            updateElement('stock-total-value', data.totalValue);

            // Render table
            renderStockTable(data.items);

            // Render pagination
            renderStockPagination(data.pagination);

            updateLastRefreshTime();
        } catch (error) {
            console.error('Error loading stock purchase stats:', error);
            showStockErrorState();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh me-1"></i> بروزرسانی';
            }
        }
    }

    function renderStockTable(items) {
        const tbody = document.getElementById('stock-table-body');
        if (!tbody) return;

        const noResults = document.getElementById('stock-no-results');

        if (!items || items.length === 0) {
            tbody.innerHTML = '';
            if (noResults) noResults.classList.remove('d-none');
            return;
        }

        if (noResults) noResults.classList.add('d-none');

        tbody.innerHTML = items.map(item => `<tr>
            <td>
                <span class="font-number text-nowrap">${escapeHtml(item.date || '-')}</span>
            </td>
            <td>
                <span class="fw-semibold">${escapeHtml(item.userName || '-')}</span>
                <small class="text-muted d-block">${escapeHtml(item.userEmail || '')}</small>
            </td>
            <td>
                <span>${escapeHtml(item.stockName)}</span>
            </td>
            <td class="text-end">
                <span class="font-number">${escapeHtml(item.amount)}</span>
            </td>
            <td class="text-end">
                <span class="font-number fw-semibold">${formatNumber(parseFloat(String(item.totalValue).replace(/,/g, '')) || 0)}</span>
            </td>
            <td class="text-center">
                <code class="small">${escapeHtml(item.contractNumber)}</code>
            </td>
            <td class="text-center">
                <span class="badge bg-label-${item.statusColor}">${escapeHtml(item.statusLabel)}</span>
            </td>
        </tr>`).join('');

        // Update sort icons
        document.querySelectorAll('[id^="stock-sort-icon-"]').forEach(icon => {
            icon.className = 'fa fa-sort text-muted ms-1';
        });
        const activeIcon = document.getElementById(`stock-sort-icon-${stockSortField}`);
        if (activeIcon) {
            activeIcon.className = stockSortDir === 'asc'
                ? 'fa fa-sort-up text-primary ms-1'
                : 'fa fa-sort-down text-primary ms-1';
        }
    }

    function renderStockPagination(pagination) {
        const info = document.getElementById('stock-pagination-info');
        const paginationEl = document.getElementById('stock-pagination');
        const wrapper = document.getElementById('stock-pagination-wrapper');

        if (!pagination || pagination.total === 0) {
            if (info) info.textContent = '';
            if (paginationEl) paginationEl.innerHTML = '';
            if (wrapper) wrapper.classList.add('d-none');
            return;
        }

        if (wrapper) wrapper.classList.remove('d-none');

        if (info) {
            info.textContent = `نمایش ${pagination.from || 0} تا ${pagination.to || 0} از ${pagination.total} مورد`;
        }

        if (paginationEl) {
            let html = '';

            // Previous
            html += `<li class="page-item ${pagination.currentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="goToStockPage(${pagination.currentPage - 1})"><i class="fa fa-chevron-right"></i></a>
            </li>`;

            // Page numbers
            const start = Math.max(1, pagination.currentPage - 2);
            const end = Math.min(pagination.lastPage, pagination.currentPage + 2);

            if (start > 1) {
                html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToStockPage(1)">1</a></li>`;
                if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }

            for (let i = start; i <= end; i++) {
                html += `<li class="page-item ${i === pagination.currentPage ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="goToStockPage(${i})">${i}</a>
                </li>`;
            }

            if (end < pagination.lastPage) {
                if (end < pagination.lastPage - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToStockPage(${pagination.lastPage})">${pagination.lastPage}</a></li>`;
            }

            // Next
            html += `<li class="page-item ${pagination.currentPage >= pagination.lastPage ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="goToStockPage(${pagination.currentPage + 1})"><i class="fa fa-chevron-left"></i></a>
            </li>`;

            paginationEl.innerHTML = html;
        }
    }

    function goToStockPage(page) {
        if (page < 1) return;
        loadStockPurchaseStats(page);
    }

    function sortStockTable(field) {
        if (stockSortField === field) {
            stockSortDir = stockSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            stockSortField = field;
            stockSortDir = field === 'contract_number' ? 'asc' : 'desc';
        }
        stockCurrentPage = 1;
        loadStockPurchaseStats();
    }

    function handleStockSearch() {
        clearTimeout(stockSearchTimeout);
        stockSearchTimeout = setTimeout(() => {
            currentStockSearch = document.getElementById('stock-search')?.value || null;
            if (currentStockSearch === '') currentStockSearch = null;
            stockCurrentPage = 1;
            loadStockPurchaseStats();
        }, 400);
    }

    function applyStockFilter() {
        const startDate = document.getElementById('stock-start-date').value;
        const endDate = document.getElementById('stock-end-date').value;

        if (startDate) {
            currentStockStartDate = startDate;
            currentStockEndDate = endDate || null;

            const badge = document.getElementById('stock-range-badge');
            if (badge) {
                badge.textContent = endDate ? `${startDate} تا ${endDate}` : `از ${startDate}`;
                badge.style.display = 'inline-block';
            }
        }

        stockCurrentPage = 1;
        loadStockPurchaseStats();
    }

    function resetStockFilter() {
        currentStockStartDate = null;
        currentStockEndDate = null;

        document.getElementById('stock-start-date').value = '';
        document.getElementById('stock-end-date').value = '';

        const badge = document.getElementById('stock-range-badge');
        if (badge) badge.style.display = 'none';

        stockCurrentPage = 1;
        loadStockPurchaseStats();
    }

    function exportStockPurchases() {
        const params = new URLSearchParams();
        if (currentStockStartDate) params.append('start_date', currentStockStartDate);
        if (currentStockEndDate) params.append('end_date', currentStockEndDate);

        const url = `/admin/financial-dashboard/ajax/stock-purchase-export?${params.toString()}`;
        window.location.href = url;
    }

    function showStockSkeletonLoading() {
        ['stock-total-contracts', 'stock-active-contracts', 'stock-total-amount', 'stock-total-value'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span>';
        });

        const tbody = document.getElementById('stock-table-body');
        if (tbody) {
            let rows = '';
            for (let i = 0; i < 5; i++) {
                rows += `<tr>
                    <td><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                    <td><span class="placeholder-glow"><span class="placeholder col-5 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-4 rounded"></span></span></td>
                    <td class="text-end"><span class="placeholder-glow"><span class="placeholder col-6 rounded"></span></span></td>
                    <td class="text-center"><span class="placeholder-glow"><span class="placeholder col-8 rounded"></span></span></td>
                    <td class="text-center"><span class="placeholder-glow"><span class="placeholder col-4 rounded"></span></span></td>
                </tr>`;
            }
            tbody.innerHTML = rows;
        }
    }

    function showStockErrorState() {
        ['stock-total-contracts', 'stock-active-contracts', 'stock-total-amount', 'stock-total-value'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="text-danger cursor-pointer" onclick="loadStockPurchaseStats()" title="کلیک برای تلاش مجدد"><i class="fa fa-exclamation-triangle me-1"></i>خطا</span>';
        });

        const tbody = document.getElementById('stock-table-body');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4 cursor-pointer" onclick="loadStockPurchaseStats()">
                <i class="fa fa-exclamation-triangle me-2"></i>خطا در بارگذاری - کلیک برای تلاش مجدد
            </td></tr>`;
        }
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        // Load initial data
        loadTradeStats();
        loadRevenueStats();
        loadAssetStats();
        loadLiabilityStats();
        loadCashFlowStats();
        loadExpenseStats();
        loadProfitLossStats();
        loadStockPurchaseStats();

        // Auto refresh every 60 seconds
        refreshInterval = setInterval(() => {
            loadTradeStats();
            loadRevenueStats();
            loadAssetStats();
            loadLiabilityStats();
            loadCashFlowStats();
            loadExpenseStats();
            loadProfitLossStats();
            loadStockPurchaseStats();
        }, 60000);
    });

})();
