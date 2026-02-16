/**
 * @file
 * Legal Intelligence Hub — Renderizado dinamico de resultados.
 *
 * Gestiona paginacion, ordenamiento y acciones sobre resultados
 * de busqueda (favoritos, citas, similares).
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  Drupal.behaviors.legalResults = {
    attach: function (context) {
      once('legal-results', '.legal-search__results-list', context).forEach(function (container) {
        container.addEventListener('click', function (e) {
          var bookmarkBtn = e.target.closest('[data-legal-bookmark]');
          if (bookmarkBtn) {
            e.preventDefault();
            toggleBookmark(bookmarkBtn);
          }
        });
      });
    }
  };

  /**
   * Alterna favorito de una resolucion via API.
   *
   * @param {HTMLElement} btn - Boton de favorito.
   */
  function toggleBookmark(btn) {
    var resolutionId = btn.dataset.legalBookmark;
    var isBookmarked = btn.classList.contains('is-bookmarked');

    fetch('/api/v1/legal/bookmark', {
      method: isBookmarked ? 'DELETE' : 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ resolution_id: resolutionId })
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success) {
        btn.classList.toggle('is-bookmarked');
        btn.setAttribute('aria-pressed', !isBookmarked);
      }
    })
    .catch(function () {
      // Silencioso en error — la UI no cambia.
    });
  }

})(Drupal, drupalSettings, once);
