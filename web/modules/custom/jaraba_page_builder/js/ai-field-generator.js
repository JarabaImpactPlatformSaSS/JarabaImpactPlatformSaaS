/**
 * @file
 * Cliente JavaScript para generación de contenido con IA en campos de formulario.
 *
 * PROPÓSITO:
 * Añade botones "✨ Generar con IA" junto a campos de texto del Page Builder.
 * Permite generar contenido contextual usando la API de IA del módulo.
 *
 * ESPECIFICACIÓN: Gap 1 - Plan Elevación Clase Mundial
 *
 * FLUJO DE USO:
 * 1. Usuario hace clic en el botón "✨ Generar"
 * 2. Se abre un modal con opciones de tono y contexto
 * 3. Request a /api/v1/page-builder/generate-field
 * 4. Respuesta mostrada en preview
 * 5. Usuario confirma o regenera
 */
(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Configuración de la API de generación.
     *
     * @type {Object}
     */
    const CONFIG = {
        endpoint: '/api/v1/page-builder/generate-field',
        // Tipos de campo que soportan generación con IA.
        supportedTypes: ['textfield', 'textarea'],
        // Tonos disponibles para la generación.
        tones: [
            { value: 'professional', label: Drupal.t('Profesional') },
            { value: 'casual', label: Drupal.t('Casual') },
            { value: 'persuasive', label: Drupal.t('Persuasivo') },
            { value: 'informative', label: Drupal.t('Informativo') },
            { value: 'creative', label: Drupal.t('Creativo') },
        ]
    };

    /**
     * Estado actual de la generación.
     *
     * @type {Object}
     */
    let generationState = {
        isGenerating: false,
        currentField: null,
        currentModal: null,
    };

    /**
     * Drupal behavior para añadir botones de generación IA.
     *
     * Este behavior se ejecuta cada vez que Drupal adjunta comportamientos
     * a elementos del DOM, incluyendo tras AJAX.
     */
    Drupal.behaviors.jarabaAiFieldGenerator = {
        attach: function (context) {
            // Seleccionar campos de texto dentro del contexto del Page Builder.
            const fields = context.querySelectorAll(
                '.page-builder-form input[type="text"], ' +
                '.page-builder-form textarea, ' +
                '.content-edit-form input[type="text"], ' +
                '.content-edit-form textarea, ' +
                '[data-ai-generate] input[type="text"], ' +
                '[data-ai-generate] textarea'
            );

            once('ai-field-generator', fields).forEach((field) => {
                addGenerateButton(field);
            });
        }
    };

    /**
     * Añade el botón de generación IA a un campo.
     *
     * Crea un wrapper alrededor del campo y añade el botón.
     * El botón incluye atributos ARIA para accesibilidad.
     *
     * @param {HTMLElement} field - El campo de texto/textarea.
     */
    function addGenerateButton(field) {
        // Evitar añadir botón a campos ya procesados o excluidos.
        if (field.dataset.aiProcessed || field.dataset.aiExclude) {
            return;
        }

        // Marcar como procesado.
        field.dataset.aiProcessed = 'true';

        // Obtener contexto del campo para prompts inteligentes.
        const fieldContext = extractFieldContext(field);

        // Crear el botón de generación.
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ai-generate-btn';
        button.innerHTML = '<span class="ai-icon">✨</span><span class="ai-label">' + Drupal.t('Generar') + '</span>';
        button.setAttribute('aria-label', Drupal.t('Generar contenido con IA para este campo'));
        button.setAttribute('title', Drupal.t('Generar con IA'));

        // Guardar contexto en el botón.
        button.dataset.fieldContext = JSON.stringify(fieldContext);

        // Evento de clic.
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openGenerateModal(field, fieldContext);
        });

        // Insertar el botón después del campo o en su wrapper.
        const wrapper = field.closest('.form-item') || field.parentElement;
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'ai-generate-btn-container';
        buttonContainer.appendChild(button);

        // Añadir después del label si existe, o al inicio del wrapper.
        const label = wrapper.querySelector('label');
        if (label) {
            label.after(buttonContainer);
        } else {
            wrapper.prepend(buttonContainer);
        }
    }

    /**
     * Extrae contexto del campo para generar prompts inteligentes.
     *
     * Analiza el campo y su entorno para proporcionar contexto
     * relevante a la IA (tipo de bloque, nombre del campo, etc.).
     *
     * @param {HTMLElement} field - El campo de formulario.
     * @returns {Object} Contexto extraído.
     */
    function extractFieldContext(field) {
        // Obtener nombre del campo desde el atributo name o id.
        const fieldName = field.name || field.id || 'unknown';

        // Obtener label del campo.
        const wrapper = field.closest('.form-item');
        const label = wrapper?.querySelector('label')?.textContent || '';

        // Detectar tipo de bloque desde contenedores padres.
        const blockContainer = field.closest('[data-block-type]');
        const blockType = blockContainer?.dataset.blockType || 'generic';

        // Detectar si es un campo de texto largo (textarea).
        const isLongText = field.tagName.toLowerCase() === 'textarea';

        // Detectar placeholder como pista adicional.
        const placeholder = field.placeholder || '';

        // Detectar vertical del tenant si está disponible.
        const vertical = drupalSettings.jarabaCore?.vertical || 'general';

        return {
            fieldName: fieldName,
            fieldLabel: label.trim(),
            blockType: blockType,
            isLongText: isLongText,
            placeholder: placeholder,
            vertical: vertical,
            maxLength: field.maxLength > 0 ? field.maxLength : null,
        };
    }

    /**
     * Abre el modal de generación con opciones.
     *
     * El modal permite seleccionar el tono y proporcionar
     * instrucciones adicionales antes de generar.
     *
     * @param {HTMLElement} field - Campo destino de la generación.
     * @param {Object} context - Contexto del campo.
     */
    function openGenerateModal(field, context) {
        // Cerrar modal anterior si existe.
        closeGenerateModal();

        // Guardar referencia al campo actual.
        generationState.currentField = field;

        // Crear overlay.
        const overlay = document.createElement('div');
        overlay.className = 'ai-modal-overlay';
        overlay.addEventListener('click', closeGenerateModal);

        // Crear modal.
        const modal = document.createElement('div');
        modal.className = 'ai-generate-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'ai-modal-title');

        modal.innerHTML = `
      <div class="ai-modal-header">
        <h3 id="ai-modal-title">✨ ${Drupal.t('Generar con IA')}</h3>
        <button type="button" class="ai-modal-close" aria-label="${Drupal.t('Cerrar')}">×</button>
      </div>
      <div class="ai-modal-body">
        <div class="ai-form-group">
          <label for="ai-tone">${Drupal.t('Tono')}</label>
          <select id="ai-tone" class="ai-select">
            ${CONFIG.tones.map(t => `<option value="${t.value}">${t.label}</option>`).join('')}
          </select>
        </div>
        <div class="ai-form-group">
          <label for="ai-instructions">${Drupal.t('Instrucciones adicionales')} (${Drupal.t('opcional')})</label>
          <textarea id="ai-instructions" class="ai-textarea" rows="2" 
            placeholder="${Drupal.t('Ej: Enfócate en beneficios para el cliente...')}"></textarea>
        </div>
        <div class="ai-context-info">
          <small>📍 ${Drupal.t('Campo')}: ${context.fieldLabel || context.fieldName}</small>
          ${context.blockType !== 'generic' ? `<br><small>🧱 ${Drupal.t('Bloque')}: ${context.blockType}</small>` : ''}
        </div>
        <div class="ai-preview-area" style="display: none;">
          <label>${Drupal.t('Vista previa')}</label>
          <div class="ai-preview-content"></div>
        </div>
      </div>
      <div class="ai-modal-footer">
        <button type="button" class="ai-btn ai-btn-secondary ai-btn-cancel">${Drupal.t('Cancelar')}</button>
        <button type="button" class="ai-btn ai-btn-primary ai-btn-generate">
          <span class="ai-btn-icon">✨</span>
          <span class="ai-btn-text">${Drupal.t('Generar')}</span>
        </button>
        <button type="button" class="ai-btn ai-btn-success ai-btn-apply" style="display: none;">
          <span class="ai-btn-text">${Drupal.t('Aplicar')}</span>
        </button>
      </div>
    `;

        // Eventos del modal.
        modal.querySelector('.ai-modal-close').addEventListener('click', closeGenerateModal);
        modal.querySelector('.ai-btn-cancel').addEventListener('click', closeGenerateModal);
        modal.querySelector('.ai-btn-generate').addEventListener('click', () => generateContent(context));
        modal.querySelector('.ai-btn-apply').addEventListener('click', applyGeneratedContent);

        // Añadir al DOM.
        document.body.appendChild(overlay);
        document.body.appendChild(modal);

        // Guardar referencia.
        generationState.currentModal = modal;

        // Focus en el select de tono.
        modal.querySelector('#ai-tone').focus();

        // Cerrar con Escape.
        document.addEventListener('keydown', handleEscapeKey);
    }

    /**
     * Cierra el modal de generación.
     */
    function closeGenerateModal() {
        if (generationState.currentModal) {
            generationState.currentModal.remove();
            generationState.currentModal = null;
        }

        const overlay = document.querySelector('.ai-modal-overlay');
        if (overlay) {
            overlay.remove();
        }

        document.removeEventListener('keydown', handleEscapeKey);
        generationState.currentField = null;
        generationState.isGenerating = false;
    }

    /**
     * Manejador para cerrar modal con tecla Escape.
     *
     * @param {KeyboardEvent} e - Evento de teclado.
     */
    function handleEscapeKey(e) {
        if (e.key === 'Escape') {
            closeGenerateModal();
        }
    }

    /**
     * Genera contenido usando la API de IA.
     *
     * Envía una solicitud al backend con el contexto del campo
     * y las preferencias del usuario (tono, instrucciones).
     *
     * @param {Object} context - Contexto del campo.
     */
    async function generateContent(context) {
        if (generationState.isGenerating) {
            return;
        }

        const modal = generationState.currentModal;
        if (!modal) {
            return;
        }

        const tone = modal.querySelector('#ai-tone').value;
        const instructions = modal.querySelector('#ai-instructions').value;
        const generateBtn = modal.querySelector('.ai-btn-generate');
        const applyBtn = modal.querySelector('.ai-btn-apply');
        const previewArea = modal.querySelector('.ai-preview-area');
        const previewContent = modal.querySelector('.ai-preview-content');

        // Estado de carga.
        generationState.isGenerating = true;
        generateBtn.disabled = true;
        generateBtn.querySelector('.ai-btn-text').textContent = Drupal.t('Generando...');
        generateBtn.querySelector('.ai-btn-icon').textContent = '⏳';

        try {
            const response = await fetch(CONFIG.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    field_name: context.fieldName,
                    field_label: context.fieldLabel,
                    block_type: context.blockType,
                    is_long_text: context.isLongText,
                    max_length: context.maxLength,
                    vertical: context.vertical,
                    tone: tone,
                    instructions: instructions,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            // Mostrar preview.
            previewContent.textContent = data.content;
            previewArea.style.display = 'block';

            // Mostrar botón de aplicar.
            applyBtn.style.display = 'inline-flex';
            generateBtn.querySelector('.ai-btn-text').textContent = Drupal.t('Regenerar');
            generateBtn.querySelector('.ai-btn-icon').textContent = '🔄';

        } catch (error) {
            console.error('[AI Field Generator] Error:', error);
            previewContent.innerHTML = `<span class="ai-error">❌ ${Drupal.t('Error al generar. Inténtalo de nuevo.')}</span>`;
            previewArea.style.display = 'block';
        } finally {
            generationState.isGenerating = false;
            generateBtn.disabled = false;
        }
    }

    /**
     * Aplica el contenido generado al campo.
     */
    function applyGeneratedContent() {
        const modal = generationState.currentModal;
        const field = generationState.currentField;

        if (!modal || !field) {
            return;
        }

        const content = modal.querySelector('.ai-preview-content').textContent;

        // Aplicar al campo.
        field.value = content;

        // Disparar evento de cambio para que Drupal detecte la modificación.
        field.dispatchEvent(new Event('change', { bubbles: true }));
        field.dispatchEvent(new Event('input', { bubbles: true }));

        // Cerrar modal.
        closeGenerateModal();

        // Feedback visual.
        field.classList.add('ai-field-updated');
        setTimeout(() => {
            field.classList.remove('ai-field-updated');
        }, 2000);
    }

    /**
     * API pública para debugging y extensión.
     */
    Drupal.jarabaAiFieldGenerator = {
        /**
         * Genera contenido programáticamente para un campo.
         *
         * @param {HTMLElement} field - Campo destino.
         * @param {Object} options - Opciones de generación.
         */
        generateFor: function (field, options = {}) {
            const context = extractFieldContext(field);
            Object.assign(context, options);
            openGenerateModal(field, context);
        },

        /**
         * Añade botón manualmente a un campo.
         *
         * @param {HTMLElement} field - Campo al que añadir el botón.
         */
        addButtonTo: function (field) {
            addGenerateButton(field);
        }
    };

})(Drupal, drupalSettings, once);
