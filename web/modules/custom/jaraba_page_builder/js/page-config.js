/**
 * @file
 * Lógica del Slide-Panel "⚙ Configuración de Página" del Canvas Editor.
 *
 * PROPÓSITO:
 * Controla el panel lateral de configuración de página: carga de datos,
 * autosave debounced, contadores de caracteres, SERP preview, toggle de
 * secciones colapsables, y toggle de publicación.
 *
 * API:
 * - GET  /api/v1/pages/{id}/config  → Carga metadatos.
 * - PATCH /api/v1/pages/{id}/config → Guarda cambios con nueva revisión.
 *
 * @see page-config-panel.html.twig
 * @see \Drupal\jaraba_page_builder\Controller\PageConfigApiController
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior principal del panel de configuración de página.
   */
  Drupal.behaviors.jarabaPageConfig = {
    attach: function (context) {
      once('jaraba-page-config', '.canvas-editor', context).forEach(function () {
        const pageId = drupalSettings.canvasEditor?.pageId;
        if (!pageId) {
          return;
        }
        // Inicializar el controlador del panel.
        window.jarabaPageConfig = new PageConfigController(pageId);
      });
    },
  };

  /**
   * Controlador del panel de configuración de página.
   *
   * Gestiona la apertura/cierre del panel, carga de datos vía API,
   * autosave debounced, contadores de caracteres y SERP preview.
   */
  class PageConfigController {
    /**
     * Constructor.
     *
     * @param {number} pageId - ID de la entidad PageContent.
     */
    constructor(pageId) {
      this.pageId = pageId;
      this.apiUrl = `/api/v1/pages/${pageId}/config`;
      this.panel = document.getElementById('page-config-panel');
      this.saveIndicator = document.getElementById('page-config-save-indicator');
      this.saveTimeout = null;
      this.isOpen = false;
      this.data = {};

      if (!this.panel) {
        return;
      }

      this.bindEvents();
      this.initCharCounters();
      this.initSERPPreview();
    }

    // =====================================================================
    // PANEL: Apertura / Cierre
    // =====================================================================

    /**
     * Abre el panel y carga datos desde la API.
     */
    open() {
      this.panel.hidden = false;
      // Forzar reflow para la animación CSS.
      this.panel.offsetHeight;
      this.panel.classList.add('is-open');
      this.isOpen = true;
      document.body.classList.add('page-config-open');
      this.loadData();
      // Focus trap: primer input.
      setTimeout(() => {
        const firstInput = this.panel.querySelector('input, textarea, select');
        if (firstInput) {
          firstInput.focus();
        }
      }, 350);
    }

    /**
     * Cierra el panel con animación.
     */
    close() {
      this.panel.classList.remove('is-open');
      this.isOpen = false;
      document.body.classList.remove('page-config-open');
      setTimeout(() => {
        this.panel.hidden = true;
      }, 350);
    }

    /**
     * Alterna el panel (abrir/cerrar).
     */
    toggle() {
      if (this.isOpen) {
        this.close();
      }
      else {
        this.open();
      }
    }

    // =====================================================================
    // API: Carga y guardado
    // =====================================================================

    /**
     * Carga los datos de configuración de la página desde la API.
     */
    async loadData() {
      try {
        const response = await fetch(this.apiUrl, {
          headers: {
            'Accept': 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        this.data = await response.json();
        this.populateFields(this.data);
      }
      catch (error) {
        console.error('[PageConfig] Error al cargar datos:', error);
      }
    }

    /**
     * Guarda los datos modificados vía PATCH.
     *
     * @param {Object} fields - Campos a actualizar { field: value }.
     */
    async save(fields) {
      this.showSaveIndicator(false);

      try {
        const csrfToken = drupalSettings.canvasEditor?.csrfToken || '';
        const response = await fetch(this.apiUrl, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-Token': csrfToken,
          },
          body: JSON.stringify(fields),
        });

        if (!response.ok) {
          const errorData = await response.json();
          console.error('[PageConfig] Error al guardar:', errorData);
          this.showSaveIndicator(false);
          return;
        }

        const result = await response.json();
        this.data = result;
        this.showSaveIndicator(true);

        // Actualizar revisión info sin necesidad de recargar.
        this.updateRevisionInfo(result.revision);

        // Si el título cambió, actualizar el header del editor.
        if (fields.title) {
          const editorTitle = document.querySelector('.canvas-editor__title');
          if (editorTitle) {
            editorTitle.textContent = fields.title;
          }
        }
      }
      catch (error) {
        console.error('[PageConfig] Error de red al guardar:', error);
      }
    }

    /**
     * Autosave con debounce de 800ms.
     *
     * @param {string} field - Nombre del campo.
     * @param {*} value - Nuevo valor.
     */
    debouncedSave(field, value) {
      if (this.saveTimeout) {
        clearTimeout(this.saveTimeout);
      }
      this.saveTimeout = setTimeout(() => {
        this.save({ [field]: value });
      }, 800);
    }

    // =====================================================================
    // CAMPOS: Población y lectura
    // =====================================================================

    /**
     * Puebla los campos del panel con datos de la API.
     *
     * @param {Object} data - Datos de configuración de la página.
     */
    populateFields(data) {
      // General.
      this.setFieldValue('page-config-title', data.title || '');
      this.setFieldValue('page-config-slug', (data.path_alias || '').replace(/^\//, ''));
      this.setFieldValue('page-config-menu', data.menu_link || '');

      // Status toggle.
      const statusBtn = document.getElementById('page-config-status');
      if (statusBtn) {
        const isPublished = data.status;
        statusBtn.dataset.value = isPublished ? '1' : '0';
        statusBtn.setAttribute('aria-checked', isPublished ? 'true' : 'false');
        statusBtn.classList.toggle('is-published', isPublished);
        const label = document.getElementById('status-label');
        if (label) {
          label.textContent = isPublished
            ? Drupal.t('Publicado')
            : Drupal.t('Borrador');
        }
      }

      // SEO.
      this.setFieldValue('page-config-meta-title', data.meta_title || '');
      this.setFieldValue('page-config-meta-desc', data.meta_description || '');

      // Slug preview.
      this.updateSlugPreview(data.path_alias || '');

      // Revisiones.
      this.updateRevisionInfo(data.revision);

      // Timestamps.
      const createdEl = document.getElementById('page-config-created');
      if (createdEl) {
        createdEl.textContent = data.created || '—';
      }
      const changedEl = document.getElementById('page-config-changed');
      if (changedEl) {
        changedEl.textContent = data.changed || '—';
      }

      // Actualizar contadores y SERP.
      this.updateAllCounters();
      this.updateSERPPreview();
    }

    /**
     * Establece el valor de un campo de formulario.
     *
     * @param {string} id - ID del elemento.
     * @param {string} value - Valor a establecer.
     */
    setFieldValue(id, value) {
      const el = document.getElementById(id);
      if (el) {
        el.value = value;
      }
    }

    // =====================================================================
    // EVENTOS
    // =====================================================================

    /**
     * Vincula todos los eventos del panel.
     */
    bindEvents() {
      // Botón toggle del toolbar.
      const toggleBtn = document.getElementById('page-config-toggle-btn');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', () => this.toggle());
      }

      // Botones de cierre (overlay + botón X).
      this.panel.querySelectorAll('[data-action="close"]').forEach(el => {
        el.addEventListener('click', () => this.close());
      });

      // Escape para cerrar.
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.isOpen) {
          e.preventDefault();
          this.close();
        }
      });

      // Campos de texto — debounced save.
      const textFields = this.panel.querySelectorAll(
        'input[data-field], textarea[data-field], select[data-field]'
      );
      textFields.forEach(field => {
        const eventType = field.tagName === 'SELECT' ? 'change' : 'input';
        field.addEventListener(eventType, (e) => {
          const fieldName = e.target.dataset.field;
          let value = e.target.value;

          // Para slug, asegurar prefijo /.
          if (fieldName === 'path_alias') {
            value = '/' + value.replace(/^\//, '');
            this.updateSlugPreview(value);
          }

          this.debouncedSave(fieldName, value);

          // Actualizar contadores y SERP en tiempo real.
          this.updateCharCounter(e.target);
          this.updateSERPPreview();
        });
      });

      // Status toggle.
      const statusBtn = document.getElementById('page-config-status');
      if (statusBtn) {
        statusBtn.addEventListener('click', () => {
          const isPublished = statusBtn.dataset.value === '1';
          const newValue = !isPublished;
          statusBtn.dataset.value = newValue ? '1' : '0';
          statusBtn.setAttribute('aria-checked', newValue ? 'true' : 'false');
          statusBtn.classList.toggle('is-published', newValue);

          const label = document.getElementById('status-label');
          if (label) {
            label.textContent = newValue
              ? Drupal.t('Publicado')
              : Drupal.t('Borrador');
          }

          this.save({ status: newValue });
        });
      }

      // Secciones colapsables.
      this.panel.querySelectorAll('.page-config-panel__section-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => {
          const section = toggle.dataset.section;
          const content = document.getElementById(`section-${section}`);
          const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

          toggle.setAttribute('aria-expanded', !isExpanded ? 'true' : 'false');
          if (content) {
            content.classList.toggle('page-config-panel__section-content--collapsed', isExpanded);
          }
        });
      });
    }

    // =====================================================================
    // CONTADORES DE CARACTERES
    // =====================================================================

    /**
     * Inicializa los contadores de caracteres al cargar.
     */
    initCharCounters() {
      const metaTitle = document.getElementById('page-config-meta-title');
      const metaDesc = document.getElementById('page-config-meta-desc');

      if (metaTitle) {
        this.updateCharCounter(metaTitle);
      }
      if (metaDesc) {
        this.updateCharCounter(metaDesc);
      }
    }

    /**
     * Actualiza todos los contadores de caracteres.
     */
    updateAllCounters() {
      const metaTitle = document.getElementById('page-config-meta-title');
      const metaDesc = document.getElementById('page-config-meta-desc');

      if (metaTitle) {
        this.updateCharCounter(metaTitle);
      }
      if (metaDesc) {
        this.updateCharCounter(metaDesc);
      }
    }

    /**
     * Actualiza un contador de caracteres individual.
     *
     * @param {HTMLElement} field - Campo de entrada.
     */
    updateCharCounter(field) {
      const counterId = field.id === 'page-config-meta-title'
        ? 'meta-title-count'
        : field.id === 'page-config-meta-desc'
          ? 'meta-desc-count'
          : null;

      if (!counterId) {
        return;
      }

      const counter = document.getElementById(counterId);
      if (!counter) {
        return;
      }

      const length = field.value.length;
      const maxRecommended = parseInt(field.dataset.maxRecommended, 10) || 60;

      counter.textContent = length;

      // Clases de color según rango.
      counter.classList.remove('is-optimal', 'is-warning', 'is-over');
      if (length === 0) {
        // Sin clase — color neutro.
      }
      else if (length <= maxRecommended * 0.9) {
        counter.classList.add('is-optimal');
      }
      else if (length <= maxRecommended) {
        counter.classList.add('is-warning');
      }
      else {
        counter.classList.add('is-over');
      }

      // Actualizar score SEO.
      this.updateSEOScore();
    }

    // =====================================================================
    // SEO SCORE
    // =====================================================================

    /**
     * Calcula y muestra el score SEO basado en los metadatos.
     */
    updateSEOScore() {
      const badge = document.getElementById('seo-meta-score');
      if (!badge) {
        return;
      }

      let score = 0;
      let total = 0;

      // Meta título: 0-30 = mal, 30-60 = bien, 60+ = largo.
      const metaTitle = document.getElementById('page-config-meta-title');
      if (metaTitle) {
        total += 40;
        const len = metaTitle.value.length;
        if (len >= 30 && len <= 60) {
          score += 40;
        }
        else if (len > 0 && len < 70) {
          score += 20;
        }
      }

      // Meta descripción: 70-155 = bien, otro = parcial.
      const metaDesc = document.getElementById('page-config-meta-desc');
      if (metaDesc) {
        total += 40;
        const len = metaDesc.value.length;
        if (len >= 70 && len <= 155) {
          score += 40;
        }
        else if (len > 0) {
          score += 15;
        }
      }

      // Slug personalizado.
      const slug = document.getElementById('page-config-slug');
      if (slug) {
        total += 20;
        if (slug.value.length > 0) {
          score += 20;
        }
      }

      const percentage = total > 0 ? Math.round((score / total) * 100) : 0;
      badge.textContent = percentage;

      badge.classList.remove('score-good', 'score-ok', 'score-bad');
      if (percentage >= 80) {
        badge.classList.add('score-good');
      }
      else if (percentage >= 50) {
        badge.classList.add('score-ok');
      }
      else {
        badge.classList.add('score-bad');
      }
    }

    // =====================================================================
    // SERP PREVIEW
    // =====================================================================

    /**
     * Inicializa la vista previa SERP.
     */
    initSERPPreview() {
      this.updateSERPPreview();
    }

    /**
     * Actualiza la vista previa de Google (SERP).
     */
    updateSERPPreview() {
      const metaTitle = document.getElementById('page-config-meta-title');
      const metaDesc = document.getElementById('page-config-meta-desc');
      const slug = document.getElementById('page-config-slug');

      const serpTitle = document.getElementById('serp-title');
      const serpDesc = document.getElementById('serp-desc');
      const serpUrl = document.getElementById('serp-url');

      if (serpTitle && metaTitle) {
        serpTitle.textContent = metaTitle.value || document.getElementById('page-config-title')?.value || '';
      }

      if (serpDesc && metaDesc) {
        serpDesc.textContent = metaDesc.value || '';
      }

      if (serpUrl && slug) {
        const domain = window.location.hostname;
        const path = slug.value ? `/${slug.value}` : '/mi-pagina';
        serpUrl.textContent = `${domain} › ${path.replace(/\//g, ' › ').replace(/^ › /, '')}`;
      }
    }

    // =====================================================================
    // SLUG PREVIEW
    // =====================================================================

    /**
     * Actualiza la vista previa de la URL del slug.
     *
     * @param {string} slug - El path alias.
     */
    updateSlugPreview(slug) {
      const preview = document.getElementById('slug-preview');
      if (preview) {
        const domain = window.location.origin;
        preview.textContent = slug ? `${domain}${slug}` : '';
      }
    }

    // =====================================================================
    // REVISIONES
    // =====================================================================

    /**
     * Actualiza la información de revisión en el panel.
     *
     * @param {Object} revision - Datos de revisión.
     */
    updateRevisionInfo(revision) {
      if (!revision) {
        return;
      }

      const infoEl = document.getElementById('page-config-revision-info');
      if (infoEl) {
        if (revision.user && revision.date) {
          infoEl.textContent = `${revision.user} · ${revision.date}`;
        }
        else {
          infoEl.textContent = '—';
        }
      }

      const countEl = document.getElementById('page-config-revision-count');
      if (countEl && revision.count !== undefined) {
        countEl.textContent = revision.count;
      }
    }

    // =====================================================================
    // INDICADOR DE GUARDADO
    // =====================================================================

    /**
     * Muestra/oculta el indicador de guardado.
     *
     * @param {boolean} saved - Si se guardó exitosamente.
     */
    showSaveIndicator(saved) {
      if (!this.saveIndicator) {
        return;
      }

      this.saveIndicator.hidden = false;
      const textEl = this.saveIndicator.querySelector('.page-config-panel__save-text');

      if (saved) {
        this.saveIndicator.classList.add('is-saved');
        if (textEl) {
          textEl.textContent = Drupal.t('Guardado');
        }
        setTimeout(() => {
          this.saveIndicator.hidden = true;
          this.saveIndicator.classList.remove('is-saved');
          if (textEl) {
            textEl.textContent = Drupal.t('Guardando...');
          }
        }, 2000);
      }
      else {
        this.saveIndicator.classList.remove('is-saved');
        if (textEl) {
          textEl.textContent = Drupal.t('Guardando...');
        }
      }
    }
  }

})(Drupal, drupalSettings, once);
