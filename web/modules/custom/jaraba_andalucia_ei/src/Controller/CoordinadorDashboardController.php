<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry;
use Drupal\jaraba_andalucia_ei\Service\AccionFormativaService;
use Drupal\jaraba_andalucia_ei\Service\AlertasNormativasService;
use Drupal\jaraba_andalucia_ei\Service\CoordinadorHubService;
use Drupal\jaraba_andalucia_ei\Service\EiAlumniBridgeService;
use Drupal\jaraba_andalucia_ei\Service\EiEmprendimientoBridgeService;
use Drupal\jaraba_andalucia_ei\Service\StoBidireccionalService;
use Drupal\jaraba_andalucia_ei\Service\IndicadoresEsfService;
use Drupal\jaraba_andalucia_ei\Service\JustificacionEconomicaService;
use Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService;
use Drupal\jaraba_andalucia_ei\Service\ProspeccionService;
use Drupal\jaraba_andalucia_ei\Service\PuntosImpactoEiService;
use Drupal\jaraba_andalucia_ei\Service\RiesgoAbandonoService;
use Drupal\jaraba_andalucia_ei\Service\SesionProgramadaService;
use Drupal\jaraba_andalucia_ei\Service\VoboSaeWorkflowService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard for coordinadores in Andalucía +ei.
 *
 * Provides a bird's-eye view of the program: participant stats,
 * phase distribution, mentor utilization, STO readiness,
 * and insertion rates.
 */
class CoordinadorDashboardController extends ControllerBase {

