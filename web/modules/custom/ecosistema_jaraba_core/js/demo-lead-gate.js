/**
 * @file
 * Soft gate: intercepta clics en perfiles demo, muestra modal de captura.
 *
 * S10-01: Estrategia de soft gate para capturar leads en jaraba_crm.
 * CSRF-JS-CACHE-001: Token de /session/token cacheado en variable.
 * INNERHTML-XSS-001: Drupal.checkPlain() para datos de API.
 * ROUTE-LANGPREFIX-001: Endpoint via drupalSettings.
 * WCAG 2.4.3: Focus trap con Tab/Shift+Tab cycling.
 * WCAG 2.4.7: Focus restoration al cerrar modal.
 *
 * Patron: Vanilla JS + Drupal.behaviors (NO React/Vue).
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var csrfToken = null;

  /**
   * Obtiene CSRF token con cache (CSRF-JS-CACHE-001).
   */
  function getCsrfToken() {
    if (csrfToken) {
      return Promise.resolve(csrfToken);
    }
    return fetch(Drupal.url('session/token'))
      .then(function (response) { return response.text(); })
      .then(function (token) {
        csrfToken = token;
        return token;
      });
  }

  /**
   * Obtiene elementos focusables dentro de un contenedor.
   *
   * WCAG 2.4.3: Necesario para focus trap en dialogs.
   */
  function getFocusableElements(container) {
    return container.querySelectorAll(
      'input:not([disabled]):not([type="hidden"]), ' +
      'button:not([disabled]), ' +
      'a[href], ' +
      'textarea:not([disabled]), ' +
      'select:not([disabled]), ' +
      '[tabindex]:not([tabindex="-1"])'
    );
  }

  Drupal.behaviors.demoLeadGate = {
    attach: function (context) {
      var modal = once('demo-lead-gate', '[data-demo-lead-gate]', context);
      if (!modal.length) {
        return;
      }

      var gate = modal[0];
      var form = gate.querySelector('[data-demo-lead-form]');
      var profileInput = gate.querySelector('[data-demo-lead-profile]');
      var feedback = gate.querySelector('[data-demo-lead-feedback]');
      var skipBtn = gate.querySelector('[data-demo-lead-skip]');
      var closeBtns = gate.querySelectorAll('[data-demo-lead-gate-close]');
      var submitBtn = gate.querySelector('.demo-lead-gate__submit');

      var pendingUrl = null;
      var pendingProfileId = null;
      // WCAG 2.4.7: Guardar elemento que abrió el modal para restaurar focus.
      var triggerElement = null;

      /**
       * WCAG 2.4.3: Focus trap — Tab/Shift+Tab cycling dentro del modal.
       */
      function handleFocusTrap(e) {
        if (e.key !== 'Tab') {
          return;
        }
        var focusable = getFocusableElements(gate);
        if (focusable.length === 0) {
          return;
        }
        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (e.shiftKey) {
          // Shift+Tab: si estamos en el primero, ir al último.
          if (document.activeElement === first) {
            e.preventDefault();
            last.focus();
          }
        } else {
          // Tab: si estamos en el último, ir al primero.
          if (document.activeElement === last) {
            e.preventDefault();
            first.focus();
          }
        }
      }

      /**
       * Abre el modal de captura.
       */
      function openGate(profileId, targetUrl, trigger) {
        pendingProfileId = profileId;
        pendingUrl = targetUrl;
        triggerElement = trigger || null;
        profileInput.value = profileId;
        gate.hidden = false;
        gate.setAttribute('aria-hidden', 'false');
        // WCAG 2.4.3: Focus al primer input.
        var firstInput = gate.querySelector('input[type="text"]');
        if (firstInput) {
          firstInput.focus();
        }
        // Prevenir scroll del body.
        document.body.style.overflow = 'hidden';
      }

      /**
       * Cierra el modal.
       */
      function closeGate() {
        gate.hidden = true;
        gate.setAttribute('aria-hidden', 'true');
        feedback.textContent = '';
        form.reset();
        submitBtn.disabled = false;
        submitBtn.textContent = Drupal.t('Acceder a la demo');
        document.body.style.overflow = '';
        // WCAG 2.4.7: Restaurar focus al elemento que abrió el modal.
        if (triggerElement && triggerElement.focus) {
          triggerElement.focus();
        }
        pendingUrl = null;
        pendingProfileId = null;
        triggerElement = null;
      }

      /**
       * Navega a la URL de demo.
       */
      function navigateToDemo(url) {
        window.location.href = url;
      }

      // A/B test: respetar flag de drupalSettings.
      var gateEnabled = drupalSettings.demoLeadGate
        ? drupalSettings.demoLeadGate.enabled !== false
        : true;

      // Interceptar clics en botones "Probar Ahora".
      var startBtns = once('demo-lead-intercept', '.demo-start-btn', context);
      startBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          if (!gateEnabled) {
            // A/B variante 'control': navegar directamente sin gate.
            return;
          }
          e.preventDefault();
          var card = btn.closest('[data-profile]');
          var profileId = card ? card.getAttribute('data-profile') : '';
          var targetUrl = btn.getAttribute('href');
          openGate(profileId, targetUrl, btn);
        });
      });

      // Cerrar modal (overlay + botón X).
      closeBtns.forEach(function (btn) {
        btn.addEventListener('click', closeGate);
      });

      // Keyboard: Escape cierra + Tab cycling (focus trap).
      gate.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closeGate();
          return;
        }
        handleFocusTrap(e);
      });

      // Skip: navegar directamente sin captura.
      if (skipBtn) {
        skipBtn.addEventListener('click', function () {
          if (pendingUrl) {
            navigateToDemo(pendingUrl);
          }
          closeGate();
        });
      }

      // Submit: capturar lead y luego navegar.
      if (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();

          var name = form.querySelector('#demo-lead-name').value.trim();
          var email = form.querySelector('#demo-lead-email').value.trim();
          var consent = form.querySelector('[name="privacy_consent"]').checked;

          // Validacion basica cliente.
          if (!name || name.length < 2) {
            feedback.textContent = Drupal.t('Introduce tu nombre (minimo 2 caracteres).');
            return;
          }
          if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            feedback.textContent = Drupal.t('Introduce un email valido.');
            return;
          }
          if (!consent) {
            feedback.textContent = Drupal.t('Debes aceptar la politica de privacidad.');
            return;
          }

          // Deshabilitar boton.
          submitBtn.disabled = true;
          submitBtn.textContent = Drupal.t('Accediendo...');
          feedback.textContent = '';

          var endpoint = drupalSettings.demoLeadGate
            ? drupalSettings.demoLeadGate.endpoint
            : Drupal.url('api/v1/demo/lead-gate');

          getCsrfToken().then(function (token) {
            return fetch(endpoint, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token,
              },
              body: JSON.stringify({
                name: name,
                email: email,
                profile_id: pendingProfileId,
              }),
            });
          }).then(function (response) {
            return response.json();
          }).then(function (data) {
            if (data.success && data.redirect_url) {
              navigateToDemo(data.redirect_url);
            } else {
              feedback.textContent = data.error
                ? Drupal.checkPlain(data.error)
                : Drupal.t('Error al procesar. Intentalo de nuevo.');
              submitBtn.disabled = false;
              submitBtn.textContent = Drupal.t('Acceder a la demo');
            }
          }).catch(function () {
            // Fallback: si el API falla, navegar sin lead.
            if (pendingUrl) {
              navigateToDemo(pendingUrl);
            }
          });
        });
      }
    },

    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('demo-lead-gate', '[data-demo-lead-gate]', context);
        once.remove('demo-lead-intercept', '.demo-start-btn', context);
        document.body.style.overflow = '';
        csrfToken = null;
      }
    },
  };

  /**
   * Showcase tabs en el hero de /demo — switching entre paneles por vertical.
   */
  Drupal.behaviors.demoShowcaseTabs = {
    attach: function (context) {
      var containers = once('demo-showcase-tabs', '.demo-landing__showcase-tabs', context);
      containers.forEach(function (tabList) {
        var tabs = tabList.querySelectorAll('[data-showcase-tab]');
        var showcase = tabList.closest('.demo-landing__showcase');
        if (!showcase) {
          return;
        }

        tabs.forEach(function (tab) {
          tab.addEventListener('click', function () {
            var targetId = tab.getAttribute('data-showcase-tab');

            // Actualizar tabs.
            tabs.forEach(function (t) {
              t.classList.remove('demo-landing__showcase-tab--active');
              t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('demo-landing__showcase-tab--active');
            tab.setAttribute('aria-selected', 'true');

            // Mostrar/ocultar paneles.
            showcase.querySelectorAll('[data-showcase-panel]').forEach(function (panel) {
              if (panel.getAttribute('data-showcase-panel') === targetId) {
                panel.style.display = '';
                panel.classList.add('demo-landing__showcase-panel--active');
              } else {
                panel.style.display = 'none';
                panel.classList.remove('demo-landing__showcase-panel--active');
              }
            });
          });
        });
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('demo-showcase-tabs', '.demo-landing__showcase-tabs', context);
      }
    },
  };

})(Drupal, drupalSettings, once);
