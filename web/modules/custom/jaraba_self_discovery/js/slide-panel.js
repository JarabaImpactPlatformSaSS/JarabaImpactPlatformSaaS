/**
 * @file
 * Slide Panel - Componente de panel deslizante.
 *
 * PROPÓSITO:
 * Gestiona apertura/cierre de paneles laterales para CRUD.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.slidePanel = {
        attach: function (context) {
            once('slide-panel', '.slide-panel', context).forEach(function (panel) {
                const panelId = panel.id;
                const overlay = panel.querySelector('.slide-panel__overlay');
                const closeButtons = panel.querySelectorAll('[data-close-panel]');

                // Cerrar con overlay.
                if (overlay) {
                    overlay.addEventListener('click', function () {
                        Drupal.behaviors.slidePanel.close(panelId);
                    });
                }

                // Cerrar con botones.
                closeButtons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        Drupal.behaviors.slidePanel.close(panelId);
                    });
                });

                // Cerrar con ESC.
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && panel.classList.contains('slide-panel--open')) {
                        Drupal.behaviors.slidePanel.close(panelId);
                    }
                });
            });
        },

        /**
         * Abre un slide panel.
         */
        open: function (panelId) {
            const panel = document.getElementById(panelId);
            if (panel) {
                panel.classList.add('slide-panel--open');
                panel.setAttribute('aria-hidden', 'false');
                document.body.classList.add('slide-panel-open');

                // Focus trap.
                const firstFocusable = panel.querySelector('input, button, select, textarea');
                if (firstFocusable) firstFocusable.focus();
            }
        },

        /**
         * Cierra un slide panel.
         */
        close: function (panelId) {
            const panel = document.getElementById(panelId);
            if (panel) {
                panel.classList.remove('slide-panel--open');
                panel.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('slide-panel-open');
            }
        },

        /**
         * Carga contenido en el body del panel.
         */
        loadContent: function (panelId, content) {
            const body = document.getElementById(panelId + '-body');
            if (body) {
                body.innerHTML = content;
            }
        }
    };

})(Drupal, once);
