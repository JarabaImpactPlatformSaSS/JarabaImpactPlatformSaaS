<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\jaraba_andalucia_ei\Service\InformeProgresoPdfService;
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

    // Group documents by category prefix.
    $documentosPorCategoria = $this->groupDocumentosByPrefix($documentos);

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
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['programa_participante_ei_list'],
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
   * @return \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface|null
   *   The participant entity or NULL.
   */
  protected function getParticipanteActual(): ?ProgramaParticipanteEiInterface {
    $uid = $this->currentUser()->id();
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $uid)
      ->condition('fase_actual', 'baja', '<>')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
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
    catch (\Exception $e) {
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
    catch (\Exception $e) {
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
    catch (\Exception $e) {
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
    $horasOrientacion = $participante->getTotalHorasOrientacion();
    $horasFormacion = (float) ($participante->get('horas_formacion')->value ?? 0);
    $tipoInsercion = $participante->get('tipo_insercion')->value;
    $fechaInsercion = $participante->get('fecha_insercion')->value;

    return [
      'atencion' => [
        'label' => t('Atención'),
        'active' => $fase === 'atencion',
        'completed' => $fase === 'insercion',
        'steps' => [
          ['label' => t('Inscripción'), 'completed' => TRUE],
          ['label' => t('Diagnóstico inicial'), 'completed' => $participante->getHorasMentoriaIa() > 0],
          ['label' => t('Orientación individual (≥5h)'), 'completed' => $horasOrientacion >= 5],
          ['label' => t('Formación activa (≥25h)'), 'completed' => $horasFormacion >= 25],
          ['label' => t('Orientación completada (≥10h)'), 'completed' => $horasOrientacion >= 10],
          ['label' => t('Formación completada (≥50h)'), 'completed' => $horasFormacion >= 50],
        ],
      ],
      'insercion' => [
        'label' => t('Inserción'),
        'active' => $fase === 'insercion',
        'completed' => !empty($fechaInsercion),
        'steps' => [
          ['label' => t('Plan de inserción definido'), 'completed' => !empty($tipoInsercion)],
          ['label' => t('Búsqueda activa / Emprendimiento'), 'completed' => $fase === 'insercion' && !empty($tipoInsercion)],
          ['label' => t('Inserción confirmada'), 'completed' => !empty($fechaInsercion)],
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

}
