/**
 * @file
 * JavaScript behaviors para el QR Dynamic Dashboard.
 *
 * Sprint AC6-1 — QR Analytics Dashboard + Lead Capture.
 * Drupal.behaviors para dashboard AJAX, tabla de QRs,
 * lead capture form y charts interactivos.
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Behavior: QR Dashboard principal.
     *
     * Carga KPIs, tabla de QR codes y charts vía API.
     */
    Drupal.behaviors.agroQrDashboard = {
        attach: function (context) {
            once('agro-qr-dashboard', '.agro-qr-dashboard', context).forEach(function (dashboard) {
                var period = '30d';
                var currentPage = 0;
                var typeFilter = '';

                // Period selector.
                var presets = dashboard.querySelectorAll('.agro-date-picker__preset');
                presets.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        presets.forEach(function (b) { b.classList.remove('agro-date-picker__preset--active'); });
                        btn.classList.add('agro-date-picker__preset--active');
                        period = btn.dataset.period;
                        loadDashboard(dashboard, period);
                    });
                });

                // Type filter.
                var filterSelect = dashboard.querySelector('.agro-qr-table__filter-select');
                if (filterSelect) {
                    filterSelect.addEventListener('change', function () {
                        typeFilter = filterSelect.value;
                        currentPage = 0;
                        loadQrTable(dashboard, typeFilter, currentPage);
                    });
                }

                // Export CSV button.
                var exportBtn = dashboard.querySelector('[data-qr-export-csv]');
                if (exportBtn) {
                    exportBtn.addEventListener('click', function () {
                        exportLeadsCsv();
                    });
                }

                // Initial loads.
                loadDashboard(dashboard, period);
                loadQrTable(dashboard, typeFilter, currentPage);
            });
        }
    };

    /**
     * Carga datos del dashboard QR vía API.
     */
    function loadDashboard(dashboard, period) {
        fetch('/api/v1/agro/qr/dashboard?period=' + period, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.kpis) {
                    updateKpi(dashboard, 'total_qr_codes', data.kpis.total_qr_codes);
                    updateKpi(dashboard, 'total_scans', data.kpis.total_scans);
                    updateKpi(dashboard, 'unique_scans', data.kpis.unique_scans);
                    updateKpi(dashboard, 'total_conversions', data.kpis.total_conversions);
                    updateKpi(dashboard, 'conversion_rate', data.kpis.conversion_rate + '%');
                    updateKpi(dashboard, 'total_leads', data.kpis.total_leads);
                }
            })
            .catch(function (err) {
                console.error('AgroConecta QR Dashboard:', err);
            });
    }

    /**
     * Actualiza un KPI card.
     */
    function updateKpi(dashboard, key, value) {
        var card = dashboard.querySelector('[data-qr-kpi="' + key + '"]');
        if (!card) return;

        var valueEl = card.querySelector('.agro-qr-kpi__value');
        if (valueEl) {
            valueEl.textContent = typeof value === 'number' ? value.toLocaleString('es-ES') : value;
        }
    }

    /**
     * Carga la tabla de QR codes.
     */
    function loadQrTable(dashboard, typeFilter, page) {
        var tableBody = dashboard.querySelector('.agro-qr-table tbody');
        var paginationInfo = dashboard.querySelector('.agro-qr-table__pagination');
        if (!tableBody) return;

        var url = '/api/v1/agro/qr/list?page=' + page + '&limit=20';
        if (typeFilter) {
            url += '&type=' + typeFilter;
        }

        tableBody.innerHTML = '<tr><td colspan="7" class="agro-qr-table__empty"><span class="agro-spinner"></span></td></tr>';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var qrs = data.qr_codes || [];

                if (qrs.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="7" class="agro-qr-table__empty">' +
                        Drupal.t('No hay QR codes creados aún.') + '</td></tr>';
                    return;
                }

                var html = '';
                qrs.forEach(function (qr) {
                    html += '<tr data-qr-id="' + qr.qr_id + '">';
                    html += '<td><strong>' + Drupal.checkPlain(qr.label || '') + '</strong></td>';
                    html += '<td><span class="agro-qr-table__type-badge agro-qr-table__type-badge--' +
                        (qr.type || 'product') + '">' + Drupal.checkPlain(qr.type || '') + '</span></td>';
                    html += '<td>' + (qr.total_scans || 0).toLocaleString('es-ES') + '</td>';
                    html += '<td>' + (qr.unique_scans || 0).toLocaleString('es-ES') + '</td>';
                    html += '<td>' + (qr.conversions || 0) + '</td>';
                    html += '<td>' + (qr.conversion_rate || 0) + '%</td>';
                    html += '<td class="agro-qr-table__actions">' +
                        '<button class="agro-qr-table__action-btn" data-qr-analytics="' + qr.qr_id +
                        '" title="' + Drupal.t('Analytics') + '" aria-label="' + Drupal.t('Ver analytics') + '">' +
                        '<span class="agro-qr-table__action-icon agro-qr-table__action-icon--analytics" aria-hidden="true"></span></button>' +
                        '<button class="agro-qr-table__action-btn" data-qr-download="' + qr.qr_id +
                        '" title="' + Drupal.t('Descargar') + '" aria-label="' + Drupal.t('Descargar QR') + '">' +
                        '<span class="agro-qr-table__action-icon agro-qr-table__action-icon--download" aria-hidden="true"></span></button>' +
                        '</td>';
                    html += '</tr>';
                });

                tableBody.innerHTML = html;

                if (paginationInfo) {
                    paginationInfo.textContent = Drupal.t('Mostrando @count QR codes', { '@count': qrs.length });
                }

                // Bind action buttons.
                bindTableActions(dashboard);
            })
            .catch(function () {
                tableBody.innerHTML = '<tr><td colspan="7" class="agro-qr-table__empty">' +
                    Drupal.t('Error al cargar QR codes.') + '</td></tr>';
            });
    }

    /**
     * Vincula acciones de la tabla de QRs.
     */
    function bindTableActions(dashboard) {
        // Analytics buttons.
        dashboard.querySelectorAll('[data-qr-analytics]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var qrId = btn.dataset.qrAnalytics;
                loadQrAnalytics(dashboard, qrId);
            });
        });

        // Download buttons.
        dashboard.querySelectorAll('[data-qr-download]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var qrId = btn.dataset.qrDownload;
                window.open('/api/v1/agro/qr/' + qrId + '/download/png', '_blank');
            });
        });
    }

    /**
     * Carga analytics detallado de un QR.
     */
    function loadQrAnalytics(dashboard, qrId) {
        fetch('/api/v1/agro/qr/' + qrId + '/analytics', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var detailEl = dashboard.querySelector('.agro-qr-detail');
                if (!detailEl) return;

                detailEl.style.display = 'block';
                var html = '<h3>' + Drupal.checkPlain(data.label || '') + '</h3>';
                html += '<div class="agro-qr-kpi-grid">';
                html += buildKpiMini(Drupal.t('Escaneos'), data.total_scans || 0);
                html += buildKpiMini(Drupal.t('Únicos'), data.unique_scans || 0);
                html += buildKpiMini(Drupal.t('Conversiones'), data.conversions || 0);
                html += buildKpiMini(Drupal.t('Tasa'), (data.conversion_rate || 0) + '%');
                html += '</div>';

                detailEl.innerHTML = html;

                // Scroll to detail.
                detailEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
    }

    /**
     * Construye un mini KPI card en HTML.
     */
    function buildKpiMini(label, value) {
        return '<div class="agro-qr-kpi">' +
            '<p class="agro-qr-kpi__label">' + label + '</p>' +
            '<p class="agro-qr-kpi__value">' +
            (typeof value === 'number' ? value.toLocaleString('es-ES') : value) +
            '</p></div>';
    }

    /**
     * Exporta leads a CSV (descarga automática).
     */
    function exportLeadsCsv() {
        window.location.href = '/api/v1/agro/leads/export';
    }

    // =========================================================
    // Lead Capture Form
    // =========================================================

    /**
     * Behavior: Formulario de captura de leads en landing QR.
     */
    Drupal.behaviors.agroLeadCapture = {
        attach: function (context) {
            once('agro-lead-capture', '.agro-lead-capture__form', context).forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    var submitBtn = form.querySelector('.agro-lead-capture__submit');
                    var feedback = form.closest('.agro-lead-capture').querySelector('.agro-lead-capture__feedback');
                    var discountEl = form.closest('.agro-lead-capture').querySelector('.agro-lead-capture__discount');

                    var qrCodeId = form.dataset.qrCodeId;
                    var scanId = form.dataset.scanId;

                    var emailInput = form.querySelector('[name="email"]');
                    var nameInput = form.querySelector('[name="name"]');
                    var phoneInput = form.querySelector('[name="phone"]');
                    var consentInput = form.querySelector('[name="consent_marketing"]');

                    // Validación básica.
                    if (!emailInput || !emailInput.value.trim()) {
                        showFeedback(feedback, 'error', Drupal.t('Por favor, introduce tu email.'));
                        return;
                    }

                    if (!consentInput || !consentInput.checked) {
                        showFeedback(feedback, 'error', Drupal.t('Debes aceptar recibir comunicaciones.'));
                        return;
                    }

                    // Disable button.
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = Drupal.t('Enviando...');
                    }

                    var payload = {
                        qr_code_id: parseInt(qrCodeId, 10),
                        email: emailInput.value.trim(),
                        name: nameInput ? nameInput.value.trim() : '',
                        phone: phoneInput ? phoneInput.value.trim() : '',
                        consent_marketing: true,
                        capture_type: form.dataset.captureType || 'newsletter',
                    };

                    if (scanId) {
                        payload.scan_event_id = parseInt(scanId, 10);
                    }

                    fetch('/api/v1/agro/leads', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.lead) {
                                showFeedback(feedback, 'success', Drupal.t('Gracias por suscribirte.'));

                                // Mostrar código de descuento si lo hay.
                                if (data.lead.discount_code && discountEl) {
                                    var codeEl = discountEl.querySelector('.agro-lead-capture__discount-code');
                                    if (codeEl) {
                                        codeEl.textContent = data.lead.discount_code;
                                    }
                                    discountEl.style.display = 'block';
                                }

                                // Ocultar formulario.
                                form.style.display = 'none';
                            } else {
                                showFeedback(feedback, 'error',
                                    data.error || Drupal.t('No se pudo completar el registro.'));
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.textContent = Drupal.t('Suscribirme');
                                }
                            }
                        })
                        .catch(function () {
                            showFeedback(feedback, 'error', Drupal.t('Error de conexión. Inténtalo de nuevo.'));
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = Drupal.t('Suscribirme');
                            }
                        });
                });
            });
        }
    };

    /**
     * Muestra feedback en el formulario.
     */
    function showFeedback(el, type, message) {
        if (!el) return;
        el.className = 'agro-lead-capture__feedback agro-lead-capture__feedback--' + type;
        el.textContent = message;
    }

    // =========================================================
    // Animations
    // =========================================================

    /**
     * Behavior: Animaciones de entrada para KPI cards y tabla.
     */
    Drupal.behaviors.agroQrAnimations = {
        attach: function (context) {
            // KPI cards staggered animation.
            var cards = once('qr-kpi-animate', '.agro-qr-kpi', context);
            cards.forEach(function (card, i) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(12px)';
                setTimeout(function () {
                    card.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, i * 80);
            });

            // Table rows.
            var rows = once('qr-row-animate', '.agro-qr-table tbody tr', context);
            rows.forEach(function (row, i) {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-8px)';
                setTimeout(function () {
                    row.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, i * 30 + 200);
            });
        }
    };

})(Drupal, drupalSettings, once);
