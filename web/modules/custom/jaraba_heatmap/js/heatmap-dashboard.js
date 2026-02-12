/**
 * @file
 * Dashboard controller for Heatmap Analytics.
 *
 * Manages page selection, data loading via AJAX, filter controls,
 * and coordinates with heatmap-viewer.js for Canvas rendering.
 *
 * Uses Drupal.behaviors + once() pattern.
 * All user-facing text uses Drupal.t() for i18n.
 *
 * Ref: Spec 20260130a §8, §9
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Heatmap Dashboard behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaHeatmapDashboard = {
    attach: function (context) {
      once('jarabaHeatmapDashboard', '.heatmap-dashboard', context).forEach(function (el) {
        var dashboard = new HeatmapDashboard(el);
        dashboard.init();
      });
    }
  };

  /**
   * Dashboard controller constructor.
   *
   * @param {HTMLElement} el
   *   Root dashboard element.
   */
  function HeatmapDashboard(el) {
    this.el = el;
    this.config = drupalSettings.jarabaHeatmap || {};
    this.apiBase = this.config.apiBase || '/api/heatmap';
    this.tenantId = this.config.tenantId || 0;

    // State.
    this.selectedPage = null;
    this.selectedDevice = 'all';
    this.selectedPeriod = 30;
    this.selectedEventType = 'click';
    this.loading = false;
  }

  /**
   * Initialize dashboard controls and event listeners.
   */
  HeatmapDashboard.prototype.init = function () {
    this.bindPageSelector();
    this.bindDeviceFilter();
    this.bindPeriodSelector();
    this.bindEventTypeFilter();
    this.bindPageSearch();
  };

  /**
   * Bind page list click events.
   */
  HeatmapDashboard.prototype.bindPageSelector = function () {
    var self = this;
    var pageList = this.el.querySelector('#heatmap-page-list');
    if (!pageList) {
      return;
    }

    pageList.addEventListener('click', function (e) {
      var item = e.target.closest('.heatmap-dashboard__page-item');
      if (!item || item.classList.contains('heatmap-dashboard__page-item--empty')) {
        return;
      }

      // Update selection state.
      pageList.querySelectorAll('.heatmap-dashboard__page-item').forEach(function (li) {
        li.classList.remove('is-active');
        li.setAttribute('aria-selected', 'false');
      });
      item.classList.add('is-active');
      item.setAttribute('aria-selected', 'true');

      self.selectedPage = item.dataset.pagePath;
      self.loadPageData();
    });

    // Keyboard navigation.
    pageList.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        e.target.click();
      }
    });
  };

  /**
   * Bind device filter buttons.
   */
  HeatmapDashboard.prototype.bindDeviceFilter = function () {
    var self = this;
    var buttons = this.el.querySelectorAll('.heatmap-dashboard__device-btn');

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        buttons.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        self.selectedDevice = btn.dataset.device;
        if (self.selectedPage) {
          self.loadPageData();
        }
      });
    });
  };

  /**
   * Bind period select dropdown.
   */
  HeatmapDashboard.prototype.bindPeriodSelector = function () {
    var self = this;
    var select = this.el.querySelector('#heatmap-period');
    if (!select) {
      return;
    }

    select.addEventListener('change', function () {
      self.selectedPeriod = parseInt(select.value, 10);
      if (self.selectedPage) {
        self.loadPageData();
      }
    });
  };

  /**
   * Bind event type filter buttons.
   */
  HeatmapDashboard.prototype.bindEventTypeFilter = function () {
    var self = this;
    var buttons = this.el.querySelectorAll('.heatmap-dashboard__event-btn');

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        buttons.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        self.selectedEventType = btn.dataset.eventType;
        if (self.selectedPage) {
          self.loadPageData();
        }
      });
    });
  };

  /**
   * Bind page search input for filtering the page list.
   */
  HeatmapDashboard.prototype.bindPageSearch = function () {
    var searchInput = this.el.querySelector('#heatmap-page-search');
    if (!searchInput) {
      return;
    }

    searchInput.addEventListener('input', function () {
      var query = searchInput.value.toLowerCase();
      var items = document.querySelectorAll('.heatmap-dashboard__page-item[data-page-path]');

      items.forEach(function (item) {
        var path = (item.dataset.pagePath || '').toLowerCase();
        item.style.display = path.indexOf(query) !== -1 ? '' : 'none';
      });
    });
  };

  /**
   * Load data for the selected page.
   */
  HeatmapDashboard.prototype.loadPageData = function () {
    if (!this.selectedPage || this.loading) {
      return;
    }

    this.loading = true;
    this.showLoading(true);

    var self = this;
    var encodedPath = encodeURIComponent(this.selectedPage);

    // Load event data (clicks/movement) and scroll data in parallel.
    var promises = [];

    if (this.selectedEventType === 'scroll') {
      promises.push(this.fetchApi('/pages/' + encodedPath + '/scroll'));
    } else {
      var endpoint = this.selectedEventType === 'move' ? '/pages/' + encodedPath + '/movement' : '/pages/' + encodedPath + '/clicks';
      promises.push(this.fetchApi(endpoint));
    }

    // Always load scroll depth for sidebar.
    promises.push(this.fetchApi('/pages/' + encodedPath + '/scroll'));

    Promise.all(promises).then(function (results) {
      self.loading = false;
      self.showLoading(false);

      var mainData = results[0];
      var scrollData = results[1];

      if (self.selectedEventType === 'scroll') {
        self.renderScrollHeatmap(mainData);
      } else {
        self.renderHeatmapData(mainData);
      }

      self.renderScrollDepthSidebar(scrollData);
      self.hideEmptyState();
    }).catch(function (err) {
      self.loading = false;
      self.showLoading(false);
      console.error('Heatmap data load error:', err);
    });
  };

  /**
   * Fetch from heatmap API.
   *
   * @param {string} path
   *   API endpoint path relative to apiBase.
   * @return {Promise}
   */
  HeatmapDashboard.prototype.fetchApi = function (path) {
    var params = '?days=' + this.selectedPeriod;
    if (this.selectedDevice !== 'all') {
      params += '&device=' + this.selectedDevice;
    }

    return fetch(this.apiBase + path + params, {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('API error: ' + response.status);
      }
      return response.json();
    });
  };

  /**
   * Render heatmap data on canvas using JarabaHeatmapViewer if available.
   *
   * @param {Object} data
   *   API response with buckets array.
   */
  HeatmapDashboard.prototype.renderHeatmapData = function (data) {
    var canvas = this.el.querySelector('#heatmap-canvas');
    if (!canvas || !data || !data.buckets) {
      return;
    }

    var container = this.el.querySelector('#heatmap-canvas-container');
    canvas.width = container.offsetWidth;
    canvas.height = Math.max(600, container.offsetHeight);

    var ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (data.buckets.length === 0) {
      ctx.fillStyle = 'var(--ej-color-muted, #94A3B8)';
      ctx.font = '16px sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(Drupal.t('No data available for this selection.'), canvas.width / 2, canvas.height / 2);
      return;
    }

    // Render heatmap using radial gradients.
    var bucketW = canvas.width / 20;
    var bucketH = 50;

    data.buckets.forEach(function (bucket) {
      var x = bucket.x * bucketW + bucketW / 2;
      var y = bucket.y * bucketH + bucketH / 2;
      var radius = Math.max(bucketW, bucketH) * (0.5 + bucket.intensity * 0.5);

      var gradient = ctx.createRadialGradient(x, y, 0, x, y, radius);
      gradient.addColorStop(0, 'rgba(255, 0, 0, ' + (bucket.intensity * 0.6) + ')');
      gradient.addColorStop(0.5, 'rgba(255, 165, 0, ' + (bucket.intensity * 0.3) + ')');
      gradient.addColorStop(1, 'rgba(255, 255, 0, 0)');

      ctx.fillStyle = gradient;
      ctx.fillRect(x - radius, y - radius, radius * 2, radius * 2);
    });
  };

  /**
   * Render scroll depth as a heatmap gradient overlay.
   *
   * @param {Object} data
   *   Scroll data from API.
   */
  HeatmapDashboard.prototype.renderScrollHeatmap = function (data) {
    var canvas = this.el.querySelector('#heatmap-canvas');
    if (!canvas || !data || !data.data) {
      return;
    }

    var container = this.el.querySelector('#heatmap-canvas-container');
    canvas.width = container.offsetWidth;
    canvas.height = Math.max(600, container.offsetHeight);

    var ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    var deviceData = data.data[this.selectedDevice] || data.data['all'] || data.data[Object.keys(data.data)[0]];
    if (!deviceData) {
      return;
    }

    // Draw gradient from top (green/hot) to bottom (blue/cold).
    var depths = [
      { pct: 0, color: 'rgba(0, 200, 0, 0.3)' },
      { pct: 0.25, color: 'rgba(100, 200, 0, 0.25)' },
      { pct: 0.5, color: 'rgba(255, 200, 0, 0.2)' },
      { pct: 0.75, color: 'rgba(255, 100, 0, 0.15)' },
      { pct: 1, color: 'rgba(255, 0, 0, 0.1)' },
    ];

    var gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
    depths.forEach(function (d) {
      gradient.addColorStop(d.pct, d.color);
    });
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Draw fold line at approximate average scroll depth.
    if (deviceData.avg_depth) {
      var foldY = (deviceData.avg_depth / 100) * canvas.height;
      ctx.setLineDash([5, 5]);
      ctx.strokeStyle = 'var(--ej-color-danger, #EF4444)';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(0, foldY);
      ctx.lineTo(canvas.width, foldY);
      ctx.stroke();
      ctx.setLineDash([]);

      ctx.fillStyle = 'var(--ej-color-danger, #EF4444)';
      ctx.font = '12px sans-serif';
      ctx.fillText(Drupal.t('Avg. fold: @depth%', { '@depth': deviceData.avg_depth }), 10, foldY - 5);
    }
  };

  /**
   * Render scroll depth bars in sidebar.
   *
   * @param {Object} data
   *   Scroll API response.
   */
  HeatmapDashboard.prototype.renderScrollDepthSidebar = function (data) {
    var section = this.el.querySelector('#heatmap-scroll-section');
    if (!section || !data || !data.data) {
      return;
    }

    section.hidden = false;

    var deviceData = data.data[this.selectedDevice] || data.data['all'] || data.data[Object.keys(data.data)[0]];
    if (!deviceData) {
      return;
    }

    var depths = [25, 50, 75, 100];
    depths.forEach(function (d) {
      var pctKey = 'pct_' + d;
      var pct = deviceData[pctKey] || 0;
      var fill = document.getElementById('scroll-fill-' + d);
      var value = document.getElementById('scroll-value-' + d);
      if (fill) {
        fill.style.width = pct + '%';
      }
      if (value) {
        value.textContent = pct + '%';
      }
    });
  };

  /**
   * Hide empty state and show canvas.
   */
  HeatmapDashboard.prototype.hideEmptyState = function () {
    var emptyState = this.el.querySelector('#heatmap-empty-state');
    var canvas = this.el.querySelector('#heatmap-canvas');
    if (emptyState) {
      emptyState.style.display = 'none';
    }
    if (canvas) {
      canvas.style.display = 'block';
    }
  };

  /**
   * Show/hide loading indicator.
   *
   * @param {boolean} show
   */
  HeatmapDashboard.prototype.showLoading = function (show) {
    var container = this.el.querySelector('#heatmap-canvas-container');
    if (container) {
      container.classList.toggle('is-loading', show);
    }
  };

})(Drupal, drupalSettings, once);
