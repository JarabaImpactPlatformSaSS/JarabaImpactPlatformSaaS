/**
 * @file
 * BMC Dashboard interactividad: filtros, semaforos, AJAX refresh.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.bmcDashboard = {
    attach: function (context) {
      once('bmc-dashboard', '.bmc-dashboard', context).forEach(function (el) {
        Drupal.bmcDashboard.init(el);
      });
    }
  };

  Drupal.bmcDashboard = {
    init: function (container) {
      // Click on BMC block to show hypotheses
      var blocks = container.querySelectorAll('.bmc-block');
      blocks.forEach(function (block) {
        block.addEventListener('click', function () {
          var code = block.querySelector('.bmc-block__code');
          if (code) {
            var bmcBlock = code.textContent.trim();
            window.location.href = Drupal.url('emprendimiento/hipotesis') + '?bmc_block=' + encodeURIComponent(bmcBlock);
          }
        });
        block.style.cursor = 'pointer';
      });
    }
  };

})(Drupal, once);
