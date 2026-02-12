/**
 * @file lead-magnet.js
 * Comportamiento para lead magnets publicos por vertical.
 *
 * Implementa:
 * - Calculadora multi-paso con scoring client-side
 * - Formularios de captura de email con validacion
 * - Animacion de analisis (SEO audit)
 * - Tracking de eventos via jaraba_pixels (lead_magnet_start / complete)
 *
 * @see docs/implementacion/2026-02-12_F3_Visitor_Journey_Complete_Doc178_Implementacion.md
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Niveles de madurez digital segun score.
   */
  var MATURITY_LEVELS = [
    {
      max: 20,
      level: Drupal.t('Principiante Digital'),
      description: Drupal.t('Tu negocio tiene mucho potencial de mejora digital. Con los pasos correctos puedes dar un gran salto.'),
      recommendations: [
        Drupal.t('Crea tu presencia online basica (web + Google My Business)'),
        Drupal.t('Empieza a usar herramientas digitales para gestionar clientes'),
        Drupal.t('Define tu estrategia de captacion digital')
      ]
    },
    {
      max: 45,
      level: Drupal.t('Digital Basico'),
      description: Drupal.t('Ya tienes bases digitales pero hay areas clave por explotar. Puedes duplicar tus resultados.'),
      recommendations: [
        Drupal.t('Integra un CRM para gestionar mejor tus ventas'),
        Drupal.t('Implementa marketing digital multicanal'),
        Drupal.t('Automatiza procesos repetitivos con herramientas digitales')
      ]
    },
    {
      max: 70,
      level: Drupal.t('Digital Intermedio'),
      description: Drupal.t('Buen nivel digital. Ahora toca optimizar y escalar con automatizaciones inteligentes.'),
      recommendations: [
        Drupal.t('Implementa automatizaciones de marketing (email, retargeting)'),
        Drupal.t('Integra tus herramientas en un ecosistema conectado'),
        Drupal.t('Mide y optimiza tus KPIs digitales clave')
      ]
    },
    {
      max: 100,
      level: Drupal.t('Digital Avanzado'),
      description: Drupal.t('Excelente nivel de madurez digital. Es momento de innovar con IA y estrategias avanzadas.'),
      recommendations: [
        Drupal.t('Incorpora inteligencia artificial en tus procesos clave'),
        Drupal.t('Implementa analisis predictivo para anticipar tendencias'),
        Drupal.t('Explora nuevos canales y modelos de negocio digitales')
      ]
    }
  ];

  /**
   * Behavior: Calculadora de Madurez Digital.
   */
  Drupal.behaviors.leadMagnetCalculadora = {
    attach: function (context) {
      var forms = once('lead-magnet-calculadora', '.calculadora-form', context);
      forms.forEach(function (form) {
        var steps = form.querySelectorAll('.calculadora-form__step');
        var totalQuestions = parseInt(form.dataset.totalQuestions, 10);
        var currentStep = 0;
        var scores = {};

        // Handle option selection
        form.addEventListener('change', function (e) {
          if (e.target.type !== 'radio') return;

          var stepEl = e.target.closest('.calculadora-form__step');
          var questionId = stepEl.dataset.questionId;
          scores[questionId] = parseInt(e.target.value, 10);

          // Auto-advance after selection (small delay for visual feedback)
          setTimeout(function () {
            advanceStep();
          }, 400);
        });

        // Handle email form submission
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          var emailInput = form.querySelector('.lead-magnet__email-input');
          if (!emailInput || !emailInput.value) return;

          showResult(emailInput.value, form.querySelector('.lead-magnet__name-input'));
        });

        function advanceStep() {
          currentStep++;

          if (currentStep < totalQuestions) {
            // Show next question
            steps.forEach(function (s) { s.hidden = true; s.classList.remove('calculadora-form__step--active'); });
            steps[currentStep].hidden = false;
            steps[currentStep].classList.add('calculadora-form__step--active');
            updateProgress();
          } else {
            // All questions answered, show email capture
            steps.forEach(function (s) { s.hidden = true; s.classList.remove('calculadora-form__step--active'); });
            var emailStep = form.querySelector('[data-step="email"]');
            if (emailStep) {
              emailStep.hidden = false;
              emailStep.classList.add('calculadora-form__step--active');
              var emailInput = emailStep.querySelector('.lead-magnet__email-input');
              if (emailInput) emailInput.focus();
            }
            updateProgress(true);
          }
        }

        function updateProgress(complete) {
          var progressBar = form.querySelector('.calculadora-form__progress-bar');
          var progressText = form.querySelector('.calculadora-form__progress-text');
          var pct = complete ? 100 : ((currentStep / totalQuestions) * 100);

          if (progressBar) {
            progressBar.style.setProperty('--progress', pct + '%');
            var bar = progressBar.querySelector('::after') || progressBar;
            // Use the ::after pseudo-element width
            progressBar.style.cssText = '';
            var afterStyle = document.createElement('style');
            afterStyle.textContent = '.calculadora-form__progress-bar::after { width: ' + pct + '% !important; }';
            // Remove any previous dynamic style
            var prevStyle = form.querySelector('[data-progress-style]');
            if (prevStyle) prevStyle.remove();
            afterStyle.dataset.progressStyle = '';
            form.appendChild(afterStyle);
          }

          if (progressText && !complete) {
            progressText.textContent = Drupal.t('Pregunta @current de @total', {
              '@current': currentStep + 1,
              '@total': totalQuestions
            });
          } else if (progressText) {
            progressText.textContent = Drupal.t('Ultimo paso');
          }
        }

        function showResult(email) {
          var totalScore = 0;
          Object.keys(scores).forEach(function (key) {
            totalScore += scores[key];
          });

          // Hide form, show result
          form.hidden = true;
          var resultEl = form.closest('.lead-magnet').querySelector('.calculadora-result');
          if (!resultEl) return;
          resultEl.hidden = false;

          // Find level
          var levelData = MATURITY_LEVELS[MATURITY_LEVELS.length - 1];
          for (var i = 0; i < MATURITY_LEVELS.length; i++) {
            if (totalScore <= MATURITY_LEVELS[i].max) {
              levelData = MATURITY_LEVELS[i];
              break;
            }
          }

          // Animate score circle
          var scoreValueEl = resultEl.querySelector('.calculadora-result__score-value');
          var progressCircle = resultEl.querySelector('.calculadora-result__progress');
          if (scoreValueEl) {
            animateCounter(scoreValueEl, 0, totalScore, 1500);
          }
          if (progressCircle) {
            var circumference = 339.29;
            var offset = circumference - (totalScore / 100) * circumference;
            setTimeout(function () {
              progressCircle.style.strokeDashoffset = offset;
            }, 100);
          }

          // Set level info
          var levelEl = resultEl.querySelector('.calculadora-result__level');
          var descEl = resultEl.querySelector('.calculadora-result__description');
          if (levelEl) levelEl.textContent = levelData.level;
          if (descEl) descEl.textContent = levelData.description;

          // Set recommendations
          var recListEl = resultEl.querySelector('.calculadora-result__recommendations-list');
          if (recListEl) {
            recListEl.innerHTML = '';
            levelData.recommendations.forEach(function (rec) {
              var li = document.createElement('li');
              li.textContent = rec;
              recListEl.appendChild(li);
            });
          }

          // Track completion
          trackEvent('lead_magnet_complete', {
            vertical: 'emprendimiento',
            type: 'calculadora_madurez',
            score: totalScore,
            level: levelData.level
          });
        }
      });
    }
  };

  /**
   * Behavior: Formulario de descarga (Guia / Template).
   */
  Drupal.behaviors.leadMagnetDownload = {
    attach: function (context) {
      var forms = once('lead-magnet-download', '.lead-magnet__download-form', context);
      forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          var emailInput = form.querySelector('.lead-magnet__email-input');
          if (!emailInput || !emailInput.value) return;

          var magnetEl = form.closest('.lead-magnet');
          var vertical = magnetEl ? magnetEl.dataset.vertical : '';
          var magnetType = magnetEl ? magnetEl.dataset.magnetType : '';

          // Hide form content, show confirmation
          var grid = magnetEl.querySelector('.lead-magnet__grid');
          var confirmation = magnetEl.querySelector('.lead-magnet__confirmation');
          if (grid) grid.hidden = true;
          if (confirmation) confirmation.hidden = false;

          // Track completion
          trackEvent('lead_magnet_complete', {
            vertical: vertical,
            type: magnetType,
            email_captured: true
          });
        });
      });
    }
  };

  /**
   * Behavior: Auditoria SEO form.
   */
  Drupal.behaviors.leadMagnetAudit = {
    attach: function (context) {
      var forms = once('lead-magnet-audit', '.lead-magnet__audit-form', context);
      forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          var emailInput = form.querySelector('.lead-magnet__email-input');
          var businessInput = form.querySelector('.lead-magnet__business-input');
          if (!emailInput || !emailInput.value || !businessInput || !businessInput.value) return;

          var magnetEl = form.closest('.lead-magnet');

          // Hide form, show analyzing
          form.hidden = true;
          var headerEl = magnetEl.querySelector('.lead-magnet__header');
          var checksEl = magnetEl.querySelector('.lead-magnet__checks');
          if (headerEl) headerEl.hidden = true;
          if (checksEl) checksEl.hidden = true;

          var analyzingEl = magnetEl.querySelector('.lead-magnet__analyzing');
          if (analyzingEl) {
            analyzingEl.hidden = false;
            runAnalysis(magnetEl, analyzingEl, businessInput.value);
          }
        });
      });

      function runAnalysis(magnetEl, analyzingEl, businessName) {
        var checks = analyzingEl.querySelectorAll('.lead-magnet__analyzing-check');
        var delay = 0;

        checks.forEach(function (check, index) {
          delay += 800 + Math.random() * 600;
          setTimeout(function () {
            check.classList.add('is-checking');
          }, delay - 400);
          setTimeout(function () {
            check.classList.remove('is-checking');
            check.classList.add('is-done');

            // After last check, show result
            if (index === checks.length - 1) {
              setTimeout(function () {
                showAuditResult(magnetEl, businessName);
              }, 600);
            }
          }, delay);
        });
      }

      function showAuditResult(magnetEl, businessName) {
        var analyzingEl = magnetEl.querySelector('.lead-magnet__analyzing');
        var resultEl = magnetEl.querySelector('.auditoria-result');
        if (analyzingEl) analyzingEl.hidden = true;
        if (!resultEl) return;
        resultEl.hidden = false;

        // Generate pseudo-random score based on business name
        var seed = 0;
        for (var i = 0; i < businessName.length; i++) {
          seed += businessName.charCodeAt(i);
        }
        var score = 25 + (seed % 50); // 25-74 range

        // Animate score bar
        var scoreFill = resultEl.querySelector('.auditoria-result__score-fill');
        var scoreValue = resultEl.querySelector('.auditoria-result__score-value');
        if (scoreFill) {
          setTimeout(function () {
            scoreFill.style.width = score + '%';
          }, 100);
        }
        if (scoreValue) {
          scoreValue.textContent = score + '/100';
        }

        // Track completion
        trackEvent('lead_magnet_complete', {
          vertical: 'comercioconecta',
          type: 'auditoria_seo',
          score: score
        });
      }
    }
  };

  /**
   * Behavior: Track lead_magnet_start on page load.
   */
  Drupal.behaviors.leadMagnetTracking = {
    attach: function (context) {
      var magnets = once('lead-magnet-track', '[data-track-event="lead_magnet_start"]', context);
      magnets.forEach(function (el) {
        var params = {};
        try {
          params = JSON.parse(el.dataset.trackParams || '{}');
        } catch (e) {
          // Ignore parse errors.
        }
        trackEvent('lead_magnet_start', params);
      });
    }
  };

  /**
   * Animate a counter from start to end value.
   */
  function animateCounter(el, start, end, duration) {
    var startTime = null;
    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      el.textContent = Math.round(start + (end - start) * eased);
      if (progress < 1) {
        requestAnimationFrame(step);
      }
    }
    requestAnimationFrame(step);
  }

  /**
   * Track event via jaraba_pixels if available.
   */
  function trackEvent(eventName, params) {
    if (typeof window.jarabaPixels !== 'undefined' && typeof window.jarabaPixels.track === 'function') {
      window.jarabaPixels.track(eventName, params);
    }
    // Also push to dataLayer for GA4
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: eventName,
      event_params: params
    });
  }

})(Drupal, drupalSettings, once);
