/**
 * @file
 * WhatsApp panel behaviors.
 *
 * Handles: message sending, auto-scroll, KPI polling, slide-panel open.
 * ROUTE-LANGPREFIX-001: URLs via drupalSettings, never hardcoded.
 * CSRF-JS-CACHE-001: Token CSRF cacheado.
 * INNERHTML-XSS-001: Drupal.checkPlain() para datos insertados.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var csrfTokenCache = null;

  /**
   * Gets CSRF token (cached after first fetch).
   */
  function getCsrfToken() {
    if (csrfTokenCache) {
      return Promise.resolve(csrfTokenCache);
    }
    return fetch('/session/token')
      .then(function (r) { return r.text(); })
      .then(function (token) {
        csrfTokenCache = token;
        return token;
      });
  }

  Drupal.behaviors.whatsappPanel = {
    attach: function (context) {
      var config = (drupalSettings.jarabaWhatsApp || {});

      // Auto-scroll messages to bottom.
      var messagesContainer = document.getElementById('wa-messages');
      if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      }

      // KPI polling (dashboard).
      once('wa-kpi-poll', '.wa-panel__kpis', context).forEach(function () {
        var pollInterval = config.pollInterval || 15000;
        var statsUrl = config.statsUrl || '/api/v1/whatsapp/stats';

        setInterval(function () {
          getCsrfToken().then(function (token) {
            fetch(statsUrl, {
              headers: { 'X-CSRF-Token': token, 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              var kpiValues = document.querySelectorAll('.wa-panel__kpi-value');
              var values = [data.active || 0, data.escalated || 0, data.leads_today || 0, data.total || 0];
              kpiValues.forEach(function (el, i) {
                if (values[i] !== undefined) {
                  el.textContent = values[i];
                }
              });
            })
            .catch(function () {});
          });
        }, pollInterval);
      });

      // Send message form (conversation detail).
      once('wa-send-form', '#wa-send-form', context).forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();

          var input = document.getElementById('wa-message-input');
          var body = input ? input.value.trim() : '';
          if (!body) {
            return;
          }

          var conversationId = form.getAttribute('data-conversation-id');
          if (!conversationId) {
            return;
          }

          var sendUrl = '/whatsapp-panel/conversation/' + conversationId + '/respond';

          getCsrfToken().then(function (token) {
            fetch(sendUrl, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
              },
              body: JSON.stringify({ message: body })
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (data.success) {
                var container = document.getElementById('wa-messages');
                if (container) {
                  var msgDiv = document.createElement('div');
                  msgDiv.className = 'wa-message wa-message--outbound';
                  msgDiv.innerHTML = '<div class="wa-message__bubble">' +
                    '<span class="wa-message__sender">' + Drupal.t('Agente Humano') + '</span>' +
                    '<p class="wa-message__body">' + Drupal.checkPlain(body) + '</p></div>';
                  container.appendChild(msgDiv);
                  container.scrollTop = container.scrollHeight;
                }
                input.value = '';
              }
            })
            .catch(function () {});
          });
        });
      });

      // Conversation list item click → slide-panel.
      once('wa-conv-click', '.wa-list-item__link', context).forEach(function (link) {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          var href = this.getAttribute('href');
          if (!href) {
            return;
          }

          fetch(href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
          .then(function (response) { return response.text(); })
          .then(function (html) {
            var panel = document.querySelector('.slide-panel');
            if (!panel) {
              panel = document.createElement('div');
              panel.className = 'slide-panel slide-panel--open';
              panel.innerHTML = '<div class="slide-panel__header">' +
                '<button class="slide-panel__close" aria-label="' + Drupal.t('Cerrar') + '">&times;</button>' +
                '</div><div class="slide-panel__body"></div>';
              document.body.appendChild(panel);
              panel.querySelector('.slide-panel__close').addEventListener('click', function () {
                panel.classList.remove('slide-panel--open');
              });
            }
            else {
              panel.classList.add('slide-panel--open');
            }

            var panelBody = panel.querySelector('.slide-panel__body');
            if (panelBody) {
              panelBody.innerHTML = html;
              Drupal.attachBehaviors(panelBody);
            }
          });
        });
      });
    }
  };

})(Drupal, drupalSettings, once);
