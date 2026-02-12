/**
 * @file
 * JavaScript del marketplace AgroConecta.
 *
 * Gestiona la interactividad del marketplace: filtros y búsqueda.
 * Usa Drupal.t() para textos traducibles y once() para prevenir
 * duplicados de comportamiento.
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Behavior del marketplace AgroConecta.
     */
    Drupal.behaviors.agroconectaMarketplace = {
        attach: function (context) {
            // Inicializar filtros del marketplace
            once('agro-marketplace-filters', '.agro-marketplace-filters', context).forEach(function (container) {
                const buttons = container.querySelectorAll('.agro-filter-btn');

                buttons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        // Remover clase activa de todos los botones
                        buttons.forEach(function (btn) {
                            btn.classList.remove('agro-filter-btn--active');
                        });

                        // Añadir clase activa al botón clicado
                        button.classList.add('agro-filter-btn--active');

                        const filter = button.getAttribute('data-filter');
                        Drupal.agroconecta.filterProducts(filter);
                    });
                });
            });

            // Inicializar lazy loading de imágenes
            once('agro-lazy-images', '.agro-product-card__image[loading="lazy"]', context).forEach(function (img) {
                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                const image = entry.target;
                                if (image.dataset.src) {
                                    image.src = image.dataset.src;
                                }
                                observer.unobserve(image);
                            }
                        });
                    });
                    observer.observe(img);
                }
            });
        }
    };

    /**
     * Namespace para funciones del marketplace.
     */
    Drupal.agroconecta = Drupal.agroconecta || {};

    /**
     * Filtra productos por categoría.
     *
     * @param {string} filter - Categoría a filtrar.
     */
    Drupal.agroconecta.filterProducts = function (filter) {
        const cards = document.querySelectorAll('.agro-product-card');
        cards.forEach(function (card) {
            if (filter === 'all') {
                card.style.display = '';
            } else {
                const category = card.getAttribute('data-category');
                card.style.display = (category === filter) ? '' : 'none';
            }
        });
    };

})(Drupal, once);
