/**
 * @file
 * JavaScript para la consola de pruebas del Copiloto.
 *
 * PROPÓSITO:
 * Maneja el chat interactivo para probar el conocimiento del tenant.
 * Envía preguntas a la API y muestra las respuestas con streaming.
 *
 * PATRÓN:
 * Usa Drupal behaviors y drupalSettings para configuración.
 */

(function (Drupal, once, drupalSettings) {
    'use strict';

    /**
     * Comportamiento principal de la consola de pruebas.
     */
    Drupal.behaviors.jarabaKnowledgeTestConsole = {
        attach: function (context) {
            const form = once('test-console-form', '#test-console-form', context);

            if (form.length) {
                form[0].addEventListener('submit', handleFormSubmit);
            }

            // Manejar clicks en sugerencias.
            const suggestions = once('suggestion-chips', '.suggestion-chip', context);
            suggestions.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    const question = this.dataset.question;
                    document.getElementById('test-console-input').value = question;
                    document.getElementById('test-console-form').dispatchEvent(new Event('submit'));
                });
            });
        }
    };

    /**
     * Maneja el envío del formulario de chat.
     */
    function handleFormSubmit(e) {
        e.preventDefault();

        const input = document.getElementById('test-console-input');
        const messagesContainer = document.getElementById('test-console-messages');
        const submitBtn = document.getElementById('test-console-submit');
        const question = input.value.trim();

        if (!question) return;

        // Deshabilitar input mientras procesa.
        input.disabled = true;
        submitBtn.disabled = true;

        // Añadir mensaje del usuario.
        addMessage(messagesContainer, question, 'user');

        // Limpiar input.
        input.value = '';

        // Llamar a la API.
        fetchResponse(question)
            .then(function (response) {
                addMessage(messagesContainer, response.answer, 'assistant');
                showSources(response.sources);
            })
            .catch(function (error) {
                addMessage(messagesContainer, Drupal.t('Error al procesar la pregunta. Intenta de nuevo.'), 'error');
                console.error('Test console error:', error);
            })
            .finally(function () {
                input.disabled = false;
                submitBtn.disabled = false;
                input.focus();
            });
    }

    /**
     * Añade un mensaje al contenedor de chat.
     */
    function addMessage(container, text, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message chat-message--' + type;

        const contentDiv = document.createElement('div');
        contentDiv.className = 'chat-message__content';
        contentDiv.textContent = text;

        messageDiv.appendChild(contentDiv);
        container.appendChild(messageDiv);

        // Scroll al final.
        container.scrollTop = container.scrollHeight;
    }

    /**
     * Muestra las fuentes utilizadas para la respuesta.
     */
    function showSources(sources) {
        const container = document.getElementById('context-sources');
        if (!container || !sources || sources.length === 0) return;

        container.innerHTML = '';

        sources.forEach(function (source) {
            const chip = document.createElement('span');
            chip.className = 'source-chip source-chip--' + source.type;
            chip.textContent = source.label;
            container.appendChild(chip);
        });
    }

    /**
     * Llama a la API de pruebas.
     */
    async function fetchResponse(question) {
        const apiUrl = drupalSettings.jarabaTenantKnowledge?.testApiUrl || '/api/v1/knowledge/test';

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ question: question }),
        });

        if (!response.ok) {
            throw new Error('API error: ' + response.status);
        }

        return await response.json();
    }

})(Drupal, once, drupalSettings);
