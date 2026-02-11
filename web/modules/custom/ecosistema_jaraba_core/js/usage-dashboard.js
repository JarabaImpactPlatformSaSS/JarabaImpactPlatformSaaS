/**
 * @file
 * usage-dashboard.js — Gráficos y animaciones del dashboard de uso.
 *
 * PROPÓSITO:
 * Renderiza el gráfico de tendencias históricas con Chart.js
 * y anima los contadores de los summary cards.
 *
 * DIRECTRICES:
 * - Usa Drupal.behaviors para compatibilidad con AJAX/BigPipe
 * - Datos de Chart.js vienen de drupalSettings.usageDashboard
 * - Los colores usan variables CSS var(--ej-*) cuando es posible
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior: Gráfico de tendencias de uso.
   */
  Drupal.behaviors.usageHistoryChart = {
    attach: function (context) {
      const canvas = once('usage-chart', '#usage-history-chart', context);
      if (!canvas.length || typeof Chart === 'undefined') {
        return;
      }

      const settings = drupalSettings.usageDashboard || {};
      const labels = settings.chartLabels || [];
      const datasets = settings.chartDatasets || [];

      if (!labels.length) {
        return;
      }

      new Chart(canvas[0], {
        type: 'line',
        data: {
          labels: labels,
          datasets: datasets,
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            mode: 'index',
            intersect: false,
          },
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                usePointStyle: true,
                padding: 16,
                font: { size: 12 },
              },
            },
            tooltip: {
              backgroundColor: 'rgba(35, 61, 99, 0.95)',
              titleFont: { size: 13 },
              bodyFont: { size: 12 },
              padding: 12,
              cornerRadius: 8,
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { font: { size: 11 } },
            },
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(35, 61, 99, 0.08)',
              },
              ticks: {
                font: { size: 11 },
                callback: function (value) {
                  if (value >= 1000000) {
                    return (value / 1000000).toFixed(1) + 'M';
                  }
                  if (value >= 1000) {
                    return (value / 1000).toFixed(1) + 'K';
                  }
                  return value;
                },
              },
            },
          },
        },
      });
    },
  };

  /**
   * Behavior: Animación de contadores numéricos.
   */
  Drupal.behaviors.usageCounters = {
    attach: function (context) {
      const counters = once('usage-counters', '[data-counter]', context);
      if (!counters.length) {
        return;
      }

      counters.forEach(function (el) {
        const target = parseFloat(el.getAttribute('data-counter')) || 0;
        const duration = 1200;
        const start = performance.now();

        function animate(currentTime) {
          const elapsed = currentTime - start;
          const progress = Math.min(elapsed / duration, 1);
          // Ease out cubic.
          const eased = 1 - Math.pow(1 - progress, 3);
          const current = target * eased;

          el.textContent = '€' + current.toLocaleString('es-ES', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          });

          if (progress < 1) {
            requestAnimationFrame(animate);
          }
        }

        // Usar IntersectionObserver para animar solo cuando es visible.
        if ('IntersectionObserver' in window) {
          const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                requestAnimationFrame(animate);
                observer.unobserve(entry.target);
              }
            });
          }, { threshold: 0.5 });
          observer.observe(el);
        }
        else {
          requestAnimationFrame(animate);
        }
      });
    },
  };

  /**
   * Behavior: Gráfico de barra de presupuesto (gauge mini).
   */
  Drupal.behaviors.usageBudgetGauge = {
    attach: function (context) {
      const bars = once('usage-budget', '.usage-progress-bar__fill', context);
      bars.forEach(function (bar) {
        // Animar el width con transición CSS.
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(function () {
          bar.style.width = width;
        }, 300);
      });
    },
  };

})(Drupal, drupalSettings, once);
