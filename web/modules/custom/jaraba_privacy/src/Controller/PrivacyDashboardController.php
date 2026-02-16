<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_privacy\Service\BreachNotificationService;
use Drupal\jaraba_privacy\Service\CookieConsentManagerService;
use Drupal\jaraba_privacy\Service\DataRightsHandlerService;
use Drupal\jaraba_privacy\Service\DpaManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CONTROLADOR DEL DASHBOARD DE PRIVACIDAD — PrivacyDashboardController.
 *
 * ESTRUCTURA:
 * Renderiza el dashboard frontend zero-region con las métricas y estado
 * de todos los componentes de privacidad del tenant activo.
 *
 * LÓGICA:
 * - Obtiene estado DPA, métricas de cookies, solicitudes ARCO-POL y brechas.
 * - Renderiza el template privacy-dashboard.html.twig.
 * - Adjunta la librería privacy-dashboard de CSS/JS.
 *
 * RELACIONES:
 * - PrivacyDashboardController → DpaManagerService (estado DPA)
 * - PrivacyDashboardController → CookieConsentManagerService (métricas cookies)
 * - PrivacyDashboardController → DataRightsHandlerService (solicitudes ARCO-POL)
 * - PrivacyDashboardController → BreachNotificationService (alertas brechas)
 *
 * Spec: Doc 183 §4. Plan: FASE 3, Stack Compliance Legal N1.
 */
class PrivacyDashboardController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected DpaManagerService $dpaManager,
    protected CookieConsentManagerService $cookieConsentManager,
    protected DataRightsHandlerService $dataRightsHandler,
    protected BreachNotificationService $breachNotification,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_privacy.dpa_manager'),
      $container->get('jaraba_privacy.cookie_consent_manager'),
      $container->get('jaraba_privacy.data_rights_handler'),
      $container->get('jaraba_privacy.breach_notification'),
    );
  }

  /**
   * Renderiza el dashboard de privacidad GDPR.
   *
   * @return array
   *   Render array con el dashboard de privacidad.
   */
  public function dashboard(): array {
    return [
      '#theme' => 'privacy_dashboard',
      '#dpa_status' => [],
      '#cookie_stats' => [],
      '#arco_pol_requests' => [],
      '#breach_alerts' => [],
      '#rat_overview' => [],
      '#metrics' => [],
      '#attached' => [
        'library' => [
          'jaraba_privacy/privacy-dashboard',
        ],
      ],
    ];
  }

}
