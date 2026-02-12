/**
 * @file
 * AI Content Generator para Form Builder.
 *
 * Añade botones "✨ Generar con IA" a campos de texto en el Form Builder
 * para generar contenido automáticamente usando ContentWriterAgent.
 *
 * DIRECTRICES:
 * - Usa {% trans %} pattern para textos (i18n ready)
 * - Loading states con feedback visual
 * - Error handling graceful
 *
 * @ingroup jaraba_page_builder
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Behavior para añadir botones de generación IA a campos de texto.
     */
    Drupal.behaviors.aiContentGenerator = {
        attach: function (context, settings) {
            // Configuración del endpoint.
            const API_ENDPOINT = '/api/v1/page-builder/generate-content';

            // Tipos de campos que soportan generación IA.
            const SUPPORTED_FIELDS = [
                'input[type="text"]',
                'textarea',
            ];

            // Selectores a ignorar (campos técnicos).
            const IGNORED_FIELDS = [
                '[name*="url"]',
                '[name*="email"]',
                '[name*="phone"]',
                '[name*="image"]',
                '[name*="color"]',
                '[name*="icon"]',
                '[type="hidden"]',
                '[type="number"]',
                '[type="date"]',
                '[readonly]',
                '[disabled]',
            ];

            // Buscar campos en contexto del Form Builder.
            const formBuilderSelector = '.page-builder-form, .jaraba-form-builder, [data-drupal-selector*="page-content"]';
            const formBuilder = context.querySelector(formBuilderSelector);

            if (!formBuilder) {
                return;
            }

            // Procesar cada campo de texto.
            const selector = SUPPORTED_FIELDS.join(', ');
            const ignoreSelector = IGNORED_FIELDS.join(', ');

            const fields = once('ai-content-generator', selector, formBuilder);

            fields.forEach(function (field) {
                // Ignorar campos técnicos.
                if (field.matches(ignoreSelector)) {
                    return;
                }

                // Ignorar campos muy pequeños (probablemente técnicos).
                if (field.tagName === 'INPUT' && field.size && field.size < 10) {
                    return;
                }

                addAiButton(field);
            });

            /**
             * Añade el botón de IA a un campo.
             */
            function addAiButton(field) {
                // Crear contenedor wrapper si no existe.
                let wrapper = field.closest('.form-item');
                if (!wrapper) {
                    wrapper = field.parentElement;
                }

                // Verificar que no exista ya un botón.
                if (wrapper.querySelector('.ai-generate-btn')) {
                    return;
                }

                // Crear botón.
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ai-generate-btn jaraba-btn jaraba-btn--small jaraba-btn--ghost';
                btn.innerHTML = '<span class="ai-icon">✨</span> <span class="ai-text">' + Drupal.t('Generar con IA') + '</span>';
                btn.title = Drupal.t('Generar contenido con inteligencia artificial');

                // Añadir estilos inline para garantizar visibilidad.
                btn.style.cssText = `
          display: inline-flex;
          align-items: center;
          gap: 4px;
          margin-top: 8px;
          padding: 6px 12px;
          font-size: 13px;
          border-radius: 6px;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          border: none;
          cursor: pointer;
          transition: all 0.2s ease;
        `;

                // Hover effect.
                btn.addEventListener('mouseenter', function () {
                    btn.style.transform = 'translateY(-1px)';
                    btn.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.4)';
                });
                btn.addEventListener('mouseleave', function () {
                    btn.style.transform = '';
                    btn.style.boxShadow = '';
                });

                // Click handler.
                btn.addEventListener('click', function () {
                    generateContent(field, btn);
                });

                // Insertar botón después del campo o su descripción.
                const description = wrapper.querySelector('.description, .form-item__description');
                if (description) {
                    description.after(btn);
                } else {
                    field.after(btn);
                }
            }

            /**
             * Genera contenido usando el API.
             */
            async function generateContent(field, btn) {
                const originalHtml = btn.innerHTML;

                // Estado de carga.
                btn.disabled = true;
                btn.innerHTML = '<span class="ai-spinner">⏳</span> ' + Drupal.t('Generando...');
                btn.style.opacity = '0.7';

                try {
                    // Determinar tipo de campo por nombre.
                    const fieldType = detectFieldType(field);

                    // Obtener contexto de la página.
                    const pageContext = getPageContext(field);

                    const response = await fetch(API_ENDPOINT, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            field_type: fieldType,
                            context: pageContext,
                            current_value: field.value,
                        }),
                    });

                    const data = await response.json();

                    if (data.success && data.content) {
                        // Aplicar contenido generado.
                        field.value = data.content;

                        // Disparar eventos para actualizar el form.
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));

                        // Feedback visual de éxito.
                        btn.innerHTML = '<span class="ai-success">✅</span> ' + Drupal.t('¡Generado!');
                        btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';

                        // Si hay variantes, mostrar selector.
                        if (data.variants && data.variants.length > 1) {
                            showVariantsDropdown(field, btn, data.variants);
                        }

                        // Restaurar botón después de 2s.
                        setTimeout(function () {
                            btn.innerHTML = originalHtml;
                            btn.disabled = false;
                            btn.style.opacity = '';
                            btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        }, 2000);
                    } else {
                        throw new Error(data.error || Drupal.t('Error desconocido'));
                    }
                } catch (error) {
                    console.error('AI Generation error:', error);

                    // Feedback de error.
                    btn.innerHTML = '<span class="ai-error">❌</span> ' + Drupal.t('Error');
                    btn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';

                    // Restaurar botón después de 2s.
                    setTimeout(function () {
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                        btn.style.opacity = '';
                        btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                    }, 2000);
                }
            }

            /**
             * Detecta el tipo de campo por su nombre.
             */
            function detectFieldType(field) {
                const name = (field.name || '').toLowerCase();
                const id = (field.id || '').toLowerCase();
                const placeholder = (field.placeholder || '').toLowerCase();
                const combined = name + ' ' + id + ' ' + placeholder;

                if (combined.match(/title|titulo|headline|heading/)) {
                    return 'headline';
                }
                if (combined.match(/description|descripcion|subtitle|subtitulo/)) {
                    return 'description';
                }
                if (combined.match(/cta|button|boton|action/)) {
                    return 'cta';
                }
                if (field.tagName === 'TEXTAREA') {
                    return 'text';
                }

                return 'headline';
            }

            /**
             * Obtiene contexto de la página para mejor generación.
             */
            function getPageContext(field) {
                const context = {
                    template_name: '',
                    page_title: '',
                    vertical: 'general',
                    field_name: field.name || '',
                };

                // Buscar nombre de template en URL o form.
                const urlParams = new URLSearchParams(window.location.search);
                const pathParts = window.location.pathname.split('/');

                // Template name desde la URL.
                if (pathParts.includes('create')) {
                    const templateIndex = pathParts.indexOf('create') + 1;
                    if (templateIndex < pathParts.length) {
                        context.template_name = pathParts[templateIndex].replace(/-/g, ' ');
                    }
                }

                // Buscar título de página en la página.
                const pageTitle = document.querySelector('h1, .page-title, [class*="title"]');
                if (pageTitle) {
                    context.page_title = pageTitle.textContent.trim();
                }

                // Detectar vertical desde body classes.
                const bodyClasses = document.body.className;
                if (bodyClasses.match(/empleo|job|talent/)) {
                    context.vertical = 'empleabilidad';
                } else if (bodyClasses.match(/emprend|business|startup/)) {
                    context.vertical = 'emprendimiento';
                } else if (bodyClasses.match(/agro|producer|commerce/)) {
                    context.vertical = 'agroconecta';
                } else if (bodyClasses.match(/comercio|shop|store/)) {
                    context.vertical = 'comercioconecta';
                } else if (bodyClasses.match(/servicio|professional/)) {
                    context.vertical = 'serviciosconecta';
                }

                return context;
            }

            /**
             * Muestra dropdown con variantes alternativas.
             */
            function showVariantsDropdown(field, btn, variants) {
                // Remover dropdown existente.
                const existingDropdown = btn.parentElement.querySelector('.ai-variants-dropdown');
                if (existingDropdown) {
                    existingDropdown.remove();
                }

                // Crear dropdown.
                const dropdown = document.createElement('div');
                dropdown.className = 'ai-variants-dropdown';
                dropdown.style.cssText = `
          position: absolute;
          z-index: 1000;
          background: white;
          border: 1px solid #e5e7eb;
          border-radius: 8px;
          box-shadow: 0 4px 20px rgba(0,0,0,0.15);
          margin-top: 8px;
          padding: 8px 0;
          min-width: 250px;
          max-width: 400px;
        `;

                // Añadir header.
                const header = document.createElement('div');
                header.style.cssText = 'padding: 8px 12px; font-size: 12px; color: #6b7280; border-bottom: 1px solid #e5e7eb;';
                header.textContent = Drupal.t('Variantes alternativas:');
                dropdown.appendChild(header);

                // Añadir variantes.
                variants.forEach(function (variant, index) {
                    if (variant === field.value) return; // Skip current.

                    const option = document.createElement('button');
                    option.type = 'button';
                    option.style.cssText = `
            display: block;
            width: 100%;
            text-align: left;
            padding: 10px 12px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 14px;
            color: #374151;
            transition: background 0.15s;
          `;
                    option.textContent = variant;

                    option.addEventListener('mouseenter', function () {
                        option.style.background = '#f3f4f6';
                    });
                    option.addEventListener('mouseleave', function () {
                        option.style.background = 'none';
                    });
                    option.addEventListener('click', function () {
                        field.value = variant;
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                        dropdown.remove();
                    });

                    dropdown.appendChild(option);
                });

                // Posicionar y mostrar.
                btn.style.position = 'relative';
                btn.after(dropdown);

                // Cerrar al hacer clic fuera.
                setTimeout(function () {
                    document.addEventListener('click', function closeDropdown(e) {
                        if (!dropdown.contains(e.target) && e.target !== btn) {
                            dropdown.remove();
                            document.removeEventListener('click', closeDropdown);
                        }
                    });
                }, 100);
            }
        },
    };

})(Drupal, once);
