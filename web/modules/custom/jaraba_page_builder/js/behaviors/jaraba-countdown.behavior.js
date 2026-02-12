/**
 * @file
 * Behavior: Temporizador de cuenta regresiva en tiempo real.
 *
 * Lee la fecha objetivo de data-end-date y actualiza
 * días, horas, minutos y segundos cada segundo.
 * Incluye cleanup del interval en detach().
 *
 * Selector: .jaraba-countdown[data-end-date]
 *
 * @see grapesjs-jaraba-blocks.js → countdownScript
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.jarabaCountdown = {
        attach: function (context) {
            var countdowns = once('jaraba-countdown', '.jaraba-countdown[data-end-date]', context);

            countdowns.forEach(function (container) {
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
                        if (daysEl) daysEl.textContent = '00';
                        if (hoursEl) hoursEl.textContent = '00';
                        if (minutesEl) minutesEl.textContent = '00';
                        if (secondsEl) secondsEl.textContent = '00';

                        // Limpiar interval cuando llega a 0
                        if (container._jarabaCountdownInterval) {
                            clearInterval(container._jarabaCountdownInterval);
                            delete container._jarabaCountdownInterval;
                        }
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

                // Actualizar inmediatamente y luego cada segundo
                updateCountdown();
                container._jarabaCountdownInterval = setInterval(updateCountdown, 1000);
            });
        },

        detach: function (context, settings, trigger) {
            if (trigger !== 'unload') return;
            var countdowns = context.querySelectorAll
                ? context.querySelectorAll('.jaraba-countdown')
                : [];
            countdowns.forEach(function (container) {
                if (container._jarabaCountdownInterval) {
                    clearInterval(container._jarabaCountdownInterval);
                    delete container._jarabaCountdownInterval;
                }
            });
        }
    };

})(Drupal, once);
