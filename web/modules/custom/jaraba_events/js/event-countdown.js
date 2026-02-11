/**
 * @file
 * Countdown dinámico para la landing page de eventos.
 *
 * ESTRUCTURA:
 * Behavior de Drupal que actualiza el countdown cada segundo usando
 * el timestamp objetivo almacenado en data-countdown-target.
 *
 * LÓGICA:
 * - Lee el timestamp objetivo del atributo data-countdown-target.
 * - Calcula la diferencia con la hora actual del cliente.
 * - Actualiza los elementos [data-countdown-days/hours/minutes/seconds].
 * - Cuando el countdown llega a 0, muestra "El evento ha comenzado".
 */
(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.jarabaEventsCountdown = {
    attach: function (context) {
      once('jaraba-countdown', '[data-countdown-target]', context).forEach(function (el) {
        var targetTimestamp = parseInt(el.getAttribute('data-countdown-target'), 10) * 1000;

        if (isNaN(targetTimestamp)) {
          return;
        }

        var daysEl = el.querySelector('[data-countdown-days]');
        var hoursEl = el.querySelector('[data-countdown-hours]');
        var minutesEl = el.querySelector('[data-countdown-minutes]');
        var secondsEl = el.querySelector('[data-countdown-seconds]');

        function updateCountdown() {
          var now = Date.now();
          var diff = Math.max(0, targetTimestamp - now);

          if (diff === 0) {
            if (daysEl) { daysEl.textContent = '0'; }
            if (hoursEl) { hoursEl.textContent = '0'; }
            if (minutesEl) { minutesEl.textContent = '0'; }
            if (secondsEl) { secondsEl.textContent = '0'; }
            clearInterval(timer);
            return;
          }

          var totalSeconds = Math.floor(diff / 1000);
          var days = Math.floor(totalSeconds / 86400);
          var hours = Math.floor((totalSeconds % 86400) / 3600);
          var minutes = Math.floor((totalSeconds % 3600) / 60);
          var seconds = totalSeconds % 60;

          if (daysEl) { daysEl.textContent = days; }
          if (hoursEl) { hoursEl.textContent = hours; }
          if (minutesEl) { minutesEl.textContent = minutes; }
          if (secondsEl) { secondsEl.textContent = seconds; }
        }

        updateCountdown();
        var timer = setInterval(updateCountdown, 1000);
      });
    }
  };

})(Drupal, once);
