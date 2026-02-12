/**
 * @file
 * Report Wizard - Multi-step report creation form.
 *
 * Handles step navigation, metric selection, filter configuration,
 * schedule setup, and recipient management for creating scheduled reports.
 *
 * @see templates/analytics-report-wizard.html.twig
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Report Wizard behavior.
   *
   * Initializes the multi-step wizard for scheduled report creation.
   */
  Drupal.behaviors.jarabaReportWizard = {
    attach: function (context) {
      var wizardElements = once('jaraba-report-wizard', '.analytics-report-wizard', context);

      if (!wizardElements.length) {
        return;
      }

      wizardElements.forEach(function (wizardEl) {
        var state = {
          currentStep: 1,
          totalSteps: 4,
          recipients: [],
          data: {}
        };

        initNavigation(wizardEl, state);
        initRecipients(wizardEl, state);
        initSubmit(wizardEl, state);
      });
    }
  };

  /**
   * Initializes step navigation.
   *
   * @param {HTMLElement} wizardEl
   *   The wizard root element.
   * @param {object} state
   *   The wizard state object.
   */
  function initNavigation(wizardEl, state) {
    var prevBtn = wizardEl.querySelector('[data-action="prev-step"]');
    var nextBtn = wizardEl.querySelector('[data-action="next-step"]');
    var submitBtn = wizardEl.querySelector('[data-action="submit-report"]');

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        if (state.currentStep < state.totalSteps) {
          goToStep(wizardEl, state, state.currentStep + 1);
        }
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        if (state.currentStep > 1) {
          goToStep(wizardEl, state, state.currentStep - 1);
        }
      });
    }

    updateNavButtons(wizardEl, state, prevBtn, nextBtn, submitBtn);
  }

  /**
   * Navigates to a specific step.
   *
   * @param {HTMLElement} wizardEl
   *   The wizard root element.
   * @param {object} state
   *   The wizard state object.
   * @param {number} step
   *   The target step number.
   */
  function goToStep(wizardEl, state, step) {
    // Hide current panel.
    var currentPanel = wizardEl.querySelector('[data-panel="' + state.currentStep + '"]');
    if (currentPanel) {
      currentPanel.classList.remove('analytics-report-wizard__panel--active');
    }

    // Deactivate current step indicator.
    var currentStepEl = wizardEl.querySelector('[data-step="' + state.currentStep + '"]');
    if (currentStepEl) {
      currentStepEl.classList.remove('analytics-report-wizard__step--active');
      currentStepEl.classList.add('analytics-report-wizard__step--completed');
    }

    // Show new panel.
    state.currentStep = step;
    var newPanel = wizardEl.querySelector('[data-panel="' + step + '"]');
    if (newPanel) {
      newPanel.classList.add('analytics-report-wizard__panel--active');
    }

    // Activate new step indicator.
    var newStepEl = wizardEl.querySelector('[data-step="' + step + '"]');
    if (newStepEl) {
      newStepEl.classList.add('analytics-report-wizard__step--active');
    }

    // Update navigation buttons.
    var prevBtn = wizardEl.querySelector('[data-action="prev-step"]');
    var nextBtn = wizardEl.querySelector('[data-action="next-step"]');
    var submitBtn = wizardEl.querySelector('[data-action="submit-report"]');
    updateNavButtons(wizardEl, state, prevBtn, nextBtn, submitBtn);

    wizardEl.setAttribute('data-current-step', String(step));
  }

  /**
   * Updates navigation button states.
   *
   * @param {HTMLElement} wizardEl
   *   The wizard root element.
   * @param {object} state
   *   The wizard state object.
   * @param {HTMLElement|null} prevBtn
   *   The previous button element.
   * @param {HTMLElement|null} nextBtn
   *   The next button element.
   * @param {HTMLElement|null} submitBtn
   *   The submit button element.
   */
  function updateNavButtons(wizardEl, state, prevBtn, nextBtn, submitBtn) {
    if (prevBtn) {
      prevBtn.disabled = (state.currentStep === 1);
    }
    if (nextBtn) {
      nextBtn.style.display = (state.currentStep < state.totalSteps) ? '' : 'none';
    }
    if (submitBtn) {
      submitBtn.style.display = (state.currentStep === state.totalSteps) ? '' : 'none';
    }
  }

  /**
   * Initializes recipient management.
   *
   * @param {HTMLElement} wizardEl
   *   The wizard root element.
   * @param {object} state
   *   The wizard state object.
   */
  function initRecipients(wizardEl, state) {
    var addBtn = wizardEl.querySelector('[data-action="add-recipient"]');
    var emailInput = wizardEl.querySelector('#wizard-recipient-email');
    var recipientList = wizardEl.querySelector('.analytics-report-wizard__recipient-list');

    if (!addBtn || !emailInput || !recipientList) {
      return;
    }

    addBtn.addEventListener('click', function () {
      var email = emailInput.value.trim();
      if (!email || !isValidEmail(email)) {
        Drupal.announce(Drupal.t('Please enter a valid email address.'));
        return;
      }

      if (state.recipients.indexOf(email) !== -1) {
        Drupal.announce(Drupal.t('This email is already added.'));
        return;
      }

      state.recipients.push(email);
      emailInput.value = '';

      var li = document.createElement('li');
      li.classList.add('analytics-report-wizard__recipient-item');
      li.innerHTML = '<span class="analytics-report-wizard__recipient-email">' +
        Drupal.checkPlain(email) + '</span>' +
        '<button type="button" class="analytics-report-wizard__recipient-remove" data-email="' +
        Drupal.checkPlain(email) + '" aria-label="' + Drupal.t('Remove recipient') + '">&#x2715;</button>';

      recipientList.appendChild(li);

      // Remove handler.
      li.querySelector('.analytics-report-wizard__recipient-remove').addEventListener('click', function () {
        var idx = state.recipients.indexOf(email);
        if (idx > -1) {
          state.recipients.splice(idx, 1);
        }
        li.remove();
      });
    });

    // Allow Enter key to add.
    emailInput.addEventListener('keypress', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        addBtn.click();
      }
    });
  }

  /**
   * Initializes the submit action.
   *
   * @param {HTMLElement} wizardEl
   *   The wizard root element.
   * @param {object} state
   *   The wizard state object.
   */
  function initSubmit(wizardEl, state) {
    var submitBtn = wizardEl.querySelector('[data-action="submit-report"]');
    if (!submitBtn) {
      return;
    }

    submitBtn.addEventListener('click', function () {
      // Collect all form data.
      var metrics = [];
      wizardEl.querySelectorAll('input[name="metrics[]"]:checked').forEach(function (cb) {
        metrics.push(cb.value);
      });

      if (metrics.length === 0) {
        Drupal.announce(Drupal.t('Please select at least one metric.'));
        goToStep(wizardEl, state, 1);
        return;
      }

      if (state.recipients.length === 0) {
        Drupal.announce(Drupal.t('Please add at least one recipient.'));
        return;
      }

      var dateRange = wizardEl.querySelector('#wizard-date-range');
      var deviceType = wizardEl.querySelector('#wizard-device-type');
      var country = wizardEl.querySelector('#wizard-country');
      var frequency = wizardEl.querySelector('#wizard-frequency');
      var day = wizardEl.querySelector('#wizard-day');
      var time = wizardEl.querySelector('#wizard-time');

      var reportData = {
        name: Drupal.t('Scheduled Report - @date', { '@date': new Date().toISOString().split('T')[0] }),
        report_config: JSON.stringify({
          metrics: metrics,
          filters: {
            date_range: dateRange ? dateRange.value : 'last_30_days',
            device_type: deviceType ? deviceType.value : '',
            country: country ? country.value : ''
          },
          format: 'csv'
        }),
        schedule_type: frequency ? frequency.value : 'weekly',
        schedule_config: JSON.stringify({
          day_of_week: day ? day.value : 'monday',
          time: time ? time.value : '08:00',
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        }),
        recipients: JSON.stringify(state.recipients),
        report_status: 'active'
      };

      submitBtn.disabled = true;
      submitBtn.textContent = Drupal.t('Creating...');

      fetch('/api/v1/analytics/scheduled-reports', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(reportData)
      })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          window.location.href = '/analytics/reports';
        }
        else {
          submitBtn.disabled = false;
          submitBtn.textContent = Drupal.t('Create Report');
          Drupal.announce(Drupal.t('Failed to create report.'));
        }
      })
      .catch(function () {
        submitBtn.disabled = false;
        submitBtn.textContent = Drupal.t('Create Report');
        Drupal.announce(Drupal.t('Error creating report.'));
      });
    });
  }

  /**
   * Validates an email address.
   *
   * @param {string} email
   *   The email to validate.
   *
   * @return {boolean}
   *   TRUE if valid.
   */
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

})(Drupal, drupalSettings, once);
