/**
 * @file
 * Proactive Insights bell notification behavior.
 *
 * GAP-AUD-010: Fetches and renders unread insight cards in a dropdown panel.
 *
 * Directives:
 * - CSRF-JS-CACHE-001: Cached CSRF token promise.
 * - ROUTE-LANGPREFIX-001: Drupal.url() for all fetch URLs.
 * - INNERHTML-XSS-001: Drupal.checkPlain() for all user-visible text.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  // Cached CSRF token promise (CSRF-JS-CACHE-001).
  var csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then(function (r) { return r.text(); });
    }
    return csrfTokenPromise;
  }

  // Severity color mapping.
  var severityColors = {
    high: 'var(--ej-insight-severity-high, #EF4444)',
    medium: 'var(--ej-insight-severity-medium, #F59E0B)',
    low: 'var(--ej-insight-severity-low, #10B981)'
  };

  Drupal.behaviors.proactiveInsights = {
    attach: function (context) {
      var bells = once('proactive-insights', '[data-proactive-insights-bell]', context);

      bells.forEach(function (bell) {
        var trigger = bell.querySelector('.proactive-insights-bell__trigger');
        var panel = bell.querySelector('[data-proactive-insights-panel]');
        var listContainer = bell.querySelector('[data-proactive-insights-list]');
        var loaded = false;

        if (!trigger || !panel || !listContainer) {
          return;
        }

        trigger.addEventListener('click', function () {
          var isOpen = panel.getAttribute('aria-hidden') === 'false';

          if (isOpen) {
            panel.setAttribute('aria-hidden', 'true');
            trigger.setAttribute('aria-expanded', 'false');
            return;
          }

          panel.setAttribute('aria-hidden', 'false');
          trigger.setAttribute('aria-expanded', 'true');

          if (!loaded) {
            loadInsights(listContainer, bell);
            loaded = true;
          }
        });

        // Close on outside click.
        document.addEventListener('click', function (e) {
          if (!bell.contains(e.target)) {
            panel.setAttribute('aria-hidden', 'true');
            trigger.setAttribute('aria-expanded', 'false');
          }
        });
      });
    }
  };

  function loadInsights(container, bell) {
    container.innerHTML = '<div class="proactive-insights-loading">' + Drupal.t('Loading...') + '</div>';

    fetch(Drupal.url('api/v1/proactive-insights'))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        container.innerHTML = '';
        var insights = data.insights || [];

        if (!insights.length) {
          container.innerHTML = '<p class="proactive-insights-empty">' + Drupal.t('No new insights.') + '</p>';
          return;
        }

        insights.forEach(function (insight) {
          var card = document.createElement('div');
          card.className = 'proactive-insight-card proactive-insight-card--' + Drupal.checkPlain(insight.severity);

          var severityDot = '<span class="proactive-insight-card__dot" style="background:' + (severityColors[insight.severity] || severityColors.medium) + '"></span>';
          var typeLabel = Drupal.checkPlain(insight.insight_type);
          var title = Drupal.checkPlain(insight.title);
          var body = Drupal.checkPlain(insight.body || '');

          card.innerHTML = '<div class="proactive-insight-card__header">'
            + severityDot
            + '<span class="proactive-insight-card__type">' + typeLabel + '</span>'
            + '</div>'
            + '<h4 class="proactive-insight-card__title">' + title + '</h4>'
            + (body ? '<p class="proactive-insight-card__body">' + body.substring(0, 150) + '</p>' : '')
            + '<div class="proactive-insight-card__actions">'
            + (insight.action_url ? '<a href="' + Drupal.checkPlain(insight.action_url) + '" class="proactive-insight-card__action">' + Drupal.t('View') + '</a>' : '')
            + '<button type="button" class="proactive-insight-card__mark-read" data-insight-id="' + insight.id + '">' + Drupal.t('Mark read') + '</button>'
            + '</div>';

          // Mark read handler.
          card.querySelector('.proactive-insight-card__mark-read').addEventListener('click', function () {
            markInsightRead(insight.id, card, bell);
          });

          container.appendChild(card);
        });
      })
      .catch(function () {
        container.innerHTML = '<p class="proactive-insights-error">' + Drupal.t('Failed to load insights.') + '</p>';
      });
  }

  function markInsightRead(insightId, card, bell) {
    getCsrfToken().then(function (token) {
      return fetch(Drupal.url('api/v1/proactive-insights/' + insightId + '/read'), {
        method: 'POST',
        headers: { 'X-CSRF-Token': token }
      });
    }).then(function (r) {
      if (r.ok) {
        card.classList.add('proactive-insight-card--read');
        card.style.opacity = '0.5';

        // Update badge count.
        var badge = bell.querySelector('.proactive-insights-bell__badge');
        if (badge) {
          var count = parseInt(badge.textContent, 10) || 0;
          count = Math.max(0, count - 1);
          if (count === 0) {
            badge.remove();
          } else {
            badge.textContent = count > 9 ? '9+' : count;
          }
        }
      }
    });
  }

})(Drupal, drupalSettings, once);
