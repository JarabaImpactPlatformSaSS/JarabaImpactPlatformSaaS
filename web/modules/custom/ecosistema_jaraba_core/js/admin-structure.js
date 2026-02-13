/**
 * @file
 * Client-side search/filter and tab navigation for admin/structure.
 *
 * Features:
 *  - Real-time search with 200ms debounce
 *  - Horizontal tab bar with smooth scroll to section
 *  - IntersectionObserver-based active tab detection on scroll
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ejAdminStructureSearch = {
    attach: function (context) {
      once('ej-admin-structure-search', '#ej-admin-structure', context).forEach(function (container) {
        var input = container.querySelector('#ej-structure-search');
        var countEl = container.querySelector('#ej-structure-count');
        var emptyEl = container.querySelector('#ej-structure-empty');
        var tabsContainer = container.querySelector('#ej-structure-tabs');
        var debounceTimer = null;
        var isTabScrolling = false;

        // ── Search ──
        if (input) {
          input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
              filterItems(input.value.trim().toLowerCase());
            }, 200);
          });

          input.addEventListener('search', function () {
            filterItems(input.value.trim().toLowerCase());
          });
        }

        function filterItems(query) {
          var cards = container.querySelectorAll('.ej-admin-structure__card');
          var sections = container.querySelectorAll('.ej-admin-structure__section');
          var visibleCount = 0;

          cards.forEach(function (card) {
            var title = card.getAttribute('data-title') || '';
            var desc = card.getAttribute('data-description') || '';
            var provider = card.getAttribute('data-provider') || '';
            var matches = !query ||
              title.indexOf(query) !== -1 ||
              desc.indexOf(query) !== -1 ||
              provider.indexOf(query) !== -1;

            card.style.display = matches ? '' : 'none';
            if (matches) { visibleCount++; }
          });

          sections.forEach(function (section) {
            var visibleCards = section.querySelectorAll('.ej-admin-structure__card:not([style*="display: none"])');
            var sectionCount = section.querySelector('.ej-admin-structure__section-count');
            section.style.display = visibleCards.length > 0 ? '' : 'none';
            if (sectionCount) {
              sectionCount.textContent = visibleCards.length;
            }
          });

          if (countEl) {
            countEl.textContent = visibleCount + ' ' + Drupal.t('elementos');
          }

          if (emptyEl) {
            emptyEl.style.display = (visibleCount === 0 && query) ? 'flex' : 'none';
          }
        }

        // ── Tab Navigation ──
        if (tabsContainer) {
          var tabs = tabsContainer.querySelectorAll('.ej-admin-structure__tab');
          var sections = container.querySelectorAll('.ej-admin-structure__section');

          // Click handler: scroll to section
          tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
              var target = tab.getAttribute('data-target');

              // Update active state
              tabs.forEach(function (t) { t.classList.remove('is-active'); });
              tab.classList.add('is-active');

              // Scroll the tab into view in the track
              tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

              if (target === 'all') {
                // Show all sections, scroll to top
                sections.forEach(function (s) { s.style.display = ''; });
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
              } else {
                // Scroll to specific section
                var section = container.querySelector('[data-category="' + target + '"]');
                if (section) {
                  isTabScrolling = true;
                  // Offset to account for sticky search + tabs
                  var headerOffset = 140;
                  var elementPosition = section.getBoundingClientRect().top + window.pageYOffset;
                  window.scrollTo({
                    top: elementPosition - headerOffset,
                    behavior: 'smooth'
                  });

                  // Reset flag after scroll completes
                  setTimeout(function () { isTabScrolling = false; }, 600);
                }
              }
            });
          });

          // IntersectionObserver: highlight active tab on scroll
          if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
              if (isTabScrolling) { return; }

              entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                  var categoryKey = entry.target.getAttribute('data-category');
                  tabs.forEach(function (t) {
                    var isMatch = t.getAttribute('data-target') === categoryKey;
                    t.classList.toggle('is-active', isMatch);
                  });

                  // Scroll active tab into view in the track
                  var activeTab = tabsContainer.querySelector('.ej-admin-structure__tab.is-active');
                  if (activeTab) {
                    activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                  }
                }
              });
            }, {
              rootMargin: '-150px 0px -60% 0px',
              threshold: 0
            });

            sections.forEach(function (section) {
              observer.observe(section);
            });
          }
        }
      });
    }
  };

})(Drupal, once);
