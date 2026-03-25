<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\UserProfile;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionInterface;
use Psr\Log\LoggerInterface;

/**
 * Seccion "Andalucia +ei" — detecta roles del programa.
 *
 * Tres roles posibles:
 * - Participante: tiene programa_participante_ei con fase != 'baja'.
 * - Orientador: tiene mentor_profile activo (cross-module @?jaraba_mentoring).
 * - Coordinador: tiene permiso 'administer andalucia ei'.
 *
 * Peso 15: entre professional_profile(10) y my_vertical(20).
 * TENANT-001: Todas las queries filtran por tenant_id.
 * OPTIONAL-CROSSMODULE-001: mentor_profile via hasDefinition().
 * ROUTE-LANGPREFIX-001: URLs via Url::fromRoute().
 * PRESAVE-RESILIENCE-001: try-catch en servicios opcionales.
 */
class AndaluciaEiUserProfileSection implements UserProfileSectionInterface {

  use StringTranslationTrait;

  /**
   * Colores contextuales por fase PIIL.
   */
  private const FASE_COLORS = [
    'acogida' => 'neutral',
    'diagnostico' => 'corporate',
    'atencion' => 'impulse',
    'insercion' => 'innovation',
    'seguimiento' => 'innovation',
    'baja' => 'danger',
  ];

  /**
   * Labels legibles por fase PIIL.
   */
  private const FASE_LABELS = [
    'acogida' => 'Acogida',
    'diagnostico' => 'Diagnostico',
    'atencion' => 'Atencion',
    'insercion' => 'Insercion',
    'seguimiento' => 'Seguimiento',
  ];

