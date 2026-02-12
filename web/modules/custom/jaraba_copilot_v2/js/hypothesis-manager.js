/**
 * @file
 * Hypothesis Manager: CRUD modal, priorizacion, filtros.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.hypothesisManager = {
    attach: function (context) {
      once('hypothesis-manager', '.hypothesis-manager', context).forEach(function (el) {
        Drupal.hypothesisManager.init(el);
      });
    }
  };

  Drupal.hypothesisManager = {
    init: function (container) {
      this.container = container;
      this.bindFilters();
      this.bindPrioritize();
    },

    bindFilters: function () {
      var self = this;
      var filters = this.container.querySelectorAll('.hypothesis-manager__filter');

      filters.forEach(function (filter) {
        filter.addEventListener('change', function () {
          self.applyFilters();
        });
      });
    },

    applyFilters: function () {
      var type = this.container.querySelector('[data-filter="type"]').value;
      var status = this.container.querySelector('[data-filter="status"]').value;
      var bmcBlock = this.container.querySelector('[data-filter="bmc_block"]').value;

      var cards = this.container.querySelectorAll('.hypothesis-card');
      cards.forEach(function (card) {
        var show = true;
        if (type && card.dataset.type !== type) show = false;
        if (status && card.dataset.status !== status) show = false;
        if (bmcBlock && card.dataset.bmcBlock !== bmcBlock) show = false;
        card.style.display = show ? '' : 'none';
      });
    },

    bindPrioritize: function () {
      var self = this;
      var btn = this.container.querySelector('[data-action="prioritize"]');
      if (!btn) return;

      btn.addEventListener('click', function () {
        self.prioritizeHypotheses();
      });
    },

    prioritizeHypotheses: function () {
      var cards = this.container.querySelectorAll('.hypothesis-card');
      var ids = [];
      cards.forEach(function (card) {
        if (card.style.display !== 'none') {
          ids.push(parseInt(card.dataset.hypothesisId, 10));
        }
      });

      if (ids.length === 0) return;

      fetch(Drupal.url('api/v1/hypotheses/prioritize?_format=json'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ hypothesis_ids: ids })
      })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.success && data.data) {
          var list = document.getElementById('hypothesis-list');
          data.data.forEach(function (item) {
            var card = list.querySelector('[data-hypothesis-id="' + item.id + '"]');
            if (card) {
              list.appendChild(card);
              // Update ICE score display
              var iceEl = card.querySelector('.hypothesis-card__score--ice .hypothesis-card__score-value');
              if (iceEl) {
                iceEl.textContent = item.ice_score;
              }
            }
          });
        }
      })
      .catch(function (err) {
        console.error('Prioritization error:', err);
      });
    }
  };

})(Drupal, once);
