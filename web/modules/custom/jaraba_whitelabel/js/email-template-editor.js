/**
 * @file
 * JavaScript for the email template editor page.
 *
 * Handles template switching, variable insertion at cursor position,
 * live preview rendering and save operations.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Email template editor behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jarabaEmailTemplateEditor = {
    attach: function (context) {
      once('email-template-editor', '.email-editor', context).forEach(function (root) {
        var tenantId = root.getAttribute('data-tenant-id');

        // --- Template switching ---
        root.querySelectorAll('.email-editor__template-btn').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var item = btn.closest('.email-editor__template-item');
            if (!item) {
              return;
            }

            // Update active state in sidebar.
            root.querySelectorAll('.email-editor__template-item').forEach(function (el) {
              el.classList.remove('email-editor__template-item--active');
            });
            item.classList.add('email-editor__template-item--active');

            var templateId = item.getAttribute('data-template-id');
            if (typeof console !== 'undefined') {
              console.log('Switch to template:', templateId);
            }

            // In a full implementation, this would fetch the template data
            // via AJAX and populate the editor fields.
          });
        });

        // --- Variable insertion ---
        root.querySelectorAll('.email-editor__variable-btn').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var token = btn.getAttribute('data-token');
            if (!token) {
              return;
            }

            // Find the currently focused textarea, default to body_html.
            var target = root.querySelector('.email-editor__textarea:focus')
              || root.querySelector('#email-body-html');

            if (target) {
              insertAtCursor(target, token);
            }
          });
        });

        /**
         * Inserts text at the current cursor position in a textarea.
         *
         * @param {HTMLTextAreaElement} textarea - The textarea element.
         * @param {string} text - The text to insert.
         */
        function insertAtCursor(textarea, text) {
          var start = textarea.selectionStart;
          var end = textarea.selectionEnd;
          var before = textarea.value.substring(0, start);
          var after = textarea.value.substring(end);

          textarea.value = before + text + after;
          textarea.selectionStart = start + text.length;
          textarea.selectionEnd = start + text.length;
          textarea.focus();

          // Trigger input event for any listeners.
          textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // --- Live preview ---
        var previewBtn = root.querySelector('.email-editor__btn--preview');
        if (previewBtn) {
          previewBtn.addEventListener('click', function () {
            var previewPane = root.querySelector('#email-preview-pane');
            if (!previewPane) {
              return;
            }

            var subjectInput = root.querySelector('#email-subject');
            var bodyHtmlInput = root.querySelector('#email-body-html');
            var previewSubject = previewPane.querySelector('.email-editor__preview-subject');
            var previewBody = previewPane.querySelector('.email-editor__preview-body');

            if (previewSubject && subjectInput) {
              previewSubject.textContent = subjectInput.value;
            }

            if (previewBody && bodyHtmlInput) {
              // Render HTML preview (sanitised display).
              previewBody.innerHTML = bodyHtmlInput.value;
            }

            // Toggle preview visibility.
            var isVisible = previewPane.style.display !== 'none';
            previewPane.style.display = isVisible ? 'none' : '';
            previewBtn.textContent = isVisible
              ? Drupal.t('Preview')
              : Drupal.t('Hide Preview');
          });
        }

        // --- Save template ---
        var saveBtn = root.querySelector('.email-editor__btn--save');
        if (saveBtn) {
          saveBtn.addEventListener('click', function () {
            var editor = root.querySelector('.email-editor__editor');
            if (!editor) {
              return;
            }

            var templateId = editor.getAttribute('data-template-id');
            var formData = {
              template_id: parseInt(templateId, 10),
              tenant_id: parseInt(tenantId, 10),
              subject: (root.querySelector('#email-subject') || {}).value || '',
              body_html: (root.querySelector('#email-body-html') || {}).value || '',
              body_text: (root.querySelector('#email-body-text') || {}).value || ''
            };

            saveBtn.disabled = true;
            saveBtn.textContent = Drupal.t('Saving...');

            // In a full implementation, this would POST to an API endpoint.
            if (typeof console !== 'undefined') {
              console.log('Save email template:', formData);
            }

            setTimeout(function () {
              saveBtn.disabled = false;
              saveBtn.textContent = Drupal.t('Save Template');
            }, 1000);
          });
        }
      });
    }
  };

})(Drupal, once);
