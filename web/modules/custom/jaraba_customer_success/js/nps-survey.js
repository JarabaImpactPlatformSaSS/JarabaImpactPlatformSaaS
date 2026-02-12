/**
 * @file
 * NPS Survey - Interactividad del formulario de encuesta NPS.
 *
 * PROPOSITO:
 * - Selector de puntuacion 0-10 con highlight visual y color coding.
 * - Validacion del formulario antes de envio.
 * - Envio AJAX de respuestas al endpoint REST.
 * - Feedback visual al usuario tras envio exitoso.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento del selector de puntuacion NPS.
   */
  Drupal.behaviors.csNpsSurveyScoreSelector = {
    attach: function (context) {
      once('cs-nps-score-selector', '.cs-nps-survey__score-selector', context).forEach(function (container) {
        var buttons = container.querySelectorAll('.cs-nps-survey__score-btn');
        var selectedScore = null;

        buttons.forEach(function (btn) {
          btn.addEventListener('click', function () {
            // Deselect all.
            buttons.forEach(function (b) {
              b.classList.remove('cs-nps-survey__score-btn--selected');
              b.setAttribute('aria-checked', 'false');
            });

            // Select current.
            btn.classList.add('cs-nps-survey__score-btn--selected');
            btn.setAttribute('aria-checked', 'true');
            selectedScore = parseInt(btn.getAttribute('data-score'), 10);

            // Enable submit button.
            var submitBtn = document.querySelector('.cs-nps-survey__submit-btn');
            if (submitBtn) {
              submitBtn.disabled = false;
            }
          });
        });
      });
    }
  };

  /**
   * Validacion y envio AJAX del formulario NPS.
   */
  Drupal.behaviors.csNpsSurveySubmit = {
    attach: function (context) {
      once('cs-nps-submit', '.cs-nps-survey__submit-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();

          var survey = document.querySelector('.cs-nps-survey');
          if (!survey) {
            return;
          }

          // Get selected score.
          var selectedBtn = survey.querySelector('.cs-nps-survey__score-btn--selected');
          if (!selectedBtn) {
            return;
          }

          var score = parseInt(selectedBtn.getAttribute('data-score'), 10);
          if (isNaN(score) || score < 0 || score > 10) {
            return;
          }

          // Get comment.
          var commentInput = survey.querySelector('.cs-nps-survey__comment-input');
          var comment = commentInput ? commentInput.value.trim() : '';

          // Get submit URL.
          var submitUrl = survey.getAttribute('data-submit-url') || '/api/v1/cs/nps/submit';

          // Disable button during submission.
          btn.disabled = true;
          btn.textContent = Drupal.t('Submitting...');

          // AJAX submit.
          var xhr = new XMLHttpRequest();
          xhr.open('POST', submitUrl, true);
          xhr.setRequestHeader('Content-Type', 'application/json');

          xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
              return;
            }

            if (xhr.status >= 200 && xhr.status < 300) {
              // Show success state.
              var formSections = survey.querySelectorAll(
                '.cs-nps-survey__header, .cs-nps-survey__score-section, .cs-nps-survey__comment-section, .cs-nps-survey__actions'
              );
              formSections.forEach(function (section) {
                section.style.display = 'none';
              });

              var successEl = survey.querySelector('.cs-nps-survey__success');
              if (successEl) {
                successEl.style.display = 'block';
              }
            }
            else {
              btn.disabled = false;
              btn.textContent = Drupal.t('Submit Response');

              var errorMsg = Drupal.t('An error occurred. Please try again.');
              try {
                var response = JSON.parse(xhr.responseText);
                if (response.error) {
                  errorMsg = response.error;
                }
              }
              catch (parseError) {
                // Use default error message.
              }
              alert(errorMsg);
            }
          };

          xhr.send(JSON.stringify({
            tenant_id: 'global',
            score: score,
            comment: comment
          }));
        });
      });
    }
  };

})(Drupal, once);
