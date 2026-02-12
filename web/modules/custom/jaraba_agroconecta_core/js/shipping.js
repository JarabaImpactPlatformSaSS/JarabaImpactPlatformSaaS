/**
 * @file
 * JavaScript behaviors para envío y tracking AgroConecta.
 *
 * Fase 5 — Shipping Core.
 * Drupal.behaviors para AJAX rate calculator y tracking auto-refresh.
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Behavior: Calculadora de tarifas de envío en checkout.
     *
     * Carga tarifas dinámicas cuando cambia la dirección de envío.
     */
    Drupal.behaviors.agroShippingRates = {
        attach: function (context) {
            once('agro-shipping-rates', '.agro-shipping-selector', context).forEach(function (container) {
                var postalInput = document.getElementById('agro-shipping-postal');
                var optionsContainer = container.querySelector('.agro-shipping-selector__options');
                var loadingIndicator = container.querySelector('.agro-shipping-selector__loading');
                var debounceTimer = null;

                if (!postalInput || !optionsContainer) return;

                // Cargar tarifas cuando cambia el código postal
                postalInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        var postalCode = postalInput.value.trim();
                        if (postalCode.length >= 5) {
                            loadRates(postalCode, optionsContainer, loadingIndicator);
                        }
                    }, 500);
                });

                // Seleccionar opción de envío
                optionsContainer.addEventListener('click', function (e) {
                    var option = e.target.closest('.agro-shipping-selector__option');
                    if (!option) return;

                    // Deseleccionar todas
                    optionsContainer.querySelectorAll('.agro-shipping-selector__option').forEach(function (opt) {
                        opt.classList.remove('agro-shipping-selector__option--selected');
                        var radio = opt.querySelector('input[type="radio"]');
                        if (radio) radio.checked = false;
                    });

                    // Seleccionar actual
                    option.classList.add('agro-shipping-selector__option--selected');
                    var radio = option.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;

                    // Actualizar total
                    updateShippingTotal(option.dataset.cost || '0');
                });
            });
        }
    };

    /**
     * Carga tarifas de envío via API.
     */
    function loadRates(postalCode, container, loading) {
        if (loading) {
            loading.style.display = 'flex';
        }
        container.innerHTML = '';

        fetch('/api/v1/agro/shipping/rates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ postal_code: postalCode })
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (loading) loading.style.display = 'none';

                if (data.rates && data.rates.length > 0) {
                    renderRates(data.rates, container);
                } else {
                    container.innerHTML = '<p class="agro-shipping-selector__no-rates">' +
                        Drupal.t('No hay opciones de envío disponibles para tu zona.') + '</p>';
                }
            })
            .catch(function () {
                if (loading) loading.style.display = 'none';
                container.innerHTML = '<p class="agro-shipping-selector__error">' +
                    Drupal.t('Error al cargar tarifas. Inténtalo de nuevo.') + '</p>';
            });
    }

    /**
     * Renderiza opciones de tarifa.
     */
    function renderRates(rates, container) {
        var html = '';
        rates.forEach(function (rate, index) {
            var priceText = rate.cost <= 0
                ? '<span class="agro-shipping-selector__option-price--free">' + Drupal.t('GRATIS') + '</span>'
                : rate.cost.toFixed(2) + ' €';

            var refrigeratedClass = rate.is_refrigerated ? ' agro-shipping-selector__option--refrigerated' : '';
            var selectedClass = index === 0 ? ' agro-shipping-selector__option--selected' : '';
            var checked = index === 0 ? ' checked' : '';

            html += '<label class="agro-shipping-selector__option' + refrigeratedClass + selectedClass + '" data-cost="' + rate.cost + '">' +
                '<input type="radio" name="shipping_rate" value="' + rate.id + '"' + checked + '>' +
                '<div class="agro-shipping-selector__option-info">' +
                '<span class="agro-shipping-selector__carrier-name">' + rate.carrier_name + ' — ' + rate.service_name + '</span>' +
                '<span class="agro-shipping-selector__delivery-time">' +
                rate.estimated_days_min + '-' + rate.estimated_days_max + ' ' + Drupal.t('días laborables') +
                '</span>' +
                '</div>' +
                '<span class="agro-shipping-selector__option-price">' + priceText + '</span>' +
                '</label>';
        });

        container.innerHTML = html;

        // Auto-seleccionar primera opción
        if (rates.length > 0) {
            updateShippingTotal(rates[0].cost.toString());
        }
    }

    /**
     * Actualiza el total de envío en el resumen.
     */
    function updateShippingTotal(cost) {
        var shippingRow = document.querySelector('[data-checkout-shipping-cost]');
        if (shippingRow) {
            var costNum = parseFloat(cost);
            shippingRow.textContent = costNum <= 0 ? Drupal.t('GRATIS') : costNum.toFixed(2) + ' €';
        }

        // Dispatch evento para que checkout.js recalcule
        document.dispatchEvent(new CustomEvent('agro:shipping:changed', {
            detail: { cost: parseFloat(cost) }
        }));
    }

    /**
     * Behavior: Auto-refresh del tracking timeline.
     */
    Drupal.behaviors.agroShippingTracking = {
        attach: function (context) {
            once('agro-shipping-tracking', '.agro-tracking', context).forEach(function (trackingEl) {
                var shipmentId = trackingEl.dataset.shipmentId;
                if (!shipmentId) return;

                // Refresh cada 30 segundos
                var refreshInterval = setInterval(function () {
                    refreshTracking(shipmentId, trackingEl);
                }, 30000);

                // Limpiar cuando el elemento se destruya
                trackingEl._agroTrackingInterval = refreshInterval;
            });
        },
        detach: function (context) {
            var trackingEls = context.querySelectorAll ? context.querySelectorAll('.agro-tracking') : [];
            trackingEls.forEach(function (el) {
                if (el._agroTrackingInterval) {
                    clearInterval(el._agroTrackingInterval);
                }
            });
        }
    };

    /**
     * Refresca el timeline de tracking.
     */
    function refreshTracking(shipmentId, container) {
        fetch('/api/v1/agro/shipping/shipments/' + shipmentId + '/tracking', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.events || data.events.length === 0) return;

                // Actualizar status badge
                var badge = container.querySelector('.agro-tracking__status-badge');
                if (badge && data.state) {
                    badge.className = 'agro-tracking__status-badge agro-tracking__status-badge--' + data.state;
                    badge.textContent = data.state_label || data.state;
                }

                // Reconstruir timeline
                var timeline = container.querySelector('.agro-tracking__timeline');
                if (timeline) {
                    var html = '';
                    data.events.forEach(function (event, idx) {
                        var stateClass = idx === 0 ? 'agro-tracking__event--current' : 'agro-tracking__event--completed';
                        html += '<div class="agro-tracking__event ' + stateClass + '">' +
                            '<div class="agro-tracking__event-time">' + event.timestamp + '</div>' +
                            '<div class="agro-tracking__event-title">' + event.description + '</div>' +
                            (event.location ? '<div class="agro-tracking__event-location"><span class="agro-tracking__location-icon" aria-hidden="true"></span> ' + event.location + '</div>' : '') +
                            '</div>';
                    });
                    timeline.innerHTML = html;
                }

                // Actualizar ETA
                var eta = container.querySelector('.agro-tracking__eta');
                if (eta && data.estimated_delivery) {
                    eta.querySelector('strong').textContent = data.estimated_delivery;
                }
            })
            .catch(function () {
                // Silenciar errores de refresh automático
            });
    }

})(Drupal, once);