  /**
   * Resultado cacheado de deteccion de roles (por request).
   *
   * @var array{participante: bool, orientador: bool, coordinador: bool, fase: string|null}|null
   */
  private ?array $cachedRoles = NULL;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ?object $tenantContext = NULL,
    protected readonly ?Connection $database = NULL,
    protected readonly ?LoggerInterface $logger = NULL,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'andalucia_ei_programa';
  }

  /**
   *
   */
  public function getTitle(int $uid): string {
    $roles = $this->detectRoles($uid);
    $labels = [];

    if ($roles['participante']) {
      $fase = $roles['fase'];
      $faseLabel = $fase && isset(self::FASE_LABELS[$fase])
        ? (string) $this->t(self::FASE_LABELS[$fase])
        : NULL;
      $labels[] = $faseLabel
        ? (string) $this->t('Fase: @fase', ['@fase' => $faseLabel])
        : (string) $this->t('Participante');
    }
    if ($roles['orientador']) {
      $labels[] = (string) $this->t('Orientador');
    }
    if ($roles['coordinador']) {
      $labels[] = (string) $this->t('Coordinacion');
    }

    if (!empty($labels)) {
      return (string) $this->t('Andalucia +ei — @roles', [
        '@roles' => implode(', ', $labels),
      ]);
    }

    return (string) $this->t('Andalucia +ei');
  }

  /**
   *
   */
  public function getSubtitle(int $uid): string {
    $roles = $this->detectRoles($uid);

    if ($roles['coordinador']) {
      return (string) $this->t('Gestion integral del programa');
    }
    if ($roles['orientador']) {
      return (string) $this->t('Seguimiento y orientacion de participantes');
    }
    if ($roles['participante']) {
      return (string) $this->t('Tu itinerario de insercion');
    }

    return (string) $this->t('Programa Andalucia +ei');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'verticals', 'name' => 'andalucia-ei'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'innovation';
  }

  /**
   *
   */
  public function getWeight(): int {
    return 15;
  }

  /**
   *
   */
  public function isApplicable(int $uid): bool {
    $roles = $this->detectRoles($uid);
    return $roles['participante'] || $roles['orientador'] || $roles['coordinador'];
  }

  /**
   *
   */
  public function getLinks(int $uid): array {
    $roles = $this->detectRoles($uid);
    $links = [];

    if ($roles['participante']) {
      $links = array_merge($links, $this->getParticipanteLinks());
    }

    if ($roles['orientador']) {
      $links = array_merge($links, $this->getOrientadorLinks());
    }

    if ($roles['coordinador']) {
      $links = array_merge($links, $this->getCoordinadorLinks());
    }

    return array_values(array_filter($links));
  }

  /**
   *
   */
  public function getExtraData(int $uid): array {
    $roles = $this->detectRoles($uid);
    $extra = [];

    // Participante: badge de fase + completitud documental + firmas pendientes.
    if ($roles['participante'] && $roles['fase']) {
      $extra['fase_badge'] = [
        'fase' => $roles['fase'],
        'label' => isset(self::FASE_LABELS[$roles['fase']])
          ? (string) $this->t(self::FASE_LABELS[$roles['fase']])
          : $roles['fase'],
        'color' => self::FASE_COLORS[$roles['fase']] ?? 'neutral',
      ];
      $participanteStats = $this->getParticipanteStats($uid);
      if (!empty($participanteStats)) {
        $extra['participante_stats'] = $participanteStats;
      }
    }

    // Orientador: hojas pendientes + proximas sesiones.
    if ($roles['orientador']) {
      $orientadorStats = $this->getOrientadorStats($uid);
      if (!empty($orientadorStats)) {
        $extra['orientador_stats'] = $orientadorStats;
      }
    }

    // Coordinador: KPIs resumen.
    if ($roles['coordinador']) {
      $coordinadorStats = $this->getCoordinadorStats();
      if (!empty($coordinadorStats)) {
        $extra['coordinador_stats'] = $coordinadorStats;
      }
    }

    return $extra;
  }

  /**
   * Detecta los 3 roles posibles, cacheando por request.
   *
   * @return array{participante: bool, orientador: bool, coordinador: bool, fase: string|null}
   */
  private function detectRoles(int $uid): array {
    if ($this->cachedRoles !== NULL) {
      return $this->cachedRoles;
    }

    $this->cachedRoles = [
      'participante' => FALSE,
      'orientador' => FALSE,
      'coordinador' => $this->isCoordinador(),
      'fase' => NULL,
    ];

    $participanteData = $this->detectParticipante($uid);
    if ($participanteData !== NULL) {
      $this->cachedRoles['participante'] = TRUE;
      $this->cachedRoles['fase'] = $participanteData;
    }

    $this->cachedRoles['orientador'] = $this->isOrientador($uid);

    return $this->cachedRoles;
  }

  /**
   * Detecta si el usuario es participante activo.
   *
   * @return string|null
   *   La fase_actual si es participante, NULL si no.
   */
  private function detectParticipante(int $uid): ?string {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('uid', $uid)
        ->condition('fase_actual', 'baja', '!=')
        ->range(0, 1);
      $this->addTenantCondition($query);
      $ids = $query->execute();

      if (empty($ids)) {
        return NULL;
      }

      $entity = $storage->load(reset($ids));
      return $entity ? ($entity->get('fase_actual')->value ?? 'acogida') : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Detecta si el usuario es orientador activo (cross-module).
   */
  private function isOrientador(int $uid): bool {
    try {
      if (!$this->entityTypeManager->hasDefinition('mentor_profile')) {
        return FALSE;
      }
      $storage = $this->entityTypeManager->getStorage('mentor_profile');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $uid)
        ->condition('status', 'active')
        ->range(0, 1);
      $this->addTenantCondition($query);
      return !empty($query->execute());
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * Detecta si el usuario actual es coordinador.
   */
  private function isCoordinador(): bool {
    return $this->currentUser->hasPermission('administer andalucia ei');
  }

  // ─── Links por rol ──────────────────────────────────────────────────────

  /**
   * Links contextuales para participante (6 links).
   *
   * @return array<int, array<string, mixed>|null>
   */
  private function getParticipanteLinks(): array {
    return [
      $this->makeLink(
        $this->t('Mi Portal'),
        'jaraba_andalucia_ei.participante_portal',
        'ui', 'user', 'innovation',
        ['description' => $this->t('Tu espacio personal en el programa')],
      ),
      $this->makeLink(
        $this->t('Mi Expediente'),
        'jaraba_andalucia_ei.expediente_hub',
        'ui', 'folder', 'innovation',
        ['description' => $this->t('Documentacion y seguimiento')],
      ),
      $this->makeLink(
        $this->t('Firmas Pendientes'),
        'jaraba_andalucia_ei.expediente_hub',
        'compliance', 'signature', 'impulse',
        ['description' => $this->t('Documentos que requieren tu firma')],
      ),
      $this->makeLink(
        $this->t('Mis Sesiones'),
        'jaraba_mentoring.mentor_dashboard',
        'ui', 'calendar', 'innovation',
        ['description' => $this->t('Sesiones de mentoria y orientacion'), 'cross_vertical' => TRUE],
      ),
      $this->makeLink(
        $this->t('Guia del Participante'),
        'jaraba_andalucia_ei.guia_participante',
        'ui', 'book', 'innovation',
        ['description' => $this->t('Descarga la guia de orientacion')],
      ),
      $this->makeLink(
        $this->t('Informe de Progreso'),
        'jaraba_andalucia_ei.informe_progreso_pdf',
        'ui', 'file-text', 'innovation',
        ['description' => $this->t('Descarga tu informe actualizado')],
      ),
    ];
  }

  /**
   * Links contextuales para orientador/tecnico (5 links).
   *
   * @return array<int, array<string, mixed>|null>
   */
  private function getOrientadorLinks(): array {
    return [
      $this->makeLink(
        $this->t('Panel Orientador'),
        'jaraba_andalucia_ei.orientador_dashboard',
        'business', 'briefcase', 'innovation',
        ['description' => $this->t('Seguimiento de tus participantes')],
      ),
      $this->makeLink(
        $this->t('Mis Participantes'),
        'entity.programa_participante_ei.collection',
        'ui', 'users', 'innovation',
        ['description' => $this->t('Lista de participantes asignados')],
      ),
      $this->makeLink(
        $this->t('Hojas Pendientes de Firma'),
        'jaraba_andalucia_ei.orientador_dashboard',
        'compliance', 'signature', 'impulse',
        ['description' => $this->t('Hojas de servicio y recibos pendientes')],
      ),
      $this->makeLink(
        $this->t('Mis Sesiones'),
        'jaraba_mentoring.mentor_dashboard',
        'ui', 'calendar', 'innovation',
        ['description' => $this->t('Calendario de sesiones programadas'), 'cross_vertical' => TRUE],
      ),
      $this->makeLink(
        $this->t('Expedientes'),
        'jaraba_andalucia_ei.orientador_dashboard',
        'ui', 'folder', 'innovation',
        ['description' => $this->t('Completitud documental de participantes')],
      ),
    ];
  }

  /**
   * Links contextuales para coordinador (9 links).
   *
   * @return array<int, array<string, mixed>|null>
   */
  private function getCoordinadorLinks(): array {
    return [
      $this->makeLink(
        $this->t('Hub Coordinador'),
        'jaraba_andalucia_ei.coordinador_dashboard',
        'analytics', 'dashboard', 'innovation',
        ['description' => $this->t('Centro de operaciones del programa')],
      ),
      $this->makeLink(
        $this->t('Solicitudes'),
        'entity.solicitud_ei.collection',
        'ui', 'inbox', 'impulse',
        ['description' => $this->t('Triage y gestion de solicitudes')],
      ),
      $this->makeLink(
        $this->t('Participantes'),
        'entity.programa_participante_ei.collection',
        'ui', 'users', 'innovation',
        ['description' => $this->t('Listado completo de participantes')],
      ),
      $this->makeLink(
        $this->t('Documentacion'),
        'jaraba_andalucia_ei.coordinador_dashboard',
        'ui', 'folder', 'innovation',
        ['description' => $this->t('Estado documental y expedientes')],
      ),
      $this->makeLink(
        $this->t('Actuaciones STO'),
        'entity.actuacion_sto.collection',
        'compliance', 'clipboard', 'innovation',
        ['description' => $this->t('Registro de actuaciones del programa')],
      ),
      $this->makeLink(
        $this->t('Prospecciones'),
        'entity.prospeccion_empresarial.collection',
        'business', 'briefcase', 'corporate',
        ['description' => $this->t('Contactos con empresas colaboradoras')],
      ),
      $this->makeLink(
        $this->t('Indicadores FSE+'),
        'entity.indicador_fse_plus.collection',
        'analytics', 'chart-line', 'corporate',
        ['description' => $this->t('Indicadores comunes de seguimiento')],
      ),
      $this->makeLink(
        $this->t('Leads Guia'),
        'jaraba_andalucia_ei.leads_guia',
        'business', 'talent-search', 'impulse',
        ['description' => $this->t('Descargas de la guia del participante')],
      ),
      $this->makeLink(
        $this->t('Exportar STO'),
        'jaraba_andalucia_ei.sto_export',
        'actions', 'export', 'innovation',
        ['description' => $this->t('Exportacion para Servicio Telefonico')],
      ),
    ];
  }

  // ─── Extra data: stats por rol ──────────────────────────────────────────

  /**
   * Stats del participante: completitud documental + firmas pendientes.
   *
   * PRESAVE-RESILIENCE-001: try-catch en queries.
   * TENANT-001: filtro de tenant en queries.
   *
   * @return array{completitud_pct: int, firmas_pendientes: int}
   */
  private function getParticipanteStats(int $uid): array {
    if (!$this->database) {
      return [];
    }

    try {
      // Obtener ID del participante.
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('uid', $uid)
        ->condition('fase_actual', 'baja', '!=')
        ->range(0, 1);
      $this->addTenantCondition($query);
      $ids = $query->execute();
      if (empty($ids)) {
        return [];
      }
      $participanteId = (int) reset($ids);

      // Completitud STO: contar docs requeridos vs existentes.
      $requiredCategories = [
        'sto_dni', 'sto_empadronamiento', 'sto_vida_laboral',
        'sto_demanda_empleo', 'sto_prestaciones',
      ];
      $totalRequired = count($requiredCategories);
      $completedCount = 0;

      if ($this->entityTypeManager->hasDefinition('expediente_documento')) {
        $docQuery = $this->entityTypeManager->getStorage('expediente_documento')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('participante_id', $participanteId)
          ->condition('categoria', $requiredCategories, 'IN')
          ->condition('status', 1);
        $this->addTenantCondition($docQuery);
        $completedCount = (int) $docQuery->count()->execute();
      }

      $completitudPct = $totalRequired > 0
        ? (int) round(($completedCount / $totalRequired) * 100)
        : 0;

      // Firmas pendientes: docs en estado pendiente_firma o pendiente_firma_participante.
      $firmasPendientes = 0;
      if ($this->entityTypeManager->hasDefinition('expediente_documento')) {
        $firmaQuery = $this->entityTypeManager->getStorage('expediente_documento')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('participante_id', $participanteId)
          ->condition('estado_firma', [
            'pendiente_firma',
            'pendiente_firma_participante',
          ], 'IN')
          ->condition('status', 1);
        $this->addTenantCondition($firmaQuery);
        $firmasPendientes = (int) $firmaQuery->count()->execute();
      }

      return [
        'completitud_pct' => $completitudPct,
        'firmas_pendientes' => $firmasPendientes,
      ];
    }
    catch (\Throwable $e) {
      if ($this->logger) {
        $this->logger->warning('Error obteniendo stats participante: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
      return [];
    }
  }

  /**
   * Stats del orientador: hojas pendientes de firma + sesiones proximas.
   *
   * PRESAVE-RESILIENCE-001: try-catch.
   * OPTIONAL-CROSSMODULE-001: mentoring_session via hasDefinition().
   *
   * @return array{hojas_pendientes: int, sesiones_proximas: int}
   */
  private function getOrientadorStats(int $uid): array {
    if (!$this->database) {
      return [];
    }

    try {
      $hojasPendientes = 0;
      $sesionesProximas = 0;

      // Hojas de servicio pendientes de firma del tecnico.
      if ($this->entityTypeManager->hasDefinition('mentoring_session')) {
        $sessionStorage = $this->entityTypeManager->getStorage('mentoring_session');

        // Primero obtener mentor_profile del usuario.
        if ($this->entityTypeManager->hasDefinition('mentor_profile')) {
          $mentorQuery = $this->entityTypeManager->getStorage('mentor_profile')
            ->getQuery()
            ->accessCheck(TRUE)
            ->condition('user_id', $uid)
            ->condition('status', 'active')
            ->range(0, 1);
          $this->addTenantCondition($mentorQuery);
          $mentorIds = $mentorQuery->execute();

          if (!empty($mentorIds)) {
            $mentorId = (int) reset($mentorIds);

            // Hojas pendientes: sesiones completadas sin firma orientador.
            $hojasQuery = $sessionStorage->getQuery()
              ->accessCheck(TRUE)
              ->condition('mentor_id', $mentorId)
              ->condition('status', 'completed')
              ->condition('firma_orientador_status', 'pending');
            $this->addTenantCondition($hojasQuery);
            $hojasPendientes = (int) $hojasQuery->count()->execute();

            // Sesiones proximas (scheduled o confirmed, futuras).
            $now = date('Y-m-d\TH:i:s');
            $sesionesQuery = $sessionStorage->getQuery()
              ->accessCheck(TRUE)
              ->condition('mentor_id', $mentorId)
              ->condition('status', ['scheduled', 'confirmed'], 'IN')
              ->condition('scheduled_start', $now, '>');
            $this->addTenantCondition($sesionesQuery);
            $sesionesProximas = (int) $sesionesQuery->count()->execute();
          }
        }
      }

      return [
        'hojas_pendientes' => $hojasPendientes,
        'sesiones_proximas' => $sesionesProximas,
      ];
    }
    catch (\Throwable $e) {
      if ($this->logger) {
        $this->logger->warning('Error obteniendo stats orientador: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
      return [];
    }
  }

  /**
   * Stats del coordinador: participantes activos, compliance, alertas.
   *
   * PRESAVE-RESILIENCE-001: try-catch.
   * TENANT-001: filtro de tenant.
   *
   * @return array{participantes_activos: int, solicitudes_pendientes: int, compliance_pct: int}
   */
  private function getCoordinadorStats(): array {
    if (!$this->database) {
      return [];
    }

    try {
      // Participantes activos (fase != baja).
      $participantesQuery = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=');
      $this->addTenantCondition($participantesQuery);
      $participantesActivos = (int) $participantesQuery->count()->execute();

      // Solicitudes pendientes.
      $solicitudesPendientes = 0;
      if ($this->entityTypeManager->hasDefinition('solicitud_ei')) {
        $solQuery = $this->entityTypeManager->getStorage('solicitud_ei')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('estado', 'pendiente');
        $this->addTenantCondition($solQuery);
        $solicitudesPendientes = (int) $solQuery->count()->execute();
      }

      // Compliance: acuerdos firmados / total participantes.
      $compliancePct = 0;
      if ($participantesActivos > 0) {
        $acuerdosFirmados = $this->entityTypeManager
          ->getStorage('programa_participante_ei')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('fase_actual', 'baja', '!=')
          ->condition('acuerdo_participacion_firmado', 1);
        $this->addTenantCondition($acuerdosFirmados);
        $firmados = (int) $acuerdosFirmados->count()->execute();
        $compliancePct = (int) round(($firmados / $participantesActivos) * 100);
      }

      return [
        'participantes_activos' => $participantesActivos,
        'solicitudes_pendientes' => $solicitudesPendientes,
        'compliance_pct' => $compliancePct,
      ];
    }
    catch (\Throwable $e) {
      if ($this->logger) {
        $this->logger->warning('Error obteniendo stats coordinador: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
      return [];
    }
  }

  // ─── Helpers ────────────────────────────────────────────────────────────

  /**
   * Construye un link solo si la ruta existe.
   *
   * ROUTE-LANGPREFIX-001: URLs SIEMPRE via Url::fromRoute().
   *
   * @return array<string, mixed>|null
   */
  private function makeLink(
    string|\Stringable $label,
    string $route,
    string $iconCategory,
    string $iconName,
    string $color,
    array $options = [],
  ): ?array {
    try {
      $url = Url::fromRoute($route, $options['params'] ?? [])->toString();
    }
    catch (\Throwable $e) {
      if ($this->logger) {
        $this->logger->notice('Ruta no disponible en perfil Andalucia +ei: @route (@msg)', [
          '@route' => $route,
          '@msg' => $e->getMessage(),
        ]);
      }
      return NULL;
    }
    return [
      'label' => $label,
      'url' => $url,
      'icon_category' => $iconCategory,
      'icon_name' => $iconName,
      'color' => $color,
      'description' => $options['description'] ?? '',
      'slide_panel' => $options['slide_panel'] ?? FALSE,
      'slide_panel_title' => $options['slide_panel_title'] ?? $label,
      'cross_vertical' => $options['cross_vertical'] ?? FALSE,
    ];
  }

  /**
   * Agrega condicion de tenant a la query (TENANT-001).
   */
  private function addTenantCondition(QueryInterface $query): void {
    if (!$this->tenantContext) {
      return;
    }
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      if ($tenant) {
        $query->condition('tenant_id', $tenant->id());
      }
    }
    catch (\Throwable) {
      // Sin tenant context, query sin filtro tenant — degradacion graceful.
    }
  }

}
