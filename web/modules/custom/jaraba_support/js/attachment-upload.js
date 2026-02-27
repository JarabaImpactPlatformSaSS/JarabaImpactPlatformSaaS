/**
 * @file
 * Attachment Upload — Drag-and-drop file upload for support tickets.
 *
 * Gestiona:
 * - Drag-and-drop upload zone
 * - Click-to-browse fallback
 * - File validation (type, size)
 * - Upload progress visual feedback
 * - File list with remove button
 *
 * DIRECTRICES:
 * - CSRF-JS-CACHE-001: Token cacheado
 * - INNERHTML-XSS-001: Drupal.checkPlain()
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  let csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then((r) => r.text());
    }
    return csrfTokenPromise;
  }

  const ALLOWED_TYPES = [
    'image/png', 'image/jpeg', 'image/gif',
    'application/pdf',
    'application/zip', 'application/x-zip-compressed',
    'text/plain',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  ];

  const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB.

  /**
   * Attachment Upload behavior.
   */
  Drupal.behaviors.supportAttachmentUpload = {
    attach(context) {
      once('support-attachment-upload', '[data-support-dropzone]', context).forEach((dropzone) => {
        new AttachmentUploader(dropzone);
      });
    },
  };

  /**
   * AttachmentUploader manages drag-and-drop and file input.
   */
  class AttachmentUploader {
    constructor(dropzone) {
      this.dropzone = dropzone;
      this.fileInput = dropzone.querySelector('.support-ticket-create__file-input');
      this.fileList = dropzone.closest('.support-ticket-create__field')
        ?.querySelector('[data-support-file-list]');
      this.files = [];

      this.bindEvents();
    }

    bindEvents() {
      // Click to open file dialog.
      this.dropzone.addEventListener('click', (e) => {
        if (e.target.closest('.support-ticket-create__file-input')) return;
        this.fileInput?.click();
      });

      // File input change.
      if (this.fileInput) {
        this.fileInput.addEventListener('change', () => {
          this.addFiles(Array.from(this.fileInput.files));
          this.fileInput.value = '';
        });
      }

      // Drag events.
      ['dragenter', 'dragover'].forEach((evt) => {
        this.dropzone.addEventListener(evt, (e) => {
          e.preventDefault();
          e.stopPropagation();
          this.dropzone.classList.add('support-ticket-create__dropzone--active');
        });
      });

      ['dragleave', 'drop'].forEach((evt) => {
        this.dropzone.addEventListener(evt, (e) => {
          e.preventDefault();
          e.stopPropagation();
          this.dropzone.classList.remove('support-ticket-create__dropzone--active');
        });
      });

      this.dropzone.addEventListener('drop', (e) => {
        const files = Array.from(e.dataTransfer?.files || []);
        this.addFiles(files);
      });
    }

    /**
     * Validates and adds files to the queue.
     */
    addFiles(files) {
      files.forEach((file) => {
        // Validate type.
        if (!ALLOWED_TYPES.includes(file.type)) {
          Drupal.announce(
            Drupal.t('File type not allowed: @name', { '@name': file.name }),
            'assertive'
          );
          return;
        }

        // Validate size.
        if (file.size > MAX_FILE_SIZE) {
          Drupal.announce(
            Drupal.t('File too large: @name (max 10MB)', { '@name': file.name }),
            'assertive'
          );
          return;
        }

        this.files.push(file);
        this.renderFileItem(file, this.files.length - 1);
      });
    }

    /**
     * Renders a file item in the list.
     */
    renderFileItem(file, index) {
      if (!this.fileList) return;

      const item = document.createElement('div');
      item.className = 'support-attachment support-attachment--pending';
      item.dataset.fileIndex = index;

      const sizeKb = (file.size / 1024).toFixed(1);
      item.innerHTML =
        '<span class="support-attachment__name">' + Drupal.checkPlain(file.name) + '</span>' +
        '<span class="support-attachment__size">' + sizeKb + ' KB</span>' +
        '<button type="button" class="support-attachment__remove" aria-label="' +
        Drupal.t('Remove') + '">&times;</button>';

      item.querySelector('.support-attachment__remove').addEventListener('click', () => {
        this.files[index] = null;
        item.remove();
      });

      this.fileList.appendChild(item);
    }

    /**
     * Returns all valid files for form submission.
     */
    getFiles() {
      return this.files.filter((f) => f !== null);
    }
  }

  // Expose for form submission integration.
  Drupal.supportAttachmentUploader = AttachmentUploader;

})(Drupal, drupalSettings, once);
