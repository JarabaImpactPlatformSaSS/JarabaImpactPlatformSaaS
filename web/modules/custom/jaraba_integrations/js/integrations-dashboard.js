/**
 * @file
 * Integrations Dashboard behaviors.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.integrationsDashboard = {
    attach: function (context) {
      once('integrations-search', '.integrations-dashboard__search-input', context).forEach(function (input) {
        input.addEventListener('input', function () {
          var query = this.value.toLowerCase();
          var cards = document.querySelectorAll('.integrations-card');
          cards.forEach(function (card) {
            var name = card.querySelector('.integrations-card__name');
            var desc = card.querySelector('.integrations-card__description');
            var text = ((name ? name.textContent : '') + ' ' + (desc ? desc.textContent : '')).toLowerCase();
            card.style.display = text.indexOf(query) !== -1 ? '' : 'none';
          });
        });
      });

      // Category sidebar filtering.
      once('integrations-categories', '.integrations-dashboard__category-link', context).forEach(function (link) {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          var category = this.dataset.category;
          var allLinks = document.querySelectorAll('.integrations-dashboard__category-link');
          allLinks.forEach(function (l) { l.classList.remove('is-active'); });
          this.classList.add('is-active');

          var cards = document.querySelectorAll('.integrations-card');
          cards.forEach(function (card) {
            if (!category || category === 'all') {
              card.style.display = '';
            } else {
              card.style.display = card.dataset.category === category ? '' : 'none';
            }
          });
        });
      });
    }
  };

})(Drupal, once);
