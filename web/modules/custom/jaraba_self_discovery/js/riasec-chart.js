/**
 * @file
 * Gráfico hexagonal RIASEC premium con Chart.js.
 */

(function (Drupal) {
    'use strict';

    // Colores RIASEC.
    const RIASEC_COLORS = {
        R: { bg: 'rgba(16, 185, 129, 0.2)', border: '#10B981' },
        I: { bg: 'rgba(59, 130, 246, 0.2)', border: '#3B82F6' },
        A: { bg: 'rgba(139, 92, 246, 0.2)', border: '#8B5CF6' },
        S: { bg: 'rgba(245, 158, 11, 0.2)', border: '#F59E0B' },
        E: { bg: 'rgba(239, 68, 68, 0.2)', border: '#EF4444' },
        C: { bg: 'rgba(107, 114, 128, 0.2)', border: '#6B7280' }
    };

    const RIASEC_LABELS = {
        R: 'Realista',
        I: 'Investigador',
        A: 'Artístico',
        S: 'Social',
        E: 'Emprendedor',
        C: 'Convencional'
    };

    /**
     * Inicializa el gráfico hexagonal RIASEC.
     */
    function initRiasecChart(scores) {
        const canvas = document.getElementById('riasec-hexagon-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const labels = Object.keys(RIASEC_LABELS);
        const data = labels.map(key => scores[key] || 0);

        // Gradiente de fondo.
        const gradient = ctx.createRadialGradient(200, 200, 0, 200, 200, 200);
        gradient.addColorStop(0, 'rgba(0, 169, 165, 0.3)');
        gradient.addColorStop(1, 'rgba(35, 61, 99, 0.1)');

        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels.map(l => RIASEC_LABELS[l]),
                datasets: [{
                    label: 'Tu perfil',
                    data: data,
                    backgroundColor: gradient,
                    borderColor: '#00A9A5',
                    borderWidth: 3,
                    pointBackgroundColor: labels.map(l => RIASEC_COLORS[l].border),
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            backdropColor: 'transparent',
                            color: '#64748B',
                            font: { size: 10 }
                        },
                        grid: {
                            color: 'rgba(100, 116, 139, 0.2)',
                            circular: true
                        },
                        angleLines: {
                            color: 'rgba(100, 116, 139, 0.2)'
                        },
                        pointLabels: {
                            color: '#1A1A2E',
                            font: {
                                size: 14,
                                weight: 'bold',
                                family: "'Outfit', sans-serif"
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(35, 61, 99, 0.95)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                return `Puntuación: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });

        // Renderizar barras de puntuación.
        renderScoreBars(scores);
    }

    /**
     * Renderiza las barras de puntuación por categoría.
     */
    function renderScoreBars(scores) {
        const container = document.getElementById('riasec-scores');
        if (!container) return;

        const labels = JSON.parse(container.dataset.labels || '{}');
        const colors = JSON.parse(container.dataset.colors || '{}');

        // Ordenar por puntuación.
        const sorted = Object.entries(scores)
            .sort((a, b) => b[1] - a[1]);

        container.innerHTML = sorted.map(([code, score], index) => `
      <div class="riasec-score-item" style="--delay: ${index * 0.1}s; --score-color: ${colors[code]}">
        <div class="riasec-score-item__header">
          <span class="riasec-score-item__label">${labels[code]}</span>
          <span class="riasec-score-item__value">${score}%</span>
        </div>
        <div class="riasec-score-item__bar">
          <div class="riasec-score-item__fill" style="width: ${score}%"></div>
        </div>
      </div>
    `).join('');
    }

    // Exponer globalmente para el Ajax callback.
    window.initRiasecChart = initRiasecChart;

    // Behavior Drupal para inicialización normal.
    Drupal.behaviors.riasecChart = {
        attach: function (context) {
            const scoresEl = context.querySelector?.('#riasec-scores');
            if (scoresEl && scoresEl.dataset.scores) {
                const scores = JSON.parse(scoresEl.dataset.scores);
                initRiasecChart(scores);
            }
        }
    };

    // Comando Ajax personalizado.
    if (Drupal.AjaxCommands) {
        Drupal.AjaxCommands.prototype.initRiasecChart = function (ajax, response) {
            if (response.data) {
                setTimeout(() => initRiasecChart(response.data), 100);
            }
        };
    }

})(Drupal);
