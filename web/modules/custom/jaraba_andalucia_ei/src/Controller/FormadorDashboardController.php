<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard for formadores in Andalucía +ei.
 *
 * PIPELINE-E2E-001: Full L1→L4 wiring.
 *   L1: Services injected in constructor + create().
 *   L2: dashboard() returns #theme render array with populated variables.
 *   L3: hook_theme() declares 'formador_dashboard' with matching variables.
 *   L4: formador-dashboard.html.twig renders setup_wizard, daily_actions, etc.
 *
 * CONTROLLER-READONLY-001: Does NOT use protected readonly for inherited
 * $entityTypeManager property.
 */
class FormadorDashboardController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ?TenantContextService $tenantContext,
    protected LoggerInterface $logger,
    protected ?SetupWizardRegistry $wizardRegistry = NULL,
    protected ?DailyActionsRegistry $dailyActionsRegistry = NULL,
  ) {
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
      $container->has('ecosistema_jaraba_core.setup_wizard_registry')
        ? $container->get('ecosistema_jaraba_core.setup_wizard_registry') : NULL,
      $container->has('ecosistema_jaraba_core.daily_actions_registry')
        ? $container->get('ecosistema_jaraba_core.daily_actions_registry') : NULL,
    );
  }

  /**
   * Renders the formador dashboard.
   *
   * ZERO-REGION-001: Returns themed render array.
   * SETUP-WIZARD-DAILY-001: Populates setup wizard + daily actions.
   *
   * @return array<string, mixed>
   *   Render array with #theme => 'formador_dashboard'.
   */
  public function dashboard(): array {
    $tenantId = 0;
    if ($this->tenantContext !== NULL) {
      try {
        $tenantId = (int) $this->tenantContext->getCurrentTenantId();
      }
      catch (\Throwable) {
        // Fallback to 0 if no tenant context.
      }
    }

    // SETUP-WIZARD-DAILY-001: Wizard + daily actions via registry.
    $setupWizard = NULL;
    $dailyActions = [];
    if ($this->wizardRegistry !== NULL) {
      $setupWizard = $this->wizardRegistry->hasWizard('formador_ei')
        ? $this->wizardRegistry->getStepsForWizard('formador_ei', $tenantId)
        : NULL;
    }
    if ($this->dailyActionsRegistry !== NULL) {
      $dailyActions = $this->dailyActionsRegistry->getActionsForDashboard('formador_ei', $tenantId);
    }

    // Formador-specific data: today's sessions, attendance stats, hours.
    $formadorData = $this->getFormadorData($tenantId);

    // Entregables completed by participants, pending formador validation.
    $entregablesPendientes = $this->getEntregablesPendientesValidacion($tenantId);

    return [
      '#theme' => 'formador_dashboard',
      '#setup_wizard' => $setupWizard,
      '#daily_actions' => $dailyActions,
      '#formador_data' => $formadorData,
      '#entregables_pendientes' => $entregablesPendientes,
      '#attached' => [
        'library' => ['jaraba_andalucia_ei/dashboard'],
      ],
    ];
  }

  /**
   * Loads formador dashboard KPI data.
   *
   * @param int $tenantId
   *   Tenant group ID.
   *
   * @return array<string, mixed>
   *   Dashboard data keyed by KPI name.
   */
  protected function getFormadorData(int $tenantId): array {
    $data = [
      'sesiones_hoy' => 0,
      'sesiones_hoy_detalle' => [],
      'asistencia_pendiente' => 0,
      'horas_impartidas' => 0,
      'asistencia_media' => 0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');

      // Today's sessions for this formador — with detail for template.
      $today = date('Y-m-d');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('fecha', $today . 'T00:00:00', '>=')
        ->condition('fecha', $today . 'T23:59:59', '<=')
        ->sort('fecha', 'ASC');
      if ($tenantId > 0) {
        $query->condition('tenant_id', $tenantId);
      }
      $todayIds = $query->execute();
      $data['sesiones_hoy'] = count($todayIds);

      if (count($todayIds) > 0) {
        $todaySessions = $storage->loadMultiple($todayIds);
        foreach ($todaySessions as $session) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $session */
          $enlace = '';
          if ($session->hasField('enlace_videoconferencia') && !$session->get('enlace_videoconferencia')->isEmpty()) {
            $enlace = (string) $session->get('enlace_videoconferencia')->value;
          }
          $modalidad = '';
          if ($session->hasField('modalidad') && !$session->get('modalidad')->isEmpty()) {
            $modalidad = (string) $session->get('modalidad')->value;
          }
          $data['sesiones_hoy_detalle'][] = [
            'id' => (int) $session->id(),
            'titulo' => $session->label() ?? $this->t('Sesión #@id', ['@id' => $session->id()]),
            'fecha' => $session->hasField('fecha') && !$session->get('fecha')->isEmpty()
              ? (string) $session->get('fecha')->value
              : '',
            'modalidad' => $modalidad,
            'enlace_videoconferencia' => $enlace,
          ];
        }
      }

      // Total sessions (for hours calculation).
      $allQuery = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('fecha', $today . 'T23:59:59', '<=');
      if ($tenantId > 0) {
        $allQuery->condition('tenant_id', $tenantId);
      }
      $totalSessions = $allQuery->count()->execute();
      // Estimate 2.5h per session average (50h / 20 sessions).
      $data['horas_impartidas'] = round($totalSessions * 2.5, 1);

      // Attendance pending — sessions without registered attendance.
      if ($this->entityTypeManager->hasDefinition('asistencia_detallada_ei')) {
        $asistStorage = $this->entityTypeManager->getStorage('asistencia_detallada_ei');
        $pendingQuery = $asistStorage->getQuery()
          ->accessCheck(TRUE)
          ->condition('asistio', NULL, 'IS NULL');
        if ($tenantId > 0) {
          $pendingQuery->condition('tenant_id', $tenantId);
        }
        $data['asistencia_pendiente'] = $pendingQuery->count()->execute();

        // Average attendance percentage.
        $totalAsist = $asistStorage->getQuery()
          ->accessCheck(TRUE);
        if ($tenantId > 0) {
          $totalAsist->condition('tenant_id', $tenantId);
        }
        $total = $totalAsist->count()->execute();
        if ($total > 0) {
          $presentQuery = $asistStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('asistio', 1);
          if ($tenantId > 0) {
            $presentQuery->condition('tenant_id', $tenantId);
          }
          $present = $presentQuery->count()->execute();
          $data['asistencia_media'] = round(($present / $total) * 100);
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading formador dashboard data: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $data;
  }

  /**
   * Gets entregables completed by participants, pending formador validation.
   *
   * @param int $tenantId
   *   Tenant group ID.
   *
   * @return list<array<string, mixed>>
   *   List of entregable data arrays.
   */
  protected function getEntregablesPendientesValidacion(int $tenantId): array {
    $pendientes = [];

    try {
      if (!$this->entityTypeManager->hasDefinition('entregable_formativo_ei')) {
        return [];
      }

      $storage = $this->entityTypeManager->getStorage('entregable_formativo_ei');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('estado', 'completado')
        ->sort('changed', 'DESC')
        ->range(0, 20);
      if ($tenantId > 0) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      if (count($ids) === 0) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        /** @var \Drupal\jaraba_andalucia_ei\Entity\EntregableFormativoEi $entity */
        $participanteRef = $entity->get('participante_id')->entity;
        // LABEL-NULLSAFE-001: label() puede devolver NULL.
        $participanteNombre = (string) ($participanteRef->label() ?? $this->t('Participante desconocido'));
        $pendientes[] = [
          'id' => (int) $entity->id(),
          'numero' => (int) $entity->get('numero')->value,
          'titulo' => (string) $entity->get('titulo')->value,
          'modulo' => (string) $entity->get('modulo')->value,
          'participante_nombre' => $participanteNombre,
          'generado_con_ia' => (bool) $entity->get('generado_con_ia')->value,
          'changed' => (int) $entity->get('changed')->value,
        ];
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading pending entregables: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $pendientes;
  }

}
