<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\EiAlumniBridgeService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller de la comunidad alumni Andalucía +ei.
 *
 * Sprint 10 — Plan Maestro Andalucía +ei Clase Mundial.
 */
class AlumniController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected LoggerInterface $logger,
    protected ?EiAlumniBridgeService $alumniBridge = NULL,
    protected ?object $tenantContext = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->has('jaraba_andalucia_ei.ei_alumni_bridge')
        ? $container->get('jaraba_andalucia_ei.ei_alumni_bridge')
        : NULL,
      $container->has('ecosistema_jaraba_core.tenant_context')
        ? $container->get('ecosistema_jaraba_core.tenant_context')
        : NULL,
    );
  }

  /**
   * Renderiza la comunidad alumni.
   */
  public function comunidad(): array {
    $tenantId = $this->resolveCurrentTenantId();
    $alumni = [];
    $stats = [];
    $historias = [];

    if ($this->alumniBridge) {
      try {
        $alumni = $this->alumniBridge->getAlumniDirectory($tenantId ?? 0);
        $stats = $this->alumniBridge->getAlumniStats($tenantId ?? 0);
        $historias = $this->alumniBridge->getHistoriasExito($tenantId ?? 0, 6);
      }
      catch (\Throwable $e) {
        $this->logger->warning('Error cargando alumni: @msg', ['@msg' => $e->getMessage()]);
      }
    }

    return [
      '#theme' => 'andalucia_ei_comunidad_alumni',
      '#alumni' => $alumni,
      '#stats' => $stats,
      '#historias' => $historias,
      '#attached' => [
        'library' => ['jaraba_andalucia_ei/participante-portal'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['programa_participante_ei_list'],
        'max-age' => 600,
      ],
    ];
  }

  /**
   * Resuelve el tenant ID actual.
   */
  protected function resolveCurrentTenantId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
