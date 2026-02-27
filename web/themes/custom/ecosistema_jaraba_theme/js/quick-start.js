/**
 * @file
 * Quick-Start Overlay — First-visit welcome modal with contextual actions.
 *
 * Shows 3 quick actions based on user vertical on first visit.
 * Dismissed state tracked via localStorage to show only once.
 *
 * DIRECTRICES:
 * - DRUPAL-BEHAVIORS-001: Drupal.behaviors.quickStart
 * - ONCE-PATTERN-001: once('quick-start')
 * - INNERHTML-XSS-001: textContent for user data, checkPlain for dynamic text
 * - MODAL-ACCESSIBILITY-001: Esc close, focus trap, focus restoration
 * - i18n: Drupal.t() for all UI text
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var STORAGE_KEY = 'jaraba_quick_start_dismissed';

  /**
   * Quick-start actions per vertical.
   * Each vertical defines 3 priority actions with icon, label, description, and URL.
   */
  function getActionsForVertical(vertical) {
    var actions = {
      emprendimiento: [
        {
          icon: 'rocket_launch',
          label: Drupal.t('Crear mi primer proyecto'),
          description: Drupal.t('Define tu idea de negocio y empieza a medir tu impacto.'),
          url: '/node/add/project'
        },
        {
          icon: 'person',
          label: Drupal.t('Completar mi perfil'),
          description: Drupal.t('Anade tu foto, bio y habilidades para destacar.'),
          url: '/mi-cuenta/editar'
        },
        {
          icon: 'explore',
          label: Drupal.t('Explorar el marketplace'),
          description: Drupal.t('Descubre servicios y recursos para tu emprendimiento.'),
          url: '/marketplace'
        }
      ],
      empleabilidad: [
        {
          icon: 'description',
          label: Drupal.t('Completar mi perfil profesional'),
          description: Drupal.t('Tu CV digital: experiencia, formacion y habilidades.'),
          url: '/mi-cuenta/editar'
        },
        {
          icon: 'work',
          label: Drupal.t('Explorar ofertas de empleo'),
          description: Drupal.t('Encuentra oportunidades alineadas con tu perfil.'),
          url: '/empleabilidad/ofertas'
        },
        {
          icon: 'school',
          label: Drupal.t('Ver formaciones disponibles'),
          description: Drupal.t('Mejora tus competencias con cursos especializados.'),
          url: '/formacion'
        }
      ],
      agroconecta: [
        {
          icon: 'grass',
          label: Drupal.t('Registrar mi primera parcela'),
          description: Drupal.t('Anade tu explotacion para gestionar cultivos y produccion.'),
          url: '/agroconecta/parcelas/add'
        },
        {
          icon: 'store',
          label: Drupal.t('Publicar en el marketplace'),
          description: Drupal.t('Vende tus productos directamente a compradores.'),
          url: '/marketplace/add'
        },
        {
          icon: 'groups',
          label: Drupal.t('Conectar con la comunidad'),
          description: Drupal.t('Unete a grupos de productores de tu zona.'),
          url: '/comunidad'
        }
      ],
      default: [
        {
          icon: 'person',
          label: Drupal.t('Completar mi perfil'),
          description: Drupal.t('Personaliza tu cuenta para sacar el maximo partido.'),
          url: '/mi-cuenta/editar'
        },
        {
          icon: 'dashboard',
          label: Drupal.t('Explorar el dashboard'),
          description: Drupal.t('Descubre todas las herramientas disponibles.'),
          url: '/dashboard'
        },
        {
          icon: 'smart_toy',
          label: Drupal.t('Probar el Copilot IA'),
          description: Drupal.t('Tu asistente inteligente para resolver dudas al instante.'),
          url: '#copilot-toggle'
        }
      ]
    };

    return actions[vertical] || actions['default'];
  }

  Drupal.behaviors.quickStart = {
    attach: function (context) {
      var overlays = once('quick-start', '#quick-start-overlay', context);
      if (!overlays.length) {
        return;
      }

      var overlay = overlays[0];

      // Check if already dismissed.
      try {
        if (localStorage.getItem(STORAGE_KEY) === 'true') {
          return;
        }
      }
      catch (e) {
        // localStorage unavailable — don't show.
        return;
      }

      var content = overlay.querySelector('.ej-quick-start__content');
      var closeBtn = overlay.querySelector('.ej-quick-start__close');
      var skipBtn = document.getElementById('quick-start-skip');
      var actionsContainer = document.getElementById('quick-start-actions');
      var previousFocus = null;

      // Detect vertical from drupalSettings or body class.
      var vertical = 'default';
      if (drupalSettings.jarabaWizard && drupalSettings.jarabaWizard.vertical) {
        vertical = drupalSettings.jarabaWizard.vertical;
      }
      else {
        // Fallback: detect from body classes.
        var bodyClasses = document.body.className;
        if (bodyClasses.indexOf('vertical-emprendimiento') !== -1) {
          vertical = 'emprendimiento';
        }
        else if (bodyClasses.indexOf('vertical-empleabilidad') !== -1) {
          vertical = 'empleabilidad';
        }
        else if (bodyClasses.indexOf('vertical-agroconecta') !== -1) {
          vertical = 'agroconecta';
        }
      }

      // Render actions.
      var actions = getActionsForVertical(vertical);
      renderActions(actionsContainer, actions);

      // Show overlay with small delay for page to settle.
      setTimeout(function () {
        previousFocus = document.activeElement;
        overlay.removeAttribute('hidden');
        // Focus the first action for keyboard accessibility.
        var firstAction = overlay.querySelector('.ej-quick-start__action');
        if (firstAction) {
          firstAction.focus();
        }
      }, 500);

      // Close handlers.
      function dismiss() {
        overlay.setAttribute('hidden', '');
        try {
          localStorage.setItem(STORAGE_KEY, 'true');
        }
        catch (e) {
          // Fail silently.
        }
        // Restore focus.
        if (previousFocus && previousFocus.focus) {
          previousFocus.focus();
        }
      }

      if (closeBtn) {
        closeBtn.addEventListener('click', dismiss);
      }
      if (skipBtn) {
        skipBtn.addEventListener('click', dismiss);
      }

      // Backdrop click closes.
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay || e.target.classList.contains('ej-quick-start__backdrop')) {
          dismiss();
        }
      });

      // Escape key closes.
      overlay.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          e.preventDefault();
          dismiss();
        }
        // Focus trap: Tab wraps within overlay.
        if (e.key === 'Tab') {
          var focusable = content.querySelectorAll(
            'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
          );
          if (focusable.length === 0) return;
          var first = focusable[0];
          var last = focusable[focusable.length - 1];
          if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
          }
          else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
          }
        }
      });

      // Action click — navigate and dismiss.
      actionsContainer.addEventListener('click', function (e) {
        var actionEl = e.target.closest('.ej-quick-start__action');
        if (!actionEl) return;

        e.preventDefault();
        var url = actionEl.getAttribute('href');
        dismiss();

        if (url && url.charAt(0) === '#') {
          // Special action (e.g., copilot toggle).
          document.dispatchEvent(new CustomEvent('commandBar:action', { detail: { action: url } }));
        }
        else if (url) {
          window.location.href = url;
        }
      });
    }
  };

  /**
   * Renders action items into the container via DOM API (XSS safe).
   */
  function renderActions(container, actions) {
    container.innerHTML = '';

    actions.forEach(function (action) {
      var link = document.createElement('a');
      link.className = 'ej-quick-start__action';
      link.href = action.url;

      // Icon.
      var iconWrap = document.createElement('div');
      iconWrap.className = 'ej-quick-start__action-icon';
      var icon = document.createElement('span');
      icon.className = 'material-icons';
      icon.textContent = action.icon;
      iconWrap.appendChild(icon);

      // Text.
      var textWrap = document.createElement('div');
      textWrap.className = 'ej-quick-start__action-text';
      var label = document.createElement('span');
      label.className = 'ej-quick-start__action-label';
      label.textContent = action.label;
      var desc = document.createElement('span');
      desc.className = 'ej-quick-start__action-desc';
      desc.textContent = action.description;
      textWrap.appendChild(label);
      textWrap.appendChild(desc);

      // Arrow.
      var arrow = document.createElement('span');
      arrow.className = 'ej-quick-start__action-arrow';
      arrow.textContent = '\u203A'; // Single right-pointing angle quotation mark.

      link.appendChild(iconWrap);
      link.appendChild(textWrap);
      link.appendChild(arrow);
      container.appendChild(link);
    });
  }

})(Drupal, drupalSettings, once);
