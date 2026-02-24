/**
 * @file
 * Producer Portal - AgroConecta
 *
 * Gestiona el Dashboard del productor:
 * - Gráfico de ventas con Chart.js
 * - Acciones sobre pedidos (confirmar, enviar)
 */
(function (Drupal, drupalSettings, once) {
    'use strict';

    // CSRF token cache for POST/DELETE requests.
    var _csrfToken = null;
    function getCsrfToken() {
        if (_csrfToken) return Promise.resolve(_csrfToken);
        return fetch('/session/token')
            .then(function (r) { return r.text(); })
            .then(function (token) { _csrfToken = token; return token; });
    }

    Drupal.behaviors.agroconectaProducerPortal = {
        attach: function (context) {
            // === Chart.js: Gráfico de ventas ===
            const chartCanvas = once('sales-chart', '#sales-chart', context);
            if (chartCanvas.length && typeof Chart !== 'undefined') {
                const chartData = drupalSettings.agroconecta?.salesChart;
                if (chartData) {
                    new Chart(chartCanvas[0], {
                        type: 'bar',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: Drupal.t('Ventas (€)'),
                                data: chartData.data,
                                backgroundColor: 'rgba(74, 144, 226, 0.6)',
                                borderColor: 'rgba(74, 144, 226, 1)',
                                borderWidth: 1,
                                borderRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function (value) {
                                            return value.toFixed(2) + ' €';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // === Acciones sobre pedidos ===
            const actionButtons = once('order-actions', '[data-action]', context);
            actionButtons.forEach(function (button) {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const action = this.dataset.action;
                    const container = this.closest('[data-suborder-id]');
                    if (!container) return;

                    const suborderId = container.dataset.suborderId;
                    let endpoint = '';

                    if (action === 'confirm') {
                        endpoint = '/api/v1/agro/producer/orders/' + suborderId + '/confirm';
                    } else if (action === 'ship') {
                        const trackingNumber = prompt(Drupal.t('Introduce el número de seguimiento:'));
                        if (!trackingNumber) return;
                        endpoint = '/api/v1/agro/producer/orders/' + suborderId + '/ship';
                    }

                    if (!endpoint) return;

                    this.disabled = true;
                    this.textContent = Drupal.t('Procesando...');

                    getCsrfToken().then(function (token) {
                        return fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': token,
                            },
                            body: JSON.stringify({ tracking_number: action === 'ship' ? trackingNumber : undefined }),
                        });
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.status === 'success' || data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || Drupal.t('Error al procesar la acción.'));
                                button.disabled = false;
                                button.textContent = action === 'confirm' ? Drupal.t('Confirmar Pedido') : Drupal.t('Marcar como Enviado');
                            }
                        })
                        .catch(function () {
                            alert(Drupal.t('Error de conexión.'));
                            button.disabled = false;
                        });
                });
            });
        }
    };

})(Drupal, drupalSettings, once);
