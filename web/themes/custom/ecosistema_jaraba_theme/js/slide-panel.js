/**
 * @file
 * Slide Panel - Componente global de panel deslizante.
 *
 * PROPÓSITO:
 * Gestiona apertura/cierre de paneles laterales para CRUD.
 * Versión global para uso en todo el SaaS.
 *
 * USO:
 * - Añadir data-slide-panel="[panelId]" a triggers (botones/links)
 * - Añadir data-slide-panel-url="[url]" para carga AJAX
 * - Añadir data-slide-panel-title="[title]" para título dinámico
 *
 * @see .agent/workflows/slide-panel-modales.md
 */

(function (Drupal, once) {
  'use strict';

  // Panel singleton - se crea una vez y se reutiliza
  let globalPanel = null;

  /**
   * Crea el panel global si no existe.
   */
  function ensurePanel() {
    if (globalPanel) return globalPanel;

    const panelHtml = `
      <div class="slide-panel slide-panel--large" id="global-slide-panel" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="slide-panel__overlay" data-close-panel="global-slide-panel"></div>
        <div class="slide-panel__content">
          <header class="slide-panel__header">
            <h2 class="slide-panel__title" id="global-slide-panel-title">Panel</h2>
            <button type="button" class="slide-panel__close" data-close-panel="global-slide-panel" aria-label="${Drupal.t('Cerrar')}">
              <svg viewBox="0 0 24 24" width="24" height="24">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </header>
          <div class="slide-panel__body" id="global-slide-panel-body">
            <div class="slide-panel__loader">
              <div class="loader-spinner"></div>
              <p>${Drupal.t('Cargando...')}</p>
            </div>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', panelHtml);
    globalPanel = document.getElementById('global-slide-panel');

    // Setup event listeners
    const overlay = globalPanel.querySelector('.slide-panel__overlay');
    const closeBtn = globalPanel.querySelector('.slide-panel__close');

    overlay.addEventListener('click', () => Drupal.behaviors.slidePanel.close());
    closeBtn.addEventListener('click', () => Drupal.behaviors.slidePanel.close());

    // Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && globalPanel.classList.contains('slide-panel--open')) {
        Drupal.behaviors.slidePanel.close();
      }
    });

    return globalPanel;
  }

  Drupal.behaviors.slidePanel = {
    attach: function (context) {
      // Inicializar triggers de slide-panel
      once('slide-panel-trigger', '[data-slide-panel]', context).forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
          e.preventDefault();

          const panelSize = this.dataset.slidePanel || 'large';
          const panelTitle = this.dataset.slidePanelTitle || Drupal.t('Panel');
          const panelUrl = this.dataset.slidePanelUrl || this.getAttribute('href');

          Drupal.behaviors.slidePanel.open({
            size: panelSize,
            title: panelTitle,
            url: panelUrl
          });
        });
      });

      // También inicializar paneles estáticos existentes
      once('slide-panel-static', '.slide-panel', context).forEach(function (panel) {
        const panelId = panel.id;
        const overlay = panel.querySelector('.slide-panel__overlay');
        const closeButtons = panel.querySelectorAll('[data-close-panel]');

        if (overlay) {
          overlay.addEventListener('click', function () {
            Drupal.behaviors.slidePanel.closeById(panelId);
          });
        }

        closeButtons.forEach(function (btn) {
          btn.addEventListener('click', function () {
            Drupal.behaviors.slidePanel.closeById(panelId);
          });
        });
      });
    },

    /**
     * Abre el slide panel global.
     *
     * @param {Object} options
     *   - size: 'small', 'medium', 'large', 'full' (default: 'large')
     *   - title: Título del panel
     *   - url: URL para cargar contenido via AJAX
     *   - content: HTML estático (alternativa a url)
     */
    open: function (options = {}) {
      const panel = ensurePanel();
      const titleEl = panel.querySelector('.slide-panel__title');
      const bodyEl = panel.querySelector('.slide-panel__body');

      // Configurar tamaño
      panel.className = 'slide-panel slide-panel--' + (options.size || 'large');

      // Configurar título
      if (options.title) {
        titleEl.textContent = options.title;
      }

      // Mostrar loader
      bodyEl.innerHTML = `
        <div class="slide-panel__loader">
          <div class="loader-spinner"></div>
          <p>${Drupal.t('Cargando...')}</p>
        </div>
      `;

      // Abrir panel
      panel.classList.add('slide-panel--open');
      panel.setAttribute('aria-hidden', 'false');
      document.body.classList.add('slide-panel-open');

      // Cargar contenido
      if (options.url) {
        this.loadContent(options.url);
      } else if (options.content) {
        bodyEl.innerHTML = options.content;
        Drupal.attachBehaviors(bodyEl);
      }
    },

    /**
     * Cierra el slide panel global.
     */
    close: function () {
      if (!globalPanel) return;

      globalPanel.classList.remove('slide-panel--open');
      globalPanel.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('slide-panel-open');

      // Limpiar contenido después de animación
      setTimeout(() => {
        const bodyEl = globalPanel.querySelector('.slide-panel__body');

        // CRÍTICO: Detach behaviors de Drupal ANTES de limpiar el contenido
        // Esto evita que Gin acumule offsets de sidebar en la siguiente apertura
        if (bodyEl.children.length > 0) {
          try {
            Drupal.detachBehaviors(bodyEl, null, 'unload');
          } catch (error) {
            // FE-03/FE-04: Variable unificada, log solo en debug.
          }
        }

        bodyEl.innerHTML = '';
      }, 300);
    },

    /**
     * Abre un panel estático por ID.
     *
     * @param {string} panelId - ID del panel a abrir.
     */
    openById: function (panelId) {
      const panel = document.getElementById(panelId);
      if (panel) {
        panel.classList.add('slide-panel--open');
        panel.setAttribute('aria-hidden', 'false');
        document.body.classList.add('slide-panel-open');

        // Focus primer elemento interactivo.
        const firstInput = panel.querySelector('input, select, textarea, button:not(.slide-panel__close)');
        if (firstInput) {
          setTimeout(() => firstInput.focus(), 100);
        }
      }
    },

    /**
     * Cierra un panel por ID (para paneles estáticos).
     */
    closeById: function (panelId) {
      const panel = document.getElementById(panelId);
      if (panel) {
        panel.classList.remove('slide-panel--open');
        panel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('slide-panel-open');
      }
    },

    /**
     * Carga contenido en el body del panel via AJAX.
     */
    loadContent: function (url) {
      if (!globalPanel) return;

      const bodyEl = globalPanel.querySelector('.slide-panel__body');
      const self = this;

      fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.text();
        })
        .then(html => {
          bodyEl.innerHTML = html;
          Drupal.attachBehaviors(bodyEl);

          // Focus first input
          const firstInput = bodyEl.querySelector('input, select, textarea');
          if (firstInput) firstInput.focus();

          // Interceptar submit del formulario para cierre automático
          self.attachFormSubmitHandler(bodyEl, url);
        })
        .catch(error => {
          bodyEl.innerHTML = `
            <div class="slide-panel__error">
              <p>${Drupal.t('Error al cargar el contenido')}</p>
              <button class="btn btn--secondary" data-close-panel="global-slide-panel">
                ${Drupal.t('Cerrar')}
              </button>
            </div>
          `;
        });
    },

    /**
     * Adjunta handler al formulario para cierre automático tras guardar.
     */
    attachFormSubmitHandler: function (container, originalUrl) {
      const form = container.querySelector('form');
      if (!form) return;

      const self = this;

      // Escuchar el evento submit del formulario
      form.addEventListener('submit', function (e) {
        // En contexto slide-panel, SIEMPRE interceptamos el submit via fetch.
        // data-drupal-form-fields es puesto por core/misc/form.js en TODOS los
        // formularios (dirty-tracking), NO indica formulario AJAX.
        e.preventDefault();

        // Añadir clase de loading al botón submit
        const submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
        if (submitBtn) {
          submitBtn.classList.add('is-loading');
          submitBtn.disabled = true;
        }

        const formData = new FormData(form);

        // CRÍTICO: Añadir el submit button al FormData.
        // Drupal requiere el triggering element para ejecutar save().
        // FormData no incluye el botón automáticamente.
        if (submitBtn && submitBtn.name) {
          formData.append(submitBtn.name, submitBtn.value || 'Guardar');
        }

        // CRÍTICO: Detectar si form.action es un placeholder BigPipe (form_action_p_...)
        // Estos placeholders causan 404. Usar originalUrl como fallback.
        let submitUrl = form.action;
        if (!submitUrl || submitUrl.includes('form_action_p_')) {
          submitUrl = originalUrl;
        }

        fetch(submitUrl, {
          method: 'POST',
          body: formData,
          redirect: 'follow',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
          .then(response => {
            const contentType = response.headers.get('content-type') || '';

            // Si la respuesta es JSON, procesarla
            if (contentType.includes('application/json')) {
              return response.json().then(data => {
                if (data.success) {
                  // Éxito - cerrar panel y refrescar
                  self.close();
                  self.showSuccessMessage(data.message || Drupal.t('Guardado correctamente'));
                  self.refreshCurrentPage();
                } else {
                  // Error desde el servidor
                  self.showErrorMessage(data.message || Drupal.t('Error al guardar'));
                  if (submitBtn) {
                    submitBtn.classList.remove('is-loading');
                    submitBtn.disabled = false;
                  }
                }
              });
            }

            // Si es HTML...
            if (response.ok) {
              return response.text().then(html => {
                // Verificar si el HTML contiene mensajes de error de Drupal
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const errorMessages = tempDiv.querySelector('.messages--error');

                if (errorMessages) {
                  // Errores de validación - mostrar formulario con errores
                  container.innerHTML = html;
                  Drupal.attachBehaviors(container);
                  self.attachFormSubmitHandler(container, originalUrl);
                  if (submitBtn) {
                    submitBtn.classList.remove('is-loading');
                    submitBtn.disabled = false;
                  }
                } else {
                  // Éxito - cerrar panel y refrescar página
                  self.close();
                  self.showSuccessMessage(Drupal.t('Guardado correctamente'));
                  self.refreshCurrentPage();
                }
              });
            } else {
              // Error HTTP - mostrar respuesta en el panel
              return response.text().then(html => {
                container.innerHTML = html;
                Drupal.attachBehaviors(container);
                self.attachFormSubmitHandler(container, originalUrl);
              });
            }
          })
          .catch(error => {
            self.showErrorMessage(Drupal.t('Error de conexión'));
            if (submitBtn) {
              submitBtn.classList.remove('is-loading');
              submitBtn.disabled = false;
            }
          });
      });
    },

    /**
     * Muestra mensaje de error temporal.
     */
    showErrorMessage: function (message) {
      const toast = document.createElement('div');
      toast.className = 'slide-panel-toast slide-panel-toast--error';
      toast.innerHTML = `
        <svg viewBox="0 0 24 24" width="20" height="20">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
          <line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>${message}</span>
      `;
      document.body.appendChild(toast);

      // Animación de entrada
      setTimeout(() => toast.classList.add('slide-panel-toast--visible'), 10);

      // Remover después de 5 segundos (más tiempo para errores)
      setTimeout(() => {
        toast.classList.remove('slide-panel-toast--visible');
        setTimeout(() => toast.remove(), 300);
      }, 5000);
    },

    /**
     * Muestra mensaje de éxito temporal.
     */
    showSuccessMessage: function (message) {
      const toast = document.createElement('div');
      toast.className = 'slide-panel-toast slide-panel-toast--success';
      toast.innerHTML = `
        <svg viewBox="0 0 24 24" width="20" height="20">
          <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
        </svg>
        <span>${message}</span>
      `;
      document.body.appendChild(toast);

      // Animación de entrada
      setTimeout(() => toast.classList.add('slide-panel-toast--visible'), 10);

      // Remover después de 3 segundos
      setTimeout(() => {
        toast.classList.remove('slide-panel-toast--visible');
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    },

    /**
     * Refresca la página actual.
     */
    refreshCurrentPage: function () {
      // Pequeño delay para que el toast sea visible
      setTimeout(() => {
        window.location.reload();
      }, 500);
    }
  };

})(Drupal, once);
