<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_andalucia_ei\Service\AccionFormativaService;
use Drupal\jaraba_andalucia_ei\Service\AlertasNormativasService;
use Drupal\jaraba_andalucia_ei\Service\CoordinadorHubService;
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
      '#prospecciones' => $prospecciones,
      '#firmas_pendientes' => $firmasPendientes ?? [],
      '#formacion_stats' => $formacionStats,
      '#vobo_pendientes' => $voboPendientes ?? 0,
      '#sesiones_proximas' => $sesionesProximas ?? [],
      '#indicadores_esf' => $indicadoresEsf,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
          'jaraba_andalucia_ei/coordinador-hub',
          'ecosistema_jaraba_theme/route-coordinador-hub',
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
              'apiUrls' => $apiUrls,
              'phases' => ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento', 'baja'],
              'phaseLabels' => array_map('strval', $phaseLabels),
              'estados' => ['pendiente', 'contactado', 'admitido', 'rechazado', 'lista_espera'],
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
    ];

    $urls = [];
    foreach ($routes as $key => $route) {
      try {
        // Para rutas con {id} placeholder, resolvemos base sin parametro.
        $urls[$key] = Url::fromRoute($route, in_array($key, ['solicitudApprove', 'solicitudReject', 'changePhase'], TRUE)
          ? ['id' => '__ID__']
          : []
        )->toString();
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
