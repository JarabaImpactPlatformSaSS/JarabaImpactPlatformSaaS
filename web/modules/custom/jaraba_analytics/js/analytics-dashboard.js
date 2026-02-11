/**
 * @file
 * Dashboard de Analytics - JavaScript Premium.
 *
 * Implementa visualización de KPIs y gráficos con Chart.js.
 * Cumple con: Drupal.t() traducible, Drupal.behaviors con once().
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Comportamiento para el Dashboard de Analytics.
     */
    Drupal.behaviors.jarabaAnalyticsDashboard = {
        attach: function (context) {
            once('analytics-dashboard', '.analytics-dashboard', context).forEach(function (dashboard) {
                const tenantId = dashboard.dataset.tenantId || null;
                const dateRangeSelect = dashboard.querySelector('.analytics-dashboard__date-range');

                // Estado inicial.
                let currentPeriod = '30d';

                /**
                 * Inicializa el dashboard.
                 */
                function init() {
                    loadDashboardData();
                    setupEventListeners();
                    setupAutoRefresh();
                }

                /**
                 * Configura listeners de eventos.
                 */
                function setupEventListeners() {
                    if (dateRangeSelect) {
                        dateRangeSelect.addEventListener('change', function () {
                            currentPeriod = this.value;
                            loadDashboardData();
                        });
                    }
                }

                /**
                 * Configura auto-refresh de datos en tiempo real.
                 */
                function setupAutoRefresh() {
                    // Refresh tiempo real cada 30 segundos.
                    setInterval(loadRealtimeData, 30000);
                }

                /**
                 * Carga todos los datos del dashboard.
                 */
                function loadDashboardData() {
                    dashboard.classList.add('is-loading');

                    Promise.all([
                        fetchDashboardKPIs(),
                        fetchTopPages(),
                        fetchRealtimeVisitors()
                    ]).then(function () {
                        dashboard.classList.remove('is-loading');
                    }).catch(function (error) {
                        console.error('Error loading dashboard:', error);
                        dashboard.classList.remove('is-loading');
                    });
                }

                /**
                 * Obtiene KPIs principales.
                 */
                function fetchDashboardKPIs() {
                    const url = '/api/v1/analytics/dashboard?period=' + currentPeriod +
                        (tenantId ? '&tenant_id=' + tenantId : '');

                    return fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.success) {
                                renderKPIs(data.data);
                                renderTrendsChart(data.data.trends || []);
                                renderDevicesChart(data.data.devices || {});
                                renderFunnelChart(data.data.funnel || []);
                            }
                        });
                }

                /**
                 * Renderiza los KPIs.
                 */
                function renderKPIs(data) {
                    const container = dashboard.querySelector('.analytics-dashboard__kpis');
                    if (!container) return;

                    const kpis = [
                        {
                            key: 'unique_visitors',
                            label: Drupal.t('Visitantes Únicos'),
                            icon: 'users',
                            color: 'corporate'
                        },
                        {
                            key: 'page_views',
                            label: Drupal.t('Páginas Vistas'),
                            icon: 'document',
                            color: 'impulse'
                        },
                        {
                            key: 'sessions',
                            label: Drupal.t('Sesiones'),
                            icon: 'clock',
                            color: 'innovation'
                        },
                        {
                            key: 'total_revenue',
                            label: Drupal.t('Ingresos'),
                            icon: 'currency',
                            color: 'success'
                        }
                    ];

                    container.innerHTML = kpis.map(function (kpi) {
                        const value = data[kpi.key] || 0;
                        const formattedValue = kpi.key === 'total_revenue'
                            ? formatCurrency(value)
                            : formatNumber(value);

                        return '<div class="analytics-kpi analytics-kpi--' + kpi.color + '">' +
                            '<div class="analytics-kpi__icon">' +
                            '<span class="icon icon--' + kpi.icon + '"></span>' +
                            '</div>' +
                            '<div class="analytics-kpi__content">' +
                            '<span class="analytics-kpi__value">' + formattedValue + '</span>' +
                            '<span class="analytics-kpi__label">' + kpi.label + '</span>' +
                            '</div>' +
                            '</div>';
                    }).join('');
                }

                /**
                 * Renderiza gráfico de tendencias.
                 */
                function renderTrendsChart(trends) {
                    const canvas = document.getElementById('chart-trends');
                    if (!canvas || typeof Chart === 'undefined') return;

                    // Destruir chart existente.
                    if (canvas._chart) {
                        canvas._chart.destroy();
                    }

                    const ctx = canvas.getContext('2d');
                    const labels = trends.map(function (t) { return t.date; });
                    const visitorsData = trends.map(function (t) { return t.visitors || 0; });
                    const viewsData = trends.map(function (t) { return t.page_views || 0; });

                    canvas._chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: Drupal.t('Visitantes'),
                                    data: visitorsData,
                                    borderColor: getComputedStyle(document.documentElement)
                                        .getPropertyValue('--ej-color-corporate').trim() || '#233D63',
                                    backgroundColor: 'rgba(35, 61, 99, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                },
                                {
                                    label: Drupal.t('Páginas Vistas'),
                                    data: viewsData,
                                    borderColor: getComputedStyle(document.documentElement)
                                        .getPropertyValue('--ej-color-primary').trim() || '#FF8C42',
                                    backgroundColor: 'transparent',
                                    fill: false,
                                    tension: 0.4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                /**
                 * Renderiza gráfico de dispositivos (donut).
                 */
                function renderDevicesChart(devices) {
                    const canvas = document.getElementById('chart-devices');
                    if (!canvas || typeof Chart === 'undefined') return;

                    if (canvas._chart) {
                        canvas._chart.destroy();
                    }

                    const ctx = canvas.getContext('2d');
                    const labels = [
                        Drupal.t('Escritorio'),
                        Drupal.t('Móvil'),
                        Drupal.t('Tablet')
                    ];
                    const data = [
                        devices.desktop || 0,
                        devices.mobile || 0,
                        devices.tablet || 0
                    ];

                    canvas._chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: [
                                    getComputedStyle(document.documentElement)
                                        .getPropertyValue('--ej-color-corporate').trim() || '#233D63',
                                    getComputedStyle(document.documentElement)
                                        .getPropertyValue('--ej-color-primary').trim() || '#FF8C42',
                                    getComputedStyle(document.documentElement)
                                        .getPropertyValue('--ej-color-secondary').trim() || '#00A9A5'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }

                /**
                 * Renderiza funnel de conversión.
                 */
                function renderFunnelChart(funnel) {
                    const canvas = document.getElementById('chart-funnel');
                    if (!canvas || typeof Chart === 'undefined') return;

                    if (canvas._chart) {
                        canvas._chart.destroy();
                    }

                    const ctx = canvas.getContext('2d');
                    const defaultFunnel = [
                        { step: Drupal.t('Visitantes'), count: 1000 },
                        { step: Drupal.t('Ver Producto'), count: 450 },
                        { step: Drupal.t('Añadir al Carrito'), count: 120 },
                        { step: Drupal.t('Iniciar Checkout'), count: 80 },
                        { step: Drupal.t('Compra'), count: 35 }
                    ];

                    const funnelData = funnel.length > 0 ? funnel : defaultFunnel;
                    const labels = funnelData.map(function (f) { return f.step; });
                    const data = funnelData.map(function (f) { return f.count; });

                    canvas._chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: Drupal.t('Usuarios'),
                                data: data,
                                backgroundColor: getComputedStyle(document.documentElement)
                                    .getPropertyValue('--ej-color-secondary').trim() || '#00A9A5',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                /**
                 * Obtiene top páginas.
                 */
                function fetchTopPages() {
                    const url = '/api/v1/analytics/pages/top?limit=5' +
                        (tenantId ? '&tenant_id=' + tenantId : '');

                    return fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.success) {
                                renderTopPages(data.data || []);
                            }
                        });
                }

                /**
                 * Renderiza tabla de top páginas.
                 */
                function renderTopPages(pages) {
                    const container = dashboard.querySelector('.analytics-dashboard__top-pages');
                    if (!container) return;

                    if (pages.length === 0) {
                        container.innerHTML = '<p class="analytics-empty">' +
                            Drupal.t('No hay datos de páginas disponibles.') + '</p>';
                        return;
                    }

                    let html = '<table class="analytics-table">' +
                        '<thead><tr>' +
                        '<th>' + Drupal.t('Página') + '</th>' +
                        '<th style="text-align:right">' + Drupal.t('Visitas') + '</th>' +
                        '</tr></thead>' +
                        '<tbody>';

                    pages.forEach(function (page) {
                        html += '<tr>' +
                            '<td class="analytics-table__url" title="' + escapeHtml(page.url) + '">' +
                            escapeHtml(page.url) +
                            '</td>' +
                            '<td class="analytics-table__count">' + formatNumber(page.count) + '</td>' +
                            '</tr>';
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                }

                /**
                 * Obtiene visitantes en tiempo real.
                 */
                function fetchRealtimeVisitors() {
                    const url = '/api/v1/analytics/realtime' +
                        (tenantId ? '?tenant_id=' + tenantId : '');

                    return fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.success) {
                                renderRealtimeWidget(data.data.active_visitors || 0);
                            }
                        });
                }

                /**
                 * Carga solo datos de tiempo real (para auto-refresh).
                 */
                function loadRealtimeData() {
                    fetchRealtimeVisitors();
                }

                /**
                 * Renderiza widget de tiempo real.
                 */
                function renderRealtimeWidget(count) {
                    const container = dashboard.querySelector('.analytics-dashboard__realtime');
                    if (!container) return;

                    container.innerHTML = '<div class="analytics-realtime">' +
                        '<div class="analytics-realtime__pulse"></div>' +
                        '<span class="analytics-realtime__count">' + formatNumber(count) + '</span>' +
                        '<span class="analytics-realtime__label">' +
                        Drupal.t('visitantes activos ahora') +
                        '</span>' +
                        '</div>';
                }

                // === Utilidades ===

                /**
                 * Formatea número con separadores de miles.
                 */
                function formatNumber(num) {
                    return new Intl.NumberFormat('es-ES').format(num);
                }

                /**
                 * Formatea moneda.
                 */
                function formatCurrency(num) {
                    return new Intl.NumberFormat('es-ES', {
                        style: 'currency',
                        currency: 'EUR',
                        minimumFractionDigits: 0
                    }).format(num);
                }

                /**
                 * Escapa HTML para prevenir XSS.
                 */
                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                // Inicializar.
                init();
            });
        }
    };

})(Drupal, drupalSettings, once);
