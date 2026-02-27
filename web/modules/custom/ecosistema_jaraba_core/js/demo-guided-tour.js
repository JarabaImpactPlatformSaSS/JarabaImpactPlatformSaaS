/**
 * @file
 * Demo Guided Tour — Driver ligero sin dependencias externas.
 *
 * Lee la configuración del tour desde drupalSettings.demoTour (generada
 * por GuidedTourService::getTourDriverJS()) y renderiza popovers
 * posicionados sobre los elementos [data-tour-step].
 *
 * UX: Auto-start en primera visita al dashboard. El usuario puede
 * navegar con Siguiente/Anterior o cerrar con Escape/botón.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.demoGuidedTour = {
    attach(context) {
      var config = drupalSettings.demoTour;
      if (!config || !config.steps || !config.steps.length) {
        return;
      }

      once('demo-guided-tour', 'body', context).forEach(function () {
        // Breve delay para asegurar que el DOM está renderizado.
        setTimeout(function () {
          new DemoTour(config).start();
        }, 800);
      });
    },

    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('demo-guided-tour', 'body', context);
        // Limpiar popover si existe.
        var overlay = document.querySelector('.demo-tour-overlay');
        if (overlay) {
          overlay.remove();
        }
        var popover = document.querySelector('.demo-tour-popover');
        if (popover) {
          popover.remove();
        }
      }
    },
  };

  /**
   * Constructor del tour.
   */
  function DemoTour(config) {
    this.tourId = config.tourId || 'demo';
    this.steps = config.steps || [];
    this.showProgress = config.showProgress !== false;
    this.currentStep = 0;
    this.overlay = null;
    this.popover = null;
    this._onKeyDown = this._handleKeyDown.bind(this);
  }

  /**
   * Inicia el tour.
   */
  DemoTour.prototype.start = function () {
    if (!this.steps.length) {
      return;
    }

    this._injectStyles();
    this._createOverlay();
    this.currentStep = 0;
    this._showStep(0);

    document.addEventListener('keydown', this._onKeyDown);
  };

  /**
   * Muestra un paso del tour.
   */
  DemoTour.prototype._showStep = function (index) {
    if (index < 0 || index >= this.steps.length) {
      this._finish();
      return;
    }

    this.currentStep = index;
    var step = this.steps[index];
    var target = document.querySelector(step.element);

    // Scroll al target si existe.
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Quitar popover anterior.
    if (this.popover) {
      this.popover.remove();
    }

    // Highlight del target.
    this._highlightTarget(target);

    // Crear popover.
    var self = this;
    var popoverData = step.popover || {};
    var total = this.steps.length;

    this.popover = document.createElement('div');
    this.popover.className = 'demo-tour-popover';
    this.popover.setAttribute('role', 'dialog');
    this.popover.setAttribute('aria-label', popoverData.title || '');

    var html = '<div class="demo-tour-popover__header">';
    html += '<h4 class="demo-tour-popover__title">' + this._escape(popoverData.title || '') + '</h4>';
    html += '<button class="demo-tour-popover__close" type="button" aria-label="' + Drupal.t('Cerrar tour') + '">&times;</button>';
    html += '</div>';
    html += '<p class="demo-tour-popover__body">' + this._escape(popoverData.description || '') + '</p>';
    html += '<div class="demo-tour-popover__footer">';

    if (this.showProgress) {
      html += '<span class="demo-tour-popover__progress">' + (index + 1) + ' / ' + total + '</span>';
    }

    html += '<div class="demo-tour-popover__nav">';
    if (index > 0) {
      html += '<button class="demo-tour-btn demo-tour-btn--prev" type="button">' + Drupal.t('Anterior') + '</button>';
    }
    if (index < total - 1) {
      html += '<button class="demo-tour-btn demo-tour-btn--next" type="button">' + Drupal.t('Siguiente') + '</button>';
    } else {
      html += '<button class="demo-tour-btn demo-tour-btn--finish" type="button">' + Drupal.t('¡Entendido!') + '</button>';
    }
    html += '</div></div>';

    this.popover.innerHTML = html;
    document.body.appendChild(this.popover);

    // Posicionar respecto al target.
    this._positionPopover(target, popoverData.position || 'bottom');

    // Eventos.
    this.popover.querySelector('.demo-tour-popover__close').addEventListener('click', function () {
      self._finish();
    });

    var prevBtn = this.popover.querySelector('.demo-tour-btn--prev');
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        self._showStep(self.currentStep - 1);
      });
    }

    var nextBtn = this.popover.querySelector('.demo-tour-btn--next');
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        self._showStep(self.currentStep + 1);
      });
    }

    var finishBtn = this.popover.querySelector('.demo-tour-btn--finish');
    if (finishBtn) {
      finishBtn.addEventListener('click', function () {
        self._finish();
      });
    }

    // Focus al popover para accesibilidad.
    this.popover.focus();
  };

  /**
   * Posiciona el popover relativo al target.
   */
  DemoTour.prototype._positionPopover = function (target, position) {
    if (!target) {
      // Sin target: centrar en pantalla.
      this.popover.style.position = 'fixed';
      this.popover.style.top = '50%';
      this.popover.style.left = '50%';
      this.popover.style.transform = 'translate(-50%, -50%)';
      return;
    }

    var rect = target.getBoundingClientRect();
    var popRect = this.popover.getBoundingClientRect();
    var scrollY = window.scrollY || window.pageYOffset;
    var scrollX = window.scrollX || window.pageXOffset;
    var margin = 12;

    this.popover.style.position = 'absolute';

    var top, left;

    if (position === 'top') {
      top = rect.top + scrollY - popRect.height - margin;
      left = rect.left + scrollX + (rect.width / 2) - (popRect.width / 2);
    } else {
      // Default: bottom.
      top = rect.bottom + scrollY + margin;
      left = rect.left + scrollX + (rect.width / 2) - (popRect.width / 2);
    }

    // Clamp horizontal.
    left = Math.max(16, Math.min(left, window.innerWidth - popRect.width - 16));

    this.popover.style.top = top + 'px';
    this.popover.style.left = left + 'px';
    this.popover.style.transform = 'none';
  };

  /**
   * Resalta el target con spotlight en el overlay.
   */
  DemoTour.prototype._highlightTarget = function (target) {
    if (!this.overlay) {
      return;
    }

    if (!target) {
      this.overlay.style.display = 'none';
      return;
    }

    this.overlay.style.display = 'block';
    var rect = target.getBoundingClientRect();
    var padding = 8;

    // Usar box-shadow para crear efecto spotlight.
    target.classList.add('demo-tour-highlight');

    // Quitar highlight de otros.
    var prev = document.querySelectorAll('.demo-tour-highlight');
    prev.forEach(function (el) {
      if (el !== target) {
        el.classList.remove('demo-tour-highlight');
      }
    });
  };

  /**
   * Crea el overlay semitransparente.
   */
  DemoTour.prototype._createOverlay = function () {
    this.overlay = document.createElement('div');
    this.overlay.className = 'demo-tour-overlay';
    document.body.appendChild(this.overlay);

    var self = this;
    this.overlay.addEventListener('click', function () {
      self._finish();
    });
  };

  /**
   * Termina el tour.
   */
  DemoTour.prototype._finish = function () {
    document.removeEventListener('keydown', this._onKeyDown);

    // Quitar highlights.
    document.querySelectorAll('.demo-tour-highlight').forEach(function (el) {
      el.classList.remove('demo-tour-highlight');
    });

    if (this.popover) {
      this.popover.remove();
      this.popover = null;
    }
    if (this.overlay) {
      this.overlay.remove();
      this.overlay = null;
    }
  };

  /**
   * Maneja teclas: Escape cierra, flechas navegan.
   */
  DemoTour.prototype._handleKeyDown = function (e) {
    if (e.key === 'Escape') {
      this._finish();
    } else if (e.key === 'ArrowRight') {
      this._showStep(this.currentStep + 1);
    } else if (e.key === 'ArrowLeft' && this.currentStep > 0) {
      this._showStep(this.currentStep - 1);
    }
  };

  /**
   * Escapa HTML para prevenir XSS.
   */
  DemoTour.prototype._escape = function (str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  };

  /**
   * Inyecta estilos CSS del tour (self-contained, sin SCSS externo).
   */
  DemoTour.prototype._injectStyles = function () {
    if (document.getElementById('demo-tour-styles')) {
      return;
    }

    var style = document.createElement('style');
    style.id = 'demo-tour-styles';
    style.textContent = [
      '.demo-tour-overlay {',
      '  position: fixed; inset: 0; z-index: 9998;',
      '  background: rgba(0,0,0,0.45);',
      '  transition: opacity 0.3s ease;',
      '}',
      '.demo-tour-popover {',
      '  position: absolute; z-index: 10000;',
      '  width: min(400px, calc(100vw - 32px));',
      '  background: #fff; border-radius: 12px;',
      '  box-shadow: 0 8px 32px rgba(0,0,0,0.18);',
      '  padding: 0; overflow: hidden;',
      '  animation: demoTourIn 0.25s ease-out;',
      '  outline: none;',
      '}',
      '@keyframes demoTourIn {',
      '  from { opacity: 0; transform: translateY(8px); }',
      '  to { opacity: 1; transform: translateY(0); }',
      '}',
      '.demo-tour-popover__header {',
      '  display: flex; justify-content: space-between; align-items: center;',
      '  padding: 16px 20px 8px; gap: 8px;',
      '}',
      '.demo-tour-popover__title {',
      '  margin: 0; font-size: 1rem; font-weight: 600;',
      '  color: var(--color-azul-corporativo, #1a365d);',
      '}',
      '.demo-tour-popover__close {',
      '  background: none; border: none; font-size: 1.4rem;',
      '  cursor: pointer; color: #94a3b8; padding: 0 4px;',
      '  line-height: 1; transition: color 0.15s;',
      '}',
      '.demo-tour-popover__close:hover { color: #334155; }',
      '.demo-tour-popover__body {',
      '  margin: 0; padding: 0 20px 16px;',
      '  font-size: 0.9rem; line-height: 1.5; color: #475569;',
      '}',
      '.demo-tour-popover__footer {',
      '  display: flex; justify-content: space-between; align-items: center;',
      '  padding: 12px 20px; background: #f8fafc;',
      '  border-top: 1px solid #e2e8f0;',
      '}',
      '.demo-tour-popover__progress {',
      '  font-size: 0.8rem; color: #94a3b8; font-weight: 500;',
      '}',
      '.demo-tour-popover__nav { display: flex; gap: 8px; }',
      '.demo-tour-btn {',
      '  padding: 6px 16px; border-radius: 6px; font-size: 0.85rem;',
      '  font-weight: 500; cursor: pointer; border: none;',
      '  transition: background 0.15s, transform 0.1s;',
      '}',
      '.demo-tour-btn:active { transform: scale(0.97); }',
      '.demo-tour-btn--prev {',
      '  background: #e2e8f0; color: #475569;',
      '}',
      '.demo-tour-btn--prev:hover { background: #cbd5e1; }',
      '.demo-tour-btn--next, .demo-tour-btn--finish {',
      '  background: var(--color-azul-corporativo, #1a365d);',
      '  color: #fff;',
      '}',
      '.demo-tour-btn--next:hover, .demo-tour-btn--finish:hover {',
      '  background: var(--color-azul-corporativo-hover, #2a4a7f);',
      '}',
      '.demo-tour-highlight {',
      '  position: relative; z-index: 9999;',
      '  box-shadow: 0 0 0 4px rgba(26,54,93,0.3), 0 0 0 9999px rgba(0,0,0,0.45);',
      '  border-radius: 8px;',
      '  transition: box-shadow 0.3s ease;',
      '}',
    ].join('\n');

    document.head.appendChild(style);
  };

})(Drupal, drupalSettings, once);
