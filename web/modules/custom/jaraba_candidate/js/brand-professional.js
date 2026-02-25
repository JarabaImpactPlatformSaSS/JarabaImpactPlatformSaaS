/**
 * @file
 * AI-assisted professional branding for candidate profiles.
 *
 * Handles 3 AI flows via the EmployabilityCopilotAgent (profile_coach mode):
 * - generate_headline: Generates 3 headline options
 * - optimize_summary: Improves existing summary text
 * - generate_summary: Creates a new summary from scratch
 *
 * Communicates with POST /api/v1/copilot/employability/chat
 * using CSRF token pattern (CSRF-JS-CACHE-001).
 */
(function (Drupal) {
  'use strict';

  // ── CSRF token cache (CSRF-JS-CACHE-001) ────────────────────────
  var _csrfTokenPromise = null;
  function getCsrfToken() {
    if (!_csrfTokenPromise) {
      _csrfTokenPromise = fetch('/session/token')
        .then(function (r) { return r.text(); });
    }
    return _csrfTokenPromise;
  }

  // ── Previous summary backup for "undo" ──────────────────────────
  var _previousSummary = '';

  /**
   * Send a message to the copilot in profile_coach mode.
   *
   * @param {string} message
   *   The prompt to send.
   * @return {Promise<object>}
   *   Resolves with {success, response, mode}.
   */
  function chatWithCopilot(message) {
    return getCsrfToken().then(function (token) {
      return fetch('/api/v1/copilot/employability/chat?_format=json', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': token,
        },
        body: JSON.stringify({
          message: message,
          mode: 'profile_coach',
        }),
      });
    }).then(function (r) {
      if (!r.ok) {
        throw new Error('HTTP ' + r.status);
      }
      return r.json();
    });
  }

  /**
   * Gather current form context values for AI prompts.
   *
   * @param {HTMLElement} form
   *   The form element.
   * @return {object}
   *   Context with headline, summary, level, years, education.
   */
  function getFormContext(form) {
    var ctx = {
      headline: '',
      summary: '',
      experience_level: '',
      experience_years: '',
      education_level: '',
    };

    // headline: text input
    var headlineInput = form.querySelector('[name*="headline"]');
    if (headlineInput) {
      ctx.headline = headlineInput.value || '';
    }

    // summary: textarea (text_long widget)
    var summaryInput = form.querySelector('[name*="summary"]');
    if (summaryInput) {
      ctx.summary = summaryInput.value || '';
    }

    // experience_level: select
    var levelSelect = form.querySelector('[name*="experience_level"]');
    if (levelSelect) {
      var opt = levelSelect.options ? levelSelect.options[levelSelect.selectedIndex] : null;
      ctx.experience_level = opt ? opt.text : '';
    }

    // experience_years: number input
    var yearsInput = form.querySelector('[name*="experience_years"]');
    if (yearsInput) {
      ctx.experience_years = yearsInput.value || '';
    }

    // education_level: select
    var eduSelect = form.querySelector('[name*="education_level"]');
    if (eduSelect) {
      var eduOpt = eduSelect.options ? eduSelect.options[eduSelect.selectedIndex] : null;
      ctx.education_level = eduOpt ? eduOpt.text : '';
    }

    return ctx;
  }

  /**
   * Set a field value in the form.
   *
   * @param {HTMLElement} form
   * @param {string} fieldName
   * @param {string} value
   */
  function setFieldValue(form, fieldName, value) {
    var input = form.querySelector('[name*="' + fieldName + '"]');
    if (input) {
      input.value = value;
      input.dispatchEvent(new Event('change', { bubbles: true }));
      input.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  /**
   * Show a spinner on a button.
   */
  function showSpinner(btn) {
    btn.disabled = true;
    btn._originalHTML = btn.innerHTML;
    btn.innerHTML = '<span class="ai-spinner"></span> ' + Drupal.checkPlain(Drupal.t('Pensando...'));
  }

  /**
   * Restore a button from spinner state.
   */
  function hideSpinner(btn) {
    btn.disabled = false;
    if (btn._originalHTML) {
      btn.innerHTML = btn._originalHTML;
      delete btn._originalHTML;
    }
  }

  /**
   * Show an error message in a container.
   */
  function showError(container, message) {
    container.hidden = false;
    container.innerHTML = '<div class="ai-error">' + Drupal.checkPlain(message) + '</div>';
  }

  // ══════════════════════════════════════════════════════════════════
  // FLOW 1: Generate Headline — 3 clickable options
  // ══════════════════════════════════════════════════════════════════
  function handleGenerateHeadline(btn) {
    var form = btn.closest('form');
    if (!form) { return; }

    var suggestionsContainer = form.querySelector('[data-ai-suggestions-for="headline"]');
    if (!suggestionsContainer) { return; }

    var ctx = getFormContext(form);
    var prompt = 'Genera 3 opciones de titular profesional.';
    if (ctx.experience_level) {
      prompt += ' Mi nivel es: ' + ctx.experience_level + '.';
    }
    if (ctx.experience_years) {
      prompt += ' Tengo ' + ctx.experience_years + ' años de experiencia.';
    }
    if (ctx.education_level) {
      prompt += ' Formación: ' + ctx.education_level + '.';
    }
    if (ctx.headline) {
      prompt += ' Mi titular actual: ' + ctx.headline + '.';
    }
    prompt += ' Formato: 3 titulares, uno por línea, max 120 chars cada uno. Solo los 3 titulares, sin numeración ni explicaciones.';

    showSpinner(btn);
    suggestionsContainer.hidden = true;

    chatWithCopilot(prompt).then(function (data) {
      hideSpinner(btn);

      if (!data.success || !data.response) {
        showError(suggestionsContainer, Drupal.t('No se pudieron generar sugerencias. Inténtalo de nuevo.'));
        return;
      }

      // Parse 3 lines from response.
      var lines = data.response.split('\n')
        .map(function (l) { return l.replace(/^\d+[\.\)\-]\s*/, '').trim(); })
        .filter(function (l) { return l.length > 0; });

      if (lines.length === 0) {
        showError(suggestionsContainer, Drupal.t('La IA no generó sugerencias válidas.'));
        return;
      }

      // Render clickable suggestion cards.
      var html = '<div class="ai-suggestions">';
      html += '<p class="ai-suggestions__label">' + Drupal.checkPlain(Drupal.t('Elige un titular:')) + '</p>';
      for (var i = 0; i < Math.min(lines.length, 3); i++) {
        html += '<button type="button" class="ai-suggestion-card" data-ai-suggestion-value="' + Drupal.checkPlain(lines[i]) + '">';
        html += Drupal.checkPlain(lines[i]);
        html += '</button>';
      }
      html += '</div>';

      suggestionsContainer.innerHTML = html;
      suggestionsContainer.hidden = false;

      // Bind click on suggestion cards.
      suggestionsContainer.querySelectorAll('[data-ai-suggestion-value]').forEach(function (card) {
        card.addEventListener('click', function () {
          setFieldValue(form, 'headline', card.getAttribute('data-ai-suggestion-value'));
          suggestionsContainer.hidden = true;
        });
      });
    }).catch(function () {
      hideSpinner(btn);
      showError(suggestionsContainer, Drupal.t('Error de conexión. Comprueba tu red e inténtalo de nuevo.'));
    });
  }

  // ══════════════════════════════════════════════════════════════════
  // FLOW 2: Optimize Summary — comparison panel
  // ══════════════════════════════════════════════════════════════════
  function handleOptimizeSummary(btn) {
    var form = btn.closest('form');
    if (!form) { return; }

    var comparisonContainer = form.querySelector('[data-ai-comparison-for="summary"]');
    if (!comparisonContainer) { return; }

    var ctx = getFormContext(form);

    if (!ctx.summary || ctx.summary.trim().length < 10) {
      showError(comparisonContainer, Drupal.t('Escribe al menos un breve resumen antes de optimizarlo.'));
      comparisonContainer.hidden = false;
      return;
    }

    var prompt = 'Optimiza este resumen profesional: "' + ctx.summary + '".';
    if (ctx.experience_level) {
      prompt += ' Contexto: nivel ' + ctx.experience_level + '.';
    }
    if (ctx.experience_years) {
      prompt += ' ' + ctx.experience_years + ' años de experiencia.';
    }
    prompt += ' Mejora: palabras clave, logros cuantificables, propuesta de valor. Max 500 chars. Solo el texto optimizado, sin explicaciones.';

    showSpinner(btn);
    comparisonContainer.hidden = true;
    _previousSummary = ctx.summary;

    chatWithCopilot(prompt).then(function (data) {
      hideSpinner(btn);

      if (!data.success || !data.response) {
        showError(comparisonContainer, Drupal.t('No se pudo optimizar el resumen. Inténtalo de nuevo.'));
        return;
      }

      var optimized = data.response.trim();

      // Render comparison panel.
      var html = '<div class="ai-comparison">';
      html += '<div class="ai-comparison__column">';
      html += '<p class="ai-comparison__label">' + Drupal.checkPlain(Drupal.t('Original')) + '</p>';
      html += '<div class="ai-comparison__text">' + Drupal.checkPlain(_previousSummary) + '</div>';
      html += '</div>';
      html += '<div class="ai-comparison__column ai-comparison__column--new">';
      html += '<p class="ai-comparison__label">' + Drupal.checkPlain(Drupal.t('Optimizado por IA')) + '</p>';
      html += '<div class="ai-comparison__text">' + Drupal.checkPlain(optimized) + '</div>';
      html += '</div>';
      html += '<div class="ai-comparison__actions">';
      html += '<button type="button" class="ai-comparison__apply button--primary">' + Drupal.checkPlain(Drupal.t('Aplicar optimizado')) + '</button>';
      html += '<button type="button" class="ai-comparison__discard">' + Drupal.checkPlain(Drupal.t('Descartar')) + '</button>';
      html += '</div>';
      html += '</div>';

      comparisonContainer.innerHTML = html;
      comparisonContainer.hidden = false;

      // Bind apply button.
      var applyBtn = comparisonContainer.querySelector('.ai-comparison__apply');
      if (applyBtn) {
        applyBtn.addEventListener('click', function () {
          setFieldValue(form, 'summary', optimized);
          comparisonContainer.hidden = true;
        });
      }

      // Bind discard button.
      var discardBtn = comparisonContainer.querySelector('.ai-comparison__discard');
      if (discardBtn) {
        discardBtn.addEventListener('click', function () {
          comparisonContainer.hidden = true;
        });
      }
    }).catch(function () {
      hideSpinner(btn);
      showError(comparisonContainer, Drupal.t('Error de conexión. Comprueba tu red e inténtalo de nuevo.'));
    });
  }

  // ══════════════════════════════════════════════════════════════════
  // FLOW 3: Generate Summary — fill textarea + undo link
  // ══════════════════════════════════════════════════════════════════
  function handleGenerateSummary(btn) {
    var form = btn.closest('form');
    if (!form) { return; }

    var comparisonContainer = form.querySelector('[data-ai-comparison-for="summary"]');
    if (!comparisonContainer) { return; }

    var ctx = getFormContext(form);
    _previousSummary = ctx.summary || '';

    var prompt = 'Genera un resumen profesional.';
    if (ctx.experience_level) {
      prompt += ' Nivel: ' + ctx.experience_level + '.';
    }
    if (ctx.experience_years) {
      prompt += ' Experiencia: ' + ctx.experience_years + ' años.';
    }
    if (ctx.education_level) {
      prompt += ' Formación: ' + ctx.education_level + '.';
    }
    if (ctx.headline) {
      prompt += ' Titular: ' + ctx.headline + '.';
    }
    prompt += ' Incluye: propuesta de valor, expertise, qué busco. Max 500 chars. Solo el texto, sin explicaciones.';

    showSpinner(btn);
    comparisonContainer.hidden = true;

    chatWithCopilot(prompt).then(function (data) {
      hideSpinner(btn);

      if (!data.success || !data.response) {
        showError(comparisonContainer, Drupal.t('No se pudo generar el resumen. Inténtalo de nuevo.'));
        return;
      }

      var generated = data.response.trim();
      setFieldValue(form, 'summary', generated);

      // Show undo link if there was previous content.
      if (_previousSummary) {
        var html = '<div class="ai-undo">';
        html += '<button type="button" class="ai-undo__link">';
        html += Drupal.checkPlain(Drupal.t('Recuperar texto anterior'));
        html += '</button>';
        html += '</div>';

        comparisonContainer.innerHTML = html;
        comparisonContainer.hidden = false;

        var undoBtn = comparisonContainer.querySelector('.ai-undo__link');
        if (undoBtn) {
          undoBtn.addEventListener('click', function () {
            setFieldValue(form, 'summary', _previousSummary);
            comparisonContainer.hidden = true;
          });
        }
      }
    }).catch(function () {
      hideSpinner(btn);
      showError(comparisonContainer, Drupal.t('Error de conexión. Comprueba tu red e inténtalo de nuevo.'));
    });
  }

  // ══════════════════════════════════════════════════════════════════
  // Drupal Behavior — bind AI buttons
  // ══════════════════════════════════════════════════════════════════
  Drupal.behaviors.brandProfessional = {
    attach: function (context) {
      var buttons = context.querySelectorAll
        ? context.querySelectorAll('[data-ai-action]:not([data-ai-processed])')
        : [];

      buttons.forEach(function (btn) {
        btn.setAttribute('data-ai-processed', 'true');
        var action = btn.getAttribute('data-ai-action');

        btn.addEventListener('click', function () {
          switch (action) {
            case 'generate_headline':
              handleGenerateHeadline(btn);
              break;
            case 'optimize_summary':
              handleOptimizeSummary(btn);
              break;
            case 'generate_summary':
              handleGenerateSummary(btn);
              break;
          }
        });
      });
    },
  };

})(Drupal);
