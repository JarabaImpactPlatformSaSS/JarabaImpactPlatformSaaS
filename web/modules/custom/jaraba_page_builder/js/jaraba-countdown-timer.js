/**
 * @file
 * Jaraba Countdown Timer - Temporizador de cuenta regresiva en tiempo real.
 *
 * Actualiza cada segundo mostrando días, horas, minutos y segundos
 * hasta la fecha objetivo definida en data-end-date.
 *
 * @see docs/arquitectura/2026-02-08_plan_elevacion_page_builder_clase_mundial.md
 */

(function (Drupal) {
    'use strict';

    /**
     * Inicializa los temporizadores de cuenta regresiva.
     *
     * @param {Element} context - Contexto DOM.
     */
    function initCountdowns(context) {
        var containers = context.querySelectorAll('.jaraba-countdown');

        containers.forEach(function (container) {
            if (container.dataset.jarabaCountdownInit) {
                return;
            }
            container.dataset.jarabaCountdownInit = 'true';

            var endDateStr = container.getAttribute('data-end-date');
            if (!endDateStr) return;

            var daysEl = container.querySelector('[data-unit="days"]');
            var hoursEl = container.querySelector('[data-unit="hours"]');
            var minutesEl = container.querySelector('[data-unit="minutes"]');
            var secondsEl = container.querySelector('[data-unit="seconds"]');

            function updateCountdown() {
                var endDate = new Date(endDateStr).getTime();
                var now = new Date().getTime();
                var diff = endDate - now;

                if (diff <= 0) {
                    if (daysEl) daysEl.textContent = '0';
                    if (hoursEl) hoursEl.textContent = '0';
                    if (minutesEl) minutesEl.textContent = '0';
                    if (secondsEl) secondsEl.textContent = '0';
                    return;
                }

                var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((diff % (1000 * 60)) / 1000);

                if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
                if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
                if (minutesEl) minutesEl.textContent = String(minutes).padStart(2, '0');
                if (secondsEl) secondsEl.textContent = String(seconds).padStart(2, '0');
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        });
    }

    // Drupal behavior
    Drupal.behaviors.jarabaCountdownTimer = {
        attach: function (context) {
            initCountdowns(context);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initCountdowns(document);
        });
    } else {
        initCountdowns(document);
    }

    window.jarabaInitCountdowns = initCountdowns;

})(Drupal);
