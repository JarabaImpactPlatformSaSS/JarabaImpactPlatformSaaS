/**
 * @file
 * JavaScript behaviors para el Analytics Dashboard AgroConecta.
 *
 * Fase 57 — Analytics Dashboard.
 * Chart.js sparklines, date range picker, AJAX dashboard refresh.
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Behavior: Analytics Dashboard con Chart.js sparklines.
     */
    Drupal.behaviors.agroAnalyticsDashboard = {
        attach: function (context) {
            once('agro-analytics-dashboard', '.agro-analytics', context).forEach(function (dashboard) {
                var period = '30d';

                // Date picker presets
                var presets = dashboard.querySelectorAll('.agro-date-picker__preset');
                presets.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        presets.forEach(function (b) { b.classList.remove('agro-date-picker__preset--active'); });
                        btn.classList.add('agro-date-picker__preset--active');
                        period = btn.dataset.period;
                        loadDashboard(dashboard, period);
                    });
                });

                // Initial load
                loadDashboard(dashboard, period);
            });
        }
    };

    /**
     * Carga datos del dashboard via API.
     */
    function loadDashboard(dashboard, period) {
        fetch('/api/v1/agro/analytics/dashboard?period=' + period, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.kpis) updateKPIs(dashboard, data.kpis);
                if (data.top_products) updateRanking(dashboard, 'products', data.top_products);
                if (data.top_producers) updateRanking(dashboard, 'producers', data.top_producers);
                if (data.alerts) updateAlerts(dashboard, data.alerts);
                if (data.sparklines) renderSparklines(dashboard, data.sparklines);
            })
            .catch(function (err) {
                console.error('AgroConecta Analytics: Error loading dashboard', err);
            });
    }

    /**
     * Actualiza los KPIs en las cards.
     */
    function updateKPIs(dashboard, kpis) {
        Object.keys(kpis).forEach(function (key) {
            var card = dashboard.querySelector('[data-kpi="' + key + '"]');
            if (!card) return;

            var valueEl = card.querySelector('.agro-kpi-card__value');
            var changeEl = card.querySelector('.agro-kpi-card__change');

            if (valueEl) valueEl.textContent = kpis[key].formatted;

            if (changeEl && kpis[key].change !== undefined) {
                var change = kpis[key].change;
                var direction = change > 0 ? 'up' : change < 0 ? 'down' : 'neutral';
                var sign = change > 0 ? '+' : '';
                changeEl.className = 'agro-kpi-card__change agro-kpi-card__change--' + direction;
                changeEl.textContent = sign + change.toFixed(1) + '%';
            }
        });
    }

    /**
     * Actualiza un ranking.
     */
    function updateRanking(dashboard, type, items) {
        var list = dashboard.querySelector('[data-ranking="' + type + '"]');
        if (!list) return;

        var html = '';
        items.forEach(function (item, idx) {
            var rankClass = idx < 3 ? 'agro-ranking-card__rank--' + (idx + 1) : 'agro-ranking-card__rank--default';
            html += '<li class="agro-ranking-card__item">' +
                '<span class="agro-ranking-card__rank ' + rankClass + '">' + (idx + 1) + '</span>' +
                '<span class="agro-ranking-card__name">' + Drupal.checkPlain(item.name) + '</span>' +
                '<span class="agro-ranking-card__value">' + Drupal.checkPlain(String(item.value)) + '</span>' +
                '</li>';
        });
        list.innerHTML = html;
    }

    /**
     * Actualiza las alertas activas.
     */
    function updateAlerts(dashboard, alerts) {
        var container = dashboard.querySelector('.agro-alerts__list');
        if (!container) return;

        if (alerts.length === 0) {
            container.innerHTML = '<p class="agro-alerts__empty">' + Drupal.t('Sin alertas activas') + '</p>';
            return;
        }

        var html = '';
        alerts.forEach(function (alert) {
            html += '<div class="agro-alert-item agro-alert-item--' + alert.severity + '">' +
                '<span class="agro-alert-item__icon" aria-hidden="true"></span>' +
                '<div class="agro-alert-item__content">' +
                '<strong>' + Drupal.checkPlain(alert.title) + '</strong>' +
                '<small>' + Drupal.checkPlain(alert.message) + '</small>' +
                '</div>' +
                '<span class="agro-alert-item__time">' + Drupal.checkPlain(alert.time_ago) + '</span>' +
                '</div>';
        });
        container.innerHTML = html;
    }

    /**
     * Renderiza sparklines con Chart.js.
     */
    function renderSparklines(dashboard, sparklines) {
        // Solo si Chart.js está cargado
        if (typeof Chart === 'undefined') return;

        Object.keys(sparklines).forEach(function (key) {
            var container = dashboard.querySelector('[data-sparkline="' + key + '"]');
            if (!container) return;

            // Limpiar canvas previo
            container.innerHTML = '<canvas></canvas>';
            var canvas = container.querySelector('canvas');
            var ctx = canvas.getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: sparklines[key].labels || [],
                    datasets: [{
                        data: sparklines[key].data || [],
                        borderColor: '#1565C0',
                        backgroundColor: 'rgba(21, 101, 192, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            titleFont: { size: 11 },
                            bodyFont: { size: 11 }
                        }
                    },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x'
                    }
                }
            });
        });
    }

})(Drupal, drupalSettings, once);
