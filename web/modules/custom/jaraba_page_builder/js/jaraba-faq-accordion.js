/**
 * @file
 * Jaraba FAQ Accordion interactivity.
 *
 * Handles expand/collapse functionality for FAQ Accordions created with the
 * Page Builder. Uses once() for idempotent initialization, event delegation
 * for efficiency, keyboard navigation (Enter/Space), and ARIA attributes
 * for WCAG 2.1 AA compliance.
 *
 * Works both in the GrapesJS editor iframe and on public pages.
 *
 * @see https://grapesjs.com/docs/modules/Components-js.html
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Toggle a single FAQ item open/closed.
     *
     * @param {Element} item - The .jaraba-faq__item element.
     */
    function toggleFaqItem(item) {
        var toggle = item.querySelector('.jaraba-faq__toggle');
        var answer = item.querySelector('.jaraba-faq__answer');
        var icon = toggle ? toggle.querySelector('.jaraba-faq__icon') : null;

        if (!toggle || !answer) {
            return;
        }

        var isOpen = item.classList.toggle('jaraba-faq__item--open');

        // ARIA: actualizar estado expandido
        toggle.setAttribute('aria-expanded', String(isOpen));
        answer.setAttribute('aria-hidden', String(!isOpen));

        // Icono visual
        if (icon) {
            icon.textContent = isOpen ? '−' : '+';
        }

        // Animación max-height
        if (isOpen) {
            answer.style.maxHeight = answer.scrollHeight + 'px';
        } else {
            answer.style.maxHeight = '0';
        }
    }

    /**
     * Initialize ARIA attributes on a FAQ container.
     *
     * @param {Element} container - The .jaraba-faq element.
     */
    function initAriaAttributes(container) {
        var items = container.querySelectorAll('.jaraba-faq__item');

        items.forEach(function (item, index) {
            var toggle = item.querySelector('.jaraba-faq__toggle');
            var answer = item.querySelector('.jaraba-faq__answer');

            if (!toggle || !answer) {
                return;
            }

            // IDs únicos para aria-controls
            var answerId = answer.id || ('jaraba-faq-answer-' + Date.now() + '-' + index);
            answer.id = answerId;

            // Button semantics y ARIA
            toggle.setAttribute('role', 'button');
            toggle.setAttribute('tabindex', '0');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-controls', answerId);

            // Answer region
            answer.setAttribute('role', 'region');
            answer.setAttribute('aria-hidden', 'true');
            answer.setAttribute('aria-labelledby', toggle.id || '');

            // Si ya está abierto por defecto
            if (item.classList.contains('jaraba-faq__item--open')) {
                toggle.setAttribute('aria-expanded', 'true');
                answer.setAttribute('aria-hidden', 'false');
            }
        });
    }

    // Drupal behavior para páginas públicas
    Drupal.behaviors.jarabaFaqAccordion = {
        attach: function (context) {
            var containers = once('jaraba-faq-accordion', '.jaraba-faq', context);

            containers.forEach(function (container) {
                // Inicializar atributos ARIA
                initAriaAttributes(container);

                // Delegación de eventos: click
                container.addEventListener('click', function (event) {
                    var toggle = event.target.closest('.jaraba-faq__toggle');
                    if (!toggle) {
                        return;
                    }

                    var item = toggle.closest('.jaraba-faq__item');
                    if (item) {
                        toggleFaqItem(item);
                    }
                });

                // Delegación de eventos: keyboard (Enter/Space)
                container.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    var toggle = event.target.closest('.jaraba-faq__toggle');
                    if (!toggle) {
                        return;
                    }

                    event.preventDefault();
                    var item = toggle.closest('.jaraba-faq__item');
                    if (item) {
                        toggleFaqItem(item);
                    }
                });
            });
        }
    };

    // Inicialización inmediata para contextos sin Drupal (iframe GrapesJS)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            Drupal.behaviors.jarabaFaqAccordion.attach(document);
        });
    } else {
        Drupal.behaviors.jarabaFaqAccordion.attach(document);
    }

    // Exponer para que GrapesJS llame desde el iframe
    window.jarabaInitFaqAccordions = function (context) {
        Drupal.behaviors.jarabaFaqAccordion.attach(context || document);
    };

})(Drupal, once);
