/**
 * @file
 * Dashboard Builder - Drag-and-drop grid for widget management.
 *
 * Provides drag-and-drop functionality for adding, removing, repositioning
 * and resizing widgets on the dashboard canvas using CSS Grid.
 *
 * @see templates/analytics-dashboard-builder.html.twig
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Dashboard Builder behavior.
   *
   * Initializes the drag-and-drop grid canvas, widget palette interactions,
   * and save/config functionality.
   */
  Drupal.behaviors.jarabaDashboardBuilder = {
    attach: function (context) {
      var builderElements = once('jaraba-dashboard-builder', '.analytics-builder', context);

      if (!builderElements.length) {
        return;
      }

      builderElements.forEach(function (builderEl) {
        var settings = drupalSettings.jarabaDashboardBuilder || {};
        var dashboardId = settings.dashboardId;
        var apiBase = settings.apiBase || '/api/v1/analytics/dashboards/' + dashboardId;

        // Initialize palette drag sources.
        initPalette(builderEl);

        // Initialize canvas drop zone.
        initCanvas(builderEl, apiBase);

        // Initialize existing widget interactions.
        initWidgetControls(builderEl, apiBase);

        // Initialize save button.
        initSaveButton(builderEl, apiBase);

        // Initialize config panel.
        initConfigPanel(builderEl, apiBase);
      });
    }
  };

  /**
   * Initializes palette drag sources.
   *
   * @param {HTMLElement} builderEl
   *   The builder root element.
   */
  function initPalette(builderEl) {
    var paletteItems = builderEl.querySelectorAll('.analytics-builder__palette-item');

    paletteItems.forEach(function (item) {
      item.addEventListener('dragstart', function (e) {
        e.dataTransfer.setData('text/plain', JSON.stringify({
          source: 'palette',
          widgetType: item.getAttribute('data-widget-type')
        }));
        e.dataTransfer.effectAllowed = 'copy';
        item.classList.add('analytics-builder__palette-item--dragging');
      });

      item.addEventListener('dragend', function () {
        item.classList.remove('analytics-builder__palette-item--dragging');
      });
    });
  }

  /**
   * Initializes the canvas drop zone.
   *
   * @param {HTMLElement} builderEl
   *   The builder root element.
   * @param {string} apiBase
   *   The API base URL.
   */
  function initCanvas(builderEl, apiBase) {
    var canvas = builderEl.querySelector('.analytics-builder__canvas');
    var grid = builderEl.querySelector('.analytics-builder__grid');
    var dropZone = builderEl.querySelector('.analytics-builder__drop-zone');

    if (!canvas || !grid) {
      return;
    }

    canvas.addEventListener('dragover', function (e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
      if (dropZone) {
        dropZone.classList.add('analytics-builder__drop-zone--active');
      }
    });

    canvas.addEventListener('dragleave', function () {
      if (dropZone) {
        dropZone.classList.remove('analytics-builder__drop-zone--active');
      }
    });

    canvas.addEventListener('drop', function (e) {
      e.preventDefault();
      if (dropZone) {
        dropZone.classList.remove('analytics-builder__drop-zone--active');
      }

      var data;
      try {
        data = JSON.parse(e.dataTransfer.getData('text/plain'));
      }
      catch (err) {
        return;
      }

      if (data.source === 'palette') {
        createWidget(grid, apiBase, data.widgetType);
      }
    });
  }

  /**
   * Creates a new widget via API and adds it to the grid.
   *
   * @param {HTMLElement} grid
   *   The grid container element.
   * @param {string} apiBase
   *   The API base URL.
   * @param {string} widgetType
   *   The widget type to create.
   */
  function createWidget(grid, apiBase, widgetType) {
    var widgetName = Drupal.t('New @type', { '@type': widgetType.replace(/_/g, ' ') });

    fetch(apiBase + '/widgets', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        name: widgetName,
        widget_type: widgetType,
        position: '1:1:4:3',
        query_config: { metric: 'page_views', date_range: 'last_30_days' },
        display_config: {}
      })
    })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (result.success) {
        // Reload to show new widget.
        window.location.reload();
      }
      else {
        Drupal.announce(Drupal.t('Failed to create widget.'));
      }
    })
    .catch(function () {
      Drupal.announce(Drupal.t('Error creating widget.'));
    });
  }

  /**
   * Initializes widget controls (remove, configure).
   *
   * @param {HTMLElement} builderEl
   *   The builder root element.
   * @param {string} apiBase
   *   The API base URL.
   */
  function initWidgetControls(builderEl, apiBase) {
    // Remove buttons.
    builderEl.addEventListener('click', function (e) {
      var removeBtn = e.target.closest('[data-action="remove-widget"]');
      if (removeBtn) {
        var widgetId = removeBtn.getAttribute('data-widget-id');
        if (confirm(Drupal.t('Remove this widget?'))) {
          removeWidget(widgetId);
        }
      }

      var configBtn = e.target.closest('[data-action="configure-widget"]');
      if (configBtn) {
        var configWidgetId = configBtn.getAttribute('data-widget-id');
        openConfigPanel(builderEl, configWidgetId);
      }
    });

    // Make existing widgets draggable for repositioning.
    var widgets = builderEl.querySelectorAll('.analytics-builder__widget');
    widgets.forEach(function (widget) {
      var handle = widget.querySelector('.analytics-builder__widget-drag-handle');
      if (handle) {
        widget.setAttribute('draggable', 'true');

        widget.addEventListener('dragstart', function (e) {
          e.dataTransfer.setData('text/plain', JSON.stringify({
            source: 'grid',
            widgetId: widget.getAttribute('data-widget-id')
          }));
          widget.classList.add('analytics-builder__widget--dragging');
        });

        widget.addEventListener('dragend', function () {
          widget.classList.remove('analytics-builder__widget--dragging');
        });
      }
    });
  }

  /**
   * Removes a widget via API.
   *
   * @param {string} widgetId
   *   The widget ID to remove.
   */
  function removeWidget(widgetId) {
    fetch('/api/v1/analytics/widgets/' + widgetId, {
      method: 'DELETE',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (result.success) {
        var widgetEl = document.querySelector('[data-widget-id="' + widgetId + '"]');
        if (widgetEl) {
          widgetEl.remove();
        }
      }
    })
    .catch(function () {
      Drupal.announce(Drupal.t('Error removing widget.'));
    });
  }

  /**
   * Opens the config panel for a widget.
   *
   * @param {HTMLElement} builderEl
   *   The builder root element.
   * @param {string} widgetId
   *   The widget ID to configure.
   */
  function openConfigPanel(builderEl, widgetId) {
    var panel = builderEl.querySelector('.analytics-builder__config-panel');
    if (panel) {
      panel.classList.remove('analytics-builder__config-panel--hidden');
      panel.setAttribute('data-editing-widget', widgetId);
    }
  }

  /**
   * Initializes save button.
   *
   * @param {HTMLElement} builderEl
   *   The builder root element.
   * @param {string} apiBase
   *   The API base URL.
   */
  function initSaveButton(builderEl, apiBase) {
    var saveBtn = builderEl.querySelector('[data-action="save-layout"]');
    if (!saveBtn) {
      return;
    }

    saveBtn.addEventListener('click', function () {
      var widgets = builderEl.querySelectorAll('.analytics-builder__widget');
      var layout = [];

      widgets.forEach(function (widget) {
        layout.push({
          widget_id: widget.getAttribute('data-widget-id'),
          position: widget.getAttribute('data-position')
        });
      });

      saveBtn.disabled = true;
      saveBtn.textContent = Drupal.t('Saving...');

      fetch(apiBase + '/layout', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ layout: layout })
      })
      .then(function (response) { return response.json(); })
      .then(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = Drupal.t('Save Layout');
        Drupal.announce(Drupal.t('Layout saved successfully.'));
      })
      .catch(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = Drupal.t('Save Layout');
        Drupal.announce(Drupal.t('Error saving layout.'));
      });
    });
  }

  /**
   * Initializes config panel close and save actions.
   *
   * @param {HTMLElement} builderEl
   *   The builder root element.
   * @param {string} apiBase
   *   The API base URL.
   */
  function initConfigPanel(builderEl, apiBase) {
    var panel = builderEl.querySelector('.analytics-builder__config-panel');
    if (!panel) {
      return;
    }

    // Close button.
    var closeBtn = panel.querySelector('[data-action="close-config"]');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        panel.classList.add('analytics-builder__config-panel--hidden');
      });
    }

    // Save config button.
    var saveConfigBtn = panel.querySelector('[data-action="save-widget-config"]');
    if (saveConfigBtn) {
      saveConfigBtn.addEventListener('click', function () {
        var widgetId = panel.getAttribute('data-editing-widget');
        if (!widgetId) {
          return;
        }

        var nameInput = panel.querySelector('#widget-name');
        var dataSourceInput = panel.querySelector('#widget-data-source');
        var metricSelect = panel.querySelector('#widget-metric');
        var dateRangeSelect = panel.querySelector('#widget-date-range');

        var updateData = {};

        if (nameInput && nameInput.value) {
          updateData.name = nameInput.value;
        }
        if (dataSourceInput && dataSourceInput.value) {
          updateData.data_source = dataSourceInput.value;
        }

        updateData.query_config = {
          metric: metricSelect ? metricSelect.value : 'page_views',
          date_range: dateRangeSelect ? dateRangeSelect.value : 'last_30_days'
        };

        fetch('/api/v1/analytics/widgets/' + widgetId, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(updateData)
        })
        .then(function (response) { return response.json(); })
        .then(function (result) {
          if (result.success) {
            panel.classList.add('analytics-builder__config-panel--hidden');
            window.location.reload();
          }
        })
        .catch(function () {
          Drupal.announce(Drupal.t('Error saving widget configuration.'));
        });
      });
    }
  }

})(Drupal, drupalSettings, once);
