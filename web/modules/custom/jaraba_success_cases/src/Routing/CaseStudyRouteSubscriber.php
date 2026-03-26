<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Overrides legacy case study routes to use the unified controller.
 *
 * SUCCESS-CASES-001: All case study pages must come from SuccessCase entity.
 * LEGACY-CONTROLLER-CLEANUP-001 (2026-03-26): Legacy routes removed from
 * vertical routing.yml files. This subscriber acts as defense-in-depth:
 * if legacy routes are ever re-added, it redirects them to the unified
 * controller. The parametrized route in jaraba_success_cases.routing.yml
 * now handles all case study URLs.
 */
class CaseStudyRouteSubscriber extends RouteSubscriberBase {

  /**
   * Maps legacy route names to their vertical_path and slug parameters.
   *
   * Slugs match the pre-launch placeholder cases in seed-success-cases.php.
   * Updated 2026-03-26: empleabilidad rosa-fernandez → luis-miguel-criado
   * (Rosa was replaced with real participant Luis Miguel Criado).
   */
  private const LEGACY_ROUTES = [
    'jaraba_agroconecta_core.case_study.sierra_cazorla' => ['vertical_path' => 'agroconecta', 'slug' => 'cooperativa-sierra-cazorla'],
    'jaraba_legal.case_study.martinez' => ['vertical_path' => 'jarabalex', 'slug' => 'despacho-martinez'],
    'jaraba_candidate.case_study.rosa_fernandez' => ['vertical_path' => 'empleabilidad', 'slug' => 'luis-miguel-criado'],
    'jaraba_business_tools.case_study.carlos_etxebarria' => ['vertical_path' => 'emprendimiento', 'slug' => 'carlos-etxebarria-bilbao'],
    'jaraba_comercio_conecta.case_study.boutique_mariposa' => ['vertical_path' => 'comercioconecta', 'slug' => 'boutique-la-mariposa'],
    'jaraba_servicios_conecta.case_study.carmen_navarro' => ['vertical_path' => 'serviciosconecta', 'slug' => 'carmen-navarro-madrid'],
    'jaraba_lms.case_study.maria_lopez' => ['vertical_path' => 'formacion', 'slug' => 'maria-lopez-madrid'],
    'jaraba_andalucia_ei.case_study.ped' => ['vertical_path' => 'andalucia-ei', 'slug' => 'plataforma-ecosistemas-digitales'],
    'jaraba_content_hub.case_study.bodega_montilla' => ['vertical_path' => 'content-hub', 'slug' => 'bodega-montilla-cordoba'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach (self::LEGACY_ROUTES as $routeName => $params) {
      $route = $collection->get($routeName);
      if (!$route) {
        continue;
      }

      // Override the controller to use the unified one.
      $route->setDefault('_controller', '\Drupal\jaraba_success_cases\Controller\CaseStudyLandingController::caseStudy');

      // Set the parameters that the unified controller expects.
      $route->setDefault('vertical_path', $params['vertical_path']);
      $route->setDefault('slug', $params['slug']);
    }
  }

}
