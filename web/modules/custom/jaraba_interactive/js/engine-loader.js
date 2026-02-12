/**
 * @file
 * Jaraba Interactive Engine Loader.
 *
 * Sistema de carga dinámica de engines para mejorar performance.
 * Solo carga el engine necesario según el content_type.
 *
 * PATRÓN: Lazy Loading con fallback para compatibilidad.
 */

(function (Drupal, drupalSettings) {
    'use strict';

    /**
     * Registry de engines disponibles.
     * Mapea content_type a su módulo de engine.
     */
    const ENGINE_REGISTRY = {
        'question_set': {
            name: 'QuestionSetEngine',
            module: 'player', // Engine base en player.js
            loaded: true // Ya está en el bundle principal
        },
        'interactive_video': {
            name: 'InteractiveVideoEngine',
            module: 'engines/interactive-video',
            loaded: false
        },
        'course_presentation': {
            name: 'CoursePresentationEngine',
            module: 'engines/course-presentation',
            loaded: false
        },
        'branching_scenario': {
            name: 'BranchingScenarioEngine',
            module: 'engines/branching-scenario',
            loaded: false
        },
        'flashcard_set': {
            name: 'FlashcardEngine',
            module: 'engines/flashcard',
            loaded: false
        }
    };

    /**
     * Cache de engines ya cargados.
     */
    const loadedEngines = {};

    /**
     * URL base para los engines.
     */
    const getEngineBasePath = () => {
        const modulePath = drupalSettings.jarabaInteractive?.modulePath ||
            '/modules/custom/jaraba_interactive/js';
        return modulePath;
    };

    /**
     * Carga un engine de forma dinámica.
     *
     * @param {string} contentType - El tipo de contenido.
     * @returns {Promise<Function>} - Promise que resuelve con la clase del engine.
     */
    Drupal.jarabaInteractive = Drupal.jarabaInteractive || {};

    Drupal.jarabaInteractive.loadEngine = async function (contentType) {
        const engineInfo = ENGINE_REGISTRY[contentType];

        if (!engineInfo) {
            console.warn(`Engine no encontrado para: ${contentType}. Usando QuestionSetEngine.`);
            return window.JarabaEngines?.QuestionSetEngine || null;
        }

        // Si ya está cargado, retornarlo del cache.
        if (loadedEngines[contentType]) {
            return loadedEngines[contentType];
        }

        // Si está en el bundle principal (ya cargado).
        if (engineInfo.loaded) {
            const engine = window.JarabaEngines?.[engineInfo.name];
            if (engine) {
                loadedEngines[contentType] = engine;
                return engine;
            }
        }

        // Cargar dinámicamente usando inyección de script.
        try {
            const basePath = getEngineBasePath();
            const moduleUrl = `${basePath}/${engineInfo.module}.js`;

            // Cargar el script del engine.
            await loadScript(moduleUrl);

            // Obtener el engine del namespace global.
            const engine = window.JarabaEngines?.[engineInfo.name];
            if (engine) {
                loadedEngines[contentType] = engine;
                ENGINE_REGISTRY[contentType].loaded = true;
                return engine;
            }

            throw new Error(`Engine ${engineInfo.name} no encontrado tras cargar ${moduleUrl}`);
        } catch (error) {
            console.error(`Error cargando engine para ${contentType}:`, error);
            // Fallback al engine base.
            return window.JarabaEngines?.QuestionSetEngine || null;
        }
    };

    /**
     * Pre-carga engines que probablemente se necesitarán.
     * Útil para mejorar UX en navegación.
     *
     * @param {string[]} contentTypes - Array de tipos a pre-cargar.
     */
    Drupal.jarabaInteractive.preloadEngines = async function (contentTypes) {
        const promises = contentTypes
            .filter(type => ENGINE_REGISTRY[type] && !ENGINE_REGISTRY[type].loaded)
            .map(type => Drupal.jarabaInteractive.loadEngine(type));

        await Promise.allSettled(promises);
    };

    /**
     * Obtiene información sobre engines disponibles.
     *
     * @returns {Object} - Registry de engines.
     */
    Drupal.jarabaInteractive.getEngineRegistry = function () {
        return { ...ENGINE_REGISTRY };
    };

    /**
     * Carga un script de forma asíncrona.
     *
     * @param {string} src - URL del script.
     * @returns {Promise<void>}
     */
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            // Verificar si ya está cargado.
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Error cargando: ${src}`));
            document.head.appendChild(script);
        });
    }

    /**
     * Behavior para pre-cargar engines basado en contenido visible.
     */
    Drupal.behaviors.jarabaEnginePreloader = {
        attach: function (context, settings) {
            // Solo ejecutar una vez en el documento.
            if (context !== document) return;

            // Pre-cargar engines de contenidos visibles en el dashboard.
            const contentCards = document.querySelectorAll('[data-content-type]');
            const typesToPreload = new Set();

            contentCards.forEach(card => {
                const type = card.dataset.contentType;
                if (type && ENGINE_REGISTRY[type] && !ENGINE_REGISTRY[type].loaded) {
                    typesToPreload.add(type);
                }
            });

            // Pre-cargar en idle time.
            if (typesToPreload.size > 0 && 'requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    Drupal.jarabaInteractive.preloadEngines([...typesToPreload]);
                }, { timeout: 5000 });
            }
        }
    };

})(Drupal, drupalSettings);
