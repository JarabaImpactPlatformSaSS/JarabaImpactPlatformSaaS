<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Service\ComplianceAggregatorService;
use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller del Panel Compliance Unificado.
 *
 * ESTRUCTURA:
 * Panel cross-modulo que agrega KPIs de jaraba_privacy, jaraba_legal
 * y jaraba_dr en una vista unificada con score global y alertas.
 *
 * LOGICA:
 * - Renderiza dashboard HTML con 9 KPIs en grid 3x3.
 * - Score global con grado (A-F) y semaforo visual.
 * - Alertas ordenadas por severidad.
 * - API endpoint para refresh via AJAX.
 *
 * RELACIONES:
 * - ComplianceAggregatorService (calcula KPIs)
 * - Ruta /admin/jaraba/compliance
 * - Template compliance-panel.html.twig
 *
 * Spec: Plan Stack Compliance Legal N1 — FASE 12.
 */
class CompliancePanelController extends ControllerBase implements ContainerInjectionInterface {

  use ApiResponseTrait;

  public function __construct(
    protected ComplianceAggregatorService $aggregator,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.compliance_aggregator'),
      $container->get('logger.channel.ecosistema_jaraba_core'),
    );
  }

  /**
   * Renderiza el panel de compliance unificado.
   *
   * @return array
   *   Render array con KPIs, score y alertas.
   */
  public function dashboard(): array {
    $overview = $this->aggregator->getComplianceOverview();

    return [
      '#theme' => 'compliance_panel',
      '#score' => $overview['score'],
      '#grade' => $overview['grade'],
      '#kpis' => $overview['kpis'],
      '#alerts' => $overview['alerts'],
      '#modules' => $overview['modules'],
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/compliance-panel',
        ],
        'drupalSettings' => [
          'compliancePanel' => [
            'refreshEndpoint' => '/api/v1/compliance/overview',
            'refreshInterval' => 60000,
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 60,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * API endpoint para obtener datos del panel de compliance.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   KPIs, score y alertas en formato JSON.
   */
  public function apiOverview(): JsonResponse {
    try {
      $overview = $this->aggregator->getComplianceOverview();
      return $this->apiSuccess($overview);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo compliance overview: @error', [
        '@error' => $e->getMessage(),
      ]);
      return $this->apiError(
        'Error al obtener datos de compliance.',
        'COMPLIANCE_ERROR',
        500,
      );
    }
  }

}
