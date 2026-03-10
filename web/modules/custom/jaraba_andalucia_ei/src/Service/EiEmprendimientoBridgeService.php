<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\PlanEmprendimientoEi;
use Psr\Log\LoggerInterface;

/**
 * Puente entre PlanEmprendimientoEi y jaraba_business_tools.
 *
 * Sprint 7 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * Conecta el itinerario de emprendimiento inclusivo con las herramientas
 * de negocio existentes: BMC, MVP Validation, Financial Projections, SROI.
 */
class EiEmprendimientoBridgeService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $canvasService = NULL,
    protected ?object $mvpValidationService = NULL,
    protected ?object $projectionService = NULL,
    protected ?object $sroiCalculatorService = NULL,
    protected ?object $tenantContext = NULL,
  ) {}

  /**
   * Crea un plan de emprendimiento desde un participante.
   *
   * Crea el PlanEmprendimientoEi + BMC vacío vinculado.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   * @param string $ideaNegocio
   *   Descripción inicial de la idea.
   *
   * @return array{success: bool, plan_id: int|null, message: string}
   */
  public function crearPlanDesdeParticipante(int $participanteId, string $ideaNegocio = ''): array {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return ['success' => FALSE, 'plan_id' => NULL, 'message' => 'Participante no encontrado.'];
      }

      // Verificar que no existe ya un plan activo.
      $existente = $this->getPlanActivo($participanteId);
      if ($existente) {
        return [
          'success' => FALSE,
          'plan_id' => (int) $existente->id(),
          'message' => 'Ya existe un plan de emprendimiento activo para este participante.',
        ];
      }

      // Resolver tenant.
      $tenantId = NULL;
      if ($participante->hasField('tenant_id') && !$participante->get('tenant_id')->isEmpty()) {
        $tenantId = (int) $participante->get('tenant_id')->target_id;
      }

      $storage = $this->entityTypeManager->getStorage('plan_emprendimiento_ei');
      $plan = $storage->create([
        'label' => 'Plan emprendimiento - ' . ($participante->label() ?? $participanteId),
        'participante_id' => $participanteId,
        'uid' => $participante->getOwnerId(),
        'tenant_id' => $tenantId,
        'idea_negocio' => $ideaNegocio,
        'fase_emprendimiento' => PlanEmprendimientoEi::FASE_IDEACION,
        'diagnostico_viabilidad' => 'pendiente',
        'status' => TRUE,
      ]);
      $plan->save();

      // Crear BMC vinculado si el servicio está disponible.
      $canvasId = NULL;
      if ($this->canvasService) {
        try {
          $canvas = $this->canvasService->createCanvas([
            'title' => 'BMC - ' . ($participante->label() ?? $participanteId),
            'owner_uid' => $participante->getOwnerId(),
          ]);
          if ($canvas && method_exists($canvas, 'id')) {
            $canvasId = (int) $canvas->id();
            $plan->set('canvas_id', $canvasId);
            $plan->save();
          }
        }
        catch (\Throwable $e) {
          $this->logger->warning('No se pudo crear BMC para plan @id: @msg', [
            '@id' => $plan->id(),
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('Plan emprendimiento @id creado para participante @pid.', [
        '@id' => $plan->id(),
        '@pid' => $participanteId,
      ]);

      return ['success' => TRUE, 'plan_id' => (int) $plan->id(), 'message' => 'Plan creado correctamente.'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error al crear plan emprendimiento: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => FALSE, 'plan_id' => NULL, 'message' => 'Error interno.'];
    }
  }

  /**
   * Sincroniza la fase de emprendimiento según la fase PIIL del participante.
   *
   * @param int $planId
   *   ID del PlanEmprendimientoEi.
   */
  public function sincronizarFaseConPiil(int $planId): void {
    try {
      $plan = $this->entityTypeManager->getStorage('plan_emprendimiento_ei')->load($planId);
      if (!$plan) {
        return;
      }

      $participanteId = $plan->getParticipanteId();
      if (!$participanteId) {
        return;
      }

      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);
      if (!$participante) {
        return;
      }

      $fasePiil = $participante->getFaseActual();
      $faseEmprendimiento = $this->mapFasePiilAEmprendimiento($fasePiil);

      if ($faseEmprendimiento && $faseEmprendimiento !== $plan->getFaseEmprendimiento()) {
        $plan->set('fase_emprendimiento', $faseEmprendimiento);
        $plan->save();

        $this->logger->info('Fase emprendimiento plan @id actualizada a @fase (PIIL: @piil).', [
          '@id' => $planId,
          '@fase' => $faseEmprendimiento,
          '@piil' => $fasePiil,
        ]);
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error sincronizando fase plan @id: @msg', [
        '@id' => $planId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Mapea fases PIIL a fases de emprendimiento.
   */
  protected function mapFasePiilAEmprendimiento(string $fasePiil): ?string {
    return match ($fasePiil) {
      'acogida', 'diagnostico' => PlanEmprendimientoEi::FASE_IDEACION,
      'atencion' => PlanEmprendimientoEi::FASE_VALIDACION,
      'insercion' => PlanEmprendimientoEi::FASE_LANZAMIENTO,
      'seguimiento' => PlanEmprendimientoEi::FASE_CONSOLIDACION,
      default => NULL,
    };
  }

  /**
   * Obtiene resumen consolidado de un plan de emprendimiento.
   *
   * @param int $planId
   *   ID del PlanEmprendimientoEi.
   *
   * @return array|null
   *   Datos consolidados o NULL.
   */
  public function getResumenPlan(int $planId): ?array {
    try {
      $plan = $this->entityTypeManager->getStorage('plan_emprendimiento_ei')->load($planId);
      if (!$plan) {
        return NULL;
      }

      $resumen = [
        'plan_id' => (int) $plan->id(),
        'label' => $plan->label() ?? '',
        'idea_negocio' => $plan->get('idea_negocio')->value ?? '',
        'sector' => $plan->get('sector')->value ?? '',
        'forma_juridica' => $plan->get('forma_juridica_objetivo')->value ?? '',
        'fase' => $plan->getFaseEmprendimiento(),
        'diagnostico' => $plan->getDiagnosticoViabilidad(),
        'lanzado' => $plan->isLanzado(),
        'tiene_primer_cliente' => $plan->tienePrimerCliente(),
        'inversion_inicial' => (float) ($plan->get('inversion_inicial')->value ?? 0),
        'facturacion_acumulada' => (float) ($plan->get('facturacion_acumulada')->value ?? 0),
        'empleo_generado' => (int) ($plan->get('empleo_generado')->value ?? 0),
        'necesita_microcredito' => (bool) ($plan->get('necesita_microcredito')->value ?? FALSE),
      ];

      // Enriquecer con datos de BMC si disponible.
      $canvasId = (int) ($plan->get('canvas_id')->value ?? 0);
      if ($canvasId > 0 && $this->canvasService) {
        try {
          $resumen['canvas'] = $this->canvasService->getCanvasSummary($canvasId);
        }
        catch (\Throwable) {
          // Non-critical.
        }
      }

      return $resumen;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo resumen plan @id: @msg', [
        '@id' => $planId,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Calcula diagnóstico de viabilidad basado en BMC + proyecciones + MVP.
   *
   * @param int $planId
   *   ID del plan.
   *
   * @return array{viabilidad: string, score: float, factores: array}
   */
  public function calcularViabilidad(int $planId): array {
    $plan = $this->entityTypeManager->getStorage('plan_emprendimiento_ei')->load($planId);
    if (!$plan) {
      return ['viabilidad' => 'pendiente', 'score' => 0.0, 'factores' => []];
    }

    $factores = [];
    $score = 0.0;

    // Factor 1: Idea articulada.
    if (!empty($plan->get('idea_negocio')->value)) {
      $score += 15.0;
      $factores[] = ['factor' => 'idea_articulada', 'peso' => 15.0, 'cumple' => TRUE];
    }
    else {
      $factores[] = ['factor' => 'idea_articulada', 'peso' => 15.0, 'cumple' => FALSE];
    }

    // Factor 2: Sector definido.
    if (!empty($plan->get('sector')->value)) {
      $score += 10.0;
      $factores[] = ['factor' => 'sector_definido', 'peso' => 10.0, 'cumple' => TRUE];
    }
    else {
      $factores[] = ['factor' => 'sector_definido', 'peso' => 10.0, 'cumple' => FALSE];
    }

    // Factor 3: Forma jurídica elegida.
    if (!empty($plan->get('forma_juridica_objetivo')->value)) {
      $score += 10.0;
      $factores[] = ['factor' => 'forma_juridica', 'peso' => 10.0, 'cumple' => TRUE];
    }
    else {
      $factores[] = ['factor' => 'forma_juridica', 'peso' => 10.0, 'cumple' => FALSE];
    }

    // Factor 4: BMC vinculado.
    $canvasId = (int) ($plan->get('canvas_id')->value ?? 0);
    if ($canvasId > 0) {
      $score += 20.0;
      $factores[] = ['factor' => 'bmc_vinculado', 'peso' => 20.0, 'cumple' => TRUE];
    }
    else {
      $factores[] = ['factor' => 'bmc_vinculado', 'peso' => 20.0, 'cumple' => FALSE];
    }

    // Factor 5: Plan financiero.
    $projectionId = (int) ($plan->get('projection_id')->value ?? 0);
    if ($projectionId > 0) {
      $score += 20.0;
      $factores[] = ['factor' => 'plan_financiero', 'peso' => 20.0, 'cumple' => TRUE];
    }
    else {
      $factores[] = ['factor' => 'plan_financiero', 'peso' => 20.0, 'cumple' => FALSE];
    }

    // Factor 6: Primer cliente.
    if ($plan->tienePrimerCliente()) {
      $score += 25.0;
      $factores[] = ['factor' => 'primer_cliente', 'peso' => 25.0, 'cumple' => TRUE];
    }
    else {
      $factores[] = ['factor' => 'primer_cliente', 'peso' => 25.0, 'cumple' => FALSE];
    }

    $viabilidad = match (TRUE) {
      $score >= 70.0 => 'viable',
      $score >= 40.0 => 'viable_con_condiciones',
      $score >= 15.0 => 'pendiente',
      default => 'no_viable',
    };

    return [
      'viabilidad' => $viabilidad,
      'score' => $score,
      'factores' => $factores,
    ];
  }

  /**
   * Calcula SROI del emprendimiento.
   *
   * @param int $planId
   *   ID del plan.
   *
   * @return array|null
   *   SROI data or NULL.
   */
  public function getSroiEmprendimiento(int $planId): ?array {
    if (!$this->sroiCalculatorService) {
      return NULL;
    }

    $plan = $this->entityTypeManager->getStorage('plan_emprendimiento_ei')->load($planId);
    if (!$plan) {
      return NULL;
    }

    try {
      return $this->sroiCalculatorService->calculate([
        'inversion' => (float) ($plan->get('inversion_inicial')->value ?? 0),
        'facturacion' => (float) ($plan->get('facturacion_acumulada')->value ?? 0),
        'empleo_generado' => (int) ($plan->get('empleo_generado')->value ?? 0),
        'tipo' => 'emprendimiento_inclusivo',
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error SROI plan @id: @msg', ['@id' => $planId, '@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Gets hitos de emprendimiento con estado para un participante.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array
   *   Lista de hitos con estado completado/pendiente.
   */
  public function getHitosEmprendimiento(int $participanteId): array {
    $plan = $this->getPlanActivo($participanteId);
    if (!$plan) {
      return [];
    }

    $fase = $plan->getFaseEmprendimiento();
    $faseOrder = [
      PlanEmprendimientoEi::FASE_IDEACION,
      PlanEmprendimientoEi::FASE_VALIDACION,
      PlanEmprendimientoEi::FASE_LANZAMIENTO,
      PlanEmprendimientoEi::FASE_CONSOLIDACION,
    ];
    $currentIdx = array_search($fase, $faseOrder, TRUE);

    return [
      'ideacion' => [
        ['label' => 'Idea de negocio articulada', 'completado' => !empty($plan->get('idea_negocio')->value)],
        ['label' => 'Sector definido', 'completado' => !empty($plan->get('sector')->value)],
        ['label' => 'Primer BMC borrador', 'completado' => (int) ($plan->get('canvas_id')->value ?? 0) > 0],
      ],
      'validacion' => [
        ['label' => 'BMC completo', 'completado' => $currentIdx > 0 && (int) ($plan->get('canvas_id')->value ?? 0) > 0],
        ['label' => 'Hipótesis MVP validada', 'completado' => (int) ($plan->get('mvp_hypothesis_id')->value ?? 0) > 0],
        ['label' => 'Plan financiero creado', 'completado' => (int) ($plan->get('projection_id')->value ?? 0) > 0],
        ['label' => 'Diagnóstico viabilidad completado', 'completado' => $plan->getDiagnosticoViabilidad() !== 'pendiente'],
      ],
      'lanzamiento' => [
        ['label' => 'Alta RETA/IAE', 'completado' => $plan->isLanzado()],
        ['label' => 'Primer cliente/ingreso', 'completado' => $plan->tienePrimerCliente()],
        ['label' => 'Facturación > 0', 'completado' => (float) ($plan->get('facturacion_acumulada')->value ?? 0) > 0],
      ],
      'consolidacion' => [
        ['label' => 'Empleo generado', 'completado' => (int) ($plan->get('empleo_generado')->value ?? 0) > 0],
        ['label' => '6 meses de actividad', 'completado' => FALSE],
      ],
    ];
  }

  /**
   * Obtiene el plan activo de un participante.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\PlanEmprendimientoEi|null
   *   Plan activo o NULL.
   */
  public function getPlanActivo(int $participanteId): ?PlanEmprendimientoEi {
    try {
      $storage = $this->entityTypeManager->getStorage('plan_emprendimiento_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      $entity = $storage->load(reset($ids));
      return $entity instanceof PlanEmprendimientoEi ? $entity : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
