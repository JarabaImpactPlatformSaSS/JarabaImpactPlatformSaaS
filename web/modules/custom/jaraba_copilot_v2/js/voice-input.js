/**
 * @file
 * GAP-AUD-011: Voice AI Interface for Copilot widget.
 *
 * Uses Web Speech API (SpeechRecognition) for client-side voice-to-text.
 * Gracefully degrades: mic button only appears if browser supports it.
 * Feature-gated for professional/enterprise plans via drupalSettings.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.copilotVoiceInput = {
    attach: function (context) {
      // Feature gate: only show if voice is enabled for this tenant.
      if (!drupalSettings.copilotVoice || !drupalSettings.copilotVoice.enabled) {
        return;
      }

      // Check browser support for SpeechRecognition.
      var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
      if (!SpeechRecognition) {
        return;
      }

      var inputAreas = once('voice-input', '[data-copilot-input], .copilot-chat__input', context);
      inputAreas.forEach(function (inputArea) {
        var inputField = inputArea.querySelector('.copilot-chat__input-field, textarea, input[type="text"]');
        if (!inputField) { return; }

        // Create mic button.
        var micBtn = document.createElement('button');
        micBtn.type = 'button';
        micBtn.className = 'copilot-voice-btn';
        micBtn.setAttribute('aria-label', Drupal.t('Voice input'));
        micBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
          '<path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>' +
          '<path d="M19 10v2a7 7 0 0 1-14 0v-2"/>' +
          '<line x1="12" y1="19" x2="12" y2="23"/>' +
          '<line x1="8" y1="23" x2="16" y2="23"/>' +
          '</svg>';

        // Insert mic button before send button or at end of input area.
        var sendBtn = inputArea.querySelector('.copilot-chat__send-btn');
        if (sendBtn) {
          inputArea.insertBefore(micBtn, sendBtn);
        } else {
          inputArea.appendChild(micBtn);
        }

        // State.
        var isRecording = false;
        var recognition = null;

        micBtn.addEventListener('click', function () {
          if (isRecording) {
            stopRecording();
          } else {
            startRecording();
          }
        });

        function startRecording() {
          recognition = new SpeechRecognition();
          recognition.lang = drupalSettings.copilotVoice.language || 'es-ES';
          recognition.continuous = false;
          recognition.interimResults = true;
          recognition.maxAlternatives = 1;

          recognition.onstart = function () {
            isRecording = true;
            micBtn.classList.add('copilot-voice-btn--recording');
            micBtn.setAttribute('aria-label', Drupal.t('Stop recording'));
          };

          recognition.onresult = function (event) {
            var transcript = '';
            for (var i = event.resultIndex; i < event.results.length; i++) {
              transcript += event.results[i][0].transcript;
            }

            // Show interim results in the input field.
            inputField.value = transcript;
            inputField.dispatchEvent(new Event('input', { bubbles: true }));

            // If final result, auto-submit.
            if (event.results[event.results.length - 1].isFinal) {
              inputField.value = transcript;
              inputField.dispatchEvent(new Event('input', { bubbles: true }));

              // Trigger send after a short delay.
              setTimeout(function () {
                var submitBtn = inputArea.querySelector('.copilot-chat__send-btn');
                if (submitBtn && !submitBtn.disabled) {
                  submitBtn.click();
                }
              }, 300);
            }
          };

          recognition.onerror = function (event) {
            stopRecording();
            if (event.error !== 'aborted' && event.error !== 'no-speech') {
              console.warn('Voice recognition error:', event.error);
            }
          };

          recognition.onend = function () {
            stopRecording();
          };

          recognition.start();
        }

        function stopRecording() {
          isRecording = false;
          micBtn.classList.remove('copilot-voice-btn--recording');
          micBtn.setAttribute('aria-label', Drupal.t('Voice input'));
          if (recognition) {
            recognition.abort();
            recognition = null;
          }
        }
      });
    },
  };

})(Drupal, drupalSettings, once);
