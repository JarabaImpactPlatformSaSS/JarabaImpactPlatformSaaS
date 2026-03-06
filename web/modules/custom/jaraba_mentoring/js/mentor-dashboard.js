/**
 * @file
 * Mentor dashboard behaviors.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.mentorDashboard = {
    attach: function (context) {
      // Initialize any dashboard-specific behaviors.
      once('mentor-dashboard', '.mentor-dashboard', context).forEach(function (dashboard) {
        // Animate stat cards on load.
        var statCards = dashboard.querySelectorAll('.mentor-dashboard__stat-card');
        statCards.forEach(function (card, index) {
          card.style.opacity = '0';
          card.style.transform = 'translateY(10px)';
          setTimeout(function () {
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          }, index * 100);
        });
      });
    }
  };

})(Drupal, once);
