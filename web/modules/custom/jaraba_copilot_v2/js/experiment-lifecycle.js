/**
 * @file
 * Experiment Lifecycle: flujo Test -> Start -> Result via API.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.experimentLifecycle = {
    attach: function (context) {
      once('experiment-lifecycle', '.experiment-lifecycle', context).forEach(function (el) {
        Drupal.experimentLifecycle.init(el);
      });
    }
  };

  Drupal.experimentLifecycle = {
    init: function (container) {
      this.container = container;
      this.bindActions();
      this.bindFilters();
    },

    bindActions: function () {
      var self = this;

      // Start experiment
      this.container.addEventListener('click', function (e) {
        var startBtn = e.target.closest('[data-action="start"]');
        if (startBtn) {
          e.preventDefault();
          self.startExperiment(startBtn.dataset.experimentId);
        }

        var resultBtn = e.target.closest('[data-action="record-result"]');
        if (resultBtn) {
          e.preventDefault();
          self.showResultDialog(resultBtn.dataset.experimentId);
        }
      });
    },

    bindFilters: function () {
      var filter = this.container.querySelector('[data-filter="status"]');
      if (!filter) return;

      var self = this;
      filter.addEventListener('change', function () {
        var status = filter.value;
        var cards = self.container.querySelectorAll('.experiment-card');
        cards.forEach(function (card) {
          if (!status || card.dataset.status === status) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
          }
        });
      });
    },

    startExperiment: function (id) {
      if (!confirm(Drupal.t('Iniciar este experimento? El estado cambiara a EN PROGRESO.'))) {
        return;
      }

      fetch(Drupal.url('api/v1/experiments/' + id + '/start?_format=json'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.success) {
          window.location.reload();
        } else {
          alert(data.error || 'Error al iniciar el experimento.');
        }
      })
      .catch(function () {
        alert('Error de conexion.');
      });
    },

    showResultDialog: function (id) {
      var decisions = ['PERSEVERE', 'PIVOT', 'ZOOM_IN', 'ZOOM_OUT', 'KILL'];
      var decision = prompt(
        Drupal.t('Selecciona la decision del experimento:') +
        '\n\nPERSEVERE (+100 Pi)' +
        '\nPIVOT (+75 Pi)' +
        '\nZOOM_IN (+75 Pi)' +
        '\nZOOM_OUT (+75 Pi)' +
        '\nKILL (+50 Pi)'
      );

      if (!decision) return;
      decision = decision.toUpperCase().trim();

      if (decisions.indexOf(decision) === -1) {
        alert(Drupal.t('Decision no valida. Usa: ') + decisions.join(', '));
        return;
      }

      var observations = prompt(Drupal.t('Describe las observaciones del experimento:')) || '';

      fetch(Drupal.url('api/v1/experiments/' + id + '/result?_format=json'), {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          decision: decision,
          observations: observations
        })
      })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.success) {
          alert(Drupal.t('Resultado registrado! +@points Pi', {'@points': data.points_awarded}));
          window.location.reload();
        } else {
          alert(data.error || 'Error al registrar resultado.');
        }
      })
      .catch(function () {
        alert('Error de conexion.');
      });
    }
  };

})(Drupal, once);
