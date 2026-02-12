/**
 * @file
 * Expansion Pipeline - Interactividad del pipeline de expansion.
 *
 * PROPOSITO:
 * - Visualizacion drag del pipeline kanban.
 * - Contadores de etapa con actualizacion visual.
 * - Animacion de tarjetas de senal.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Inicializa el pipeline kanban con drag visual.
   */
  Drupal.behaviors.csExpansionPipeline = {
    attach: function (context) {
      once('cs-expansion-pipeline', '.cs-expansion-pipeline__board', context).forEach(function (board) {
        var columns = board.querySelectorAll('.cs-expansion-pipeline__column');
        var cards = board.querySelectorAll('.cs-expansion-pipeline__card');

        // Make cards draggable.
        cards.forEach(function (card) {
          card.setAttribute('draggable', 'true');

          card.addEventListener('dragstart', function (e) {
            card.classList.add('cs-expansion-pipeline__card--dragging');
            e.dataTransfer.setData('text/plain', card.getAttribute('data-signal-id'));
            e.dataTransfer.effectAllowed = 'move';
          });

          card.addEventListener('dragend', function () {
            card.classList.remove('cs-expansion-pipeline__card--dragging');
          });
        });

        // Make columns drop targets.
        columns.forEach(function (column) {
          var body = column.querySelector('.cs-expansion-pipeline__column-body');
          if (!body) {
            return;
          }

          column.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            column.classList.add('cs-expansion-pipeline__column--drag-over');
          });

          column.addEventListener('dragleave', function () {
            column.classList.remove('cs-expansion-pipeline__column--drag-over');
          });

          column.addEventListener('drop', function (e) {
            e.preventDefault();
            column.classList.remove('cs-expansion-pipeline__column--drag-over');

            var signalId = e.dataTransfer.getData('text/plain');
            var draggedCard = board.querySelector('[data-signal-id="' + signalId + '"]');
            if (!draggedCard) {
              return;
            }

            var newStage = column.getAttribute('data-stage');
            if (!newStage) {
              return;
            }

            // Remove empty column placeholder if present.
            var emptyPlaceholder = body.querySelector('.cs-expansion-pipeline__empty-column');
            if (emptyPlaceholder) {
              emptyPlaceholder.remove();
            }

            // Move card to new column.
            body.appendChild(draggedCard);

            // Update column counts.
            updateColumnCounts(board);
          });
        });
      });
    }
  };

  /**
   * Anima los stage counts al cargar.
   */
  Drupal.behaviors.csExpansionStageCounts = {
    attach: function (context) {
      once('cs-expansion-stage-counts', '.cs-expansion-pipeline__count-value', context).forEach(function (el) {
        var target = parseInt(el.textContent, 10);
        if (isNaN(target) || target === 0) {
          return;
        }

        var current = 0;
        var increment = Math.ceil(target / 20);
        var timer = setInterval(function () {
          current += increment;
          if (current >= target) {
            current = target;
            clearInterval(timer);
          }
          el.textContent = current.toString();
        }, 40);
      });
    }
  };

  /**
   * Updates column count badges after drag operations.
   *
   * @param {HTMLElement} board
   *   The pipeline board element.
   */
  function updateColumnCounts(board) {
    var columns = board.querySelectorAll('.cs-expansion-pipeline__column');
    columns.forEach(function (column) {
      var cards = column.querySelectorAll('.cs-expansion-pipeline__card');
      var countEl = column.querySelector('.cs-expansion-pipeline__column-count');
      if (countEl) {
        countEl.textContent = cards.length.toString();
      }
    });

    // Also update the summary stage counts if they exist.
    var stageCounts = document.querySelectorAll('.cs-expansion-pipeline__stage-count');
    stageCounts.forEach(function (stageCount) {
      var stageClass = Array.from(stageCount.classList).find(function (cls) {
        return cls.indexOf('cs-expansion-pipeline__stage-count--') === 0;
      });
      if (!stageClass) {
        return;
      }

      var stage = stageClass.replace('cs-expansion-pipeline__stage-count--', '');
      var column = board.querySelector('[data-stage="' + stage + '"]');
      if (column) {
        var cardCount = column.querySelectorAll('.cs-expansion-pipeline__card').length;
        var valueEl = stageCount.querySelector('.cs-expansion-pipeline__count-value');
        if (valueEl) {
          valueEl.textContent = cardCount.toString();
        }
      }
    });
  }

})(Drupal, once);
