/**
 * @file
 * Wizard de diagnostico express de empleabilidad.
 *
 * PROPOSITO:
 * Gestiona el flujo de 3 pasos del wizard, la captura de respuestas,
 * el envio AJAX al backend y la animacion de resultados.
 *
 * ESTRUCTURA:
 * - Wizard steps navigation (avance automatico al seleccionar opcion)
 * - AJAX submit al endpoint POST /empleabilidad/diagnostico
 * - Animacion del score ring SVG
 * - Render de resultados en el DOM
 *
 * SPEC: 20260120b S3
 */
(function (Drupal) {
  'use strict';

  // Cache CSRF token for reuse across requests.
  var _csrfTokenPromise = null;
  function getCsrfToken() {
    if (!_csrfTokenPromise) {
      _csrfTokenPromise = fetch('/session/token')
        .then(function (r) { return r.text(); });
    }
    return _csrfTokenPromise;
  }

  Drupal.behaviors.employabilityDiagnostic = {
    attach: function (context) {
      var wizard = context.querySelector('[data-diagnostic-wizard]');
      if (!wizard || wizard.dataset.diagnosticInitialized) {
        return;
      }
      wizard.dataset.diagnosticInitialized = 'true';

      var answers = {};
      var totalSteps = 3;
      var currentStep = 0;

      // Elementos del DOM.
      var heroEl = wizard.querySelector('.ej-diagnostic__hero');
      var progressEl = wizard.querySelector('[data-diagnostic-progress]');
      var stepsEl = wizard.querySelector('[data-diagnostic-steps]');
      var resultsEl = wizard.querySelector('[data-diagnostic-results]');
      var loadingEl = wizard.querySelector('[data-diagnostic-loading]');
      var startBtn = wizard.querySelector('[data-diagnostic-start]');

      // Boton iniciar.
      if (startBtn) {
        startBtn.addEventListener('click', function () {
          heroEl.style.display = 'none';
          progressEl.style.display = 'block';
          stepsEl.style.display = 'block';
          currentStep = 1;
          updateProgress();
        });
      }

      // Opciones de respuesta.
      var options = wizard.querySelectorAll('.ej-diagnostic__option');
      options.forEach(function (option) {
        option.addEventListener('click', function () {
          var questionId = this.dataset.question;
          var value = parseInt(this.dataset.value, 10);

          // Marcar seleccionada.
          var siblings = wizard.querySelectorAll('[data-question="' + questionId + '"]');
          siblings.forEach(function (s) { s.classList.remove('ej-diagnostic__option--selected'); });
          this.classList.add('ej-diagnostic__option--selected');

          answers[questionId] = value;

          // Avanzar al siguiente paso tras breve delay.
          setTimeout(function () {
            currentStep++;
            if (currentStep <= totalSteps) {
              showStep(currentStep);
              updateProgress();
            } else {
              showEmailStep();
            }
          }, 300);
        });
      });

      // Paso de email.
      var submitBtn = wizard.querySelector('[data-diagnostic-submit]');
      var skipBtn = wizard.querySelector('[data-diagnostic-skip]');

      if (submitBtn) {
        submitBtn.addEventListener('click', function () {
          var emailInput = wizard.querySelector('[data-diagnostic-email]');
          answers.email = emailInput ? emailInput.value : '';
          submitDiagnostic();
        });
      }

      if (skipBtn) {
        skipBtn.addEventListener('click', function () {
          submitDiagnostic();
        });
      }

      // Reiniciar.
      var restartBtn = wizard.querySelector('[data-diagnostic-restart]');
      if (restartBtn) {
        restartBtn.addEventListener('click', function () {
          answers = {};
          currentStep = 0;
          resultsEl.style.display = 'none';
          heroEl.style.display = 'block';
          progressEl.style.display = 'none';
          stepsEl.style.display = 'none';
          // Deseleccionar opciones.
          options.forEach(function (o) { o.classList.remove('ej-diagnostic__option--selected'); });
        });
      }

      /**
       * Muestra un paso especifico del wizard.
       */
      function showStep(step) {
        var steps = wizard.querySelectorAll('[data-step]');
        steps.forEach(function (s) { s.style.display = 'none'; });
        var target = wizard.querySelector('[data-step="' + step + '"]');
        if (target) {
          target.style.display = 'block';
        }
      }

      /**
       * Muestra el paso de captura de email.
       */
      function showEmailStep() {
        var steps = wizard.querySelectorAll('.ej-diagnostic__step');
        steps.forEach(function (s) { s.style.display = 'none'; });
        var emailStep = wizard.querySelector('[data-step="email"]');
        if (emailStep) {
          emailStep.style.display = 'block';
        }
        updateProgress(true);
      }

      /**
       * Actualiza la barra de progreso.
       */
      function updateProgress(complete) {
        var fill = wizard.querySelector('[data-progress-fill]');
        var text = wizard.querySelector('[data-progress-text]');
        var percent = complete ? 100 : (currentStep / totalSteps) * 100;
        if (fill) fill.style.width = percent + '%';
        if (text) text.textContent = complete
          ? Drupal.t('Completado')
          : Drupal.t('Paso @current de @total', { '@current': currentStep, '@total': totalSteps });
      }

      /**
       * Envia las respuestas al backend via AJAX.
       */
      function submitDiagnostic() {
        stepsEl.style.display = 'none';
        progressEl.style.display = 'none';
        loadingEl.style.display = 'block';

        var payload = {
          q_linkedin: answers.q_linkedin || 1,
          q_cv_ats: answers.q_cv_ats || 1,
          q_estrategia: answers.q_estrategia || 1,
          email: answers.email || ''
        };

        getCsrfToken().then(function (csrfToken) {
        fetch('/empleabilidad/diagnostico', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify(payload)
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          loadingEl.style.display = 'none';
          renderResults(data);
        })
        .catch(function (error) {
          loadingEl.style.display = 'none';
          console.error('Error en diagnostico:', error);
          stepsEl.style.display = 'block';
          progressEl.style.display = 'block';
        });
        }); // CSRF token fetch
      }

      /**
       * Renderiza los resultados en el DOM con animaciones.
       */
      function renderResults(data) {
        resultsEl.style.display = 'block';

        // Animar score ring.
        animateScoreRing(data.score);

        // Perfil.
        var profileLabel = resultsEl.querySelector('[data-profile-label]');
        var profileDesc = resultsEl.querySelector('[data-profile-description]');
        if (profileLabel) profileLabel.textContent = data.profile_label;
        if (profileDesc) profileDesc.textContent = data.profile_description;

        // Dimensiones.
        if (data.dimension_scores) {
          Object.keys(data.dimension_scores).forEach(function (key) {
            var fill = resultsEl.querySelector('[data-dimension-fill="' + key + '"]');
            var value = resultsEl.querySelector('[data-dimension-value="' + key + '"]');
            if (fill) {
              setTimeout(function () {
                fill.style.width = (data.dimension_scores[key] * 10) + '%';
              }, 500);
            }
            if (value) value.textContent = data.dimension_scores[key].toFixed(1);
          });
        }

        // Recomendaciones.
        var recList = resultsEl.querySelector('[data-recommendation-list]');
        if (recList && data.recommendations) {
          recList.innerHTML = '';
          data.recommendations.forEach(function (rec) {
            var recEl = document.createElement('div');
            recEl.className = 'ej-diagnostic__recommendation';
            recEl.innerHTML = '<h4>' + Drupal.checkPlain(rec.title) + '</h4>' +
              '<p>' + Drupal.checkPlain(rec.description) + '</p>' +
              (rec.action ? '<span class="ej-diagnostic__recommendation-action">' + Drupal.checkPlain(rec.action) + '</span>' : '');
            recList.appendChild(recEl);
          });
        }

        // Actualizar link de registro con UUID del diagnostico.
        var registerLink = resultsEl.querySelector('[data-register-link]');
        if (registerLink && data.uuid) {
          registerLink.href = '/user/register?diagnostic=' + data.uuid;
        }
      }

      /**
       * Anima el score ring SVG con transicion suave.
       */
      function animateScoreRing(score) {
        var arc = resultsEl.querySelector('[data-score-arc]');
        var valueEl = resultsEl.querySelector('[data-score-value]');
        var circumference = 339.3;
        var targetOffset = circumference - (score / 10 * circumference);

        // Determinar color segun score.
        var color = score >= 8 ? 'var(--ej-success, #10B981)' :
                    score >= 6 ? 'var(--ej-color-primary, #1565C0)' :
                    score >= 4 ? 'var(--ej-warning, #F59E0B)' :
                                 'var(--ej-danger, #EF4444)';

        if (arc) {
          arc.style.transition = 'stroke-dashoffset 1.5s ease-out, stroke 0.5s ease';
          arc.style.stroke = color;
          setTimeout(function () {
            arc.style.strokeDashoffset = targetOffset;
          }, 100);
        }

        // Animar numero.
        if (valueEl) {
          var start = 0;
          var duration = 1500;
          var startTime = null;

          function animateNumber(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var current = (progress * score).toFixed(1);
            valueEl.textContent = current;
            if (progress < 1) {
              requestAnimationFrame(animateNumber);
            }
          }
          requestAnimationFrame(animateNumber);
        }
      }
    }
  };

})(Drupal);
