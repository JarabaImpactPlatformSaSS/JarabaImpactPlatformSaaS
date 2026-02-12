/**
 * @file
 * JavaScript del Dashboard de Knowledge Training.
 *
 * Placeholder inicial. La interactividad avanzada se desarrollará
 * en una fase posterior.
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Behavior para el dashboard de Knowledge Training.
     */
    Drupal.behaviors.knowledgeDashboard = {
        attach: function (context) {
            // Animar los progress rings al cargar.
            const progressRings = once('progress-ring-anim', '.progress-ring', context);

            progressRings.forEach((ring) => {
                const fill = ring.querySelector('.progress-ring__fill');
                if (fill) {
                    // Forzar reflow para reiniciar la animación.
                    fill.style.strokeDashoffset = '339.292';
                    requestAnimationFrame(() => {
                        fill.style.strokeDashoffset = fill.getAttribute('stroke-dashoffset');
                    });
                }
            });

            console.log('[KnowledgeDashboard] Dashboard inicializado.');
        },
    };

})(Drupal, once);
