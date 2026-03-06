<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\UserProfile;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionInterface;

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
  ) {}

  public function getId(): string {
    return 'andalucia_ei_programa';
  }

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

  public function getIcon(): array {
    return ['category' => 'verticals', 'name' => 'andalucia-ei'];
  }

  public function getColor(): string {
    return 'andalucia';
  }

  public function getWeight(): int {
    return 15;
  }

  public function isApplicable(int $uid): bool {
    $roles = $this->detectRoles($uid);
    return $roles['participante'] || $roles['orientador'] || $roles['coordinador'];
  }

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

  public function getExtraData(int $uid): array {
    $roles = $this->detectRoles($uid);

    if (!$roles['participante'] || !$roles['fase']) {
      return [];
    }

    return [
      'fase_badge' => [
        'fase' => $roles['fase'],
        'label' => isset(self::FASE_LABELS[$roles['fase']])
          ? (string) $this->t(self::FASE_LABELS[$roles['fase']])
          : $roles['fase'],
        'color' => self::FASE_COLORS[$roles['fase']] ?? 'neutral',
      ],
    ];
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

  /**
   * Links contextuales para participante.
   *
   * @return array<int, array<string, mixed>|null>
   */
  private function getParticipanteLinks(): array {
    return [
      $this->makeLink(
        $this->t('Mi Portal'),
        'jaraba_andalucia_ei.participante_portal',
        'general', 'user', 'andalucia',
        ['description' => $this->t('Tu espacio personal en el programa')],
      ),
      $this->makeLink(
        $this->t('Mi Expediente'),
        'jaraba_andalucia_ei.expediente_hub',
        'general', 'folder', 'andalucia',
        ['description' => $this->t('Documentacion y seguimiento')],
      ),
      $this->makeLink(
        $this->t('Informe de Progreso'),
        'jaraba_andalucia_ei.informe_progreso_pdf',
        'general', 'file-text', 'andalucia',
        ['description' => $this->t('Descarga tu informe actualizado')],
      ),
    ];
  }

  /**
   * Links contextuales para orientador.
   *
   * @return array<int, array<string, mixed>|null>
   */
  private function getOrientadorLinks(): array {
    return [
      $this->makeLink(
        $this->t('Panel Orientador'),
        'jaraba_andalucia_ei.orientador_dashboard',
        'business', 'briefcase', 'andalucia',
        ['description' => $this->t('Seguimiento de tus participantes')],
      ),
      $this->makeLink(
        $this->t('Mis Participantes'),
        'entity.programa_participante_ei.collection',
        'general', 'users', 'andalucia',
        ['description' => $this->t('Lista de participantes asignados')],
      ),
    ];
  }

  /**
   * Links contextuales para coordinador.
   *
   * @return array<int, array<string, mixed>|null>
   */
  private function getCoordinadorLinks(): array {
    return [
      $this->makeLink(
        $this->t('Hub Coordinador'),
        'jaraba_andalucia_ei.coordinador_dashboard',
        'ui', 'dashboard', 'andalucia',
        ['description' => $this->t('Centro de operaciones del programa')],
      ),
      $this->makeLink(
        $this->t('Gestion Solicitudes'),
        'entity.solicitud_ei.collection',
        'general', 'inbox', 'andalucia',
        ['description' => $this->t('Triage y gestion de solicitudes')],
      ),
      $this->makeLink(
        $this->t('Exportar STO'),
        'jaraba_andalucia_ei.sto_export',
        'general', 'download', 'andalucia',
        ['description' => $this->t('Exportacion para el Servicio Telefonico de Orientacion')],
      ),
    ];
  }

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
    catch (\Throwable) {
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
      'cross_vertical' => TRUE,
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
