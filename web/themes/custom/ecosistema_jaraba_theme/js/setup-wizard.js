/**
 * @file
 * Setup Wizard behavior — SETUP-WIZARD-DAILY-001.
 *
 * Premium world-class UI: staggered entrance, animated progress ring,
 * confetti on completion, smooth state transitions.
 *
 * Directives:
 *   - Vanilla JS + Drupal.behaviors (no frameworks)
 *   - Drupal.t() for translations
 *   - Drupal.checkPlain() for API data (INNERHTML-XSS-001)
 *   - core/once for idempotent attach (Drupal 11)
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.setupWizard = {
    attach: function (context) {
      once('setup-wizard', '[data-setup-wizard]', context).forEach(function (wizard) {
        var wizardId = wizard.getAttribute('data-setup-wizard');
        var isComplete = wizard.getAttribute('data-wizard-complete') === 'true';
        var storageKey = 'jaraba_setup_wizard_' + wizardId + '_dismissed';

        var collapsedEl = wizard.querySelector('[data-wizard-collapsed]');
        var panelEl = wizard.querySelector('[data-wizard-panel]');
        var toggleBtns = wizard.querySelectorAll('[data-wizard-toggle]');

        // Restore dismiss state from localStorage.
        if (isComplete && localStorage.getItem(storageKey) === 'true') {
          showCollapsed(false);
        }

        // Toggle handlers with smooth animation.
        toggleBtns.forEach(function (btn) {
          btn.addEventListener('click', function () {
            if (panelEl.classList.contains('setup-wizard__panel--hidden')) {
              showExpanded(true);
              localStorage.removeItem(storageKey);
            }
            else {
              showCollapsed(true);
              if (isComplete) {
                localStorage.setItem(storageKey, 'true');
              }
            }
          });
        });

        function showCollapsed(animate) {
          if (collapsedEl) {
            collapsedEl.classList.remove('setup-wizard__collapsed--hidden');
            if (animate) {
              collapsedEl.style.opacity = '0';
              collapsedEl.style.transform = 'translateY(-8px)';
              requestAnimationFrame(function () {
                collapsedEl.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                collapsedEl.style.opacity = '1';
                collapsedEl.style.transform = 'translateY(0)';
              });
            }
          }
          if (panelEl) {
            panelEl.classList.add('setup-wizard__panel--hidden');
          }
          toggleBtns.forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
          });
        }

        function showExpanded(animate) {
          if (collapsedEl) {
            collapsedEl.classList.add('setup-wizard__collapsed--hidden');
          }
          if (panelEl) {
            panelEl.classList.remove('setup-wizard__panel--hidden');
            if (animate) {
              panelEl.style.opacity = '0';
              panelEl.style.transform = 'scale(0.97)';
              requestAnimationFrame(function () {
                panelEl.style.transition = 'opacity 0.35s ease, transform 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
                panelEl.style.opacity = '1';
                panelEl.style.transform = 'scale(1)';
              });
            }
          }
          toggleBtns.forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'true');
          });
        }

        // Listen for slide-panel close events to refresh wizard state.
        document.addEventListener('jaraba:slide-panel:closed', function () {
          refreshWizardState(wizard, wizardId);
        });
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        // Clean up handled by GC — no persistent listeners to remove.
      }
    }
  };

  /**
   * Refreshes wizard completion state via API call.
   *
   * After a slide-panel form is saved (e.g., new AccionFormativaEi),
   * this fetches the updated wizard status and updates the UI with
   * smooth transitions and celebrates milestones.
   */
  function refreshWizardState(wizardEl, wizardId) {
    var apiUrl = Drupal.url('api/v1/setup-wizard/' + wizardId + '/status');

    fetch(apiUrl, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
    .then(function (response) {
      if (!response.ok) { return; }
      return response.json();
    })
    .then(function (data) {
      if (!data) { return; }

      var wasComplete = wizardEl.getAttribute('data-wizard-complete') === 'true';

      // Animate progress ring.
      var ringFill = wizardEl.querySelector('.setup-wizard__ring-fill');
      if (ringFill) {
        var circumference = 106.8;
        var offset = (data.completion_percentage * circumference) / 100;
        ringFill.style.strokeDasharray = offset + ' ' + circumference;
      }

      // Animate percentage number.
      var percentText = wizardEl.querySelector('[data-wizard-percentage]');
      if (percentText) {
        animateNumber(percentText, data.completion_percentage);
      }

      // Update progressbar aria value.
      var progressBar = wizardEl.querySelector('[role="progressbar"]');
      if (progressBar) {
        progressBar.setAttribute('aria-valuenow', data.completion_percentage);
      }

      // Update step states with transitions.
      if (data.steps) {
        data.steps.forEach(function (step) {
          var stepEl = wizardEl.querySelector('[data-step-id="' + step.id + '"]');
          if (!stepEl) { return; }

          var wasStepComplete = stepEl.classList.contains('setup-wizard__step--complete');
          stepEl.classList.toggle('setup-wizard__step--complete', step.is_complete);
          stepEl.classList.toggle('setup-wizard__step--active', step.is_active);

          // Celebrate newly completed step.
          if (!wasStepComplete && step.is_complete) {
            celebrateStep(stepEl);
          }

          // Update status label.
          var statusEl = stepEl.querySelector('.setup-wizard__step-status');
          if (statusEl && step.completion_data && step.completion_data.label) {
            statusEl.textContent = Drupal.checkPlain(String(step.completion_data.label));
            statusEl.classList.toggle('setup-wizard__step-status--success', step.is_complete);
            statusEl.classList.toggle('setup-wizard__step-status--warning', !!step.completion_data.warning);
          }

          // Update warning.
          var warningEl = stepEl.querySelector('.setup-wizard__step-warning');
          if (warningEl) {
            if (step.completion_data && step.completion_data.warning && !step.is_complete) {
              warningEl.style.display = '';
              // Keep icon, update text after icon.
              var textNode = warningEl.lastChild;
              if (textNode && textNode.nodeType === 3) {
                textNode.textContent = ' ' + Drupal.checkPlain(String(step.completion_data.warning));
              }
            }
            else {
              warningEl.style.display = 'none';
            }
          }

          // Update connector.
          var connector = stepEl.querySelector('.setup-wizard__step-connector');
          if (connector) {
            connector.classList.toggle('setup-wizard__step-connector--complete', step.is_complete);
          }
        });
      }

      // Update complete state.
      wizardEl.setAttribute('data-wizard-complete', data.is_complete ? 'true' : 'false');

      // Celebrate full wizard completion.
      if (!wasComplete && data.is_complete) {
        celebrateCompletion(wizardEl);
      }
    })
    .catch(function () {
      // Fail silently — wizard state will refresh on next page load.
    });
  }

  /**
   * Animates a number counting up/down.
   */
  function animateNumber(el, target) {
    var current = parseInt(el.textContent, 10) || 0;
    if (current === target) { return; }

    var duration = 600;
    var start = performance.now();

    function update(now) {
      var elapsed = now - start;
      var progress = Math.min(elapsed / duration, 1);
      // Ease out cubic.
      var eased = 1 - Math.pow(1 - progress, 3);
      var value = Math.round(current + (target - current) * eased);
      el.innerHTML = Drupal.checkPlain(String(value)) + '<small>%</small>';
      if (progress < 1) {
        requestAnimationFrame(update);
      }
    }

    requestAnimationFrame(update);
  }

  /**
   * Quick celebration effect when a step completes.
   */
  function celebrateStep(stepEl) {
    var indicator = stepEl.querySelector('.setup-wizard__step-indicator');
    if (!indicator) { return; }

    indicator.style.transform = 'scale(1.3)';
    indicator.style.transition = 'transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
    setTimeout(function () {
      indicator.style.transform = '';
    }, 400);

    // Flash the card.
    var card = stepEl.querySelector('.setup-wizard__step-card');
    if (card) {
      card.style.boxShadow = '0 0 20px var(--ej-color-verde-innovacion, #00A9A5)';
      card.style.transition = 'box-shadow 0.3s ease';
      setTimeout(function () {
        card.style.boxShadow = '';
      }, 800);
    }
  }

  /**
   * Celebration effect when the entire wizard is complete.
   * Emits subtle particles from the progress ring.
   */
  function celebrateCompletion(wizardEl) {
    var ring = wizardEl.querySelector('.setup-wizard__progress-ring');
    if (!ring) { return; }

    var rect = ring.getBoundingClientRect();
    var cx = rect.left + rect.width / 2;
    var cy = rect.top + rect.height / 2;

    // Create 12 tiny particles.
    for (var i = 0; i < 12; i++) {
      createParticle(cx, cy, i);
    }

    function createParticle(x, y, index) {
      var particle = document.createElement('div');
      var colors = ['#00A9A5', '#233D63', '#FF8C42', '#10B981'];
      var color = colors[index % colors.length];
      var angle = (index / 12) * 360;
      var distance = 30 + Math.random() * 40;
      var tx = Math.cos(angle * Math.PI / 180) * distance;
      var ty = Math.sin(angle * Math.PI / 180) * distance;

      particle.style.cssText = [
        'position:fixed',
        'width:6px',
        'height:6px',
        'border-radius:50%',
        'background:' + color,
        'left:' + x + 'px',
        'top:' + y + 'px',
        'pointer-events:none',
        'z-index:9999',
        'transition:all 0.6s cubic-bezier(0.16,1,0.3,1)',
        'opacity:1'
      ].join(';');

      document.body.appendChild(particle);

      requestAnimationFrame(function () {
        particle.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(0)';
        particle.style.opacity = '0';
      });

      setTimeout(function () {
        if (particle.parentNode) {
          particle.parentNode.removeChild(particle);
        }
      }, 700);
    }
  }

})(Drupal, once);