  /**
   * Constructs a CoordinadorDashboardController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ?TenantContextService $tenantContext,
    protected LoggerInterface $logger,
    protected ?CoordinadorHubService $hubService = NULL,
    protected ?AlertasNormativasService $alertasService = NULL,
    protected ?JustificacionEconomicaService $justificacionService = NULL,
    protected ?RiesgoAbandonoService $riesgoService = NULL,
    protected ?PuntosImpactoEiService $puntosImpactoService = NULL,
    protected ?ProspeccionService $prospeccionService = NULL,
    protected ?FirmaWorkflowService $firmaWorkflowService = NULL,
    protected ?AccionFormativaService $accionFormativaService = NULL,
    protected ?SesionProgramadaService $sesionProgramadaService = NULL,
    protected ?VoboSaeWorkflowService $voboSaeService = NULL,
    protected ?IndicadoresEsfService $indicadoresEsfService = NULL,
    protected ?EiEmprendimientoBridgeService $emprendimientoBridge = NULL,
    protected ?EiAlumniBridgeService $alumniBridge = NULL,
    protected ?StoBidireccionalService $stoBidireccional = NULL,
    protected ?SetupWizardRegistry $wizardRegistry = NULL,
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
      $container->get('jaraba_andalucia_ei.coordinador_hub'),
      $container->has('jaraba_andalucia_ei.alertas_normativas')
        ? $container->get('jaraba_andalucia_ei.alertas_normativas') : NULL,
      $container->has('jaraba_andalucia_ei.justificacion_economica')
        ? $container->get('jaraba_andalucia_ei.justificacion_economica') : NULL,
      $container->has('jaraba_andalucia_ei.riesgo_abandono')
        ? $container->get('jaraba_andalucia_ei.riesgo_abandono') : NULL,
      $container->has('jaraba_andalucia_ei.puntos_impacto')
        ? $container->get('jaraba_andalucia_ei.puntos_impacto') : NULL,
      $container->has('jaraba_andalucia_ei.prospeccion')
        ? $container->get('jaraba_andalucia_ei.prospeccion') : NULL,
      $container->has('jaraba_andalucia_ei.firma_workflow')
        ? $container->get('jaraba_andalucia_ei.firma_workflow') : NULL,
      $container->has('jaraba_andalucia_ei.accion_formativa')
        ? $container->get('jaraba_andalucia_ei.accion_formativa') : NULL,
      $container->has('jaraba_andalucia_ei.sesion_programada')
        ? $container->get('jaraba_andalucia_ei.sesion_programada') : NULL,
      $container->has('jaraba_andalucia_ei.vobo_sae_workflow')
        ? $container->get('jaraba_andalucia_ei.vobo_sae_workflow') : NULL,
      $container->has('jaraba_andalucia_ei.indicadores_esf')
        ? $container->get('jaraba_andalucia_ei.indicadores_esf') : NULL,
      $container->has('jaraba_andalucia_ei.ei_emprendimiento_bridge')
        ? $container->get('jaraba_andalucia_ei.ei_emprendimiento_bridge') : NULL,
      $container->has('jaraba_andalucia_ei.ei_alumni_bridge')
        ? $container->get('jaraba_andalucia_ei.ei_alumni_bridge') : NULL,
      $container->has('jaraba_andalucia_ei.sto_bidireccional')
        ? $container->get('jaraba_andalucia_ei.sto_bidireccional') : NULL,
      $container->has('ecosistema_jaraba_core.setup_wizard_registry')
        ? $container->get('ecosistema_jaraba_core.setup_wizard_registry') : NULL,
    );
  }

  /**
   * Renders the coordinador dashboard.
   *
   * @return array
   *   Render array.
   */
  public function dashboard(): array {
    $tenantId = $this->resolveTenantGroupId();
    $stats = $this->buildProgramStats($tenantId);
    $phaseDistribution = $this->getPhaseDistribution($tenantId);
    $mentorUtilization = $this->getMentorUtilization($tenantId);
    $recentActivity = $this->getRecentActivity($tenantId);
    $compliance = $this->buildComplianceMetrics($tenantId);

    // Hub KPIs via CoordinadorHubService.
    $hubKpis = $this->hubService ? $this->hubService->getHubKpis($tenantId) : $stats;

    // Alertas normativas PIIL.
    $alertas = [];
    $alertasResumen = [];
    if ($this->alertasService) {
      try {
        $alertas = $this->alertasService->getAlertas($tenantId);
        $alertasResumen = $this->alertasService->getResumenAlertas($tenantId);
      }
      catch (\Throwable) {
      }
    }

    // Justificación económica PIIL (202.500€).
    $justificacion = $this->justificacionService
      ? $this->safeCall(fn() => $this->justificacionService->getResumenJustificacion($tenantId))
      : NULL;

    // Participantes en riesgo de abandono.
    $riesgo = $this->riesgoService
      ? $this->safeCall(fn() => $this->riesgoService->getParticipantesEnRiesgo($tenantId, 'medio'))
      : [];

    // KPIs de impacto global del programa.
    $puntosImpacto = $this->puntosImpactoService
      ? $this->safeCall(fn() => $this->puntosImpactoService->getImpactoGlobalPrograma($tenantId))
      : NULL;

    // Sprint 17: Leaderboard de gamificación (top 10).
    $rankingImpacto = $this->puntosImpactoService
      ? $this->safeCall(fn() => $this->puntosImpactoService->getRankingParticipantes($tenantId, 10))
      : [];

    // Estadísticas de prospecciones empresariales.
    $prospecciones = $this->prospeccionService
      ? $this->safeCall(fn() => $this->prospeccionService->getEstadisticas($tenantId))
      : NULL;

    // Firma electrónica: documentos pendientes de firma masiva (Sprint 4).
    $firmasPendientes = $this->firmaWorkflowService
      ? $this->safeCall(fn() => $this->firmaWorkflowService->getDocumentosPendientes(
          (int) $this->currentUser()->id(),
          $tenantId,
        ))
      : [];

    // Sprint 13: Formación y VoBo SAE.
    $formacionStats = $this->buildFormacionStats($tenantId);
    $voboPendientes = $this->voboSaeService
      ? $this->safeCall(fn() => count($this->voboSaeService->getAccionesPendientesVobo($tenantId)))
      : 0;
    $sesionesProximas = $this->sesionProgramadaService
      ? $this->safeCall(fn() => $this->sesionProgramadaService->getSesionesFuturas($tenantId, 7))
      : [];
    $indicadoresEsf = $this->indicadoresEsfService
      ? $this->safeCall(fn() => $this->indicadoresEsfService->getKpisGlobales($tenantId))
      : NULL;

    // Sprint 17: STO bidireccional — resumen de sincronización.
    $stoResumen = $this->stoBidireccional
      ? $this->safeCall(fn() => $this->stoBidireccional->getResumenSync($tenantId))
      : NULL;

    // Sprint 17: Advanced analytics (funnel, colectivo, provincia, burndown).
    $advancedAnalytics = $this->buildAdvancedAnalytics($tenantId);

    // Sprint 15: Emprendimiento BMC dashboard.
    $emprendimientoStats = $this->emprendimientoBridge
      ? $this->safeCall(fn() => $this->emprendimientoBridge->getDashboardStats($tenantId))
      : NULL;

    // Sprint 17: Club Alumni — estadísticas e historias de éxito.
    $alumniStats = $this->alumniBridge
      ? $this->safeCall(fn() => $this->alumniBridge->getAlumniStats($tenantId))
      : NULL;
    $historiasExito = $this->alumniBridge
      ? $this->safeCall(fn() => $this->alumniBridge->getHistoriasExito($tenantId))
      : [];

    // Sprint 14: PIIL phase statistics and upcoming sessions.
    $estadisticasPorFase = $this->hubService
      ? $this->safeCall(fn() => $this->hubService->getEstadisticasPorFase($tenantId))
      : [];
    $sesionesProximasPiil = $this->hubService
      ? $this->safeCall(fn() => $this->hubService->getUpcomingSessionsPiil($tenantId))
      : [];

    // SETUP-WIZARD-DAILY-001: Wizard + daily actions data.
    $setupWizard = NULL;
    $dailyActions = [];
    if ($this->wizardRegistry && $tenantId) {
      $setupWizard = $this->wizardRegistry->hasWizard('coordinador_ei')
        ? $this->wizardRegistry->getStepsForWizard('coordinador_ei', $tenantId)
        : NULL;
      $dailyActions = $this->buildDailyActions($tenantId, $formacionStats, $voboPendientes ?? 0);
    }

    // ROUTE-LANGPREFIX-001: URLs API via drupalSettings.
    $apiUrls = $this->resolveHubApiUrls();

    // Phase labels for JS (translatable).
    $phaseLabels = [
      'acogida' => $this->t('Acogida'),
      'diagnostico' => $this->t('Diagnóstico'),
      'atencion' => $this->t('Atención'),
      'insercion' => $this->t('Inserción'),
      'seguimiento' => $this->t('Seguimiento'),
      'baja' => $this->t('Baja'),
    ];

    return [
      '#theme' => 'coordinador_dashboard',
      '#stats' => $stats,
      '#phase_distribution' => $phaseDistribution,
      '#mentor_utilization' => $mentorUtilization,
      '#recent_activity' => $recentActivity,
      '#hub_kpis' => $hubKpis,
      '#alertas' => $alertas,
      '#alertas_resumen' => $alertasResumen,
      '#compliance' => $compliance,
      '#justificacion' => $justificacion,
      '#riesgo' => $riesgo,
      '#puntos_impacto' => $puntosImpacto,
      '#ranking_impacto' => $rankingImpacto,
      '#prospecciones' => $prospecciones,
      '#firmas_pendientes' => $firmasPendientes ?? [],
      '#formacion_stats' => $formacionStats,
      '#vobo_pendientes' => $voboPendientes ?? 0,
      '#sesiones_proximas' => $sesionesProximas ?? [],
      '#indicadores_esf' => $indicadoresEsf,
      '#estadisticas_por_fase' => $estadisticasPorFase ?? [],
      '#sesiones_proximas_piil' => $sesionesProximasPiil ?? [],
      '#emprendimiento_stats' => $emprendimientoStats,
      '#alumni_stats' => $alumniStats,
      '#historias_exito' => $historiasExito ?? [],
      '#sto_resumen' => $stoResumen,
      '#advanced_analytics' => $advancedAnalytics,
      '#setup_wizard' => $setupWizard,
      '#daily_actions' => $dailyActions,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
          'jaraba_andalucia_ei/coordinador-hub',
          'jaraba_andalucia_ei/coordinador-calendar',
          'ecosistema_jaraba_theme/route-coordinador-hub',
          'ecosistema_jaraba_theme/setup-wizard',
          // Pre-load for slide-panel entity forms (renderPlain strips #attached).
          'jaraba_andalucia_ei/recurrence-form',
        ],
        'drupalSettings' => [
          'jarabaAndaluciaEi' => [
            'hub' => [
              'kpis' => $hubKpis,
              'justificacion' => $justificacion,
              'riesgo' => $riesgo,
              'puntosImpacto' => $puntosImpacto,
              'prospecciones' => $prospecciones,
              'firmasPendientes' => $firmasPendientes ?? [],
              'formacionStats' => $formacionStats,
              'voboPendientes' => $voboPendientes ?? 0,
              'sesionesProximas' => $sesionesProximas ?? [],
              'indicadoresEsf' => $indicadoresEsf,
              'estadisticasPorFase' => $estadisticasPorFase ?? [],
              'sesionesProximasPiil' => $sesionesProximasPiil ?? [],
              'apiUrls' => $apiUrls,
              'phases' => ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento', 'baja'],
              'phaseLabels' => array_map('strval', $phaseLabels),
              'estados' => ['pendiente', 'contactado', 'admitido', 'rechazado', 'lista_espera'],
              // Sprint 19: Calendar config.
              'calendarConfig' => [
                'tiposSesion' => \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface::TIPOS_SESION,
                'modalidades' => \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface::MODALIDADES,
                'estadosSesion' => \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface::ESTADOS,
                'fases' => \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface::FASES_PROGRAMA,
              ],
            ],
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.site', 'route.group'],
        'tags' => [
          'programa_participante_ei_list',
          'mentoring_session_list',
          'solicitud_ei_list',
          'accion_formativa_ei_list',
          'sesion_programada_ei_list',
          'plan_formativo_ei_list',
        ],
        'max-age' => 600,
      ],
    ];
  }

