/**
 * @file
 * JavaScript behaviors para trazabilidad AgroConecta.
 *
 * Fase 7a/7b — Traceability + QR Landing.
 * Timeline interactiva con expand/collapse y verificación de integridad.
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Behavior: Timeline interactiva de trazabilidad.
     *
     * Expand/collapse eventos y scroll-triggered animations.
     */
    Drupal.behaviors.agroTraceTimeline = {
        attach: function (context) {
            once('agro-trace-timeline', '.agro-trace-timeline', context).forEach(function (timeline) {
                var events = timeline.querySelectorAll('.agro-trace-timeline__event');

                // Scroll-triggered animations
                if ('IntersectionObserver' in window) {
                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('agro-trace-timeline__event--visible');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.2, rootMargin: '0px 0px -50px 0px' });

                    events.forEach(function (event) {
                        observer.observe(event);
                    });
                } else {
                    // Fallback: mostrar todos de inmediato
                    events.forEach(function (event) {
                        event.classList.add('agro-trace-timeline__event--visible');
                    });
                }

                // Expand/collapse eventos
                events.forEach(function (event) {
                    var card = event.querySelector('.agro-trace-timeline__event-card');
                    if (!card) return;

                    card.addEventListener('click', function () {
                        var wasExpanded = card.classList.contains('agro-trace-timeline__event-card--expanded');

                        // Cerrar todos
                        timeline.querySelectorAll('.agro-trace-timeline__event-card--expanded')
                            .forEach(function (c) {
                                c.classList.remove('agro-trace-timeline__event-card--expanded');
                            });

                        // Toggle el actual
                        if (!wasExpanded) {
                            card.classList.add('agro-trace-timeline__event-card--expanded');
                        }
                    });
                });

                // Calcular progreso de la línea
                updateTimelineProgress(timeline, events);
            });
        }
    };

    /**
     * Actualiza el progreso visual de la línea de timeline.
     */
    function updateTimelineProgress(timeline, events) {
        var completedCount = 0;
        events.forEach(function (event) {
            var icon = event.querySelector('.agro-trace-timeline__event-icon');
            if (icon && !icon.classList.contains('agro-trace-timeline__event-icon--pending')) {
                completedCount++;
            }
        });

        var progress = events.length > 0
            ? Math.round((completedCount / events.length) * 100)
            : 0;

        var line = timeline.querySelector('.agro-trace-timeline__line');
        if (line) {
            line.style.setProperty('--trace-progress', progress + '%');
        }
    }

    /**
     * Behavior: Verificación de integridad de lote.
     */
    Drupal.behaviors.agroTraceVerify = {
        attach: function (context) {
            once('agro-trace-verify', '[data-trace-verify]', context).forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var batchId = btn.dataset.traceVerify;
                    if (!batchId) return;

                    var badge = document.getElementById('agro-integrity-badge');
                    if (!badge) return;

                    // Estado: verificando
                    badge.className = 'agro-integrity-badge agro-integrity-badge--pending';
                    var textEl = badge.querySelector('.agro-integrity-badge__text');
                    if (textEl) {
                        textEl.innerHTML = '<strong>' + Drupal.t('Verificando...') + '</strong>' +
                            '<small>' + Drupal.t('Comprobando cadena de hashes') + '</small>';
                    }

                    fetch('/api/v1/agro/traceability/' + batchId + '/verify', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.verified) {
                                badge.className = 'agro-integrity-badge agro-integrity-badge--verified';
                                textEl.innerHTML = '<strong>' + Drupal.t('Integridad verificada') + '</strong>' +
                                    '<small>' + Drupal.t('Todos los datos son auténticos') + '</small>';
                            } else {
                                badge.className = 'agro-integrity-badge agro-integrity-badge--failed';
                                textEl.innerHTML = '<strong>' + Drupal.t('Verificación fallida') + '</strong>' +
                                    '<small>' + (data.error || Drupal.t('Los datos podrían haber sido alterados')) + '</small>';
                            }

                            // Mostrar hash
                            var hashEl = badge.querySelector('.agro-integrity-badge__hash');
                            if (hashEl && data.data_hash) {
                                hashEl.textContent = data.data_hash;
                                hashEl.style.display = 'block';
                            }
                        })
                        .catch(function () {
                            badge.className = 'agro-integrity-badge agro-integrity-badge--failed';
                            textEl.innerHTML = '<strong>' + Drupal.t('Error de conexión') + '</strong>' +
                                '<small>' + Drupal.t('No se pudo verificar') + '</small>';
                        });
                });
            });
        }
    };

    /**
     * Behavior: Copiar hash al portapapeles.
     */
    Drupal.behaviors.agroTraceCopyHash = {
        attach: function (context) {
            once('agro-trace-copy-hash', '.agro-integrity-badge__hash', context).forEach(function (hashEl) {
                hashEl.addEventListener('click', function () {
                    var hash = hashEl.textContent;
                    if (navigator.clipboard && hash) {
                        navigator.clipboard.writeText(hash).then(function () {
                            var original = hashEl.textContent;
                            hashEl.textContent = Drupal.t('Copiado');
                            setTimeout(function () {
                                hashEl.textContent = original;
                            }, 1500);
                        });
                    }
                });
            });
        }
    };

})(Drupal, once);
