/**
 * @file
 * Pipeline Kanban drag-and-drop for CRM prospección.
 *
 * Vanilla JS + Drupal.behaviors + once().
 * Updates negocio estado_embudo via AJAX when card is dropped.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.aeiProspeccionPipeline = {
    attach: function (context) {
      once('aei-pipeline', '[data-pipeline]', context).forEach(function (pipeline) {
        var cards = pipeline.querySelectorAll('[data-pipeline-card]');
        var dropZones = pipeline.querySelectorAll('[data-pipeline-drop]');

        // Drag start.
        cards.forEach(function (card) {
          card.addEventListener('dragstart', function (e) {
            e.dataTransfer.setData('text/plain', card.getAttribute('data-pipeline-card'));
            e.dataTransfer.effectAllowed = 'move';
            card.classList.add('is-dragging');
          });

          card.addEventListener('dragend', function () {
            card.classList.remove('is-dragging');
            dropZones.forEach(function (zone) {
              zone.classList.remove('is-drag-over');
            });
          });
        });

        // Drop zones.
        dropZones.forEach(function (zone) {
          zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            zone.classList.add('is-drag-over');
          });

          zone.addEventListener('dragleave', function () {
            zone.classList.remove('is-drag-over');
          });

          zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('is-drag-over');

            var cardId = e.dataTransfer.getData('text/plain');
            var card = pipeline.querySelector('[data-pipeline-card="' + cardId + '"]');
            if (!card) {
              return;
            }

            var newFase = zone.getAttribute('data-pipeline-drop');
            var oldFase = card.getAttribute('data-pipeline-fase-actual');

            if (newFase === oldFase) {
              return;
            }

            // Move card visually.
            zone.appendChild(card);
            card.setAttribute('data-pipeline-fase-actual', newFase);

            // Update counters.
            _updateColumnCounts(pipeline);

            // Persist via AJAX (CSRF-JS-CACHE-001).
            _persistFaseChange(cardId, newFase);
          });
        });
      });

      /**
       * Updates column counts after drag-and-drop.
       */
      function _updateColumnCounts(pipeline) {
        pipeline.querySelectorAll('[data-pipeline-fase]').forEach(function (col) {
          var fase = col.getAttribute('data-pipeline-fase');
          var count = col.querySelectorAll('[data-pipeline-card]').length;
          var countEl = col.querySelector('.aei-pipeline__column-count');
          if (countEl) {
            countEl.textContent = count.toString();
          }
        });
      }

      /**
       * Persists fase change to backend.
       */
      function _persistFaseChange(entityId, newFase) {
        // Use drupalSettings for the API endpoint (ROUTE-LANGPREFIX-001).
        var basePath = drupalSettings.path ? drupalSettings.path.baseUrl : '/';
        var url = basePath + 'api/v1/andalucia-ei/prospeccion/' + entityId + '/fase';

        // Get CSRF token (CSRF-JS-CACHE-001).
        fetch(basePath + 'session/token')
          .then(function (response) { return response.text(); })
          .then(function (token) {
            return fetch(url, {
              method: 'PATCH',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token,
              },
              body: JSON.stringify({ estado_embudo: newFase }),
            });
          })
          .then(function (response) {
            if (!response.ok) {
              console.error('Pipeline update failed:', response.status);
            }
          })
          .catch(function (error) {
            console.error('Pipeline update error:', error);
          });
      }
    },
  };
})(Drupal, once);
