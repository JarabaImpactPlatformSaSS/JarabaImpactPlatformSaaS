/**
 * @file
 * JavaScript del dashboard de privacidad GDPR.
 *
 * Proporciona interactividad al dashboard: métricas en tiempo real,
 * gestión de estado DPA y visualización de solicitudes ARCO-POL.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Comportamiento del dashboard de privacidad.
   */
  Drupal.behaviors.jarabaPrivacyDashboard = {
    attach: function (context) {
      once('jaraba-privacy-dashboard', '.jaraba-privacy-dashboard', context).forEach(function (element) {
        Drupal.jarabaPrivacy = Drupal.jarabaPrivacy || {};

        // Inicializar componentes del dashboard.
        Drupal.jarabaPrivacy.initDpaStatus(element);
        Drupal.jarabaPrivacy.initArcoPol(element);
        Drupal.jarabaPrivacy.initBreachAlerts(element);
      });
    }
  };

  /**
   * Inicializa el widget de estado DPA.
   */
  Drupal.jarabaPrivacy = Drupal.jarabaPrivacy || {};

  Drupal.jarabaPrivacy.initDpaStatus = function (dashboard) {
    var dpaSection = dashboard.querySelector('.privacy-dashboard__section--dpa');
    if (!dpaSection) {
      return;
    }

    // Cargar estado DPA via API.
    fetch('/api/v1/dpa/current', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data.success && data.data) {
        Drupal.jarabaPrivacy.renderDpaStatus(dpaSection, data.data);
      }
    })
    .catch(function (error) {
      console.warn('Error al cargar estado DPA:', error);
    });
  };

  /**
   * Renderiza el estado DPA en el widget.
   */
  Drupal.jarabaPrivacy.renderDpaStatus = function (container, data) {
    var badge = container.querySelector('.dpa-status__badge');
    if (badge && data.has_dpa) {
      badge.className = 'dpa-status__badge dpa-status__badge--active';
      badge.textContent = Drupal.t('Activo');
    }
  };

  /**
   * Inicializa el widget de solicitudes ARCO-POL.
   */
  Drupal.jarabaPrivacy.initArcoPol = function (dashboard) {
    var arcoSection = dashboard.querySelector('.privacy-dashboard__section--arco-pol');
    if (!arcoSection) {
      return;
    }
    // Placeholder: cargar solicitudes activas via API.
  };

  /**
   * Inicializa el widget de alertas de brechas.
   */
  Drupal.jarabaPrivacy.initBreachAlerts = function (dashboard) {
    var breachSection = dashboard.querySelector('.privacy-dashboard__section--breaches');
    if (!breachSection) {
      return;
    }
    // Placeholder: cargar alertas activas.
  };

})(Drupal, once);
