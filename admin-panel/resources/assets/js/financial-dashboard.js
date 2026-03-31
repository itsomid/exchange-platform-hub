/**
 * Financial Dashboard - Trade Stats
 */

'use strict';

(function () {
    let refreshInterval = null;
    let currentVolumeStartDate = null;
    let currentVolumeEndDate = null;
    let currentTradeCountDate = null;

    // Load Trade Stats
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

    // Expose globally for onclick handlers
    window.loadTradeStats = loadTradeStats;

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        // Load initial data
        loadTradeStats();

        // Auto refresh every 60 seconds
        refreshInterval = setInterval(() => {
            loadTradeStats();
        }, 60000);
    });

})();
