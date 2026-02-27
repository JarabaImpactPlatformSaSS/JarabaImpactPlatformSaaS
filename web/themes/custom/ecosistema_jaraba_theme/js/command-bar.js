/**
 * @file
 * Command Bar (Cmd+K) — Global command palette for all pages.
 *
 * GAP-AUD-008: Implements keyboard-driven command palette with
 * debounced search, keyboard navigation, XSS prevention, and
 * recent items history via localStorage.
 *
 * DIRECTRICES:
 * - Drupal.checkPlain() for XSS prevention (INNERHTML-XSS-001)
 * - Drupal.url() for language-prefixed URLs (ROUTE-LANGPREFIX-001)
 * - CSRF token cached as Promise (CSRF-JS-CACHE-001)
 */
(function (Drupal, drupalSettings) {
  'use strict';

  // CSRF token cached promise (CSRF-JS-CACHE-001).
  let csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('/session/token'))
        .then(function (response) { return response.text(); })
        .catch(function () {
          csrfTokenPromise = null;
          return '';
        });
    }
    return csrfTokenPromise;
  }

  // === Recent items — localStorage persistence ===
  var RECENTS_KEY = 'jaraba_command_bar_recents';
  var MAX_RECENTS = 5;

  function getRecents() {
    try {
      var stored = localStorage.getItem(RECENTS_KEY);
      return stored ? JSON.parse(stored) : [];
    }
    catch (e) {
      return [];
    }
  }

  function addToRecents(item) {
    if (!item || !item.url || !item.label) {
      return;
    }
    var recents = getRecents();
    // Remove duplicate by URL.
    recents = recents.filter(function (r) { return r.url !== item.url; });
    // Add to front.
    recents.unshift({
      label: item.label,
      url: item.url,
      icon: item.icon || 'history',
      category: item.category || ''
    });
    // Trim to max.
    recents = recents.slice(0, MAX_RECENTS);
    try {
      localStorage.setItem(RECENTS_KEY, JSON.stringify(recents));
    }
    catch (e) {
      // localStorage full or unavailable — fail silently.
    }
  }

  Drupal.behaviors.commandBar = {
    attach: function (context) {
      // Only attach once on the document.
      if (context !== document) {
        return;
      }

      var overlay = document.getElementById('command-bar-overlay');
      var modal = document.getElementById('command-bar-modal');
      var input = document.getElementById('command-bar-input');
      var resultsList = document.getElementById('command-bar-results');

      if (!overlay || !modal || !input || !resultsList) {
        return;
      }

      var isOpen = false;
      var selectedIndex = -1;
      var results = [];
      var debounceTimer = null;

      // Open/close.
      function open() {
        overlay.classList.add('command-bar--open');
        modal.classList.add('command-bar__modal--open');
        input.value = '';
        selectedIndex = -1;
        results = [];
        isOpen = true;
        // Show recent items when opening with empty query.
        showRecents();
        // Focus after animation.
        setTimeout(function () { input.focus(); }, 50);
      }

      function close() {
        overlay.classList.remove('command-bar--open');
        modal.classList.remove('command-bar__modal--open');
        isOpen = false;
        input.blur();
      }

      // Show recent items as initial content.
      function showRecents() {
        var recents = getRecents();
        if (recents.length === 0) {
          resultsList.innerHTML = '<li class="command-bar__hint">' +
            Drupal.checkPlain(Drupal.t('Type to search pages, articles, and users...')) +
            '</li>';
          return;
        }

        results = recents;
        selectedIndex = 0;

        var html = '<li class="command-bar__section-header">' +
          Drupal.checkPlain(Drupal.t('Recent')) +
          '</li>';
        for (var i = 0; i < recents.length; i++) {
          var r = recents[i];
          var activeClass = i === selectedIndex ? ' command-bar__result--active' : '';
          html += '<li class="command-bar__result' + activeClass + '" data-index="' + i + '">';
          html += '<span class="command-bar__result-icon material-icons">' + Drupal.checkPlain(r.icon || 'history') + '</span>';
          html += '<span class="command-bar__result-content">';
          html += '<span class="command-bar__result-label">' + Drupal.checkPlain(r.label) + '</span>';
          if (r.category) {
            html += '<span class="command-bar__result-category">' + Drupal.checkPlain(r.category) + '</span>';
          }
          html += '</span>';
          html += '</li>';
        }
        resultsList.innerHTML = html;
        attachResultHandlers();
      }

      // Keyboard shortcut: Cmd+K / Ctrl+K.
      document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
          e.preventDefault();
          if (isOpen) {
            close();
          } else {
            open();
          }
        }
        if (e.key === 'Escape' && isOpen) {
          e.preventDefault();
          close();
        }
      });

      // Close on overlay click.
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
          close();
        }
      });

      // Debounced search.
      input.addEventListener('input', function () {
        var query = input.value.trim();

        if (debounceTimer) {
          clearTimeout(debounceTimer);
        }

        if (query.length < 2) {
          if (query.length === 0) {
            showRecents();
          } else {
            resultsList.innerHTML = '';
            results = [];
            selectedIndex = -1;
          }
          return;
        }

        debounceTimer = setTimeout(function () {
          performSearch(query);
        }, 300);
      });

      // Keyboard navigation in results.
      input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          if (selectedIndex < results.length - 1) {
            selectedIndex++;
            highlightResult();
          }
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          if (selectedIndex > 0) {
            selectedIndex--;
            highlightResult();
          }
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (selectedIndex >= 0 && results[selectedIndex]) {
            navigateTo(results[selectedIndex]);
          }
        }
      });

      function performSearch(query) {
        getCsrfToken().then(function (token) {
          var url = Drupal.url('/api/v1/command-bar/search') + '?q=' + encodeURIComponent(query);

          fetch(url, {
            headers: {
              'X-CSRF-Token': token,
              'Accept': 'application/json',
            },
            credentials: 'same-origin',
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (data.success && data.results) {
                results = data.results;
                renderResults();
              }
            })
            .catch(function () {
              results = [];
              renderResults();
            });
        });
      }

      function renderResults() {
        selectedIndex = results.length > 0 ? 0 : -1;

        if (results.length === 0) {
          resultsList.innerHTML = '<li class="command-bar__empty">' +
            Drupal.checkPlain(Drupal.t('No results found')) +
            '</li>';
          return;
        }

        var html = '';
        var currentCategory = '';
        for (var i = 0; i < results.length; i++) {
          var r = results[i];

          // Group header by category.
          if (r.category && r.category !== currentCategory) {
            currentCategory = r.category;
            html += '<li class="command-bar__section-header">' +
              Drupal.checkPlain(currentCategory) +
              '</li>';
          }

          var activeClass = i === selectedIndex ? ' command-bar__result--active' : '';
          html += '<li class="command-bar__result' + activeClass + '" data-index="' + i + '">';
          html += '<span class="command-bar__result-icon material-icons">' + Drupal.checkPlain(r.icon || 'link') + '</span>';
          html += '<span class="command-bar__result-content">';
          html += '<span class="command-bar__result-label">' + Drupal.checkPlain(r.label) + '</span>';
          html += '<span class="command-bar__result-category">' + Drupal.checkPlain(r.category || '') + '</span>';
          html += '</span>';
          html += '</li>';
        }
        resultsList.innerHTML = html;
        attachResultHandlers();
      }

      function attachResultHandlers() {
        var items = resultsList.querySelectorAll('.command-bar__result');
        items.forEach(function (item) {
          item.addEventListener('click', function () {
            var idx = parseInt(item.getAttribute('data-index'), 10);
            if (results[idx]) {
              navigateTo(results[idx]);
            }
          });
          item.addEventListener('mouseenter', function () {
            var idx = parseInt(item.getAttribute('data-index'), 10);
            selectedIndex = idx;
            highlightResult();
          });
        });
      }

      function highlightResult() {
        var items = resultsList.querySelectorAll('.command-bar__result');
        items.forEach(function (item, idx) {
          if (idx === selectedIndex) {
            item.classList.add('command-bar__result--active');
            item.scrollIntoView({ block: 'nearest' });
          } else {
            item.classList.remove('command-bar__result--active');
          }
        });
      }

      function navigateTo(result) {
        var url = result.url;
        // Save to recents before navigating.
        addToRecents(result);
        close();
        if (url && url.charAt(0) === '#') {
          // Special action: trigger JS event.
          document.dispatchEvent(new CustomEvent('commandBar:action', { detail: { action: url } }));
        } else if (url) {
          window.location.href = url;
        }
      }
    },
  };

})(Drupal, drupalSettings);
