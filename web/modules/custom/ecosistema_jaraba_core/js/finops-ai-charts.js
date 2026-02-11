/**
 * @file
 * FinOps AI Cost Charts - Chart.js Integration
 *
 * Visualiza tendencias de costes de IA en el Dashboard FinOps
 * utilizando Chart.js para gráficos de líneas y distribución por proveedor.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Configuración de colores por proveedor.
   */
  const PROVIDER_COLORS = {
    anthropic: {
      primary: 'rgb(147, 51, 234)',   // Purple
      background: 'rgba(147, 51, 234, 0.1)'
    },
    openai: {
      primary: 'rgb(16, 185, 129)',   // Green
      background: 'rgba(16, 185, 129, 0.1)'
    },
    google_gemini: {
      primary: 'rgb(59, 130, 246)',   // Blue
      background: 'rgba(59, 130, 246, 0.1)'
    },
    default: {
      primary: 'rgb(148, 163, 184)',  // Slate
      background: 'rgba(148, 163, 184, 0.1)'
    }
  };

  /**
   * Configuración por defecto para Chart.js.
   */
  const CHART_DEFAULTS = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          usePointStyle: true,
          padding: 20,
          color: '#94a3b8'
        }
      },
      tooltip: {
        backgroundColor: 'rgba(15, 23, 42, 0.9)',
        titleColor: '#f1f5f9',
        bodyColor: '#cbd5e1',
        borderColor: 'rgba(148, 163, 184, 0.2)',
        borderWidth: 1,
        cornerRadius: 8,
        padding: 12
      }
    },
    scales: {
      x: {
        grid: {
          color: 'rgba(148, 163, 184, 0.1)'
        },
        ticks: {
          color: '#94a3b8'
        }
      },
      y: {
        grid: {
          color: 'rgba(148, 163, 184, 0.1)'
        },
        ticks: {
          color: '#94a3b8',
          callback: function(value) {
            return '€' + value.toFixed(2);
          }
        }
      }
    }
  };

  /**
   * Comportamiento Drupal para los gráficos FinOps.
   */
  Drupal.behaviors.finopsAiCharts = {
    attach: function (context, settings) {
      // Verificar que Chart.js está disponible
      if (typeof Chart === 'undefined') {
        console.warn('FinOps AI Charts: Chart.js not loaded');
        return;
      }

      // Obtener datos del drupalSettings
      const aiCostData = settings.finops?.ai_cost_history || null;
      if (!aiCostData) {
        return;
      }

      // Gráfico de tendencia diaria
      once('finops-daily-chart', '#ai-cost-daily-chart', context).forEach(function (canvas) {
        renderDailyTrendChart(canvas, aiCostData.daily || []);
      });

      // Gráfico de distribución por proveedor
      once('finops-provider-chart', '#ai-cost-provider-chart', context).forEach(function (canvas) {
        renderProviderChart(canvas, aiCostData.by_provider || {});
      });
    }
  };

  /**
   * Renderiza el gráfico de tendencia diaria de costes.
   *
   * @param {HTMLCanvasElement} canvas
   *   El elemento canvas donde renderizar.
   * @param {Array} dailyData
   *   Array de objetos con {date, cost, tokens}.
   */
  function renderDailyTrendChart(canvas, dailyData) {
    if (!dailyData.length) {
      showEmptyState(canvas, 'No hay datos históricos disponibles');
      return;
    }

    const labels = dailyData.map(d => d.date);
    const costs = dailyData.map(d => parseFloat(d.cost) || 0);
    const tokens = dailyData.map(d => parseInt(d.tokens) || 0);

    new Chart(canvas, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Coste (€)',
            data: costs,
            borderColor: 'rgb(234, 88, 12)',
            backgroundColor: 'rgba(234, 88, 12, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6
          }
        ]
      },
      options: {
        ...CHART_DEFAULTS,
        plugins: {
          ...CHART_DEFAULTS.plugins,
          title: {
            display: true,
            text: 'Coste Diario de IA (últimos 30 días)',
            color: '#f1f5f9',
            font: {
              size: 14,
              weight: 'normal'
            }
          }
        }
      }
    });
  }

  /**
   * Renderiza el gráfico de distribución por proveedor.
   *
   * @param {HTMLCanvasElement} canvas
   *   El elemento canvas donde renderizar.
   * @param {Object} providerData
   *   Objeto con datos por proveedor {provider: {cost, tokens}}.
   */
  function renderProviderChart(canvas, providerData) {
    const providers = Object.keys(providerData);
    if (!providers.length) {
      showEmptyState(canvas, 'No hay datos de proveedores');
      return;
    }

    const labels = providers.map(p => formatProviderName(p));
    const costs = providers.map(p => parseFloat(providerData[p].cost) || 0);
    const colors = providers.map(p => (PROVIDER_COLORS[p] || PROVIDER_COLORS.default).primary);
    const bgColors = providers.map(p => (PROVIDER_COLORS[p] || PROVIDER_COLORS.default).background);

    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: costs,
          backgroundColor: colors,
          borderColor: 'rgba(15, 23, 42, 0.8)',
          borderWidth: 2,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              usePointStyle: true,
              padding: 15,
              color: '#94a3b8',
              generateLabels: function(chart) {
                const data = chart.data;
                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                return data.labels.map((label, i) => {
                  const value = data.datasets[0].data[i];
                  const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                  return {
                    text: `${label}: €${value.toFixed(2)} (${percent}%)`,
                    fillStyle: data.datasets[0].backgroundColor[i],
                    index: i
                  };
                });
              }
            }
          },
          title: {
            display: true,
            text: 'Distribución por Proveedor',
            color: '#f1f5f9',
            font: {
              size: 14,
              weight: 'normal'
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const value = context.parsed;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percent = ((value / total) * 100).toFixed(1);
                return `€${value.toFixed(2)} (${percent}%)`;
              }
            }
          }
        }
      }
    });
  }

  /**
   * Formatea el nombre del proveedor para mostrar.
   */
  function formatProviderName(provider) {
    const names = {
      'anthropic': 'Anthropic (Claude)',
      'openai': 'OpenAI (GPT)',
      'google_gemini': 'Google (Gemini)'
    };
    return names[provider] || provider.replace('_', ' ').charAt(0).toUpperCase() + provider.slice(1);
  }

  /**
   * Muestra estado vacío en lugar del gráfico.
   */
  function showEmptyState(canvas, message) {
    const container = canvas.parentElement;
    container.innerHTML = `
      <div class="finops-chart-empty" style="
        display: flex;
        align-items: center;
        justify-content: center;
        height: 200px;
        color: #94a3b8;
        font-size: 0.875rem;
      ">
        <span>📊 ${message}</span>
      </div>
    `;
  }

})(Drupal, drupalSettings, once);
