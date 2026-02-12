/**
 * @file
 * Jaraba Canvas Editor v3 - Integración AI Content Assistant.
 *
 * Plugin GrapesJS que integra el generador de contenido IA
 * para generar texto, imágenes y estructuras de bloques.
 *
 * Sprint C4: Añade selector de Vertical, Brand Voice, modo Prompt-to-Page
 * y sugerencias SEO con IA.
 *
 * @see docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md
 * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §11
 */

(function (Drupal, drupalSettings) {
    'use strict';

    /**
     * Plugin GrapesJS para integración de IA.
     *
     * @param {Object} editor - Instancia del editor GrapesJS.
     * @param {Object} opts - Opciones de configuración.
     */
    const jarabaAIPlugin = (editor, opts = {}) => {
        const vertical = drupalSettings.jarabaCanvas?.vertical || 'generic';
        const tenantId = drupalSettings.jarabaCanvas?.tenantId || null;

        /**
         * Comando para generar contenido con IA.
         */
        editor.Commands.add('jaraba:ai-generate', {
            run(editor, sender, options = {}) {
                const selected = editor.getSelected();
                if (!selected) {
                    console.warn('No hay componente seleccionado para generar contenido IA.');
                    return;
                }

                const blockType = selected.get('type') || 'generic';
                const context = {
                    blockType,
                    vertical,
                    tenantId,
                    currentContent: selected.toHTML(),
                };

                // Mostrar modal de generación
                showAIModal(editor, selected, context);
            },
        });

        /**
         * Muestra el modal de generación de contenido IA.
         *
         * @param {Object} editor - Instancia del editor.
         * @param {Object} component - Componente seleccionado.
         * @param {Object} context - Contexto para la IA.
         */
        function showAIModal(editor, component, context) {
            const modal = editor.Modal;

            modal.setTitle(Drupal.t('✨ Asistente de Contenido IA'));

            const content = document.createElement('div');
            content.className = 'jaraba-ai-modal';
            content.innerHTML = `
        <div class="jaraba-ai-modal__form">
          <div class="jaraba-ai-modal__field">
            <label for="ai-prompt">${Drupal.t('¿Qué contenido quieres generar?')}</label>
            <textarea id="ai-prompt" rows="3" placeholder="${Drupal.t('Ej: Landing para bootcamp Python con hero, features, pricing y CTA')}"></textarea>
          </div>

          <div class="jaraba-ai-modal__row">
            <div class="jaraba-ai-modal__field jaraba-ai-modal__field--half">
              <label for="ai-vertical">${Drupal.t('Vertical')}</label>
              <select id="ai-vertical">
                <option value="empleabilidad">${Drupal.t('Empleabilidad')}</option>
                <option value="emprendimiento">${Drupal.t('Emprendimiento')}</option>
                <option value="agroconecta">${Drupal.t('AgroConecta')}</option>
                <option value="formacion">${Drupal.t('Formación')}</option>
                <option value="generica" selected>${Drupal.t('Genérica')}</option>
              </select>
            </div>

            <div class="jaraba-ai-modal__field jaraba-ai-modal__field--half">
              <label for="ai-tone">${Drupal.t('Tono')}</label>
              <select id="ai-tone">
                <option value="profesional">${Drupal.t('Profesional')}</option>
                <option value="cercano">${Drupal.t('Cercano')}</option>
                <option value="inspirador">${Drupal.t('Inspirador')}</option>
                <option value="formal">${Drupal.t('Formal')}</option>
                <option value="tecnico">${Drupal.t('Técnico')}</option>
              </select>
            </div>
          </div>

          <div class="jaraba-ai-modal__field">
            <label>${Drupal.t('Modo de generación')}</label>
            <div class="jaraba-ai-modal__mode-toggle">
              <button type="button" class="jaraba-ai-mode-btn jaraba-ai-mode-btn--active" data-mode="section">
                📝 ${Drupal.t('Sección')}
              </button>
              <button type="button" class="jaraba-ai-mode-btn" data-mode="page">
                📄 ${Drupal.t('Página completa')}
              </button>
            </div>
          </div>

          <div class="jaraba-ai-modal__sections-config" style="display: none;">
            <label>${Drupal.t('Secciones a generar')}</label>
            <div class="jaraba-ai-modal__checkboxes">
              <label><input type="checkbox" value="hero" checked> Hero</label>
              <label><input type="checkbox" value="features" checked> Features</label>
              <label><input type="checkbox" value="pricing"> Pricing</label>
              <label><input type="checkbox" value="testimonials"> Testimonios</label>
              <label><input type="checkbox" value="cta" checked> CTA</label>
            </div>
          </div>

          <div class="jaraba-ai-modal__actions">
            <button type="button" class="jaraba-btn jaraba-btn--primary" id="ai-generate-btn">
              ${Drupal.t('✨ Generar')}
            </button>
            <button type="button" class="jaraba-btn jaraba-btn--secondary" id="ai-cancel-btn">
              ${Drupal.t('Cancelar')}
            </button>
          </div>

          <div class="jaraba-ai-modal__preview" style="display: none;">
            <h4>${Drupal.t('Vista previa')}</h4>
            <div id="ai-preview-content"></div>
            <div class="jaraba-ai-modal__preview-actions">
              <button type="button" class="jaraba-btn jaraba-btn--primary" id="ai-apply-btn">
                ${Drupal.t('Aplicar')}
              </button>
              <button type="button" class="jaraba-btn jaraba-btn--secondary" id="ai-regenerate-btn">
                ${Drupal.t('Regenerar')}
              </button>
            </div>
          </div>
        </div>
      `;

            modal.setContent(content);
            modal.open();

            // --- Mode toggle logic (C4.4) ---
            let currentMode = 'section';
            const modeBtns = content.querySelectorAll('.jaraba-ai-mode-btn');
            const sectionsConfig = content.querySelector('.jaraba-ai-modal__sections-config');

            modeBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    modeBtns.forEach(b => b.classList.remove('jaraba-ai-mode-btn--active'));
                    btn.classList.add('jaraba-ai-mode-btn--active');
                    currentMode = btn.dataset.mode;
                    sectionsConfig.style.display = currentMode === 'page' ? 'block' : 'none';
                });
            });

            // --- Set vertical from drupalSettings if available ---
            const verticalSelect = content.querySelector('#ai-vertical');
            if (vertical && vertical !== 'generic') {
                const opt = verticalSelect.querySelector(`option[value="${vertical}"]`);
                if (opt) opt.selected = true;
            }

            // Event listeners
            const generateBtn = content.querySelector('#ai-generate-btn');
            const cancelBtn = content.querySelector('#ai-cancel-btn');
            const applyBtn = content.querySelector('#ai-apply-btn');
            const regenerateBtn = content.querySelector('#ai-regenerate-btn');

            let generatedContent = null;

            // Generar contenido
            generateBtn.addEventListener('click', async () => {
                const prompt = content.querySelector('#ai-prompt').value;
                const tone = content.querySelector('#ai-tone').value;
                const selectedVertical = content.querySelector('#ai-vertical').value;

                if (!prompt.trim()) {
                    alert(Drupal.t('Por favor, describe qué contenido quieres generar.'));
                    return;
                }

                generateBtn.disabled = true;
                generateBtn.textContent = Drupal.t('⏳ Generando...');

                try {
                    if (currentMode === 'page') {
                        // C4.4: Prompt-to-Page
                        const checkboxes = content.querySelectorAll('.jaraba-ai-modal__checkboxes input:checked');
                        const sections = Array.from(checkboxes).map(cb => cb.value);
                        generatedContent = await generateFullPageContent(prompt, selectedVertical, tone, sections);
                    } else {
                        // Modo sección (original, enriquecido con vertical)
                        context.vertical = selectedVertical;
                        generatedContent = await generateAIContent(prompt, tone, context);
                    }

                    // Mostrar preview
                    const previewSection = content.querySelector('.jaraba-ai-modal__preview');
                    const previewContent = content.querySelector('#ai-preview-content');

                    previewContent.innerHTML = generatedContent.html || `<p>${generatedContent.text}</p>`;
                    previewSection.style.display = 'block';

                    generateBtn.disabled = false;
                    generateBtn.textContent = Drupal.t('✨ Generar');
                } catch (error) {
                    console.error('Error generando contenido IA:', error);
                    alert(Drupal.t('Error al generar contenido. Por favor, inténtalo de nuevo.'));
                    generateBtn.disabled = false;
                    generateBtn.textContent = Drupal.t('✨ Generar');
                }
            });

            // Cancelar
            cancelBtn.addEventListener('click', () => {
                modal.close();
            });

            // Aplicar contenido generado
            applyBtn.addEventListener('click', () => {
                if (generatedContent) {
                    if (currentMode === 'page') {
                        applyFullPageContent(editor, generatedContent);
                    } else {
                        applyGeneratedContent(component, generatedContent);
                    }
                    modal.close();
                }
            });

            // Regenerar
            regenerateBtn.addEventListener('click', () => {
                generateBtn.click();
            });
        }

        /**
         * Genera contenido usando la API de IA del Page Builder.
         *
         * ENDPOINT: POST /api/page-builder/generate-content
         * CONTROLLER: AiContentController::generateContent()
         *
         * El controller espera:
         *   - field_type: tipo de campo (headline, description, text, cta)
         *   - context: objeto con page_title, vertical, tone, template_name
         *   - current_value: valor actual del campo (opcional)
         *
         * FLUJO IA:
         *   1. Intenta ContentWriterAgent (si está instalado)
         *   2. Fallback: módulo AI de Drupal con @ai.provider
         *   3. Último recurso: placeholder inteligente
         *
         * @param {string} prompt - Prompt del usuario.
         * @param {string} tone - Tono deseado (professional, friendly, etc.).
         * @param {Object} context - Contexto del componente seleccionado.
         * @returns {Promise<Object>} Contenido generado con {success, content, variants, tokens_used}.
         *
         * @see AiContentController::generateContent()
         * @see 00_DIRECTRICES_PROYECTO.md §2.10 — Usa @ai.provider, NUNCA HTTP directo.
         */
        async function generateAIContent(prompt, tone, context) {
            // Determinar el field_type según el tipo de bloque seleccionado.
            // Mapeo de blockType GrapesJS → field_type del controlador.
            const fieldTypeMap = {
                'heading': 'headline',
                'text': 'description',
                'paragraph': 'description',
                'jaraba-button': 'cta',
                'link': 'cta',
            };
            const fieldType = fieldTypeMap[context.blockType] || 'text';

            const response = await fetch('/api/page-builder/generate-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': drupalSettings.jarabaCanvas?.csrfToken || '',
                },
                body: JSON.stringify({
                    field_type: fieldType,
                    context: {
                        page_title: prompt,
                        vertical: context.vertical || 'general',
                        tone: tone || 'professional',
                        template_name: context.templateName || '',
                        audience: context.audience || 'usuarios web',
                    },
                    current_value: context.currentValue || '',
                }),
            });

            if (!response.ok) {
                throw new Error(Drupal.t('Error en la API de IA'));
            }

            const result = await response.json();

            // Adaptar respuesta del controller al formato esperado por applyGeneratedContent.
            // El controller devuelve {success, content, variants, tokens_used}.
            // La función applyGeneratedContent espera {html, text}.
            if (result.success && result.content) {
                return {
                    text: result.content,
                    html: null,
                    variants: result.variants || [],
                    tokens_used: result.tokens_used || 0,
                };
            }

            throw new Error(result.error || Drupal.t('Error desconocido'));
        }

        /**
         * Genera una página completa con múltiples secciones (Sprint C4.4).
         *
         * ENDPOINT: POST /api/v1/page-builder/ai/generate-page
         * CONTROLLER: AiContentController::generateFullPage()
         *
         * @param {string} prompt - Instrucciones del usuario.
         * @param {string} vertical - Vertical activa.
         * @param {string} tone - Tono deseado.
         * @param {string[]} sections - Secciones a generar.
         * @returns {Promise<Object>} Resultado con {html, css, sections}.
         */
        async function generateFullPageContent(prompt, vertical, tone, sections) {
            const response = await fetch('/api/v1/page-builder/ai/generate-page', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': drupalSettings.jarabaCanvas?.csrfToken || '',
                },
                body: JSON.stringify({ prompt, vertical, tone, sections }),
            });

            if (!response.ok) {
                throw new Error(Drupal.t('Error en la API de generación de página'));
            }

            const result = await response.json();
            if (result.success) {
                return {
                    html: result.html,
                    css: result.css,
                    sections: result.sections || [],
                    isFullPage: true,
                };
            }

            throw new Error(result.error || Drupal.t('Error desconocido'));
        }

        /**
         * Solicita sugerencias SEO con IA (Sprint C4.1).
         *
         * ENDPOINT: POST /api/v1/page-builder/seo-ai-suggest
         * CONTROLLER: AiContentController::seoSuggest()
         *
         * @param {string} html - HTML de la página actual.
         * @param {string} keyword - Keyword objetivo.
         * @returns {Promise<Object>} Resultado con {score, suggestions}.
         */
        async function fetchSeoSuggestions(html, keyword = '') {
            const response = await fetch('/api/v1/page-builder/seo-ai-suggest', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': drupalSettings.jarabaCanvas?.csrfToken || '',
                },
                body: JSON.stringify({
                    html,
                    keyword,
                    page_title: document.title || '',
                    meta_description: '',
                }),
            });

            if (!response.ok) {
                throw new Error(Drupal.t('Error en la API de sugerencias SEO'));
            }

            return await response.json();
        }

        /**
         * Aplica el contenido generado al componente (modo sección).
         *
         * @param {Object} component - Componente GrapesJS.
         * @param {Object} content - Contenido generado.
         */
        function applyGeneratedContent(component, content) {
            if (content.html) {
                component.components(content.html);
            } else if (content.text) {
                const textComponents = component.find('[data-gjs-type=text]');
                if (textComponents.length) {
                    textComponents[0].components(content.text);
                } else {
                    component.components(`<p>${content.text}</p>`);
                }
            }

            component.em.trigger('change:changesCount');
            console.log('Contenido IA aplicado correctamente.');
        }

        /**
         * Aplica contenido de página completa al canvas (Sprint C4.4).
         *
         * Reemplaza todo el canvas con el HTML generado y añade CSS.
         *
         * @param {Object} editor - Instancia del editor GrapesJS.
         * @param {Object} content - Contenido con {html, css, sections}.
         */
        function applyFullPageContent(editor, content) {
            if (content.html) {
                editor.setComponents(content.html);
            }
            if (content.css) {
                // Añadir CSS generado sin sobreescribir los estilos existentes.
                const currentCss = editor.getCss() || '';
                editor.setStyle(currentCss + '\n/* IA Generated Styles */\n' + content.css);
            }

            editor.trigger('change:changesCount');
            console.log(`Prompt-to-Page aplicado: ${content.sections?.length || 0} secciones.`);
        }

        /**
         * Muestra sugerencias SEO en un slide-panel (Sprint C4.1 Frontend).
         *
         * @param {Object} editor - Instancia del editor.
         * @param {Object} result - Resultado de la API {score, suggestions}.
         */
        function showSeoSuggestionsPanel(editor, result) {
            const modal = editor.Modal;
            modal.setTitle(Drupal.t('🤖 Sugerencias SEO con IA'));

            const priorityColors = {
                high: '#ef4444',
                medium: '#f59e0b',
                low: '#22c55e',
            };

            const priorityLabels = {
                high: Drupal.t('Alta'),
                medium: Drupal.t('Media'),
                low: Drupal.t('Baja'),
            };

            let suggestionsHtml = '';
            if (result.suggestions && result.suggestions.length) {
                suggestionsHtml = result.suggestions.map(s => `
                    <div class="jaraba-seo-suggestion" style="border-left: 4px solid ${priorityColors[s.priority] || '#94a3b8'}; padding: 12px 16px; margin-bottom: 12px; background: #f8fafc; border-radius: 0 8px 8px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <strong style="color: #233D63;">${s.type || ''}</strong>
                            <span style="font-size: 12px; padding: 2px 8px; border-radius: 20px; color: white; background: ${priorityColors[s.priority] || '#94a3b8'};">
                                ${priorityLabels[s.priority] || s.priority}
                            </span>
                        </div>
                        <p style="margin: 0 0 6px; color: #475569;">${s.message || ''}</p>
                        ${s.fix ? `<p style="margin: 0; font-style: italic; color: #00A9A5;">💡 ${s.fix}</p>` : ''}
                    </div>
                `).join('');
            } else {
                suggestionsHtml = `<p style="text-align: center; color: #94a3b8;">${Drupal.t('No se generaron sugerencias.')}</p>`;
            }

            const scoreColor = result.score >= 80 ? '#22c55e' : result.score >= 50 ? '#f59e0b' : '#ef4444';

            const container = document.createElement('div');
            container.innerHTML = `
                <div style="padding: 16px;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div style="display: inline-block; width: 80px; height: 80px; border-radius: 50%; background: ${scoreColor}20; border: 4px solid ${scoreColor}; line-height: 72px; font-size: 28px; font-weight: 800; color: ${scoreColor};">
                            ${result.score || 0}
                        </div>
                        <p style="margin-top: 8px; font-size: 14px; color: #64748b;">${Drupal.t('Score SEO estimado')}</p>
                    </div>
                    <div class="jaraba-seo-suggestions-list">
                        ${suggestionsHtml}
                    </div>
                    <p style="margin-top: 12px; font-size: 12px; color: #94a3b8; text-align: center;">
                        ${Drupal.t('Generado por')}: ${result.provider || 'IA'}
                    </p>
                </div>
            `;

            modal.setContent(container);
            modal.open();
        }

        // ──────────────────────────────────────────────────────
        // Comando: Sugerencias SEO con IA (Sprint C4.1)
        // ──────────────────────────────────────────────────────
        editor.Commands.add('jaraba:seo-ai-suggest', {
            async run(editor) {
                const html = editor.getHtml();
                if (!html || html.trim().length < 20) {
                    alert(Drupal.t('Añade contenido al canvas antes de solicitar sugerencias SEO.'));
                    return;
                }

                try {
                    const result = await fetchSeoSuggestions(html);
                    if (result.success) {
                        showSeoSuggestionsPanel(editor, result);
                    } else {
                        alert(result.error || Drupal.t('Error al obtener sugerencias SEO.'));
                    }
                } catch (error) {
                    console.error('Error SEO AI:', error);
                    alert(Drupal.t('Error al obtener sugerencias SEO.'));
                }
            },
        });

        /**
         * Añade botón de IA a la toolbar de componentes.
         */
        // ──────────────────────────────────────────────────────
        // Conectar botón de toolbar externa SEO IA (Sprint C4.1)
        // ──────────────────────────────────────────────────────
        const seoAiBtn = document.getElementById('seo-ai-suggest-btn');
        if (seoAiBtn) {
            seoAiBtn.addEventListener('click', () => {
                editor.runCommand('jaraba:seo-ai-suggest');
            });
        }

        editor.on('component:selected', (component) => {
            const type = component.get('type');
            if (type === 'jaraba-header' || type === 'jaraba-footer' || type === 'jaraba-content-zone') {
                return;
            }

            const toolbar = component.get('toolbar') || [];
            const hasAIButton = toolbar.some(item => item.command === 'jaraba:ai-generate');

            if (!hasAIButton) {
                toolbar.push({
                    attributes: { class: 'jaraba-toolbar-ai', title: Drupal.t('Generar con IA') },
                    command: 'jaraba:ai-generate',
                    label: '✨',
                });
                component.set('toolbar', toolbar);
            }
        });

        console.log('Jaraba AI Plugin v2 inicializado (C4: Vertical, Brand Voice, Prompt-to-Page, SEO IA).');
    };

    // Registrar plugin en GrapesJS
    if (typeof grapesjs !== 'undefined') {
        grapesjs.plugins.add('jaraba-ai', jarabaAIPlugin);
    }

})(Drupal, drupalSettings);
