/**
 * @file
 * Programa Mentores - Filtrado de mentores por sector.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.programaMentores = {
    attach: function (context) {
      once('programa-mentores-filter', '.programa-mentores__filter-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var filter = this.getAttribute('data-filter');
          var buttons = document.querySelectorAll('.programa-mentores__filter-btn');
          var cards = document.querySelectorAll('.mentor-card');

          // Update active state.
          buttons.forEach(function (b) {
            b.classList.remove('is-active');
          });
          this.classList.add('is-active');

          // Filter cards.
          cards.forEach(function (card) {
            if (filter === 'all') {
              card.style.display = '';
              return;
            }
            var sectors = (card.getAttribute('data-sectors') || '').split(',');
            card.style.display = sectors.indexOf(filter) !== -1 ? '' : 'none';
          });
        });
      });
    }
  };

})(Drupal, once);