  /**
   * Safely calls a service method, catching any exceptions.
   *
   * PRESAVE-RESILIENCE-001: Optional services wrapped in try-catch.
   */
  protected function safeCall(callable $fn): mixed {
    try {
      return $fn();
    }
    catch (\Throwable $e) {
      $this->logger->warning('Dashboard service error: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Resuelve URLs de la API del hub (ROUTE-LANGPREFIX-001).
   *
   * @return array<string, string|null>
   */
  protected function resolveHubApiUrls(): array {
    $routes = [
      'solicitudes' => 'jaraba_andalucia_ei.api.hub.solicitudes',
      'solicitudApprove' => 'jaraba_andalucia_ei.api.hub.solicitud.approve',
      'solicitudReject' => 'jaraba_andalucia_ei.api.hub.solicitud.reject',
      'participants' => 'jaraba_andalucia_ei.api.hub.participants',
      'changePhase' => 'jaraba_andalucia_ei.api.hub.participant.change_phase',
      'sessions' => 'jaraba_andalucia_ei.api.hub.sessions',
      'kpis' => 'jaraba_andalucia_ei.api.hub.kpis',
      'stoExport' => 'jaraba_andalucia_ei.sto_export',
      'documentacion' => 'jaraba_andalucia_ei.api.hub.documentacion',
      'firmaSello' => 'jaraba_andalucia_ei.firma.firmar_sello',
      'firmaPendientes' => 'jaraba_andalucia_ei.firma.pendientes',
      // Sprint 13 API URLs:
      'accionesFormativas' => 'jaraba_andalucia_ei.api.hub.acciones_formativas',
      'sesionesFormativas' => 'jaraba_andalucia_ei.api.hub.sesiones_formativas',
      'planesFormativos' => 'jaraba_andalucia_ei.api.hub.planes_formativos',
      'indicadoresEsf' => 'jaraba_andalucia_ei.api.hub.indicadores_esf',
      // Sprint 17: iCal feed subscription.
      'calendarSubscribe' => 'jaraba_andalucia_ei.ical.subscribe_url',
      // Sprint 19: Calendar interactive API.
      'calendarEvents' => 'jaraba_andalucia_ei.api.hub.calendar_events',
      'sessionReschedule' => 'jaraba_andalucia_ei.api.hub.session_reschedule',
      'sessionCreate' => 'entity.sesion_programada_ei.add_form',
      'sessionEdit' => 'entity.sesion_programada_ei.edit_form',
      // Sprint 20: Slide-panel form URLs for frontend hub.
      'accionFormativaAdd' => 'jaraba_andalucia_ei.hub.accion_formativa.add',
      'accionFormativaEdit' => 'jaraba_andalucia_ei.hub.accion_formativa.edit',
      'sesionProgramadaAdd' => 'jaraba_andalucia_ei.hub.sesion_programada.add',
      'sesionProgramadaEdit' => 'jaraba_andalucia_ei.hub.sesion_programada.edit',
    ];

    // Rutas con {id} custom placeholder (API endpoints).
    // Uses __ID__ as placeholder — JS replaces with actual ID.
    $idPlaceholderRoutes = ['solicitudApprove', 'solicitudReject', 'changePhase', 'sessionReschedule'];
    // Rutas entity con {entity_type_id} placeholder (entity forms).
    // Entity routes require numeric IDs — use 99999999 as placeholder,
    // JS replaces it with actual ID via string replace.
    $entityPlaceholderRoutes = ['sessionEdit' => 'sesion_programada_ei'];
    // Rutas con {id} constrained to \d+ — use numeric placeholder.
    // JS replaces 99999999 with actual ID.
    $numericIdPlaceholderRoutes = ['accionFormativaEdit', 'sesionProgramadaEdit'];

    $urls = [];
    foreach ($routes as $key => $route) {
      try {
        if (in_array($key, $idPlaceholderRoutes, TRUE)) {
          $urls[$key] = Url::fromRoute($route, ['id' => '__ID__'])->toString();
        }
        elseif (isset($entityPlaceholderRoutes[$key])) {
          $urls[$key] = Url::fromRoute($route, [$entityPlaceholderRoutes[$key] => 99999999])->toString();
        }
        elseif (in_array($key, $numericIdPlaceholderRoutes, TRUE)) {
          // Routes with {id} constrained to \d+ — numeric placeholder.
          $urls[$key] = Url::fromRoute($route, ['id' => 99999999])->toString();
        }
        else {
          $urls[$key] = Url::fromRoute($route)->toString();
        }
      }
      catch (\Throwable) {
        $urls[$key] = NULL;
      }
    }
    return $urls;
  }

  /**
   * Resolves the current tenant Group ID.
   */
  protected function resolveTenantGroupId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }

    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    catch (\Throwable) {
    }

    return NULL;
  }

  /**
   * Adds tenant condition to an entity query (TENANT-001).
   */
  protected function addTenantCondition(mixed $query, ?int $tenantId): void {
    if ($tenantId) {
      $query->condition('tenant_id', $tenantId);
    }
  }

  /**
   * Builds aggregate program statistics.
   */
  protected function buildProgramStats(?int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // Total participants (TENANT-001).
      $totalQuery = $storage->getQuery()->accessCheck(TRUE);
      $this->addTenantCondition($totalQuery, $tenantId);
      $totalIds = $totalQuery->execute();

      // Active (not in baja).
      $activeQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=');
      $this->addTenantCondition($activeQuery, $tenantId);
      $activeIds = $activeQuery->execute();

      // In insertion phase.
      $insertionQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'insercion');
      $this->addTenantCondition($insertionQuery, $tenantId);
      $insertionIds = $insertionQuery->execute();

      // Completed sessions (TENANT-001).
      $completedSessions = 0;
      if ($this->entityTypeManager->hasDefinition('mentoring_session')) {
        $sessQuery = $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'completed')
          ->count();
        $this->addTenantCondition($sessQuery, $tenantId);
        $completedSessions = (int) $sessQuery->execute();
      }

