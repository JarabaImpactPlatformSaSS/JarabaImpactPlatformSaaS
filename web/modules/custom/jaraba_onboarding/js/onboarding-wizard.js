/**
 * @file
 * Onboarding Wizard — 7-step interactive navigation.
 *
 * Handles step navigation, form data collection, logo upload preview,
 * live color preview, NIF validation, team invite rows, and confetti.
 *
 * Fase 5 — Doc 179.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Wizard behavior — attaches to the wizard container.
   */
  Drupal.behaviors.jarabaOnboardingWizard = {
    attach(context) {
      once('jaraba-wizard', '.onboarding-wizard', context).forEach(function (wizard) {
        const config = drupalSettings.jarabaWizard || {};
        new OnboardingWizard(wizard, config);
      });
    }
  };

  /**
   * OnboardingWizard class.
   */
  function OnboardingWizard(el, config) {
    this.el = el;
    this.config = config;
    this.currentStep = config.currentStep || 1;
    this.apiUrls = config.apiUrls || {};
    this.isSubmitting = false;

    this.bindActions();
    this.bindLogoUpload();
    this.bindColorInputs();
    this.bindVerticalSelector();
    this.bindTeamRows();
    this.bindNifValidation();

    // Trigger confetti on launch step.
    if (this.currentStep === 7) {
      this.triggerConfetti();
    }
  }

  /**
   * Bind wizard action buttons (next, back, skip, launch).
   */
  OnboardingWizard.prototype.bindActions = function () {
    var self = this;

    this.el.querySelectorAll('[data-wizard-action]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (self.isSubmitting) return;

        var action = btn.getAttribute('data-wizard-action');
        switch (action) {
          case 'next':
            self.advanceStep();
            break;
          case 'back':
            self.goBack();
            break;
          case 'skip':
            self.skipStep();
            break;
          case 'launch':
            self.launchWizard();
            break;
        }
      });
    });
  };

  /**
   * Collect form data from current step.
   */
  OnboardingWizard.prototype.collectStepData = function () {
    var data = {};
    this.el.querySelectorAll('[data-field]').forEach(function (field) {
      var key = field.getAttribute('data-field');
      if (field.type === 'color') {
        data[key] = field.value;
      } else if (field.type === 'file') {
        // Skip file inputs in data collection.
      } else {
        data[key] = field.value;
      }
    });
    return data;
  };

  /**
   * Advance to next step via API.
   */
  OnboardingWizard.prototype.advanceStep = function () {
    var self = this;
    var stepData = this.collectStepData();

    this.isSubmitting = true;
    this.setButtonsLoading(true);

    fetch(this.apiUrls.advance, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ step_data: stepData })
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        self.isSubmitting = false;
        self.setButtonsLoading(false);

        if (result.success && result.data && result.data.next_url) {
          window.location.href = result.data.next_url;
        } else {
          self.showError(result.error || Drupal.t('Error avanzando al siguiente paso.'));
        }
      })
      .catch(function () {
        self.isSubmitting = false;
        self.setButtonsLoading(false);
        self.showError(Drupal.t('Error de conexion. Intenta de nuevo.'));
      });
  };

  /**
   * Skip current step via API.
   */
  OnboardingWizard.prototype.skipStep = function () {
    var self = this;
    this.isSubmitting = true;

    fetch(this.apiUrls.skip, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({})
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        self.isSubmitting = false;
        if (result.success && result.data && result.data.next_url) {
          window.location.href = result.data.next_url;
        }
      })
      .catch(function () {
        self.isSubmitting = false;
      });
  };

  /**
   * Go back to previous step.
   */
  OnboardingWizard.prototype.goBack = function () {
    window.history.back();
  };

  /**
   * Launch wizard (final step).
   */
  OnboardingWizard.prototype.launchWizard = function () {
    var self = this;
    var stepData = this.collectStepData();

    this.isSubmitting = true;
    this.setButtonsLoading(true);

    fetch(this.apiUrls.advance, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ step_data: stepData })
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        self.isSubmitting = false;
        self.setButtonsLoading(false);

        if (result.success) {
          self.triggerConfetti();
          // Redirect to dashboard after celebration.
          setTimeout(function () {
            window.location.href = '/onboarding';
          }, 3000);
        }
      })
      .catch(function () {
        self.isSubmitting = false;
        self.setButtonsLoading(false);
      });
  };

  /**
   * Logo upload preview + color extraction.
   */
  OnboardingWizard.prototype.bindLogoUpload = function () {
    var self = this;
    var uploadArea = this.el.querySelector('[data-logo-upload]');
    var input = this.el.querySelector('[data-logo-input]');
    var preview = this.el.querySelector('[data-logo-preview]');

    if (!uploadArea || !input) return;

    // Drag & drop.
    uploadArea.addEventListener('dragover', function (e) {
      e.preventDefault();
      uploadArea.classList.add('is-dragover');
    });

    uploadArea.addEventListener('dragleave', function () {
      uploadArea.classList.remove('is-dragover');
    });

    uploadArea.addEventListener('drop', function (e) {
      e.preventDefault();
      uploadArea.classList.remove('is-dragover');
      if (e.dataTransfer.files.length > 0) {
        input.files = e.dataTransfer.files;
        self.handleLogoFile(e.dataTransfer.files[0], preview);
      }
    });

    input.addEventListener('change', function () {
      if (input.files.length > 0) {
        self.handleLogoFile(input.files[0], preview);
      }
    });
  };

  /**
   * Handle logo file — preview + color extraction.
   */
  OnboardingWizard.prototype.handleLogoFile = function (file, preview) {
    if (!file || !file.type.startsWith('image/')) return;
    if (file.size > 2 * 1024 * 1024) {
      this.showError(Drupal.t('El archivo excede 2 MB.'));
      return;
    }

    var reader = new FileReader();
    reader.onload = function (e) {
      if (preview) {
        preview.innerHTML = '<img src="' + e.target.result + '" alt="Logo preview" />';
      }
    };
    reader.readAsDataURL(file);
  };

  /**
   * Bind color inputs for live preview.
   */
  OnboardingWizard.prototype.bindColorInputs = function () {
    var previewHeader = this.el.querySelector('[data-preview-header]');
    var previewName = this.el.querySelector('[data-preview-name]');
    var nameField = this.el.querySelector('[data-field="business_name"]');
    var taglineField = this.el.querySelector('[data-field="tagline"]');
    var previewTagline = this.el.querySelector('[data-preview-tagline]');

    this.el.querySelectorAll('.wizard-step__color-input').forEach(function (input) {
      var hexSpan = input.parentElement.querySelector('[data-color-hex]');

      input.addEventListener('input', function () {
        if (hexSpan) {
          hexSpan.textContent = input.value.toUpperCase();
        }
        if (previewHeader && input.getAttribute('data-field') === 'color_primary') {
          previewHeader.style.background = input.value;
        }
      });
    });

    // Live name preview.
    if (nameField && previewName) {
      nameField.addEventListener('input', function () {
        previewName.textContent = nameField.value || Drupal.t('Mi Negocio');
      });
    }

    if (taglineField && previewTagline) {
      taglineField.addEventListener('input', function () {
        previewTagline.textContent = taglineField.value;
      });
    }
  };

  /**
   * Bind vertical selector on welcome step.
   */
  OnboardingWizard.prototype.bindVerticalSelector = function () {
    var options = this.el.querySelectorAll('[data-vertical]');
    options.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var vertical = btn.getAttribute('data-vertical');
        var url = new URL(window.location.href);
        url.searchParams.set('vertical', vertical);
        window.location.href = url.toString();
      });
    });
  };

  /**
   * Bind add team row button.
   */
  OnboardingWizard.prototype.bindTeamRows = function () {
    var addBtn = this.el.querySelector('[data-add-team-row]');
    var container = this.el.querySelector('[data-team-invites]');

    if (!addBtn || !container) return;

    var rowCount = 1;
    addBtn.addEventListener('click', function () {
      rowCount++;
      if (rowCount > 5) return; // Limit to 5 invites.

      var row = document.createElement('div');
      row.className = 'wizard-step__team-row';
      row.innerHTML =
        '<div class="wizard-step__field">' +
        '<input type="email" class="wizard-step__input" data-field="team_email_' + rowCount + '" placeholder="' + Drupal.t('correo@ejemplo.com') + '" />' +
        '</div>' +
        '<div class="wizard-step__field">' +
        '<select class="wizard-step__select" data-field="team_role_' + rowCount + '">' +
        '<option value="editor">' + Drupal.t('Editor') + '</option>' +
        '<option value="viewer">' + Drupal.t('Solo lectura') + '</option>' +
        '<option value="admin">' + Drupal.t('Administrador') + '</option>' +
        '</select>' +
        '</div>';
      container.appendChild(row);
    });
  };

  /**
   * Bind NIF/CIF validation on fiscal step.
   */
  OnboardingWizard.prototype.bindNifValidation = function () {
    var nifInput = this.el.querySelector('[data-field="nif"]');
    var errorSpan = this.el.querySelector('[data-nif-error]');

    if (!nifInput) return;

    nifInput.addEventListener('blur', function () {
      var value = nifInput.value.toUpperCase().trim();
      nifInput.value = value;

      if (value.length === 0) {
        if (errorSpan) errorSpan.style.display = 'none';
        return;
      }

      if (!validateSpanishNif(value)) {
        nifInput.style.borderColor = 'var(--ej-color-danger, #ef4444)';
        if (errorSpan) {
          errorSpan.textContent = Drupal.t('NIF/CIF no valido. Verifica el formato.');
          errorSpan.style.display = 'block';
        }
      } else {
        nifInput.style.borderColor = 'var(--ej-color-success, #22c55e)';
        if (errorSpan) errorSpan.style.display = 'none';
      }
    });
  };

  /**
   * Spanish NIF/CIF validation (client-side).
   */
  function validateSpanishNif(nif) {
    if (nif.length !== 9) return false;

    var letters = 'TRWAGMYFPDXBNJZSQVHLCKE';

    // DNI: 8 digits + letter.
    if (/^[0-9]{8}[A-Z]$/.test(nif)) {
      var num = parseInt(nif.substring(0, 8), 10);
      return nif[8] === letters[num % 23];
    }

    // NIE: X/Y/Z + 7 digits + letter.
    if (/^[XYZ][0-9]{7}[A-Z]$/.test(nif)) {
      var replaced = nif.replace('X', '0').replace('Y', '1').replace('Z', '2');
      var nieNum = parseInt(replaced.substring(0, 8), 10);
      return nif[8] === letters[nieNum % 23];
    }

    // CIF: Letter + 7 digits + control.
    if (/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[A-J0-9]$/.test(nif)) {
      return true; // Simplified check for client-side.
    }

    return false;
  }

  /**
   * Set buttons loading state.
   */
  OnboardingWizard.prototype.setButtonsLoading = function (loading) {
    this.el.querySelectorAll('.wizard__btn--next, .wizard__btn--launch').forEach(function (btn) {
      btn.disabled = loading;
      if (loading) {
        btn.dataset.originalText = btn.textContent;
        btn.textContent = Drupal.t('Procesando...');
      } else if (btn.dataset.originalText) {
        btn.textContent = btn.dataset.originalText;
      }
    });
  };

  /**
   * Show error message.
   */
  OnboardingWizard.prototype.showError = function (message) {
    // Use Drupal messages if available.
    if (typeof Drupal.Message !== 'undefined') {
      var messages = new Drupal.Message();
      messages.add(message, { type: 'error' });
    } else {
      // Fallback: alert.
      window.alert(message);
    }
  };

  /**
   * Confetti animation for launch step.
   */
  OnboardingWizard.prototype.triggerConfetti = function () {
    var canvas = this.el.querySelector('[data-confetti-canvas]');
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    var particles = [];
    var colors = ['#FF8C42', '#233D63', '#00A9A5', '#22c55e', '#556B2F', '#f59e0b', '#8b5cf6'];

    // Create particles.
    for (var i = 0; i < 150; i++) {
      particles.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height - canvas.height,
        w: Math.random() * 10 + 5,
        h: Math.random() * 6 + 3,
        color: colors[Math.floor(Math.random() * colors.length)],
        speed: Math.random() * 3 + 2,
        angle: Math.random() * 360,
        spin: (Math.random() - 0.5) * 8
      });
    }

    var frame = 0;
    var maxFrames = 180; // ~3 seconds at 60fps.

    function animate() {
      if (frame >= maxFrames) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        return;
      }

      ctx.clearRect(0, 0, canvas.width, canvas.height);

      particles.forEach(function (p) {
        p.y += p.speed;
        p.angle += p.spin;

        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate((p.angle * Math.PI) / 180);
        ctx.fillStyle = p.color;
        ctx.globalAlpha = Math.max(0, 1 - frame / maxFrames);
        ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
        ctx.restore();
      });

      frame++;
      requestAnimationFrame(animate);
    }

    animate();
  };

})(Drupal, drupalSettings, once);
