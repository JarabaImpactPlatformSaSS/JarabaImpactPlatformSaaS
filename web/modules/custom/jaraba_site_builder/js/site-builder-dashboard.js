/**
 * @file
 * Site Builder Dashboard Premium — JavaScript.
 *
 * Sprint B1: Animación de KPIs, preview iframe, y
 * gestión del panel derecho de vista previa.
 *
 * Cumple directrices:
 *   - Drupal.t() para textos.
 *   - Drupal.behaviors para inicialización.
 *   - once() para evitar doble attach.
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Behavior para el dashboard premium del Site Builder.
     */
    Drupal.behaviors.siteBuilderDashboard = {
        attach(context) {
            const elements = once('sb-dashboard', '.site-builder-dashboard', context);
            if (!elements.length) return;

            const dashboard = elements[0];
            const kpis = drupalSettings.jaraba_site_builder?.kpis || {};

            // Animar KPIs al cargar.
            this.animateKpis(dashboard, kpis);

            // Configurar preview al hacer click en páginas del tree.
            this.setupPreview(dashboard);
        },

        /**
         * Anima los valores de los KPI cards con count-up.
         *
         * @param {HTMLElement} dashboard Contenedor del dashboard.
         * @param {Object} kpis Datos de KPIs del backend.
         */
        animateKpis(dashboard, kpis) {
            const cards = dashboard.querySelectorAll('.site-kpi-card__value');

            cards.forEach((card) => {
                const targetValue = parseInt(card.dataset.value, 10);
                if (isNaN(targetValue) || targetValue === 0) {
                    card.textContent = card.dataset.value || '0';
                    return;
                }

                // Count-up animation.
                const duration = 800;
                const startTime = performance.now();

                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);

                    // Ease-out cubic.
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const currentValue = Math.round(eased * targetValue);

                    card.textContent = currentValue;

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                };

                requestAnimationFrame(animate);
            });

            // Fade-in stagger para cards.
            const cardElements = dashboard.querySelectorAll('.site-kpi-card');
            cardElements.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 + (index * 120));
            });
        },

        /**
         * Configura el panel de vista previa de páginas.
         *
         * Al hacer click en un nodo del árbol, carga la página
         * en el iframe de preview.
         *
         * @param {HTMLElement} dashboard Contenedor del dashboard.
         */
        setupPreview(dashboard) {
            const previewFrame = dashboard.querySelector('.site-preview__iframe');
            const previewTitle = dashboard.querySelector('.site-preview__page-title');
            const previewEmpty = dashboard.querySelector('.site-preview__empty');

            if (!previewFrame) return;

            // Escuchar clicks en los nodos del tree.
            dashboard.addEventListener('click', (e) => {
                const treeNode = e.target.closest('[data-page-url]');
                if (!treeNode) return;

                const pageUrl = treeNode.dataset.pageUrl;
                const pageTitle = treeNode.dataset.pageTitle || '';

                if (pageUrl) {
                    previewFrame.src = pageUrl;
                    previewFrame.hidden = false;

                    if (previewTitle) {
                        previewTitle.textContent = pageTitle;
                    }
                    if (previewEmpty) {
                        previewEmpty.hidden = true;
                    }

                    // Marcar nodo activo.
                    dashboard.querySelectorAll('.site-tree-node--preview-active')
                        .forEach(n => n.classList.remove('site-tree-node--preview-active'));
                    treeNode.closest('.site-tree-node')?.classList.add('site-tree-node--preview-active');
                }
            });
        },
    };

})(Drupal, drupalSettings, once);
