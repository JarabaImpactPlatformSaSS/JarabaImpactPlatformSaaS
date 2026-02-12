/**
 * @file
 * Jaraba Canvas Editor - Tour de Onboarding Interactivo.
 *
 * Plugin GrapesJS que implementa un tour guiado para nuevos usuarios
 * del Canvas Editor usando Driver.js v1.x.
 *
 * COMPORTAMIENTO:
 * - Se activa automáticamente en el primer uso (localStorage + backend).
 * - Botón "?" en toolbar para relanzar en cualquier momento.
 * - Respeta prefers-reduced-motion.
 * - Todos los textos traducibles con Drupal.t().
 *
 * PERSISTENCE:
 * - localStorage: `jaraba_onboarding_completed` (rápida, sin latencia).
 * - Backend: POST /api/v1/page-builder/onboarding (persistencia cruzada).
 *
 * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §3
 * @see https://driverjs.com/docs/installation
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    // -----------------------------------------------------------------------
    // CONSTANTES
    // -----------------------------------------------------------------------

    const STORAGE_KEY = 'jaraba_onboarding_completed';
    const API_ENDPOINT = '/api/v1/page-builder/onboarding';

    // -----------------------------------------------------------------------
    // DEFINICIÓN DE PASOS DEL TOUR
    // -----------------------------------------------------------------------

    /**
     * Genera los pasos del tour con textos traducidos.
     *
     * @return {Array} Lista de pasos para Driver.js.
     */
    function getTourSteps() {
        return [
            {
                element: '#gjs-blocks-container',
                popover: {
                    title: Drupal.t('Bloques disponibles'),
                    description: Drupal.t('Arrastra cualquier bloque al canvas para añadirlo a tu página. Tienes más de 60 bloques organizados por categoría: Hero, Contenido, CTA, Precios y más.'),
                    side: 'right',
                    align: 'start',
                },
            },
            {
                element: '.gjs-frame',
                popover: {
                    title: Drupal.t('Tu canvas de edición'),
                    description: Drupal.t('Este es tu lienzo visual. Arrastra bloques aquí, haz clic en cualquier texto para editarlo directamente, y reorganiza secciones con drag & drop.'),
                    side: 'left',
                    align: 'center',
                },
            },
            {
                element: '#gjs-traits-container',
                popover: {
                    title: Drupal.t('Propiedades del bloque'),
                    description: Drupal.t('Selecciona un bloque y personaliza sus propiedades: enlaces, textos alternativos, configuración de formularios y más.'),
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#gjs-styles-container',
                popover: {
                    title: Drupal.t('Estilos visuales'),
                    description: Drupal.t('Ajusta colores, tipografías, espaciados y fondos de cada bloque. Los cambios se aplican en tiempo real.'),
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '.canvas-editor__viewport-btn[data-viewport="mobile"], .canvas-editor__viewport-toggle',
                popover: {
                    title: Drupal.t('Vista responsive'),
                    description: Drupal.t('Previsualiza tu página en diferentes dispositivos: escritorio, tablet y móvil. Asegúrate de que se ve perfecto en todas las pantallas.'),
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '.jaraba-seo-toggle, [data-cmd="jaraba:seo"]',
                popover: {
                    title: Drupal.t('Auditoría SEO'),
                    description: Drupal.t('Activa la auditoría SEO en tiempo real para verificar que tu página cumple con las mejores prácticas: H1 único, textos alt, jerarquía de títulos y más.'),
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-cmd="jaraba:save"], .canvas-editor__btn--save',
                popover: {
                    title: Drupal.t('Guardar tu trabajo'),
                    description: Drupal.t('Guarda tu página con este botón o usa Ctrl+S. El autoguardado también protege tu trabajo cada 30 segundos.'),
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '.canvas-editor__preview, [data-cmd="jaraba:preview"]',
                popover: {
                    title: Drupal.t('Vista previa'),
                    description: Drupal.t('Previsualiza tu página tal como la verán tus visitantes. ¡Tu diseño está listo para publicar!'),
                    side: 'bottom',
                    align: 'center',
                },
            },
        ];
    }

    // -----------------------------------------------------------------------
    // SERVICIO DE PERSISTENCIA
    // -----------------------------------------------------------------------

    /**
     * Verifica si el tour ya fue completado.
     *
     * @return {boolean} True si ya fue completado.
     */
    function isTourCompleted() {
        return localStorage.getItem(STORAGE_KEY) === 'true';
    }

    /**
     * Marca el tour como completado en localStorage y backend.
     */
    function markTourCompleted() {
        localStorage.setItem(STORAGE_KEY, 'true');

        // Persistir en backend de forma asíncrona (fire-and-forget).
        try {
            fetch(API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ completed: true }),
            }).catch(() => {
                // Silencioso — localStorage ya tiene el valor.
            });
        }
        catch (e) {
            // Fallback silencioso.
        }
    }

    /**
     * Resetea el estado del tour (para relanzar).
     */
    function resetTourState() {
        localStorage.removeItem(STORAGE_KEY);
    }

    /**
     * Sincroniza el estado con el backend al iniciar.
     *
     * Si el backend dice que el tour fue completado pero localStorage no,
     * actualiza localStorage para mantener consistencia.
     */
    async function syncStateFromBackend() {
        try {
            const response = await fetch(API_ENDPOINT, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.ok) {
                const data = await response.json();
                if (data.completed && !isTourCompleted()) {
                    localStorage.setItem(STORAGE_KEY, 'true');
                }
            }
        }
        catch (e) {
            // Silencioso — usa localStorage como fallback.
        }
    }

    // -----------------------------------------------------------------------
    // MOTOR DEL TOUR
    // -----------------------------------------------------------------------

    /**
     * Inicia el tour de onboarding con Driver.js.
     *
     * @param {boolean} force Si true, inicia aunque ya esté completado.
     */
    function startTour(force = false) {
        // Verificar si Driver.js está disponible.
        if (typeof window.driver === 'undefined' && typeof window.Driver === 'undefined') {
            console.warn('[Jaraba Onboarding] Driver.js no está disponible.');
            return;
        }

        // Si ya completó y no es forzado, no mostrar.
        if (!force && isTourCompleted()) {
            return;
        }

        // Respetar prefers-reduced-motion.
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Filtrar pasos a solo los elementos que existen en el DOM.
        const allSteps = getTourSteps();
        const validSteps = allSteps.filter(step => {
            // Soportar selectores múltiples separados por coma.
            const selectors = step.element.split(',').map(s => s.trim());
            return selectors.some(sel => document.querySelector(sel));
        }).map(step => {
            // Usar el primer selector que exista.
            const selectors = step.element.split(',').map(s => s.trim());
            const validSelector = selectors.find(sel => document.querySelector(sel));
            return { ...step, element: validSelector };
        });

        if (validSteps.length < 3) {
            console.warn('[Jaraba Onboarding] Insuficientes elementos en el DOM para el tour.');
            return;
        }

        // Crear instancia de Driver.js.
        const driverFactory = window.driver?.js?.driver || window.driver;
        if (!driverFactory) {
            console.warn('[Jaraba Onboarding] No se pudo inicializar Driver.js.');
            return;
        }

        const driverInstance = driverFactory({
            // Configuración visual.
            showProgress: true,
            animate: !prefersReducedMotion,
            overlayColor: 'rgba(0, 0, 0, 0.6)',
            stagePadding: 8,
            stageRadius: 12,
            popoverClass: 'jaraba-onboarding__popover',

            // Textos i18n.
            nextBtnText: Drupal.t('Siguiente'),
            prevBtnText: Drupal.t('Anterior'),
            doneBtnText: Drupal.t('¡Entendido!'),
            progressText: Drupal.t('{{current}} de {{total}}'),

            // Steps.
            steps: validSteps,

            // Callbacks.
            onDestroyStarted: () => {
                // Marcar como completado al cerrar o al terminar.
                markTourCompleted();
                driverInstance.destroy();
            },
        });

        // Iniciar tour con un pequeño delay para que el DOM se estabilice.
        setTimeout(() => {
            driverInstance.drive();
        }, 800);
    }

    // -----------------------------------------------------------------------
    // BOTÓN DE RELANZAR TOUR
    // -----------------------------------------------------------------------

    /**
     * Añade el botón "?" al toolbar del Canvas Editor para relanzar el tour.
     *
     * @return {boolean} True si el botón fue añadido o ya existía.
     */
    function addHelpButton() {
        // Buscar el toolbar del canvas editor (expandido para más selectores).
        const toolbar = document.querySelector(
            '.canvas-editor__toolbar-right, .canvas-editor__toolbar, .canvas-editor__header-actions'
        );

        if (!toolbar) {
            return false;
        }

        // Verificar si ya existe el botón.
        if (toolbar.querySelector('.jaraba-onboarding__help-btn')) {
            return true;
        }

        const helpBtn = document.createElement('button');
        helpBtn.className = 'jaraba-onboarding__help-btn';
        helpBtn.type = 'button';
        helpBtn.setAttribute('title', Drupal.t('Tour de ayuda'));
        helpBtn.setAttribute('aria-label', Drupal.t('Iniciar tour de ayuda del editor'));
        helpBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        `;

        helpBtn.addEventListener('click', () => {
            resetTourState();
            startTour(true);
        });

        // Insertar al principio del toolbar-right (antes de los otros botones).
        if (toolbar.firstChild) {
            toolbar.insertBefore(helpBtn, toolbar.firstChild);
        } else {
            toolbar.appendChild(helpBtn);
        }

        return true;
    }

    // -----------------------------------------------------------------------
    // DRUPAL BEHAVIOR
    // -----------------------------------------------------------------------

    Drupal.behaviors.jarabaOnboarding = {
        attach: function (context) {
            // Ampliar selector: buscar canvas-editor, gjs-editor, o body como fallback.
            var targets = once('jaraba-onboarding', '.canvas-editor, #gjs-editor, body', context);
            if (!targets.length) return;

            // Verificar que realmente estamos en el Canvas Editor.
            if (!document.querySelector('.canvas-editor') && !document.querySelector('#gjs-editor')) return;

            // 1. Sincronizar estado con backend.
            syncStateFromBackend().then(function () {
                // 2. Añadir botón de ayuda al toolbar (con retry).
                function tryAddButton() {
                    var added = addHelpButton();
                    if (!added) {
                        // Reintentar hasta 5 veces con delays.
                        var attempts = 0;
                        var interval = setInterval(function () {
                            attempts++;
                            if (addHelpButton() || attempts >= 5) {
                                clearInterval(interval);
                            }
                        }, 1000);
                    }
                }

                // Delay para esperar que el toolbar esté renderizado.
                setTimeout(tryAddButton, 500);

                // 3. Si es primer uso, iniciar tour tras un delay.
                if (!isTourCompleted()) {
                    // Esperar a que GrapesJS termine de cargar.
                    const waitForEditor = setInterval(() => {
                        const canvas = document.querySelector('.gjs-frame');
                        const blocks = document.querySelector('#gjs-blocks-container .gjs-block');
                        if (canvas && blocks) {
                            clearInterval(waitForEditor);
                            startTour();
                        }
                    }, 500);

                    // Timeout de seguridad: 15 segundos máximo de espera.
                    setTimeout(() => {
                        clearInterval(waitForEditor);
                    }, 15000);
                }
            });
        },
    };

    // Exportar para testing / acceso externo.
    Drupal.jarabaOnboarding = {
        startTour: startTour,
        resetTour: resetTourState,
    };

})(Drupal, drupalSettings, once);
