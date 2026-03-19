/**
 * @file
 * Quiz de Recomendación de Vertical — Conversación adaptativa con IA.
 *
 * Client-side rendering desde drupalSettings.verticalQuiz.structure.
 * 3 pasos adaptativos: Q1 (perfil) → Q2 (contextual) → Q3 (contextual).
 * Reacciones IA entre pasos (micro-copy del asesor digital).
 *
 * Directrices cumplidas:
 * - CSRF-JS-CACHE-001: Token cacheado
 * - ROUTE-LANGPREFIX-001: Endpoints via drupalSettings
 * - INNERHTML-XSS-001: Drupal.checkPlain()
 * - ICON-COLOR-001: CSS filter para recolorar SVGs duotone
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var csrfToken = null;
  function getCsrfToken() {
    if (csrfToken) { return Promise.resolve(csrfToken); }
    return fetch(Drupal.url('session/token'))
      .then(function (r) { return r.text(); })
      .then(function (t) { csrfToken = t; return t; });
  }

  function esc(s) { return Drupal.checkPlain(String(s || '')); }

  // ICON-COLOR-001: CSS filter map (JarabaTwigExtension::getColorFilter parity).
  var colorFilters = {
    'verde-innovacion': 'brightness(0) saturate(100%) invert(55%) sepia(50%) saturate(700%) hue-rotate(130deg) brightness(95%)',
    'naranja-impulso': 'brightness(0) saturate(100%) invert(60%) sepia(80%) saturate(500%) hue-rotate(350deg) brightness(100%)',
    'azul-corporativo': 'brightness(0) saturate(100%) invert(19%) sepia(25%) saturate(1500%) hue-rotate(190deg) brightness(95%)',
    'verde-oliva': 'brightness(0) saturate(100%) invert(40%) sepia(15%) saturate(800%) hue-rotate(50deg) brightness(85%)'
  };
  var iconBase = '/modules/custom/ecosistema_jaraba_core/images/icons/';

  function iconImg(cat, name, size, color) {
    var f = colorFilters[color] || 'none';
    return '<img src="' + iconBase + esc(cat) + '/' + esc(name) + '-duotone.svg"'
      + ' width="' + size + '" height="' + size + '" alt="" loading="lazy" aria-hidden="true"'
      + ' style="filter: ' + f + '">';
  }

  function renderOptions(question, stepNum) {
    var cols = question.options.length <= 3 ? 3 : 2;
    var h = '<div class="quiz-step__options quiz-step__options--cols-' + cols + '" role="radiogroup" aria-label="' + esc(question.title) + '">';
    question.options.forEach(function (opt) {
      h += '<button class="quiz-step__option" type="button"'
        + ' data-value="' + esc(opt.value) + '"'
        + ' data-field="' + esc(question.field) + '"'
        + ' role="radio" aria-checked="false"'
        + ' data-track-cta="quiz_' + esc(question.field) + '_' + esc(opt.value) + '"'
        + ' data-track-position="quiz_step_' + stepNum + '">'
        + '<span class="quiz-step__option-icon">' + iconImg(opt.icon_cat, opt.icon_name, 44, opt.color) + '</span>'
        + '<span class="quiz-step__option-text">' + esc(opt.label) + '</span>'
        + '</button>';
    });
    h += '</div>';
    return h;
  }

  Drupal.behaviors.verticalQuiz = {
    attach: function (context) {
      var apps = once('vertical-quiz', '#quiz-app', context);
      if (!apps.length) { return; }
      var app = apps[0];
      var config = drupalSettings.verticalQuiz || {};
      var S = config.structure || {};
      var submitEndpoint = config.submitEndpoint;
      var resultBaseUrl = config.resultBaseUrl;
      if (!S.q1 || !submitEndpoint) { return; }

      var totalSteps = 3;
      var currentStep = 0;
      var answers = {};
      var selectedPerfil = '';

      // Build initial structure: hero + AI bubble + progress + Q1.
      var html = '<div class="quiz-page__inner">';

      // Hero header — ubica e inspira.
      html += '<div class="quiz-hero">'
        + '<div class="quiz-hero__icon">' + iconImg('ai', 'sparkles', 36, 'naranja-impulso') + '</div>'
        + '<h1 class="quiz-hero__title">' + Drupal.t('Encuentra tu solución ideal en 30 segundos') + '</h1>'
        + '<p class="quiz-hero__subtitle">' + Drupal.t('Nuestro asesor con IA analiza tu perfil y te recomienda la herramienta perfecta de entre 9 verticales especializados.') + '</p>'
        + '</div>';

      // AI advisor bubble.
      html += '<div class="quiz-ai">'
        + '<div class="quiz-ai__avatar">' + iconImg('ai', 'sparkles', 24, 'naranja-impulso') + '</div>'
        + '<div class="quiz-ai__bubble" id="quiz-ai-msg">'
        + '<p>' + Drupal.t('Empecemos. Responde a 3 preguntas rápidas y te diré exactamente qué necesitas.') + '</p>'
        + '</div></div>';

      // Progress steps.
      html += '<div class="quiz-page__progress"><div class="quiz-page__progress-steps">';
      for (var p = 0; p < totalSteps; p++) {
        html += '<div class="quiz-page__progress-step' + (p === 0 ? ' quiz-page__progress-step--active' : '') + '" data-progress-step="' + p + '"></div>';
      }
      html += '</div><span class="quiz-page__progress-text">' + Drupal.t('Pregunta 1 de 3') + '</span></div>';

      // Step container.
      html += '<div class="quiz-page__steps" id="quiz-steps">';

      // Q1 (always shown first).
      html += '<div class="quiz-step" data-step="0">'
        + '<h2 class="quiz-step__title">' + esc(S.q1.title) + '</h2>'
        + '<p class="quiz-step__subtitle">' + esc(S.q1.subtitle) + '</p>'
        + renderOptions(S.q1, 1)
        + '</div>';

      // Q2 and Q3 placeholders (filled dynamically after Q1 answer).
      html += '<div class="quiz-step" data-step="1" hidden id="quiz-step-2"></div>';
      html += '<div class="quiz-step" data-step="2" hidden id="quiz-step-3"></div>';

      html += '</div>'; // .quiz-page__steps

      // Loading.
      html += '<div class="quiz-page__loading" hidden id="quiz-loading">'
        + '<div class="quiz-page__spinner"></div>'
        + '<p>' + Drupal.t('Estoy analizando tu perfil con IA para encontrar la mejor solución...') + '</p></div>';

      html += '</div>'; // .quiz-page__inner
      app.innerHTML = html;

      var stepsContainer = app.querySelector('#quiz-steps');
      var steps = app.querySelectorAll('.quiz-step');
      var progressSteps = app.querySelectorAll('.quiz-page__progress-step');
      var progressText = app.querySelector('.quiz-page__progress-text');
      var loadingEl = app.querySelector('#quiz-loading');
      var aiBubble = app.querySelector('#quiz-ai-msg');

      function setAiMessage(msg) {
        if (aiBubble) {
          aiBubble.innerHTML = '<p>' + esc(msg) + '</p>';
          aiBubble.classList.add('quiz-ai__bubble--typing');
          setTimeout(function () { aiBubble.classList.remove('quiz-ai__bubble--typing'); }, 600);
        }
      }

      function updateProgress(step) {
        progressSteps.forEach(function (ps, i) {
          ps.classList.toggle('quiz-page__progress-step--active', i <= step);
          ps.classList.toggle('quiz-page__progress-step--done', i < step);
        });
        if (progressText) {
          progressText.textContent = Drupal.t('Pregunta @current de 3', { '@current': step + 1 });
        }
      }

      function showStep(idx) {
        steps = app.querySelectorAll('.quiz-step'); // Re-query after dynamic insert.
        steps.forEach(function (s, i) { s.hidden = (i !== idx); });
        currentStep = idx;
        updateProgress(idx);
        var first = steps[idx] && steps[idx].querySelector('.quiz-step__option');
        if (first) { setTimeout(function () { first.focus(); }, 200); }
      }

      function buildAdaptiveStep(stepData, stepNum) {
        return '<h2 class="quiz-step__title">' + esc(stepData.title) + '</h2>'
          + '<p class="quiz-step__subtitle">' + esc(stepData.subtitle) + '</p>'
          + renderOptions(stepData, stepNum);
      }

      function submitQuiz() {
        steps.forEach(function (s) { s.hidden = true; });
        if (loadingEl) { loadingEl.hidden = false; }
        setAiMessage(Drupal.t('Dame un momento... estoy preparando tu recomendación personalizada.'));

        getCsrfToken().then(function (token) {
          return fetch(submitEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
            body: JSON.stringify(answers)
          });
        })
        .then(function (r) { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
        .then(function (data) { if (data.uuid) { window.location.href = resultBaseUrl + data.uuid; } })
        .catch(function () {
          if (loadingEl) { loadingEl.hidden = true; }
          showStep(currentStep);
        });
      }

      // Event delegation.
      app.addEventListener('click', function (e) {
        var opt = e.target.closest('.quiz-step__option');
        if (opt) {
          var siblings = opt.closest('.quiz-step__options').querySelectorAll('.quiz-step__option');
          siblings.forEach(function (s) { s.setAttribute('aria-checked', 'false'); });
          opt.setAttribute('aria-checked', 'true');
          var field = opt.dataset.field;
          var value = opt.dataset.value;
          answers[field] = value;

          setTimeout(function () {
            if (currentStep === 0) {
              // After Q1: Build adaptive Q2 + Q3 based on perfil.
              selectedPerfil = value;
              var reactions = S.reactions[selectedPerfil] || {};
              var q2Data = S.q2[selectedPerfil];
              var q3Data = S.q3[selectedPerfil];

              if (reactions.after_q1) { setAiMessage(reactions.after_q1); }

              if (q2Data) {
                var step2El = app.querySelector('#quiz-step-2');
                step2El.innerHTML = buildAdaptiveStep(q2Data, 2);
                if (q3Data) {
                  var step3El = app.querySelector('#quiz-step-3');
                  step3El.innerHTML = buildAdaptiveStep(q3Data, 3)
                    + '<button class="quiz-step__back" type="button">&larr; ' + Drupal.t('Volver') + '</button>';
                }
                showStep(1);
              }
            } else if (currentStep === 1) {
              // After Q2: Show Q3.
              var reactions2 = S.reactions[selectedPerfil] || {};
              if (reactions2.after_q2) { setAiMessage(reactions2.after_q2); }
              showStep(2);
            } else {
              // After Q3: Submit.
              submitQuiz();
            }
          }, 300);
          return;
        }

        var back = e.target.closest('.quiz-step__back');
        if (back && currentStep > 0) { showStep(currentStep - 1); }
      });

      app.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('quiz-step__option')) {
          e.preventDefault(); e.target.click();
        }
      });

      updateProgress(0);
    }
  };

  /**
   * Renderizar resultado.
   */
  Drupal.behaviors.verticalQuizResult = {
    attach: function (context) {
      var apps = once('vertical-quiz-result', '#quiz-result-app', context);
      if (!apps.length) { return; }
      var app = apps[0];
      var r = drupalSettings.verticalQuizResult;
      if (!r) { return; }
      var lp = r.language_prefix || '';

      var html = '<div class="quiz-result__card quiz-result__card--primary">';

      // AI badge.
      html += '<div class="quiz-result__ai-badge">'
        + iconImg('ai', 'sparkles', 20, 'naranja-impulso')
        + ' ' + Drupal.t('Recomendación personalizada con IA') + '</div>';

      html += '<div class="quiz-result__badge">' + Drupal.t('Tu vertical ideal') + '</div>';
      html += '<div class="quiz-result__icon">' + iconImg(r.icon_cat, r.icon_name, 72, r.color) + '</div>';
      html += '<h1 class="quiz-result__title">' + esc(r.vertical_title) + '</h1>';
      if (r.ai_explanation) {
        html += '<p class="quiz-result__explanation">' + esc(r.ai_explanation) + '</p>';
      }
      if (r.benefits && r.benefits.length) {
        html += '<ul class="quiz-result__benefits">';
        r.benefits.forEach(function (b) {
          html += '<li>' + iconImg('actions', 'check-circle', 20, 'verde-innovacion') + ' ' + esc(b) + '</li>';
        });
        html += '</ul>';
      }
      html += '<div class="quiz-result__pricing">' + Drupal.t('Desde') + ' <strong>' + esc(r.price_from)
        + '</strong>/' + Drupal.t('mes') + '<span class="quiz-result__pricing-note">'
        + Drupal.t('Plan gratuito disponible') + '</span></div>';
      // CTA principal.
      if (r.logged_in) {
        html += '<a href="' + esc(lp) + '/addons" class="btn-primary btn-primary--glow quiz-result__cta"'
          + ' data-track-cta="quiz_result_addons" data-track-position="quiz_result">'
          + Drupal.t('Activar este vertical') + ' &rarr;</a>';
      } else {
        html += '<a href="' + esc(lp) + '/user/register?vertical=' + esc(r.vertical_id) + '&amp;source=quiz&amp;quiz_uuid=' + esc(r.uuid)
          + '" class="btn-primary btn-primary--glow quiz-result__cta"'
          + ' data-track-cta="quiz_result_register" data-track-position="quiz_result">'
          + Drupal.t('Empieza gratis con @v', {'@v': esc(r.vertical_title)}) + ' &rarr;</a>';
      }

      // CTA secundario: Demo interactiva (puente quiz→demo).
      if (r.demo_profile) {
        html += '<a href="' + esc(lp) + '/demo/start/' + esc(r.demo_profile)
          + '" class="quiz-result__demo-cta"'
          + ' data-track-cta="quiz_result_demo" data-track-position="quiz_result">'
          + Drupal.t('¿Quieres verlo en acción? Probar demo gratuita de @v', {'@v': esc(r.vertical_title)})
          + '</a>';
      }

      // Risk reversal — reduce fricción.
      html += '<div class="quiz-result__trust-micro">'
        + '<span>' + Drupal.t('Sin tarjeta de crédito') + '</span>'
        + '<span>' + Drupal.t('14 días gratis') + '</span>'
        + '<span>' + Drupal.t('Cancela cuando quieras') + '</span>'
        + '</div>';

      html += '</div>'; // fin card--primary

      // Email capture para no registrados — "Guarda tu resultado".
      if (!r.logged_in) {
        html += '<div class="quiz-result__email-capture" id="quiz-email-capture">'
          + '<h3>' + Drupal.t('¿Aún no estás listo? Guarda tu resultado') + '</h3>'
          + '<p>' + Drupal.t('Te enviaremos tu recomendación personalizada por email.') + '</p>'
          + '<form class="quiz-result__email-form" id="quiz-email-form">'
          + '<input type="email" placeholder="' + Drupal.t('Tu email') + '" required class="quiz-result__email-input" id="quiz-email-input">'
          + '<button type="submit" class="btn-primary btn-primary--sm"'
          + ' data-track-cta="quiz_result_save_email" data-track-position="quiz_result">'
          + Drupal.t('Enviar resultado') + '</button>'
          + '</form>'
          + '<p class="quiz-result__email-success" id="quiz-email-ok" style="display:none">'
          + iconImg('actions', 'check-circle', 20, 'verde-innovacion')
          + ' ' + Drupal.t('¡Enviado! Revisa tu bandeja de entrada.') + '</p>'
          + '</div>';
      }

      // Alternativas.
      if (r.alternatives && r.alternatives.length) {
        html += '<div class="quiz-result__alternatives"><h3>' + Drupal.t('También podría interesarte') + '</h3><div class="quiz-result__alt-grid">';
        r.alternatives.forEach(function (alt) {
          html += '<a href="' + esc(lp) + '/' + esc(alt.path) + '" class="quiz-result__alt-card"'
            + ' data-track-cta="quiz_result_alt_' + esc(alt.id) + '" data-track-position="quiz_result">'
            + iconImg(alt.icon_cat, alt.icon_name, 36, alt.color)
            + '<strong>' + esc(alt.title) + '</strong>'
            + '<small>' + alt.match_pct + '% ' + Drupal.t('de coincidencia') + '</small></a>';
        });
        html += '</div></div>';
      }

      // Share buttons.
      var shareUrl = window.location.href;
      var shareText = Drupal.t('Mi vertical ideal es @v. Descubre el tuyo:', {'@v': esc(r.vertical_title)});
      html += '<div class="quiz-result__share">'
        + '<p>' + Drupal.t('Comparte tu resultado') + '</p>'
        + '<div class="quiz-result__share-buttons">'
        + '<a href="https://twitter.com/intent/tweet?text=' + encodeURIComponent(shareText) + '&url=' + encodeURIComponent(shareUrl)
        + '" target="_blank" rel="noopener" class="quiz-result__share-btn" data-track-cta="quiz_share_twitter" data-track-position="quiz_result">'
        + Drupal.t('Twitter') + '</a>'
        + '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(shareUrl)
        + '" target="_blank" rel="noopener" class="quiz-result__share-btn" data-track-cta="quiz_share_linkedin" data-track-position="quiz_result">'
        + Drupal.t('LinkedIn') + '</a>'
        + '<button class="quiz-result__share-btn" id="quiz-copy-link" data-track-cta="quiz_share_copy" data-track-position="quiz_result">'
        + Drupal.t('Copiar enlace') + '</button>'
        + '</div></div>';

      // Social proof.
      html += '<div class="quiz-result__social-proof"><p>' + Drupal.t('+50.000 profesionales ya usan Jaraba Impact Platform') + '</p></div>';
      app.innerHTML = html;

      // Email capture handler.
      var emailForm = app.querySelector('#quiz-email-form');
      if (emailForm) {
        emailForm.addEventListener('submit', function (ev) {
          ev.preventDefault();
          var emailInput = app.querySelector('#quiz-email-input');
          var email = emailInput ? emailInput.value.trim() : '';
          if (!email) { return; }

          // Guardar email en el QuizResult via API.
          getCsrfToken().then(function (token) {
            return fetch(drupalSettings.verticalQuiz.submitEndpoint, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
              body: JSON.stringify({ perfil: 'email_save', email: email, quiz_uuid: r.uuid })
            });
          }).then(function () {
            emailForm.style.display = 'none';
            var okMsg = app.querySelector('#quiz-email-ok');
            if (okMsg) { okMsg.style.display = 'flex'; }
          });
        });
      }

      // Copy link handler.
      var copyBtn = app.querySelector('#quiz-copy-link');
      if (copyBtn) {
        copyBtn.addEventListener('click', function () {
          navigator.clipboard.writeText(window.location.href).then(function () {
            copyBtn.textContent = Drupal.t('¡Copiado!');
            setTimeout(function () { copyBtn.textContent = Drupal.t('Copiar enlace'); }, 2000);
          });
        });
      }
    }
  };

})(Drupal, drupalSettings, once);
