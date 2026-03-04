/**
 * @file
 * Queue Monitoring Dashboard behavior.
 *
 * GAP-QUEUE-MON: Auto-refresh queue metrics every 30 seconds.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.queueMonitoring = {
    attach: function (context) {
      once('queue-monitoring', '.queue-monitoring', context).forEach(function (el) {
        var apiUrl = el.getAttribute('data-api-url');
        if (!apiUrl) {
          return;
        }

        var refreshBtn = el.querySelector('.queue-monitoring__refresh');
        if (refreshBtn) {
          refreshBtn.addEventListener('click', function () {
            fetchAndUpdate(el, apiUrl);
          });
        }

        // Auto-refresh every 30 seconds.
        setInterval(function () {
          fetchAndUpdate(el, apiUrl);
        }, 30000);
      });
    }
  };

  function fetchAndUpdate(container, apiUrl) {
    fetch(apiUrl, {
      headers: { 'Accept': 'application/json' }
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        updateSummary(container, data.summary);
        updateTimestamp(container, data.summary.timestamp);
      })
      .catch(function (err) {
        // Silently fail — dashboard shows stale data.
      });
  }

  function updateSummary(container, summary) {
    var mappings = {
      '--total': summary.total_queues,
      '--pending': summary.total_pending,
      '--healthy': summary.healthy,
      '--warning': summary.warning,
      '--error': summary.error
    };

    Object.keys(mappings).forEach(function (suffix) {
      var stat = container.querySelector('.queue-monitoring__stat' + suffix + ' .queue-monitoring__stat-value');
      if (stat) {
        stat.textContent = mappings[suffix];
      }
    });
  }

  function updateTimestamp(container, timestamp) {
    var ts = container.querySelector('.queue-monitoring__timestamp');
    if (ts) {
      var date = new Date(timestamp * 1000);
      ts.textContent = Drupal.t('Last updated') + ': ' + date.toISOString().replace('T', ' ').substr(0, 19);
    }
  }

})(Drupal, once);
