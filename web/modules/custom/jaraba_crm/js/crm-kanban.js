/**
 * @file
 * JavaScript para Kanban Drag & Drop.
 */
(function (Drupal) {
    'use strict';

    Drupal.behaviors.crmKanban = {
        attach: function (context) {
            const board = context.querySelector('#crm-kanban-board');
            if (!board || board.dataset.processed) return;
            board.dataset.processed = 'true';

            const cards = board.querySelectorAll('.crm-kanban__card');
            const columns = board.querySelectorAll('.crm-kanban__cards');

            // Drag start
            cards.forEach(card => {
                card.addEventListener('dragstart', function (e) {
                    this.classList.add('dragging');
                    e.dataTransfer.setData('text/plain', this.dataset.id);
                });

                card.addEventListener('dragend', function () {
                    this.classList.remove('dragging');
                });
            });

            // Drop zones
            columns.forEach(column => {
                column.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    this.classList.add('drag-over');
                });

                column.addEventListener('dragleave', function () {
                    this.classList.remove('drag-over');
                });

                column.addEventListener('drop', function (e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');

                    const opportunityId = e.dataTransfer.getData('text/plain');
                    const newStage = this.dataset.stage;
                    const card = board.querySelector(`[data-id="${opportunityId}"]`);

                    if (card && card.parentNode !== this) {
                        this.appendChild(card);
                        Drupal.crmKanban.moveOpportunity(opportunityId, newStage);
                    }
                });
            });
        }
    };

    Drupal.crmKanban = {
        moveOpportunity: function (opportunityId, stage) {
            fetch('/api/v1/crm/pipeline/move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    opportunity_id: opportunityId,
                    stage: stage
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Drupal.crmKanban.updateCounts();
                    } else {
                        console.error('Error moving opportunity:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        },

        updateCounts: function () {
            const columns = document.querySelectorAll('.crm-kanban__column');
            columns.forEach(column => {
                const count = column.querySelectorAll('.crm-kanban__card').length;
                const countEl = column.querySelector('.crm-kanban__column-count');
                if (countEl) {
                    countEl.textContent = count;
                }
            });
        }
    };

})(Drupal);
