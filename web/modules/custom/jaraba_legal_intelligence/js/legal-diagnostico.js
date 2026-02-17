/**
 * @file
 * legal-diagnostico.js — Lead Magnet: Diagnostico Legal Gratuito.
 *
 * Plan Elevacion JarabaLex Clase Mundial v1 — Fase 0.
 * Procesa el formulario de diagnostico y renderiza el resultado.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.legalDiagnostico = {
    attach(context) {
      once('legal-diagnostico', '[data-diagnostico-form]', context).forEach(form => {
        form.addEventListener('submit', async (e) => {
          e.preventDefault();

          const btn = form.querySelector('button[type="submit"]');
          const originalText = btn.textContent;
          btn.disabled = true;
          btn.textContent = Drupal.t('Analizando...');

          const payload = {
            area_legal: form.querySelector('[name="area_legal"]').value,
            situacion: form.querySelector('[name="situacion"]').value,
            urgencia: form.querySelector('[name="urgencia"]').value,
            email: form.querySelector('[name="email"]').value || null,
          };

          try {
            const response = await fetch('/api/v1/legal/diagnostico', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(payload),
            });

            const json = await response.json();

            if (json.error) {
              btn.disabled = false;
              btn.textContent = originalText;
              return;
            }

            renderResult(json.data);
          }
          catch (err) {
            btn.disabled = false;
            btn.textContent = originalText;
          }
        });
      });

      /**
       * Renderiza el resultado del diagnostico en el DOM.
       */
      function renderResult(data) {
        const wrapper = document.querySelector('[data-diagnostico-result]');
        if (!wrapper) return;

        const scoreEl = wrapper.querySelector('[data-score-value]');
        if (scoreEl) {
          scoreEl.textContent = data.score;
          const circle = wrapper.querySelector('[data-score-circle]');
          if (circle) {
            circle.classList.remove('score--low', 'score--medium', 'score--high');
            if (data.score >= 80) circle.classList.add('score--high');
            else if (data.score >= 60) circle.classList.add('score--medium');
            else circle.classList.add('score--low');
          }
        }

        const summaryEl = wrapper.querySelector('[data-diagnostico-summary]');
        if (summaryEl) {
          summaryEl.innerHTML = '<p>' + Drupal.checkPlain(data.resumen) + '</p>';
        }

        const recsEl = wrapper.querySelector('[data-diagnostico-recommendations]');
        if (recsEl && data.recomendaciones) {
          recsEl.innerHTML = data.recomendaciones
            .map(r => '<li>' + Drupal.checkPlain(r) + '</li>')
            .join('');
        }

        const ctaEl = wrapper.querySelector('[data-diagnostico-cta]');
        if (ctaEl && data.cta) {
          ctaEl.innerHTML =
            '<a href="' + Drupal.checkPlain(data.cta.url) + '" class="ej-btn ej-btn--primary ej-btn--lg">' +
            Drupal.checkPlain(data.cta.text) + '</a>' +
            '<a href="' + Drupal.checkPlain(data.cta.secondary_url) + '" class="ej-btn ej-btn--secondary">' +
            Drupal.checkPlain(data.cta.secondary_text) + '</a>';
        }

        wrapper.style.display = '';
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    },
  };
})(Drupal, once);
