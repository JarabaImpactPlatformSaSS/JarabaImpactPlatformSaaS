/**
 * @file
 * WhatsApp Panel — Drupal.behaviors con polling y slide-panel.
 *
 * ZERO-REGION-003: Datos dinámicos via drupalSettings.
 * CSRF-JS-CACHE-001: Token CSRF cacheado.
 * INNERHTML-XSS-001: Contenido del servidor sanitizado por Drupal.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var csrfToken = null;

  Drupal.behaviors.jarabaWhatsAppPanel = {
    attach: function (context) {
      once('wa-panel-init', '.wa-panel', context).forEach(function (el) {
        var config = drupalSettings.jarabaWhatsApp || {};
        var pollInterval = config.pollInterval || 15000;

        // Polling para actualizar KPIs.
        setInterval(function () {
          Drupal.behaviors.jarabaWhatsAppPanel.refreshStats();
        }, pollInterval);

        // Click en conversación → slide-panel.
        el.querySelectorAll('.wa-conv-item').forEach(function (item) {
          item.addEventListener('click', function () {
            var convId = this.dataset.conversationId;
            if (convId) {
              Drupal.behaviors.jarabaWhatsAppPanel.openConversation(convId);
            }
          });
        });
      });

      // Enviar mensaje manual.
      once('wa-send-init', '.wa-chat__send', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var convId = this.dataset.conversationId;
          var textarea = this.closest('.wa-chat__input').querySelector('.wa-chat__textarea');
          var message = textarea ? textarea.value.trim() : '';
          if (message && convId) {
            Drupal.behaviors.jarabaWhatsAppPanel.sendMessage(convId, message, textarea);
          }
        });
      });

      // Devolver a IA.
      once('wa-return-ia-init', '.wa-chat__return-ia', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var convId = this.dataset.conversationId;
          if (convId) {
            Drupal.behaviors.jarabaWhatsAppPanel.returnToIa(convId);
          }
        });
      });
    },

    refreshStats: function () {
      var apiUrl = (drupalSettings.jarabaWhatsApp || {}).statsUrl;
      if (!apiUrl) {
        return;
      }

      Drupal.behaviors.jarabaWhatsAppPanel.getCsrfToken().then(function (token) {
        fetch(apiUrl, {
          headers: {
            'X-CSRF-Token': token,
            'Accept': 'application/json'
          }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          // Actualizar valores de KPIs en el DOM.
          var kpis = document.querySelectorAll('.wa-panel__kpi-value');
          var values = [data.active || 0, data.escalated || 0, data.leads_today || 0];
          kpis.forEach(function (el, i) {
            if (values[i] !== undefined) {
              el.textContent = values[i];
            }
          });
        })
        .catch(function () {});
      });
    },

    openConversation: function (convId) {
      var url = '/whatsapp-panel/conversation/' + convId;
      fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function (response) { return response.text(); })
      .then(function (html) {
        // Usar slide-panel existente o crear uno.
        var panel = document.querySelector('.slide-panel');
        if (!panel) {
          panel = document.createElement('div');
          panel.className = 'slide-panel slide-panel--open';
          panel.innerHTML = '<div class="slide-panel__header"><button class="slide-panel__close" aria-label="' + Drupal.t('Cerrar') + '">&times;</button></div><div class="slide-panel__body"></div>';
          document.body.appendChild(panel);
          panel.querySelector('.slide-panel__close').addEventListener('click', function () {
            panel.classList.remove('slide-panel--open');
          });
        }
        else {
          panel.classList.add('slide-panel--open');
        }

        var body = panel.querySelector('.slide-panel__body');
        if (body) {
          body.innerHTML = html;
          Drupal.attachBehaviors(body);
        }
      });
    },

    sendMessage: function (convId, message, textarea) {
      Drupal.behaviors.jarabaWhatsAppPanel.getCsrfToken().then(function (token) {
        fetch('/whatsapp-panel/conversation/' + convId + '/respond', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': token
          },
          body: JSON.stringify({ message: message })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success && textarea) {
            textarea.value = '';
            // Recargar conversación.
            Drupal.behaviors.jarabaWhatsAppPanel.openConversation(convId);
          }
        });
      });
    },

    returnToIa: function (convId) {
      Drupal.behaviors.jarabaWhatsAppPanel.getCsrfToken().then(function (token) {
        fetch('/whatsapp-panel/conversation/' + convId + '/return-to-ia', {
          method: 'POST',
          headers: { 'X-CSRF-Token': token }
        })
        .then(function () {
          // Cerrar slide-panel y refrescar.
          var panel = document.querySelector('.slide-panel');
          if (panel) {
            panel.classList.remove('slide-panel--open');
          }
          Drupal.behaviors.jarabaWhatsAppPanel.refreshStats();
        });
      });
    },

    getCsrfToken: function () {
      if (csrfToken) {
        return Promise.resolve(csrfToken);
      }
      return fetch('/session/token')
        .then(function (r) { return r.text(); })
        .then(function (token) {
          csrfToken = token;
          return token;
        });
    }
  };

})(Drupal, drupalSettings, once);
