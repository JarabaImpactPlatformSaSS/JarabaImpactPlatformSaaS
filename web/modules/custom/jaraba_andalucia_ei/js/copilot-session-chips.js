(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.aeiCopilotSessionChips = {
    attach: function (context) {
      // Find copilot chat input area
      once('aei-copilot-chips', '[data-copilot-input], .copilot-chat__input-area', context).forEach(function (inputArea) {
        var config = drupalSettings.jarabaAndaluciaEi || {};
        var sessionPrompts = config.sessionPrompts || {};
        var currentSession = config.currentSession || '';

        if (!currentSession || !sessionPrompts[currentSession]) return;

        var prompts = sessionPrompts[currentSession];
        if (!Array.isArray(prompts) || prompts.length === 0) return;

        // Create chips container
        var chipsContainer = document.createElement('div');
        chipsContainer.className = 'copilot-session-chips';
        chipsContainer.setAttribute('aria-label', Drupal.t('Sugerencias de conversación'));

        prompts.forEach(function (promptText) {
          var chip = document.createElement('button');
          chip.type = 'button';
          chip.className = 'copilot-session-chips__chip';
          chip.textContent = promptText.length > 60 ? promptText.substring(0, 57) + '...' : promptText;
          chip.setAttribute('title', promptText);
          chip.setAttribute('data-full-prompt', promptText);

          chip.addEventListener('click', function () {
            // Find the chat input textarea
            var textarea = inputArea.querySelector('textarea, input[type="text"]');
            if (textarea) {
              textarea.value = promptText;
              textarea.dispatchEvent(new Event('input', { bubbles: true }));
              // Auto-focus
              textarea.focus();
            }
            // Remove chips after selection
            chipsContainer.remove();
          });

          chipsContainer.appendChild(chip);
        });

        // Insert before the input area
        inputArea.parentNode.insertBefore(chipsContainer, inputArea);
      });
    }
  };
})(Drupal, drupalSettings, once);
