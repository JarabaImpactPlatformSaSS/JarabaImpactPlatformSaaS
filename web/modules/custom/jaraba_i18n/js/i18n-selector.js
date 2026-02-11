/**
 * @file
 * Comportamiento Alpine.js para el selector de idioma y traducción IA.
 *
 * PROPÓSITO:
 * Gestiona la interactividad del componente i18n-selector incluyendo:
 * - Dropdown de selección de idioma
 * - Modal de configuración de traducción
 * - Comunicación con API de traducción
 * - Feedback de progreso y errores
 *
 * INTEGRACIÓN:
 * - TranslationManagerService (estado de traducciones)
 * - AITranslationService (traducción con IA)
 *
 * DEPENDENCIAS:
 * - Alpine.js (cargado globalmente)
 * - API endpoints de jaraba_i18n
 */

((Drupal, drupalSettings, Alpine) => {
    'use strict';

    /**
     * Componente Alpine para el selector de idioma.
     *
     * @returns {object}
     *   Objecto de estado y métodos del componente.
     */
    window.i18nSelector = function () {
        return {
            // Estado del dropdown.
            isOpen: false,

            // Idioma actual.
            currentLang: 'es',

            // Idiomas disponibles con información de estado.
            languages: {},

            // ID y tipo de entidad.
            entityId: null,
            entityType: null,

            // Estado del modal de traducción.
            showTranslateModal: false,
            targetLanguages: [],

            // Estado de la traducción.
            isTranslating: false,
            translationProgress: 0,
            translationStatus: '',
            translationError: null,

            // API base path.
            apiBasePath: '/api/jaraba-i18n',

            /**
             * Inicializa el componente.
             */
            init() {
                const el = this.$el;

                // Leer datos del DOM.
                this.entityId = el.dataset.entityId;
                this.entityType = el.dataset.entityType;
                this.currentLang = el.dataset.currentLang || 'es';

                // Cargar estado de traducciones.
                this.loadTranslationStatus();
            },

            /**
             * Carga el estado de traducciones desde la API.
             */
            async loadTranslationStatus() {
                try {
                    const response = await fetch(
                        `${this.apiBasePath}/status/${this.entityType}/${this.entityId}`,
                        {
                            headers: {
                                'Accept': 'application/json',
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Error al cargar estado de traducciones');
                    }

                    const data = await response.json();
                    this.languages = data.languages || {};

                } catch (error) {
                    console.error('[i18n-selector] Error cargando estado:', error);
                    // Usar datos por defecto.
                    this.languages = {
                        es: { label: 'Español', exists: true, outdated: false, is_original: true },
                        en: { label: 'English', exists: false, outdated: false, is_original: false },
                        ca: { label: 'Català', exists: false, outdated: false, is_original: false },
                    };
                }
            },

            /**
             * Alterna el dropdown.
             */
            toggleDropdown() {
                this.isOpen = !this.isOpen;
            },

            /**
             * Cierra el dropdown.
             */
            closeDropdown() {
                this.isOpen = false;
            },

            /**
             * Genera la URL para cambiar de idioma.
             *
             * @param {string} langcode
             *   Código de idioma.
             *
             * @returns {string}
             *   URL de la versión traducida.
             */
            getLanguageUrl(langcode) {
                const currentPath = window.location.pathname;
                // Reemplazar prefijo de idioma.
                const pathWithoutLang = currentPath.replace(/^\/(?:es|en|ca|eu|fr|de|pt)\//, '/');
                return `/${langcode}${pathWithoutLang}`;
            },

            /**
             * Cambia al idioma seleccionado.
             *
             * @param {string} langcode
             *   Código de idioma.
             */
            switchLanguage(langcode) {
                const info = this.languages[langcode];

                if (!info) {
                    return;
                }

                // Si existe la traducción, navegar a ella.
                if (info.exists || info.is_original) {
                    window.location.href = this.getLanguageUrl(langcode);
                } else {
                    // Si no existe, ofrecer crear o traducir.
                    this.targetLanguages = [langcode];
                    this.openTranslateModal();
                }

                this.closeDropdown();
            },

            /**
             * Abre el modal de traducción.
             */
            openTranslateModal() {
                this.showTranslateModal = true;
                this.translationError = null;
                this.translationProgress = 0;
                this.translationStatus = '';

                // Preseleccionar idiomas faltantes.
                if (this.targetLanguages.length === 0) {
                    this.targetLanguages = Object.entries(this.languages)
                        .filter(([_, info]) => !info.exists && !info.is_original)
                        .map(([code]) => code);
                }
            },

            /**
             * Cierra el modal de traducción.
             */
            closeTranslateModal() {
                if (!this.isTranslating) {
                    this.showTranslateModal = false;
                    this.targetLanguages = [];
                }
            },

            /**
             * Inicia el proceso de traducción.
             */
            async startTranslation() {
                if (this.targetLanguages.length === 0) {
                    return;
                }

                this.isTranslating = true;
                this.translationError = null;
                this.translationProgress = 0;

                const total = this.targetLanguages.length;
                let completed = 0;

                try {
                    for (const langcode of this.targetLanguages) {
                        this.translationStatus = Drupal.t('Traduciendo a @lang...', {
                            '@lang': this.languages[langcode]?.label || langcode,
                        });

                        await this.translateToLanguage(langcode);

                        completed++;
                        this.translationProgress = Math.round((completed / total) * 100);
                    }

                    // Éxito.
                    this.translationStatus = Drupal.t('¡Traducciones completadas!');

                    // Recargar estado.
                    await this.loadTranslationStatus();

                    // Cerrar modal después de un momento.
                    setTimeout(() => {
                        this.closeTranslateModal();
                        this.isTranslating = false;
                    }, 1500);

                } catch (error) {
                    console.error('[i18n-selector] Error en traducción:', error);
                    this.translationError = error.message || Drupal.t('Error durante la traducción');
                    this.isTranslating = false;
                }
            },

            /**
             * Traduce a un idioma específico.
             *
             * @param {string} langcode
             *   Código del idioma destino.
             */
            async translateToLanguage(langcode) {
                const response = await fetch(
                    `${this.apiBasePath}/translate`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            entity_type: this.entityType,
                            entity_id: this.entityId,
                            source_lang: this.currentLang,
                            target_lang: langcode,
                        }),
                    }
                );

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Error en la API de traducción');
                }

                return response.json();
            },
        };
    };

    // Registrar comportamiento Drupal.
    Drupal.behaviors.jarabaI18nSelector = {
        attach(context) {
            // Alpine se inicializa automáticamente en elementos con x-data.
        },
    };

})(Drupal, drupalSettings, window.Alpine);
