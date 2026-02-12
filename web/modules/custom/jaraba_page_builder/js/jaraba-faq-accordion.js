/**
 * @file
 * Jaraba FAQ Accordion interactivity.
 *
 * This script handles the expand/collapse functionality for FAQ Accordions
 * created with the Page Builder. It uses event delegation for efficiency
 * and works both in the GrapesJS editor iframe and on public pages.
 *
 * @see https://grapesjs.com/docs/modules/Components-js.html
 */

(function (Drupal) {
    'use strict';

    /**
     * Initialize FAQ Accordion interactivity.
     *
     * @param {Element} context - The DOM context to search within.
     */
    function initFaqAccordions(context) {
        const faqContainers = context.querySelectorAll('.jaraba-faq');

        faqContainers.forEach(function (container) {
            // Skip if already initialized
            if (container.dataset.jarabaFaqInit) {
                return;
            }
            container.dataset.jarabaFaqInit = 'true';

            // Use event delegation on the container
            container.addEventListener('click', function (event) {
                const toggle = event.target.closest('.jaraba-faq__toggle');
                if (!toggle) {
                    return;
                }

                const item = toggle.closest('.jaraba-faq__item');
                if (!item) {
                    return;
                }

                const answer = item.querySelector('.jaraba-faq__answer');
                const icon = toggle.querySelector('span');

                if (!answer) {
                    return;
                }

                // Toggle the open state
                const isOpen = item.classList.toggle('jaraba-faq__item--open');

                // Update icon
                if (icon) {
                    icon.textContent = isOpen ? '−' : '+';
                }

                // Animate max-height
                if (isOpen) {
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                } else {
                    answer.style.maxHeight = '0';
                }
            });
        });
    }

    // Drupal behavior for public pages
    Drupal.behaviors.jarabaFaqAccordion = {
        attach: function (context) {
            initFaqAccordions(context);
        }
    };

    // Also initialize immediately for non-Drupal contexts (like GrapesJS iframe)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initFaqAccordions(document);
        });
    } else {
        initFaqAccordions(document);
    }

    // Expose for GrapesJS to call in iframe
    window.jarabaInitFaqAccordions = initFaqAccordions;

})(Drupal);
