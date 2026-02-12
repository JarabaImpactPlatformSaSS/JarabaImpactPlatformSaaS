/**
 * @file
 * Widget Renderer - Renders Chart.js charts and data displays.
 *
 * Renders line, bar, pie charts via Chart.js, number cards, and tables
 * based on widget type and configuration from the API.
 *
 * @see templates/analytics-dashboard-view.html.twig
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Widget Renderer behavior.
   *
   * Initializes widget rendering for dashboard view. Fetches data from
   * the API and renders appropriate visualizations.
   */
  Drupal.behaviors.jarabaWidgetRenderer = {
    attach: function (context) {
      var viewElements = once('jaraba-widget-renderer', '.analytics-dashboard-view', context);

      if (!viewElements.length) {
        return;
      }

      viewElements.forEach(function (viewEl) {
        var settings = drupalSettings.jarabaDashboard || {};
        var widgets = settings.widgets || [];

        widgets.forEach(function (widget) {
          fetchAndRenderWidget(viewEl, widget);
        });

        // Refresh buttons.
        initRefreshButtons(viewEl);
      });
    }
  };

  /**
   * Fetches widget data and renders the visualization.
   *
   * @param {HTMLElement} viewEl
   *   The dashboard view root element.
   * @param {object} widget
   *   The widget configuration object.
   */
  function fetchAndRenderWidget(viewEl, widget) {
    var container = viewEl.querySelector('[data-widget-container="' + widget.id + '"]');
    if (!container) {
      return;
    }

    fetch('/api/v1/analytics/widgets/' + widget.id + '/data', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      container.innerHTML = '';
      renderWidget(container, widget, result.data || []);
    })
    .catch(function () {
      container.innerHTML = '<div class="analytics-dashboard-view__widget-error">' +
        Drupal.t('Failed to load data.') + '</div>';
    });
  }

  /**
   * Renders a widget based on its type.
   *
   * @param {HTMLElement} container
   *   The widget body container.
   * @param {object} widget
   *   The widget configuration.
   * @param {Array} data
   *   The data from the API.
   */
  function renderWidget(container, widget, data) {
    switch (widget.widget_type) {
      case 'line_chart':
        renderChart(container, widget, data, 'line');
        break;
      case 'bar_chart':
        renderChart(container, widget, data, 'bar');
        break;
      case 'pie_chart':
        renderChart(container, widget, data, 'pie');
        break;
      case 'number_card':
        renderNumberCard(container, widget, data);
        break;
      case 'table':
        renderTable(container, widget, data);
        break;
      case 'funnel':
        renderFunnel(container, widget, data);
        break;
      case 'cohort_heatmap':
        renderCohortHeatmap(container, widget, data);
        break;
      default:
        container.innerHTML = '<p>' + Drupal.t('Unknown widget type.') + '</p>';
    }
  }

  /**
   * Renders a Chart.js chart (line, bar, or pie).
   *
   * @param {HTMLElement} container
   *   The container element.
   * @param {object} widget
   *   The widget configuration.
   * @param {Array} data
   *   The data array.
   * @param {string} chartType
   *   Chart.js chart type ('line', 'bar', 'pie').
   */
  function renderChart(container, widget, data, chartType) {
    if (typeof Chart === 'undefined') {
      container.innerHTML = '<p>' + Drupal.t('Chart.js not loaded.') + '</p>';
      return;
    }

    var canvas = document.createElement('canvas');
    canvas.classList.add('analytics-dashboard-view__chart-canvas');
    container.appendChild(canvas);

    var displayConfig = widget.display_config || {};
    var colors = displayConfig.colors || [
      'var(--ej-primary, #0d6efd)',
      'var(--ej-success, #198754)',
      'var(--ej-warning, #ffc107)',
      'var(--ej-danger, #dc3545)',
      'var(--ej-info, #0dcaf0)',
      'var(--ej-purple, #6f42c1)',
      'var(--ej-orange, #fd7e14)'
    ];

    var labels = data.map(function (item, index) {
      return item.period || item.dim_date || item.dim_event_type || Drupal.t('Item @n', { '@n': index + 1 });
    });

    var values = data.map(function (item) {
      return parseFloat(item.metric_value || item.value || 0);
    });

    var chartConfig = {
      type: chartType,
      data: {
        labels: labels,
        datasets: [{
          label: widget.name,
          data: values,
          backgroundColor: chartType === 'pie' ? colors : (colors[0] || '#0d6efd'),
          borderColor: chartType === 'line' ? (colors[0] || '#0d6efd') : undefined,
          borderWidth: chartType === 'line' ? 2 : 1,
          fill: chartType === 'line' ? false : undefined,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: displayConfig.labels !== false
          }
        }
      }
    };

    new Chart(canvas, chartConfig);
  }

  /**
   * Renders a number card widget.
   *
   * @param {HTMLElement} container
   *   The container element.
   * @param {object} widget
   *   The widget configuration.
   * @param {Array} data
   *   The data array.
   */
  function renderNumberCard(container, widget, data) {
    var value = 0;
    if (data.length > 0) {
      value = parseFloat(data[0].metric_value || data[0].value || 0);
    }

    var displayConfig = widget.display_config || {};
    var format = displayConfig.format || 'number';
    var formattedValue;

    switch (format) {
      case 'percentage':
        formattedValue = value.toFixed(1) + '%';
        break;
      case 'currency':
        formattedValue = '$' + value.toLocaleString();
        break;
      default:
        formattedValue = value.toLocaleString();
    }

    var cardHtml = '<div class="analytics-dashboard-view__number-card">' +
      '<span class="analytics-dashboard-view__number-value">' + formattedValue + '</span>' +
      '<span class="analytics-dashboard-view__number-label">' + Drupal.checkPlain(widget.name) + '</span>' +
      '</div>';

    container.innerHTML = cardHtml;
  }

  /**
   * Renders a table widget.
   *
   * @param {HTMLElement} container
   *   The container element.
   * @param {object} widget
   *   The widget configuration.
   * @param {Array} data
   *   The data array.
   */
  function renderTable(container, widget, data) {
    if (!data.length) {
      container.innerHTML = '<p class="analytics-dashboard-view__table-empty">' +
        Drupal.t('No data available.') + '</p>';
      return;
    }

    var headers = Object.keys(data[0]);
    var tableHtml = '<div class="analytics-dashboard-view__table-wrapper">' +
      '<table class="analytics-dashboard-view__data-table">' +
      '<thead><tr>';

    headers.forEach(function (header) {
      tableHtml += '<th class="analytics-dashboard-view__table-th">' + Drupal.checkPlain(header) + '</th>';
    });

    tableHtml += '</tr></thead><tbody>';

    data.forEach(function (row) {
      tableHtml += '<tr>';
      headers.forEach(function (header) {
        tableHtml += '<td class="analytics-dashboard-view__table-td">' +
          Drupal.checkPlain(String(row[header] || '')) + '</td>';
      });
      tableHtml += '</tr>';
    });

    tableHtml += '</tbody></table></div>';
    container.innerHTML = tableHtml;
  }

  /**
   * Renders a funnel widget.
   *
   * @param {HTMLElement} container
   *   The container element.
   * @param {object} widget
   *   The widget configuration.
   * @param {Array} data
   *   The data array.
   */
  function renderFunnel(container, widget, data) {
    if (!data.length) {
      container.innerHTML = '<p>' + Drupal.t('No funnel data.') + '</p>';
      return;
    }

    var maxValue = Math.max.apply(null, data.map(function (d) {
      return parseFloat(d.metric_value || d.value || 0);
    }));

    var funnelHtml = '<div class="analytics-dashboard-view__funnel">';

    data.forEach(function (step, index) {
      var value = parseFloat(step.metric_value || step.value || 0);
      var widthPercent = maxValue > 0 ? (value / maxValue * 100) : 0;
      var label = step.dim_event_type || step.label || Drupal.t('Step @n', { '@n': index + 1 });

      funnelHtml += '<div class="analytics-dashboard-view__funnel-step">' +
        '<div class="analytics-dashboard-view__funnel-bar" style="width: ' + widthPercent + '%;">' +
        '<span class="analytics-dashboard-view__funnel-value">' + value.toLocaleString() + '</span>' +
        '</div>' +
        '<span class="analytics-dashboard-view__funnel-label">' + Drupal.checkPlain(label) + '</span>' +
        '</div>';
    });

    funnelHtml += '</div>';
    container.innerHTML = funnelHtml;
  }

  /**
   * Renders a cohort heatmap widget.
   *
   * @param {HTMLElement} container
   *   The container element.
   * @param {object} widget
   *   The widget configuration.
   * @param {Array} data
   *   The data array.
   */
  function renderCohortHeatmap(container, widget, data) {
    if (!data.length) {
      container.innerHTML = '<p>' + Drupal.t('No cohort data.') + '</p>';
      return;
    }

    var heatmapHtml = '<div class="analytics-dashboard-view__cohort-heatmap">';
    heatmapHtml += '<table class="analytics-dashboard-view__heatmap-table">';
    heatmapHtml += '<thead><tr><th>' + Drupal.t('Period') + '</th><th>' + Drupal.t('Value') + '</th></tr></thead>';
    heatmapHtml += '<tbody>';

    data.forEach(function (row) {
      var value = parseFloat(row.metric_value || row.value || 0);
      var intensity = Math.min(value / 100, 1);
      var bgColor = 'rgba(13, 110, 253, ' + intensity + ')';

      heatmapHtml += '<tr>' +
        '<td>' + Drupal.checkPlain(row.period || row.dim_date || '') + '</td>' +
        '<td style="background-color: ' + bgColor + '; color: ' + (intensity > 0.5 ? '#fff' : '#000') + ';">' +
        value.toFixed(1) + '</td></tr>';
    });

    heatmapHtml += '</tbody></table></div>';
    container.innerHTML = heatmapHtml;
  }

  /**
   * Initializes refresh buttons.
   *
   * @param {HTMLElement} viewEl
   *   The dashboard view element.
   */
  function initRefreshButtons(viewEl) {
    // Refresh all.
    var refreshAllBtn = viewEl.querySelector('[data-action="refresh-all"]');
    if (refreshAllBtn) {
      refreshAllBtn.addEventListener('click', function () {
        var settings = drupalSettings.jarabaDashboard || {};
        var widgets = settings.widgets || [];
        widgets.forEach(function (widget) {
          fetchAndRenderWidget(viewEl, widget);
        });
      });
    }

    // Individual refresh buttons.
    viewEl.addEventListener('click', function (e) {
      var refreshBtn = e.target.closest('[data-action="refresh-widget"]');
      if (refreshBtn) {
        var widgetId = refreshBtn.getAttribute('data-widget-id');
        var settings = drupalSettings.jarabaDashboard || {};
        var widgets = settings.widgets || [];
        var widget = widgets.find(function (w) { return String(w.id) === widgetId; });
        if (widget) {
          fetchAndRenderWidget(viewEl, widget);
        }
      }
    });
  }

})(Drupal, drupalSettings, once);
