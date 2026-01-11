/**
 * Health Dashboard - Real-time Updates
 * 
 * Handles auto-refresh and API calls for the health dashboard.
 */

(function (Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.healthDashboard = {
        attach: function (context, settings) {
            const refreshBtn = document.getElementById('refresh-health');

            if (refreshBtn && !refreshBtn.dataset.attached) {
                refreshBtn.dataset.attached = 'true';

                refreshBtn.addEventListener('click', function () {
                    refreshBtn.disabled = true;
                    refreshBtn.innerHTML = '<span class="btn__icon">⏳</span> Refreshing...';

                    fetch('/admin/health/api')
                        .then(response => response.json())
                        .then(data => {
                            updateDashboard(data);
                            refreshBtn.disabled = false;
                            refreshBtn.innerHTML = '<span class="btn__icon">🔄</span> Refresh';
                        })
                        .catch(error => {
                            console.error('Health check failed:', error);
                            refreshBtn.disabled = false;
                            refreshBtn.innerHTML = '<span class="btn__icon">❌</span> Error';
                            setTimeout(() => {
                                refreshBtn.innerHTML = '<span class="btn__icon">🔄</span> Refresh';
                            }, 2000);
                        });
                });
            }

            // Auto-refresh every 30 seconds
            if (!window.healthDashboardInterval) {
                window.healthDashboardInterval = setInterval(function () {
                    if (document.getElementById('services-grid')) {
                        document.getElementById('refresh-health')?.click();
                    }
                }, 30000);
            }
        }
    };

    function updateDashboard(data) {
        // Update overall health
        const healthEl = document.getElementById('overall-health');
        if (healthEl && data.metrics) {
            healthEl.textContent = data.metrics.overall_health + '%';
        }

        // Update last updated
        const updatedEl = document.getElementById('last-updated');
        if (updatedEl) {
            updatedEl.textContent = new Date().toLocaleTimeString();
        }

        // Update service cards
        if (data.services) {
            Object.keys(data.services).forEach(function (key) {
                const service = data.services[key];
                const card = document.querySelector('[data-service="' + key + '"]');

                if (card) {
                    // Update status class
                    card.className = 'service-card service-card--' + service.status;

                    // Update badge
                    const badge = card.querySelector('.service-card__status-badge');
                    if (badge) {
                        badge.className = 'service-card__status-badge service-card__status-badge--' + service.status;
                        badge.textContent = service.status.toUpperCase();
                    }

                    // Update message
                    const message = card.querySelector('.service-card__message');
                    if (message) {
                        message.textContent = service.message;
                    }

                    // Update latency
                    const latency = card.querySelector('.service-card__latency-value');
                    if (latency && service.latency) {
                        latency.textContent = service.latency + 'ms';
                    }
                }
            });
        }
    }

})(Drupal, drupalSettings);
