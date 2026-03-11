<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Drupal\jaraba_andalucia_ei\Service\AccesoProgramaService;
use Drupal\jaraba_andalucia_ei\Service\EiEmprendimientoBridgeService;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService;
use Drupal\jaraba_andalucia_ei\Service\InformeProgresoPdfService;
use Drupal\jaraba_andalucia_ei\Service\InscripcionSesionService;
use Drupal\jaraba_andalucia_ei\Service\ProgramaVerticalAccessInterface;
use Drupal\jaraba_andalucia_ei\Service\RiesgoAbandonoService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller del portal de participación premium.
 *
 * Renderiza la experiencia central del participante en Andalucía +ei:
 * hero, timeline, formación, expediente, acciones rápidas y logros.
 */
class ParticipantePortalController extends ControllerBase {

  /**
   * Constructs a ParticipantePortalController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ExpedienteService $expedienteService,
    protected ?object $healthScoreService,
    protected ?object $journeyProgressionService,
    protected ?object $crossVerticalBridgeService,
    protected ?InformeProgresoPdfService $informePdfService,
    protected LoggerInterface $logger,
    protected ?TenantContextService $tenantContext = NULL,
    protected ?AccesoProgramaService $accesoProgramaService = NULL,
    protected ?RiesgoAbandonoService $riesgoService = NULL,
    protected ?FirmaWorkflowService $firmaWorkflow = NULL,
    protected ?EiEmprendimientoBridgeService $emprendimientoBridge = NULL,
    protected ?InscripcionSesionService $inscripcionSesionService = NULL,
    protected ?ProgramaVerticalAccessInterface $verticalAccessService = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_andalucia_ei.expediente'),
      $container->has('ecosistema_jaraba_core.andalucia_ei_health_score')
        ? $container->get('ecosistema_jaraba_core.andalucia_ei_health_score')
        : NULL,
      $container->has('ecosistema_jaraba_core.andalucia_ei_journey_progression')
        ? $container->get('ecosistema_jaraba_core.andalucia_ei_journey_progression')
        : NULL,
      $container->has('ecosistema_jaraba_core.andalucia_ei_cross_vertical_bridge')
        ? $container->get('ecosistema_jaraba_core.andalucia_ei_cross_vertical_bridge')
        : NULL,
      $container->has('jaraba_andalucia_ei.informe_progreso_pdf')
        ? $container->get('jaraba_andalucia_ei.informe_progreso_pdf')
        : NULL,
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->has('ecosistema_jaraba_core.tenant_context')
        ? $container->get('ecosistema_jaraba_core.tenant_context')
        : NULL,
      $container->has('jaraba_andalucia_ei.acceso_programa')
        ? $container->get('jaraba_andalucia_ei.acceso_programa')
        : NULL,
      $container->has('jaraba_andalucia_ei.riesgo_abandono')
        ? $container->get('jaraba_andalucia_ei.riesgo_abandono')
        : NULL,
      $container->has('jaraba_andalucia_ei.firma_workflow')
        ? $container->get('jaraba_andalucia_ei.firma_workflow')
        : NULL,
      $container->has('jaraba_andalucia_ei.ei_emprendimiento_bridge')
        ? $container->get('jaraba_andalucia_ei.ei_emprendimiento_bridge')
        : NULL,
      $container->has('jaraba_andalucia_ei.inscripcion_sesion')
        ? $container->get('jaraba_andalucia_ei.inscripcion_sesion')
        : NULL,
      $container->has('jaraba_andalucia_ei.programa_vertical_access')
        ? $container->get('jaraba_andalucia_ei.programa_vertical_access')
        : NULL,
    );
  }

  /**
   * Renders the participant portal.
   *
   * @return array
   *   Render array.
   */
  public function portal(): array {
    $participante = $this->getParticipanteActual();
    if (!$participante) {
      throw new AccessDeniedHttpException('No active participant found.');
    }

    // Build all portal data.
    $healthScore = $this->getHealthScore($participante);
    $completitud = $this->expedienteService->getCompletuDocumental((int) $participante->id());
    $documentos = $this->expedienteService->listarDocumentos((int) $participante->id());
    $bridges = $this->getBridges($participante);
    $proactiveAction = $this->getProactiveAction();
    $timeline = $this->buildTimeline($participante);
    $formacion = $this->buildFormacion($participante);

    // Riesgo de abandono para el participante.
    $riesgo = NULL;
    if ($this->riesgoService) {
      try {
        $riesgo = $this->riesgoService->evaluarRiesgo((int) $participante->id());
      }
      catch (\Throwable) {
      }
    }

    // Group documents by category prefix.
    $documentosPorCategoria = $this->groupDocumentosByPrefix($documentos);

    // Sprint 4: Documentos pendientes de firma del participante.
    $firmaPendientes = $this->getFirmasPendientes();

    // Sprint 7: Hitos de emprendimiento si tiene plan activo.
    $hitosEmprendimiento = [];
    if ($this->emprendimientoBridge) {
      try {
        $hitosEmprendimiento = $this->emprendimientoBridge->getHitosEmprendimiento(
          (int) $participante->id()
        );
      }
      catch (\Throwable) {
      }
    }

    // Sprint 13: Sesiones inscritas del participante.
    $misSesiones = $this->getMisSesiones($participante);

    // Sprint 13: Acceso cross-vertical y expiración.
    $verticalAccess = $this->getVerticalAccessData($participante);

    $libraries = ['jaraba_andalucia_ei/participante-portal'];
    if (!empty($firmaPendientes)) {
      $libraries[] = 'jaraba_andalucia_ei/firma-electronica';
    }

    return [
      '#theme' => 'participante_portal',
      '#participante' => $participante,
      '#health_score' => $healthScore,
      '#completitud' => $completitud,
      '#documentos_por_categoria' => $documentosPorCategoria,
      '#bridges' => $bridges,
      '#proactive_action' => $proactiveAction,
      '#timeline' => $timeline,
      '#formacion' => $formacion,
      '#firma_pendientes' => $firmaPendientes,
      '#hitos_emprendimiento' => $hitosEmprendimiento,
      '#mis_sesiones' => $misSesiones,
      '#vertical_access' => $verticalAccess,
      '#attached' => [
        'library' => $libraries,
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['programa_participante_ei_list', 'inscripcion_sesion_ei_list', 'sesion_programada_ei_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Returns portal data as JSON for API consumers.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   JSON response.
   */
  public function portalData(): Response {
    $participante = $this->getParticipanteActual();
    if (!$participante) {
      return new Response(json_encode(['error' => 'Not a participant']), 403, ['Content-Type' => 'application/json']);
    }

    $healthScore = $this->getHealthScore($participante);
    $completitud = $this->expedienteService->getCompletuDocumental((int) $participante->id());

    $data = [
      'participante_id' => (int) $participante->id(),
      'fase_actual' => $participante->getFaseActual(),
      'health_score' => $healthScore,
      'horas' => [
        'orientacion_total' => $participante->getTotalHorasOrientacion(),
        'formacion' => (float) ($participante->get('horas_formacion')->value ?? 0),
        'mentoria_ia' => $participante->getHorasMentoriaIa(),
        'mentoria_humana' => $participante->getHorasMentoriaHumana(),
      ],
      'completitud_documental' => $completitud,
      'puede_transitar' => $participante->canTransitToInsercion(),
    ];

    return new Response(
      json_encode($data, JSON_THROW_ON_ERROR),
      200,
      ['Content-Type' => 'application/json'],
    );
  }

  /**
   * Downloads the progress report PDF.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Binary file response.
   */
  public function descargarInforme(): Response {
    $participante = $this->getParticipanteActual();
    if (!$participante) {
      throw new AccessDeniedHttpException('No active participant found.');
    }

    if (!$this->informePdfService) {
      throw new NotFoundHttpException('PDF service not available.');
    }

    $uri = $this->informePdfService->generarInforme($participante);
    if (!$uri) {
      throw new NotFoundHttpException('Could not generate report.');
    }

    $realPath = \Drupal::service('file_system')->realpath($uri);
    if (!$realPath || !file_exists($realPath)) {
      throw new NotFoundHttpException('Report file not found.');
    }

    $response = new BinaryFileResponse($realPath);
    $response->setContentDisposition('attachment', 'informe-progreso-andalucia-ei.pdf');
    $response->headers->set('Content-Type', 'application/pdf');
    return $response;
  }

  /**
   * Gets the current user's participant entity.
   *
   * TENANT-001: Filters by uid AND tenant_id when available.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface|null
   *   The participant entity or NULL.
   */
  protected function getParticipanteActual(): ?ProgramaParticipanteEiInterface {
    $uid = $this->currentUser()->id();
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $uid)
      ->condition('fase_actual', 'baja', '<>')
      ->range(0, 1);

    // TENANT-001: filtrar por tenant del usuario actual.
    $tenantId = $this->resolveCurrentTenantId();
    if ($tenantId) {
      $query->condition('tenant_id', $tenantId);
    }

    $ids = $query->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Resolves the current tenant Group ID.
   *
   * @return int|null
   *   The group ID, or NULL if unavailable.
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

  /**
   * Gets the health score for a participant.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $participante
   *   The participant.
   *
   * @return array|null
   *   Health score data or NULL.
   */
  protected function getHealthScore(ProgramaParticipanteEiInterface $participante): ?array {
    if (!$this->healthScoreService) {
      return NULL;
    }

    try {
      return $this->healthScoreService->calculate($participante);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Health score error: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Gets cross-vertical bridges for a participant.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $participante
   *   The participant.
   *
   * @return array
   *   Bridge data.
   */
  protected function getBridges(ProgramaParticipanteEiInterface $participante): array {
    if (!$this->crossVerticalBridgeService) {
      return [];
    }

    try {
      return $this->crossVerticalBridgeService->getBridges($participante);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Bridge error: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets proactive action for current user.
   *
   * @return array|null
   *   Proactive action data or NULL.
   */
  protected function getProactiveAction(): ?array {
    if (!$this->journeyProgressionService) {
      return NULL;
    }

    try {
      return $this->journeyProgressionService->getPendingAction((int) $this->currentUser()->id());
    }
    catch (\Throwable $e) {
      $this->logger->warning('Journey progression error: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Builds the expanded timeline with sub-steps.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $participante
   *   The participant.
   *
   * @return array
   *   Timeline data with phases and sub-steps.
   */
  protected function buildTimeline(ProgramaParticipanteEiInterface $participante): array {
    $fase = $participante->getFaseActual();
    $faseOrder = ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento', 'baja'];
    $currentIdx = array_search($fase, $faseOrder, TRUE);
    $horasOrientacion = $participante->getTotalHorasOrientacion();
    $horasFormacion = (float) ($participante->get('horas_formacion')->value ?? 0);
    $tipoInsercion = $participante->get('tipo_insercion')->value;
    $fechaInsercion = $participante->get('fecha_insercion')->value;
    $acuerdoFirmado = method_exists($participante, 'isAcuerdoParticipacionFirmado') ? $participante->isAcuerdoParticipacionFirmado() : FALSE;
    $daciFirmado = method_exists($participante, 'isDaciFirmado') ? $participante->isDaciFirmado() : FALSE;
    $carril = $participante->get('carril')->value ?? '';

    return [
      'acogida' => [
        'label' => t('Acogida'),
        'active' => $fase === 'acogida',
        'completed' => $currentIdx > 0,
        'steps' => [
          ['label' => t('Alta en STO'), 'completed' => TRUE],
          ['label' => t('Firma del Acuerdo de Participación'), 'completed' => $acuerdoFirmado],
          ['label' => t('Firma del DACI (Aceptación de Compromisos)'), 'completed' => $daciFirmado],
          ['label' => t('Recogida indicadores FSE+ entrada'), 'completed' => (bool) ($participante->get('fse_entrada_completado')->value ?? FALSE)],
        ],
      ],
      'diagnostico' => [
        'label' => t('Diagnóstico'),
        'active' => $fase === 'diagnostico',
        'completed' => $currentIdx > 1,
        'steps' => [
          ['label' => t('Diagnóstico DIME completado'), 'completed' => !empty($carril)],
          ['label' => t('Itinerario asignado'), 'completed' => !empty($carril)],
          ['label' => t('Primera sesión de mentoría'), 'completed' => $participante->getHorasMentoriaIa() > 0 || $participante->getHorasMentoriaHumana() > 0],
        ],
      ],
      'atencion' => [
        'label' => t('Atención'),
        'active' => $fase === 'atencion',
        'completed' => $currentIdx > 2,
        'steps' => [
          ['label' => t('Orientación individual (≥5h)'), 'completed' => $horasOrientacion >= 5],
          ['label' => t('Formación activa (≥25h)'), 'completed' => $horasFormacion >= 25],
          ['label' => t('Orientación completada (≥10h)'), 'completed' => $horasOrientacion >= 10],
          ['label' => t('Formación completada (≥50h)'), 'completed' => $horasFormacion >= 50],
        ],
      ],
      'insercion' => [
        'label' => t('Inserción'),
        'active' => $fase === 'insercion',
        'completed' => $currentIdx > 3,
        'steps' => [
          ['label' => t('Plan de inserción definido'), 'completed' => !empty($tipoInsercion)],
          ['label' => t('Búsqueda activa / Emprendimiento'), 'completed' => in_array($fase, ['insercion', 'seguimiento'], TRUE) && !empty($tipoInsercion)],
          ['label' => t('Inserción confirmada'), 'completed' => !empty($fechaInsercion)],
        ],
      ],
      'seguimiento' => [
        'label' => t('Seguimiento'),
        'active' => $fase === 'seguimiento',
        'completed' => $fase === 'baja' && !empty($fechaInsercion),
        'steps' => [
          ['label' => t('Indicadores FSE+ salida recogidos'), 'completed' => (bool) ($participante->get('fse_salida_completado')->value ?? FALSE)],
          ['label' => t('Seguimiento a 6 meses'), 'completed' => FALSE],
        ],
      ],
    ];
  }

  /**
   * Builds training progress data.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $participante
   *   The participant.
   *
   * @return array
   *   Training progress data.
   */
  protected function buildFormacion(ProgramaParticipanteEiInterface $participante): array {
    $horasFormacion = (float) ($participante->get('horas_formacion')->value ?? 0);
    $metaFormacion = 50.0;

    return [
      'horas' => $horasFormacion,
      'meta' => $metaFormacion,
      'porcentaje' => min(100, round(($horasFormacion / $metaFormacion) * 100)),
      'milestones' => [
        ['horas' => 10, 'label' => t('10h'), 'alcanzado' => $horasFormacion >= 10],
        ['horas' => 25, 'label' => t('25h'), 'alcanzado' => $horasFormacion >= 25],
        ['horas' => 50, 'label' => t('50h'), 'alcanzado' => $horasFormacion >= 50],
      ],
    ];
  }

  /**
   * Groups documents by category prefix (sto, programa, tarea, cert).
   *
   * @param array $documentos
   *   All participant documents.
   *
   * @return array
   *   Documents grouped by prefix key.
   */
  protected function groupDocumentosByPrefix(array $documentos): array {
    $grouped = ['sto' => [], 'programa' => [], 'tarea' => [], 'cert' => []];

    foreach ($documentos as $doc) {
      $categoria = $doc->getCategoria();
      foreach (array_keys($grouped) as $prefix) {
        if (str_starts_with($categoria, $prefix . '_') || $categoria === $prefix) {
          $grouped[$prefix][] = [
            'id' => $doc->id(),
            'titulo' => $doc->getTitulo(),
            'archivo_nombre' => $doc->get('archivo_nombre')->value ?? '',
            'archivo_mime' => $doc->get('archivo_mime')->value ?? '',
            'archivo_vault_id' => $doc->getArchivoVaultId(),
            'estado_revision' => $doc->getEstadoRevision(),
            'revision_ia_score' => $doc->getRevisionIaScore(),
            'firmado' => $doc->isFirmado(),
          ];
          break;
        }
      }
    }

    return $grouped;
  }

  /**
   * Gets upcoming sessions the participant is inscribed in.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $participante
   *   The participant.
   *
   * @return array
   *   List of session data arrays.
   */
  protected function getMisSesiones(ProgramaParticipanteEiInterface $participante): array {
    if (!$this->inscripcionSesionService) {
      return [];
    }

    try {
      $inscripciones = $this->inscripcionSesionService->getInscripcionesPorParticipante(
        (int) $participante->id()
      );

      $sesiones = [];
      $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');

      foreach ($inscripciones as $inscripcion) {
        $sesionId = $inscripcion->get('sesion_id')->target_id ?? NULL;
        if (!$sesionId) {
          continue;
        }
        $sesion = $storage->load($sesionId);
        if (!$sesion) {
          continue;
        }

        // Solo sesiones futuras o de hoy.
        $fecha = $sesion->getFecha();
        if ($fecha && $fecha < date('Y-m-d')) {
          continue;
        }

        $sesiones[] = [
          'id' => (int) $sesion->id(),
          'titulo' => $sesion->getTitulo(),
          'fecha' => $fecha,
          'hora_inicio' => $sesion->getHoraInicio(),
          'hora_fin' => $sesion->getHoraFin(),
          'tipo_sesion' => $sesion->getTipoSesion(),
          'modalidad' => $sesion->getModalidad(),
          'estado_inscripcion' => $inscripcion->get('estado')->value ?? 'inscrito',
        ];
      }

      // Ordenar por fecha + hora.
      usort($sesiones, fn($a, $b) => ($a['fecha'] . $a['hora_inicio']) <=> ($b['fecha'] . $b['hora_inicio']));

      return array_slice($sesiones, 0, 10);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading mis sesiones: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets vertical access data and expiration info.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $participante
   *   The participant.
   *
   * @return array
   *   Access data: active_verticals, is_expired, dias_restantes.
   */
  protected function getVerticalAccessData(ProgramaParticipanteEiInterface $participante): array {
    $data = [
      'active_verticals' => [],
      'is_expired' => FALSE,
      'dias_restantes' => -1,
    ];

    if (!$this->verticalAccessService) {
      return $data;
    }

    try {
      $pid = (int) $participante->id();
      $data['active_verticals'] = $this->verticalAccessService->getActiveVerticals($pid);
      $data['is_expired'] = $this->verticalAccessService->isExpired($pid);
      $data['dias_restantes'] = $this->verticalAccessService->getDiasRestantes($pid);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading vertical access: @msg', ['@msg' => $e->getMessage()]);
    }

    return $data;
  }

  /**
   * Gets pending signature documents for the current user.
   *
   * @return array
   *   List of pending documents with titulo and documento_id.
   */
  protected function getFirmasPendientes(): array {
    if (!$this->firmaWorkflow) {
      return [];
    }

    try {
      $tenantId = $this->resolveCurrentTenantId();
      return $this->firmaWorkflow->getDocumentosPendientes(
        (int) $this->currentUser()->id(),
        $tenantId,
      );
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error al obtener firmas pendientes: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

}