      // Pending solicitudes (TENANT-001).
      $pendingSolicitudes = 0;
      if ($this->entityTypeManager->hasDefinition('solicitud_ei')) {
        $solQuery = $this->entityTypeManager->getStorage('solicitud_ei')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'pendiente')
          ->count();
        $this->addTenantCondition($solQuery, $tenantId);
        $pendingSolicitudes = (int) $solQuery->execute();
      }

      return [
        'total_participants' => count($totalIds),
        'active_participants' => count($activeIds),
        'insertion_phase' => count($insertionIds),
        'completed_sessions' => $completedSessions,
        'pending_solicitudes' => $pendingSolicitudes,
        'insertion_rate' => count($totalIds) > 0
          ? round((count($insertionIds) / count($totalIds)) * 100, 1)
          : 0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error building program stats: @msg', ['@msg' => $e->getMessage()]);
      return [
        'total_participants' => 0,
        'active_participants' => 0,
        'insertion_phase' => 0,
        'completed_sessions' => 0,
        'pending_solicitudes' => 0,
        'insertion_rate' => 0,
      ];
    }
  }

  /**
   * Gets participant distribution by phase.
   */
  protected function getPhaseDistribution(?int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $phases = ['acogida' => 0, 'diagnostico' => 0, 'atencion' => 0, 'insercion' => 0, 'seguimiento' => 0, 'baja' => 0];

      foreach (array_keys($phases) as $phase) {
        $query = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('fase_actual', $phase)
          ->count();
        $this->addTenantCondition($query, $tenantId);
        $phases[$phase] = (int) $query->execute();
      }

      return $phases;
    }
    catch (\Throwable $e) {
      return ['acogida' => 0, 'diagnostico' => 0, 'atencion' => 0, 'insercion' => 0, 'seguimiento' => 0, 'baja' => 0];
    }
  }

  /**
   * Gets mentor utilization stats.
   */
  protected function getMentorUtilization(?int $tenantId): array {
    if (!$this->entityTypeManager->hasDefinition('mentor_profile')) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('mentor_profile');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'active');
      $this->addTenantCondition($query, $tenantId);
      $activeMentors = $query->execute();

      $mentors = [];
      foreach ($storage->loadMultiple($activeMentors) as $mentor) {
        $mentors[] = [
          'id' => (int) $mentor->id(),
          'name' => $mentor->getDisplayName(),
          'total_sessions' => (int) ($mentor->get('total_sessions')->value ?? 0),
          'average_rating' => $mentor->getAverageRating(),
          'is_available' => $mentor->isAvailable(),
        ];
      }

      // Sort by total_sessions descending.
      usort($mentors, fn($a, $b) => $b['total_sessions'] <=> $a['total_sessions']);

      return array_slice($mentors, 0, 10);
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Builds compliance metrics for the dashboard.
   *
   * Tracks TWO separate documents:
   * - Acuerdo de Participación (Acuerdo_participacion_ICV25.odt): bilateral agreement.
   * - DACI (Anexo_DACI_ICV25.odt): Documento de Aceptación de Compromisos e Información.
   *
   * @return array<string, mixed>
   *   Compliance data: acuerdo_rate, daci_rate, fse_entrada_rate, fse_salida_rate,
   *   incentivo_rate (pagados + renunciados / total).
   */
  protected function buildComplianceMetrics(?int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // Active participants (not baja).
      $activeQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=');
      $this->addTenantCondition($activeQuery, $tenantId);
      $activeIds = $activeQuery->execute();
      $activeCount = count($activeIds);

      $emptyMetrics = [
        'acuerdo_rate' => 0,
        'acuerdo_signed' => 0,
        'acuerdo_total' => 0,
        'daci_rate' => 0,
        'daci_signed' => 0,
        'daci_total' => 0,
        'fse_entrada_rate' => 0,
        'fse_entrada_done' => 0,
        'fse_entrada_total' => 0,
        'fse_salida_rate' => 0,
        'fse_salida_done' => 0,
        'fse_salida_total' => 0,
        'incentivo_rate' => 0,
        'incentivo_gestionados' => 0,
        'incentivo_total' => 0,
        'incentivo_pagados' => 0,
        'incentivo_renunciados' => 0,
      ];

      if ($activeCount === 0) {
        return $emptyMetrics;
      }

      // Acuerdos de Participación firmados.
      $acuerdoQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=')
        ->condition('acuerdo_participacion_firmado', TRUE);
      $this->addTenantCondition($acuerdoQuery, $tenantId);
      $acuerdoSigned = count($acuerdoQuery->execute());

      // DACI firmados.
      $daciQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=')
        ->condition('daci_firmado', TRUE);
      $this->addTenantCondition($daciQuery, $tenantId);
      $daciSigned = count($daciQuery->execute());

      // FSE+ entrada.
      $fseEntradaQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=')
        ->condition('fse_entrada_completado', TRUE);
      $this->addTenantCondition($fseEntradaQuery, $tenantId);
      $fseEntradaDone = count($fseEntradaQuery->execute());

      // FSE+ salida (only relevant for insercion/seguimiento phases).
      $fseSalidaBaseQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', ['insercion', 'seguimiento'], 'IN');
      $this->addTenantCondition($fseSalidaBaseQuery, $tenantId);
      $fseSalidaTotal = count($fseSalidaBaseQuery->execute());

      $fseSalidaDone = 0;
      if ($fseSalidaTotal > 0) {
        $fseSalidaQuery = $storage->getQuery()->accessCheck(TRUE)
          ->condition('fase_actual', ['insercion', 'seguimiento'], 'IN')
          ->condition('fse_salida_completado', TRUE);
        $this->addTenantCondition($fseSalidaQuery, $tenantId);
        $fseSalidaDone = count($fseSalidaQuery->execute());
      }

      // Incentivo €528: pagados + renunciados = gestionados.
      $incentivoPagadoQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=')
        ->condition('incentivo_recibido', TRUE);
      $this->addTenantCondition($incentivoPagadoQuery, $tenantId);
      $incentivoPagados = count($incentivoPagadoQuery->execute());

      $incentivoRenunciaQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=')
        ->condition('incentivo_renuncia', TRUE);
      $this->addTenantCondition($incentivoRenunciaQuery, $tenantId);
      $incentivoRenunciados = count($incentivoRenunciaQuery->execute());

      $incentivoGestionados = $incentivoPagados + $incentivoRenunciados;

      return [
        'acuerdo_rate' => round(($acuerdoSigned / $activeCount) * 100, 1),
        'acuerdo_signed' => $acuerdoSigned,
        'acuerdo_total' => $activeCount,
        'daci_rate' => round(($daciSigned / $activeCount) * 100, 1),
        'daci_signed' => $daciSigned,
        'daci_total' => $activeCount,
        'fse_entrada_rate' => round(($fseEntradaDone / $activeCount) * 100, 1),
        'fse_entrada_done' => $fseEntradaDone,
        'fse_entrada_total' => $activeCount,
        'fse_salida_rate' => $fseSalidaTotal > 0 ? round(($fseSalidaDone / $fseSalidaTotal) * 100, 1) : 0,
        'fse_salida_done' => $fseSalidaDone,
        'fse_salida_total' => $fseSalidaTotal,
        'incentivo_rate' => round(($incentivoGestionados / $activeCount) * 100, 1),
        'incentivo_gestionados' => $incentivoGestionados,
        'incentivo_total' => $activeCount,
        'incentivo_pagados' => $incentivoPagados,
        'incentivo_renunciados' => $incentivoRenunciados,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error building compliance metrics: @msg', ['@msg' => $e->getMessage()]);
      return $emptyMetrics;
    }
  }

  /**
   * Builds formacion stats for Sprint 13 integration.
   *
   * @return array<string, mixed>
   *   Stats: total_acciones, en_ejecucion, vobo_pendiente,
   *   sesiones_programadas, planes_activos, horas_formacion_previstas.
   */
  protected function buildFormacionStats(?int $tenantId): array {
    $defaults = [
      'total_acciones' => 0,
      'en_ejecucion' => 0,
      'vobo_pendiente' => 0,
      'sesiones_programadas' => 0,
      'planes_activos' => 0,
      'horas_formacion_previstas' => 0.0,
    ];

    try {
      if (!$this->entityTypeManager->hasDefinition('accion_formativa_ei')) {
        return $defaults;
      }

      $accionStorage = $this->entityTypeManager->getStorage('accion_formativa_ei');

      // Total acciones formativas (TENANT-001).
      $totalQuery = $accionStorage->getQuery()->accessCheck(TRUE);
      $this->addTenantCondition($totalQuery, $tenantId);
      $defaults['total_acciones'] = (int) (clone $totalQuery)->count()->execute();

      // En ejecucion.
      $ejecQuery = $accionStorage->getQuery()->accessCheck(TRUE)
        ->condition('estado', 'en_ejecucion')
        ->count();
      $this->addTenantCondition($ejecQuery, $tenantId);
      $defaults['en_ejecucion'] = (int) $ejecQuery->execute();

      // VoBo pendiente.
      $voboQuery = $accionStorage->getQuery()->accessCheck(TRUE)
        ->condition('estado', ['pendiente_vobo', 'vobo_enviado'], 'IN')
        ->count();
      $this->addTenantCondition($voboQuery, $tenantId);
      $defaults['vobo_pendiente'] = (int) $voboQuery->execute();

      // Sesiones programadas futuras.
      if ($this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        $sesQuery = $this->entityTypeManager->getStorage('sesion_programada_ei')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('estado', 'cancelada', '<>')
          ->count();
        $this->addTenantCondition($sesQuery, $tenantId);
        $defaults['sesiones_programadas'] = (int) $sesQuery->execute();
      }

      // Planes formativos activos.
      if ($this->entityTypeManager->hasDefinition('plan_formativo_ei')) {
        $planQuery = $this->entityTypeManager->getStorage('plan_formativo_ei')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('estado', 'borrador', '<>')
          ->count();
        $this->addTenantCondition($planQuery, $tenantId);
        $defaults['planes_activos'] = (int) $planQuery->execute();
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error building formacion stats: @msg', ['@msg' => $e->getMessage()]);
    }

    return $defaults;
  }

  /**
   * Builds daily action cards for the coordinador dashboard.
   *
   * SETUP-WIZARD-DAILY-001: Operational actions separated from setup steps.
   * These are recurring daily tasks, NOT one-time configuration.
   *
   * @param int $tenantId
   *   Tenant group ID.
   * @param array $formacionStats
   *   Pre-computed formacion stats from buildFormacionStats().
   * @param int $voboPendientes
   *   Count of VoBo pending actions.
   *
   * @return array
   *   Array of action definitions ready for _daily-actions.html.twig.
   */
  protected function buildDailyActions(int $tenantId, array $formacionStats, int $voboPendientes): array {
    $pendingSolicitudes = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('solicitud_ei')) {
        $query = $this->entityTypeManager->getStorage('solicitud_ei')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('estado', 'pendiente')
          ->count();
        $this->addTenantCondition($query, $tenantId);
        $pendingSolicitudes = (int) $query->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      [
        'id' => 'solicitudes',
        'label' => $this->t('Gestionar solicitudes'),
        'description' => $this->t('Revisar y procesar solicitudes de participantes'),
        'icon' => ['category' => 'users', 'name' => 'user-check', 'variant' => 'duotone'],
        'color' => 'azul-corporativo',
        'href_override' => '#panel-solicitudes',
        'route' => 'jaraba_andalucia_ei.coordinador_dashboard',
        'route_params' => [],
        'use_slide_panel' => FALSE,
        'badge' => $pendingSolicitudes,
        'badge_type' => $pendingSolicitudes > 10 ? 'critical' : ($pendingSolicitudes > 0 ? 'warning' : 'info'),
        'is_primary' => TRUE,
      ],
      [
        'id' => 'nuevo_participante',
        'label' => $this->t('Nuevo participante'),
        'description' => $this->t('Registrar un nuevo participante en el programa'),
        'icon' => ['category' => 'users', 'name' => 'user-plus', 'variant' => 'duotone'],
        'color' => 'azul-corporativo',
        'route' => 'entity.programa_participante_ei.add_form',
        'route_params' => [],
        'use_slide_panel' => TRUE,
        'slide_panel_size' => 'large',
        'badge' => NULL,
        'badge_type' => 'info',
        'is_primary' => FALSE,
      ],
      [
        'id' => 'programar_sesion',
        'label' => $this->t('Programar sesión'),
        'description' => $this->t('Crear una nueva sesión formativa'),
        'icon' => ['category' => 'education', 'name' => 'calendar-clock', 'variant' => 'duotone'],
        'color' => 'verde-innovacion',
        'route' => 'jaraba_andalucia_ei.hub.sesion_programada.add',
        'route_params' => [],
        'use_slide_panel' => TRUE,
        'slide_panel_size' => 'large',
        'badge' => NULL,
        'badge_type' => 'info',
        'is_primary' => FALSE,
      ],
      [
        'id' => 'exportar_sto',
        'label' => $this->t('Exportar STO'),
        'description' => $this->t('Generar fichero STO para SEPE'),
        'icon' => ['category' => 'business', 'name' => 'file-export', 'variant' => 'duotone'],
        'color' => 'naranja-impulso',
        'route' => 'jaraba_andalucia_ei.sto_export',
        'route_params' => [],
        'use_slide_panel' => FALSE,
        'badge' => NULL,
        'badge_type' => 'info',
        'is_primary' => FALSE,
      ],
      [
        'id' => 'leads',
        'label' => $this->t('Captación de leads'),
        'description' => $this->t('Gestionar leads y prospección empresarial'),
        'icon' => ['category' => 'analytics', 'name' => 'funnel', 'variant' => 'duotone'],
        'color' => 'naranja-impulso',
        'route' => 'jaraba_andalucia_ei.leads_guia',
        'route_params' => [],
        'use_slide_panel' => FALSE,
        'badge' => NULL,
        'badge_type' => 'info',
        'is_primary' => FALSE,
      ],
    ];
  }

  /**
   * Builds advanced analytics for the metrics panel.
   *
   * Sprint 17: Funnel de conversión, distribución por colectivo/provincia,
   * burndown de horas promedio por participante.
   *
   * @param int|null $tenantId
   *   Tenant group ID. TENANT-001.
   *
   * @return array{funnel: array, por_colectivo: array, por_provincia: array, horas_promedio: array}
   */
  protected function buildAdvancedAnalytics(?int $tenantId): array {
    $result = [
      'funnel' => [],
      'por_colectivo' => [],
      'por_provincia' => [],
      'horas_promedio' => [
        'orientacion' => 0.0,
        'formacion' => 0.0,
        'orientacion_individual' => 0.0,
        'asistencia_media' => 0.0,
      ],
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // All participants.
      $allQuery = $storage->getQuery()->accessCheck(FALSE);
      $this->addTenantCondition($allQuery, $tenantId);
      $allIds = $allQuery->execute();
      $participantes = $storage->loadMultiple($allIds);
      $total = count($participantes);

      if ($total === 0) {
        return $result;
      }

      // Funnel: count per phase (ordered).
      $faseOrden = ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento'];
      $faseCounts = array_fill_keys($faseOrden, 0);
      $colectivoCounts = [];
      $provinciaCounts = [];
      $sumOrientacion = 0.0;
      $sumFormacion = 0.0;
      $sumOrientacionInd = 0.0;
      $sumAsistencia = 0.0;
      $countAsistencia = 0;

      foreach ($participantes as $p) {
        $fase = $p->get('fase_actual')->value ?? 'acogida';
        if (isset($faseCounts[$fase])) {
          $faseCounts[$fase]++;
        }

        $colectivo = $p->get('colectivo')->value ?? 'sin_colectivo';
        $colectivoCounts[$colectivo] = ($colectivoCounts[$colectivo] ?? 0) + 1;

        $provincia = $p->get('provincia_participacion')->value ?? 'sin_provincia';
        $provinciaCounts[$provincia] = ($provinciaCounts[$provincia] ?? 0) + 1;

        $sumOrientacion += (float) ($p->get('horas_orientacion_ind')->value ?? 0)
          + (float) ($p->get('horas_orientacion_grup')->value ?? 0)
          + (float) ($p->get('horas_mentoria_ia')->value ?? 0)
          + (float) ($p->get('horas_mentoria_humana')->value ?? 0);
        $sumFormacion += (float) ($p->get('horas_formacion')->value ?? 0);
        $sumOrientacionInd += (float) ($p->get('horas_orientacion_ind')->value ?? 0);

        $asist = $p->get('asistencia_porcentaje')->value ?? NULL;
        if ($asist !== NULL) {
          $sumAsistencia += (float) $asist;
          $countAsistencia++;
        }
      }

      // Funnel: acumulativo (cuántos llegaron a cada fase o más allá).
      $acumulado = 0;
      $funnelReverse = array_reverse($faseOrden);
      $funnelAccum = [];
      foreach ($funnelReverse as $fase) {
        $acumulado += $faseCounts[$fase];
        $funnelAccum[$fase] = $acumulado;
      }
      foreach ($faseOrden as $fase) {
        $count = $funnelAccum[$fase];
        $result['funnel'][] = [
          'fase' => $fase,
          'count' => $count,
          'pct' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
        ];
      }

      arsort($colectivoCounts);
      $result['por_colectivo'] = $colectivoCounts;

      arsort($provinciaCounts);
      $result['por_provincia'] = $provinciaCounts;

      $activos = max(1, $total);
      $result['horas_promedio'] = [
        'orientacion' => round($sumOrientacion / $activos, 1),
        'formacion' => round($sumFormacion / $activos, 1),
        'orientacion_individual' => round($sumOrientacionInd / $activos, 1),
        'asistencia_media' => $countAsistencia > 0 ? round($sumAsistencia / $countAsistencia, 1) : 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error building advanced analytics: @msg', ['@msg' => $e->getMessage()]);
    }

    return $result;
  }

  /**
   * Gets recent activity items.
   */
  protected function getRecentActivity(?int $tenantId): array {
    $activity = [];

    try {
      // Recent completed sessions (TENANT-001).
      if ($this->entityTypeManager->hasDefinition('mentoring_session')) {
        $sessQuery = $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'completed')
          ->sort('changed', 'DESC')
          ->range(0, 5);
        $this->addTenantCondition($sessQuery, $tenantId);
        $sessionIds = $sessQuery->execute();

        foreach ($this->entityTypeManager->getStorage('mentoring_session')->loadMultiple($sessionIds) as $session) {
          $mentee = $session->get('mentee_id')->entity;
          $activity[] = [
            'type' => 'session_completed',
            'label' => sprintf('Sesión #%d completada', $session->get('session_number')->value ?? 1),
            'detail' => $mentee ? ($mentee->getDisplayName() ?? $mentee->getAccountName()) : '-',
            'timestamp' => $session->get('changed')->value,
          ];
        }
      }

      // Recent phase transitions (TENANT-001).
      $partQuery = $this->entityTypeManager->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(TRUE)
        ->sort('changed', 'DESC')
        ->range(0, 5);
      $this->addTenantCondition($partQuery, $tenantId);
      $participanteIds = $partQuery->execute();

      foreach ($this->entityTypeManager->getStorage('programa_participante_ei')->loadMultiple($participanteIds) as $p) {
        $activity[] = [
          'type' => 'participant_update',
          'label' => sprintf('%s - Fase: %s', $p->label() ?? '-', $p->get('fase_actual')->value ?? '-'),
          'detail' => $p->get('carril')->value ?? '',
          'timestamp' => $p->get('changed')->value,
        ];
      }

      // Sort by timestamp.
      usort($activity, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

      return array_slice($activity, 0, 10);
    }
    catch (\Throwable $e) {
      return [];
    }
  }

}
