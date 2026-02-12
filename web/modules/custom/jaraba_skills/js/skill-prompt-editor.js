/**
 * @file
 * EDITOR AVANZADO DE PROMPTS CON MONACO
 *
 * PROPÓSITO:
 * Proporciona experiencia de edición profesional para prompts de skills IA
 * con syntax highlighting, autocompletado de variables y preview en tiempo real.
 *
 * CARACTERÍSTICAS:
 * - Lenguaje custom 'jaraba-prompt' con tokens para variables y tags
 * - Autocompletado de variables de contexto ({{vertical}}, {{tenant}}, etc.)
 * - Tema oscuro profesional
 * - Sincronización bidireccional con textarea original
 *
 * INTEGRACIÓN:
 * Se adjunta a cualquier textarea con clase 'skill-prompt-editor'.
 * El textarea original se oculta y Monaco sincroniza los cambios.
 *
 * DEPENDENCIA:
 * Monaco Editor cargado desde CDN (vs.min.js).
 *
 * @see jaraba_skills.libraries.yml
 * @see AiSkillForm.php
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Variables de contexto disponibles para autocompletado.
     *
     * ESTRUCTURA:
     * Cada variable tiene:
     * - label: Nombre para mostrar y insertar
     * - detail: Descripción para tooltip
     * - documentation: Documentación extendida
     */
    const CONTEXT_VARIABLES = [
        { label: '{{vertical}}', detail: 'ID de la vertical activa', documentation: 'Ej: emprendimiento, empleabilidad, comercio' },
        { label: '{{vertical_name}}', detail: 'Nombre de la vertical', documentation: 'Nombre legible de la vertical actual' },
        { label: '{{tenant_id}}', detail: 'ID del tenant actual', documentation: 'Identificador numérico del tenant' },
        { label: '{{tenant_name}}', detail: 'Nombre del negocio', documentation: 'Nombre comercial del tenant' },
        { label: '{{agent_type}}', detail: 'Tipo de agente IA', documentation: 'Ej: sales, support, diagnostic, mentor' },
        { label: '{{user_role}}', detail: 'Rol del usuario', documentation: 'Rol actual del usuario en la plataforma' },
        { label: '{{user_name}}', detail: 'Nombre del usuario', documentation: 'Nombre del usuario que interactúa' },
        { label: '{{language}}', detail: 'Idioma actual', documentation: 'Código de idioma: es, en, etc.' },
        { label: '{{current_date}}', detail: 'Fecha actual', documentation: 'Fecha en formato ISO' },
        { label: '{{session_context}}', detail: 'Contexto de la sesión', documentation: 'JSON con datos de la conversación actual' },
    ];

    /**
     * Tags XML estructurales para prompts.
     */
    const STRUCTURE_TAGS = [
        { label: '<instructions>', detail: 'Bloque de instrucciones principales' },
        { label: '</instructions>', detail: 'Cierre de instrucciones' },
        { label: '<context>', detail: 'Bloque de contexto' },
        { label: '</context>', detail: 'Cierre de contexto' },
        { label: '<examples>', detail: 'Bloque de ejemplos' },
        { label: '</examples>', detail: 'Cierre de ejemplos' },
        { label: '<rules>', detail: 'Bloque de reglas' },
        { label: '</rules>', detail: 'Cierre de reglas' },
        { label: '<output_format>', detail: 'Formato de salida esperado' },
        { label: '</output_format>', detail: 'Cierre de formato' },
    ];

    /**
     * Configuración del tema personalizado para prompts.
     */
    const JARABA_THEME = {
        base: 'vs-dark',
        inherit: true,
        rules: [
            { token: 'variable', foreground: '9CDCFE', fontStyle: 'bold' },
            { token: 'tag', foreground: '4EC9B0' },
            { token: 'tag.open', foreground: '4EC9B0' },
            { token: 'tag.close', foreground: '4EC9B0' },
            { token: 'comment', foreground: '6A9955', fontStyle: 'italic' },
            { token: 'string', foreground: 'CE9178' },
            { token: 'keyword', foreground: 'C586C0' },
        ],
        colors: {
            'editor.background': '#1E1E2E',
            'editor.foreground': '#CDD6F4',
            'editor.lineHighlightBackground': '#313244',
            'editor.selectionBackground': '#45475A',
            'editorCursor.foreground': '#F5E0DC',
            'editorWhitespace.foreground': '#45475A',
        }
    };

    /**
     * Behavior principal para el editor de prompts.
     */
    Drupal.behaviors.skillPromptEditor = {
        attach: function (context) {
            // Buscar textareas del campo content que usen el editor.
            const textareas = once('skill-prompt-editor', 'textarea[name="content[0][value]"]', context);

            if (textareas.length === 0) {
                return;
            }

            // Cargar Monaco desde CDN si no está disponible.
            this.loadMonaco().then(() => {
                textareas.forEach((textarea) => {
                    this.initializeEditor(textarea);
                });
            }).catch((error) => {
                console.error('[SkillEditor] Error cargando Monaco:', error);
                // Fallback: mantener textarea original.
            });
        },

        /**
         * Carga Monaco Editor desde CDN.
         *
         * @return {Promise}
         *   Promise que resuelve cuando Monaco está listo.
         */
        loadMonaco: function () {
            return new Promise((resolve, reject) => {
                // Si ya está cargado, resolver inmediatamente.
                if (typeof window.monaco !== 'undefined') {
                    resolve();
                    return;
                }

                // Si require está definido (AMD loader), Monaco ya está disponible.
                if (typeof require !== 'undefined' && require.defined && require.defined('vs/editor/editor.main')) {
                    require(['vs/editor/editor.main'], resolve);
                    return;
                }

                // Cargar loader desde CDN.
                const loaderScript = document.createElement('script');
                loaderScript.src = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js';
                loaderScript.onload = () => {
                    // Configurar paths de Monaco.
                    require.config({
                        paths: {
                            'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs'
                        }
                    });

                    // Cargar editor principal.
                    require(['vs/editor/editor.main'], () => {
                        this.registerLanguageAndTheme();
                        resolve();
                    });
                };
                loaderScript.onerror = reject;
                document.head.appendChild(loaderScript);
            });
        },

        /**
         * Registra el lenguaje custom y tema para prompts.
         */
        registerLanguageAndTheme: function () {
            // Registrar lenguaje 'jaraba-prompt'.
            monaco.languages.register({ id: 'jaraba-prompt' });

            // Definir tokenización.
            monaco.languages.setMonarchTokensProvider('jaraba-prompt', {
                tokenizer: {
                    root: [
                        // Variables con doble llave: {{variable}}
                        [/\{\{[a-zA-Z_][a-zA-Z0-9_]*\}\}/, 'variable'],
                        // Tags XML: <tag> y </tag>
                        [/<\/[a-zA-Z_][a-zA-Z0-9_]*>/, 'tag.close'],
                        [/<[a-zA-Z_][a-zA-Z0-9_]*>/, 'tag.open'],
                        // Comentarios estilo XML: <!-- -->
                        [/<!--/, 'comment', '@comment'],
                        // Strings entre comillas
                        [/"[^"]*"/, 'string'],
                        [/'[^']*'/, 'string'],
                        // Keywords comunes en prompts
                        [/\b(IMPORTANTE|NOTA|REGLA|EJEMPLO|CONTEXTO|INSTRUCCIONES)\b/i, 'keyword'],
                    ],
                    comment: [
                        [/-->/, 'comment', '@pop'],
                        [/./, 'comment'],
                    ],
                }
            });

            // Registrar provider de autocompletado.
            monaco.languages.registerCompletionItemProvider('jaraba-prompt', {
                triggerCharacters: ['{', '<'],
                provideCompletionItems: (model, position) => {
                    const word = model.getWordUntilPosition(position);
                    const range = {
                        startLineNumber: position.lineNumber,
                        endLineNumber: position.lineNumber,
                        startColumn: word.startColumn,
                        endColumn: word.endColumn,
                    };

                    // Obtener caracter anterior para contexto.
                    const lineContent = model.getLineContent(position.lineNumber);
                    const charBefore = lineContent.charAt(position.column - 2);

                    const suggestions = [];

                    // Sugerir variables si se escribió '{'
                    if (charBefore === '{') {
                        CONTEXT_VARIABLES.forEach(v => {
                            suggestions.push({
                                label: v.label,
                                kind: monaco.languages.CompletionItemKind.Variable,
                                insertText: v.label.substring(1), // Quitar primera llave
                                detail: v.detail,
                                documentation: v.documentation,
                                range: range,
                            });
                        });
                    }

                    // Sugerir tags si se escribió '<'
                    if (charBefore === '<') {
                        STRUCTURE_TAGS.forEach(t => {
                            suggestions.push({
                                label: t.label,
                                kind: monaco.languages.CompletionItemKind.Snippet,
                                insertText: t.label.substring(1), // Quitar '<'
                                detail: t.detail,
                                range: range,
                            });
                        });
                    }

                    // Sugerencias generales.
                    if (suggestions.length === 0) {
                        CONTEXT_VARIABLES.forEach(v => {
                            suggestions.push({
                                label: v.label,
                                kind: monaco.languages.CompletionItemKind.Variable,
                                insertText: v.label,
                                detail: v.detail,
                                documentation: v.documentation,
                                range: range,
                            });
                        });
                    }

                    return { suggestions };
                }
            });

            // Registrar tema custom.
            monaco.editor.defineTheme('jaraba-prompt-theme', JARABA_THEME);
        },

        /**
         * Inicializa el editor Monaco sobre un textarea.
         *
         * @param {HTMLTextAreaElement} textarea
         *   El textarea original del formulario.
         */
        initializeEditor: function (textarea) {
            // Crear contenedor para Monaco.
            const container = document.createElement('div');
            container.className = 'monaco-editor-container';
            container.style.cssText = `
        width: 100%;
        height: 400px;
        border: 1px solid var(--ej-border-color, #45475A);
        border-radius: 8px;
        overflow: hidden;
        margin-top: 8px;
      `;

            // Insertar contenedor después del textarea.
            textarea.parentNode.insertBefore(container, textarea.nextSibling);

            // Ocultar textarea original.
            textarea.style.display = 'none';

            // Crear editor Monaco.
            const editor = monaco.editor.create(container, {
                value: textarea.value || '',
                language: 'jaraba-prompt',
                theme: 'jaraba-prompt-theme',
                minimap: { enabled: false },
                lineNumbers: 'on',
                wordWrap: 'on',
                fontSize: 14,
                fontFamily: "'JetBrains Mono', 'Fira Code', 'Consolas', monospace",
                scrollBeyondLastLine: false,
                automaticLayout: true,
                tabSize: 2,
                insertSpaces: true,
                roundedSelection: true,
                cursorBlinking: 'smooth',
                cursorSmoothCaretAnimation: 'on',
                smoothScrolling: true,
                padding: { top: 12, bottom: 12 },
                suggest: {
                    showWords: false,
                    showSnippets: true,
                    showVariables: true,
                },
            });

            // Sincronizar cambios de Monaco → Textarea.
            editor.onDidChangeModelContent(() => {
                textarea.value = editor.getValue();
                // Disparar evento change para que Drupal detecte cambios.
                textarea.dispatchEvent(new Event('change', { bubbles: true }));
            });

            // Guardar referencia para cleanup.
            container.monacoEditor = editor;

            // Añadir barra de herramientas.
            this.addToolbar(container, editor);

            console.log('[SkillEditor] Monaco Editor inicializado correctamente.');
        },

        /**
         * Añade barra de herramientas sobre el editor.
         *
         * @param {HTMLElement} container
         *   Contenedor del editor.
         * @param {monaco.editor.IStandaloneCodeEditor} editor
         *   Instancia de Monaco.
         */
        addToolbar: function (container, editor) {
            const toolbar = document.createElement('div');
            toolbar.className = 'monaco-editor-toolbar';
            toolbar.style.cssText = `
        display: flex;
        gap: 8px;
        padding: 8px 12px;
        background: var(--ej-bg-surface, #1E1E2E);
        border-bottom: 1px solid var(--ej-border-color, #45475A);
        border-radius: 8px 8px 0 0;
      `;

            // Botón insertar variable.
            const insertVarBtn = document.createElement('button');
            insertVarBtn.type = 'button';
            insertVarBtn.className = 'btn btn--small btn--secondary';
            insertVarBtn.innerHTML = '{{ }} Variable';
            insertVarBtn.title = 'Insertar variable de contexto';
            insertVarBtn.onclick = () => {
                // Mostrar lista de variables.
                const vars = CONTEXT_VARIABLES.map(v => v.label).join('\n');
                const selected = prompt('Variables disponibles:\n\n' + vars + '\n\nEscribe el nombre de la variable:');
                if (selected) {
                    const varToInsert = selected.startsWith('{{') ? selected : `{{${selected}}}`;
                    editor.trigger('keyboard', 'type', { text: varToInsert });
                }
            };

            // Botón insertar tag.
            const insertTagBtn = document.createElement('button');
            insertTagBtn.type = 'button';
            insertTagBtn.className = 'btn btn--small btn--secondary';
            insertTagBtn.innerHTML = '&lt;/&gt; Tag';
            insertTagBtn.title = 'Insertar tag estructural';
            insertTagBtn.onclick = () => {
                const tags = ['instructions', 'context', 'examples', 'rules', 'output_format'];
                const selected = prompt('Tags disponibles:\n\n' + tags.join('\n') + '\n\nEscribe el nombre del tag:');
                if (selected && tags.includes(selected)) {
                    editor.trigger('keyboard', 'type', { text: `<${selected}>\n\n</${selected}>` });
                    // Mover cursor al centro.
                    const position = editor.getPosition();
                    editor.setPosition({ lineNumber: position.lineNumber - 1, column: 1 });
                }
            };

            // Botón formatear.
            const formatBtn = document.createElement('button');
            formatBtn.type = 'button';
            formatBtn.className = 'btn btn--small btn--secondary';
            formatBtn.innerHTML = '⚡ Formatear';
            formatBtn.title = 'Formatear contenido (Ctrl+Shift+F)';
            formatBtn.onclick = () => {
                editor.getAction('editor.action.formatDocument').run();
            };

            toolbar.appendChild(insertVarBtn);
            toolbar.appendChild(insertTagBtn);
            toolbar.appendChild(formatBtn);

            // Insertar toolbar antes del editor.
            container.insertBefore(toolbar, container.firstChild);
        },
    };

})(Drupal, once);
