<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_andalucia_ei\Service\ProspeccionPipelineService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller para la vista Kanban del pipeline de prospección.
 *
 * Muestra los negocios prospectados agrupados por fase del embudo
 * comercial (6 columnas) con estadísticas agregadas.
 *
 * ZERO-REGION-001: Devuelve render array con #theme.
 * CONTROLLER-READONLY-001: No usa protected readonly para entityTypeManager.
 */
class ProspeccionPipelineController extends ControllerBase {

  /**
   * Constructs a ProspeccionPipelineController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   El gestor de tipos de entidad.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null $tenantContext
   *   Servicio de contexto de tenant (opcional, @?).
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log para andalucia_ei.
   * @param \Drupal\jaraba_andalucia_ei\Service\ProspeccionPipelineService|null $pipelineService
   *   Servicio del pipeline de prospección (opcional, @?).
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ?TenantContextService $tenantContext,
    protected LoggerInterface $logger,
    protected ?ProspeccionPipelineService $pipelineService = NULL,
  ) {
    // CONTROLLER-READONLY-001: ControllerBase::$entityTypeManager no tiene
    // declaración de tipo. Asignar manualmente en constructor body.
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->get('jaraba_andalucia_ei.prospeccion_pipeline', ContainerInterface::NULL_ON_INVALID_REFERENCE),
    );
  }

  /**
   * Muestra el pipeline Kanban de prospección comercial.
   *
   * Resuelve el tenant actual, obtiene los datos del pipeline agrupados
   * por fase del embudo y las estadísticas agregadas, y devuelve un
   * render array con #theme para la vista Kanban.
   *
   * @return array<string, mixed>
   *   Render array con #theme => 'prospeccion_pipeline'.
   */
  public function pipeline(): array {
    $pipeline = [];
    $stats = [
      'por_fase' => [],
      'total' => 0,
      'tasa_conversion' => 0.0,
    ];

    // Resolver tenant via TenantContextService.
    $tenantId = 0;
    if ($this->tenantContext !== NULL) {
      try {
        $tenantId = $this->tenantContext->getCurrentTenantId();
      }
      catch (\Throwable $e) {
        $this->logger->warning('No se pudo resolver tenant para pipeline de prospección: @message', [
          '@message' => $e->getMessage(),
        ]);
      }
    }

    // Obtener datos del pipeline si el servicio está disponible.
    if ($this->pipelineService !== NULL && $tenantId > 0) {
      $pipeline = $this->pipelineService->getPipelineByEstado($tenantId);
      $stats = $this->pipelineService->getEstadisticas($tenantId);
    }

    // ROUTE-LANGPREFIX-001: URLs via Url::fromRoute(), nunca hardcoded.
    $landingUrl = Url::fromRoute('jaraba_andalucia_ei.prueba_gratuita')->toString();

    // ZERO-REGION-001: Devolver render array con #theme.
    return [
      '#theme' => 'prospeccion_pipeline',
      '#pipeline' => $pipeline,
      '#stats' => $stats,
      '#landing_url' => $landingUrl,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/prospeccion-pipeline',
        ],
      ],
    ];
  }

}
