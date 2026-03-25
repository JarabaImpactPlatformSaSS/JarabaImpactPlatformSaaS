<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\AvatarWizardMapping;

/**
 * Bridge entre avatar persistido y los registries de Wizard/DailyActions.
 *
 * PROPOSITO:
 * Centraliza el mapping avatar_type → wizard_id + dashboard_id + contextId.
 * Usa una cascada de 2 niveles para resolver el avatar del usuario:
 *
 *   1. JourneyState entity (avatar persistido al registrarse) — fuente fiable
 *   2. AvatarDetectionService (detección por contexto URL) — fallback
 *
 * La razón de esta cascada: AvatarDetectionService es CONTEXTUAL (depende
 * del dominio/URL/UTM actual), por lo que en /user/{uid} (perfil) devuelve
 * 'general' para la mayoría de usuarios. JourneyState tiene el avatar REAL
 * asignado al momento del registro, independiente del contexto de navegación.
 *
 * NOMENCLATURA DUAL:
 * El SaaS usa DOS convenciones de nombres para avatares:
 * - JourneyState::AVATARS (español): productor, emprendedor, comerciante...
 * - AvatarDetectionService (inglés): producer, entrepreneur, merchant...
 * Este servicio normaliza ambas a una clave unificada via JOURNEY_TO_CANONICAL.
 *
 * DIRECTRICES:
 * - SETUP-WIZARD-DAILY-001: Patrón transversal Setup Wizard + Daily Actions.
 * - ZEIGARNIK-PRELOAD-001: Global steps inyectados por SetupWizardRegistry.
 * - OPTIONAL-CROSSMODULE-001: Dependencias opcionales con @? en services.yml.
 * - PHANTOM-ARG-001: args YAML coinciden exactamente con params constructor.
 *
 * CONTEXT SCOPE:
 * - User-scoped (candidate, legal, content_hub, mentor, student):
 *   contextId = currentUser()->id()
 * - Tenant-scoped (agro, comercio, servicios, emprendimiento):
 *   contextId = TenantContextService::getCurrentTenantId()
 *
 * @see \Drupal\ecosistema_jaraba_core\ValueObject\AvatarWizardMapping
 * @see \Drupal\jaraba_journey\Entity\JourneyState
 * @see \Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService
 */
class AvatarWizardBridgeService {

  /**
   * Normaliza avatares de JourneyState (español) a clave canónica.
   *
   * JourneyState::AVATARS usa nombres en español (productor, emprendedor).
   * AvatarDetectionService usa nombres en inglés (producer, entrepreneur).
   * Este mapa traduce los valores de JourneyState a la clave canónica
   * usada en AVATAR_MAPPING.
   *
   * Avatares que ya son canónicos (profesional, mentor) se incluyen
   * como identidad para explicitez.
   */
  protected const JOURNEY_TO_CANONICAL = [
    // JourneyState español → clave canónica.
    'job_seeker' => 'jobseeker',
    'employer' => 'recruiter',
    'orientador' => 'recruiter',
    'emprendedor' => 'entrepreneur',
    'productor' => 'producer',
    'comprador_b2b' => 'buyer',
    'consumidor' => 'buyer',
    'comerciante' => 'merchant',
    'comprador_local' => 'buyer',
    'profesional' => 'profesional',
    'cliente_servicios' => 'cliente_servicios',
    'estudiante' => 'student',
    'formador' => 'instructor',
    'admin_lms' => 'admin',
    'beneficiario_ei' => 'beneficiario_ei',
    'tecnico_sto' => 'orientador_ei',
    'admin_ei' => 'coordinador_ei',
    'gestor_programa' => 'entrepreneur',
    'mentor' => 'mentor',
    // AvatarDetectionService inglés → identidad (ya canónico)
    'jobseeker' => 'jobseeker',
    'recruiter' => 'recruiter',
    'entrepreneur' => 'entrepreneur',
    'producer' => 'producer',
    'merchant' => 'merchant',
    'service_provider' => 'profesional',
    'student' => 'student',
    'legal_professional' => 'legal_professional',
    'tenant_admin' => 'tenant_admin',
    // Content Hub + Page Builder avatars.
    'editor_content_hub' => 'editor_content_hub',
    'editor' => 'editor_content_hub',
    'page_builder' => 'page_builder',
    'web_designer' => 'page_builder',
  ];

  /**
   * Mapping canónico Avatar → Wizard/Dashboard/Context.
   *
   * Cada entrada define:
   * - wizard_id: ID del wizard en SetupWizardRegistry (NULL si no tiene).
   * - dashboard_id: ID del dashboard en DailyActionsRegistry (NULL si no tiene).
   * - scope: 'user' (contextId = uid) | 'tenant' (contextId = tenantId).
   * - dashboard_route: Nombre de ruta Drupal del dashboard vertical.
   * - vertical_label: Nombre humano del vertical para títulos i18n.
   *
   * MANTENIMIENTO:
   * Al añadir un nuevo vertical/wizard, agregar entrada aquí Y en
   * JOURNEY_TO_CANONICAL si el avatar tiene nombre en español.
   */
  protected const AVATAR_MAPPING = [
    'jobseeker' => [
      'wizard_id' => 'candidato_empleo',
      'dashboard_id' => 'candidato_empleo',
      'scope' => 'user',
      'dashboard_route' => 'jaraba_candidate.dashboard',
    ],
    'entrepreneur' => [
      'wizard_id' => 'entrepreneur_tools',
      'dashboard_id' => 'entrepreneur_tools',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_business_tools.entrepreneur_dashboard',
    ],
    'producer' => [
      'wizard_id' => 'producer_agro',
      'dashboard_id' => 'producer_agro',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_agroconecta_core.producer.dashboard',
    ],
    'merchant' => [
      'wizard_id' => 'merchant_comercio',
      'dashboard_id' => 'merchant_comercio',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_comercio_conecta.merchant_portal',
    ],
    'profesional' => [
      'wizard_id' => 'provider_servicios',
      'dashboard_id' => 'provider_servicios',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_servicios_conecta.provider_portal',
    ],
    'student' => [
      'wizard_id' => 'learner_lms',
      'dashboard_id' => 'learner_lms',
      'scope' => 'user',
      'dashboard_route' => 'jaraba_lms.my_learning',
    ],
    'instructor' => [
      'wizard_id' => 'instructor_lms',
      'dashboard_id' => 'instructor_lms',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_lms.instructor.courses',
    ],
    'mentor' => [
      'wizard_id' => 'mentor',
      'dashboard_id' => 'mentor',
      'scope' => 'user',
      'dashboard_route' => 'jaraba_mentoring.mentor_dashboard',
    ],
    'legal_professional' => [
      'wizard_id' => 'legal_professional',
      'dashboard_id' => 'legal_professional',
      'scope' => 'user',
      'dashboard_route' => 'jaraba_legal.dashboard',
    ],
    'coordinador_ei' => [
      'wizard_id' => 'coordinador_ei',
      'dashboard_id' => 'coordinador_ei',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_andalucia_ei.coordinador_dashboard',
    ],
    'orientador_ei' => [
      'wizard_id' => 'orientador_ei',
      'dashboard_id' => 'orientador_ei',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_andalucia_ei.orientador_dashboard',
    ],
    'beneficiario_ei' => [
      'wizard_id' => NULL,
      'dashboard_id' => NULL,
      'scope' => 'user',
      'dashboard_route' => NULL,
    ],
    // Content Hub editor — Sprint 3 Content Publication 100/100.
    'editor_content_hub' => [
      'wizard_id' => 'editor_content_hub',
      'dashboard_id' => 'editor_content_hub',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_content_hub.dashboard.frontend',
    ],
    // Page Builder — Sprint 1 Content Publication 100/100.
    'page_builder' => [
      'wizard_id' => 'page_builder',
      'dashboard_id' => 'page_builder',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_page_builder.dashboard',
    ],
  ];

  /**
   * Construye el AvatarWizardBridgeService.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Proxy del usuario actual.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para cargar JourneyState.
   * @param \Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService|null $avatarDetection
   *   Servicio de detección de avatar (opcional — @?). Fallback si no hay JourneyState.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null $tenantContext
   *   Servicio de contexto de tenant (opcional — @?).
   */
  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?AvatarDetectionService $avatarDetection = NULL,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * Resuelve el mapping wizard/dashboard para el usuario actual.
   *
   * Cascada de resolución (2 niveles):
   *
   * 1. JourneyState entity — avatar persistido al registrarse.
   *    Es la fuente FIABLE porque no depende del contexto URL actual.
   *    En /user/{uid} (perfil), AvatarDetectionService devolvería 'general'
   *    porque la URL no tiene contexto vertical, pero JourneyState conserva
   *    el avatar real (ej: 'emprendedor' detectado al registrarse en /emprendimiento).
   *
   * 2. AvatarDetectionService::detect() — fallback si no hay JourneyState.
   *    Útil cuando el módulo jaraba_journey no está instalado o el usuario
   *    no tiene JourneyState (usuarios legacy pre-journey).
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\AvatarWizardMapping|null
   *   NULL si: usuario no autenticado, sin avatar, o avatar sin mapping.
   */
  public function resolveForCurrentUser(): ?AvatarWizardMapping {
    if (!$this->currentUser->isAuthenticated()) {
      return NULL;
    }

    $uid = (int) $this->currentUser->id();

    // Nivel 1: JourneyState (avatar persistido — fuente fiable).
    $canonicalAvatar = $this->resolveFromJourneyState($uid);

    // Nivel 2: AvatarDetectionService (contextual — fallback).
    if (!$canonicalAvatar && $this->avatarDetection) {
      $result = $this->avatarDetection->detect();
      if ($result->isDetected()) {
        $canonicalAvatar = self::JOURNEY_TO_CANONICAL[$result->avatarType] ?? $result->avatarType;
      }
    }

    if (!$canonicalAvatar) {
      return NULL;
    }

    $mapping = self::AVATAR_MAPPING[$canonicalAvatar] ?? NULL;
    if (!$mapping || ($mapping['wizard_id'] === NULL && $mapping['dashboard_id'] === NULL)) {
      return NULL;
    }

    $contextId = $this->resolveContextId($mapping['scope'], $uid);

    // Resolver vertical desde JourneyState o mapping.
    $vertical = $this->resolveVertical($canonicalAvatar);

    return new AvatarWizardMapping(
      wizardId: $mapping['wizard_id'],
      dashboardId: $mapping['dashboard_id'],
      contextId: $contextId,
      avatarType: $canonicalAvatar,
      vertical: $vertical,
      dashboardRoute: $mapping['dashboard_route'],
    );
  }

  /**
   * Carga JourneyState del usuario y extrae el avatar canónico.
   *
   * @param int $uid
   *   ID del usuario.
   *
   * @return string|null
   *   Avatar canónico normalizado, o NULL si no hay JourneyState.
   */
  protected function resolveFromJourneyState(int $uid): ?string {
    try {
      if (!$this->entityTypeManager->hasDefinition('journey_state')) {
        return NULL;
      }

      $states = $this->entityTypeManager
        ->getStorage('journey_state')
        ->loadByProperties(['user_id' => $uid]);

      if (empty($states)) {
        return NULL;
      }

      $state = reset($states);
      $rawAvatar = $state->get('avatar_type')->value ?? '';

      if (empty($rawAvatar) || $rawAvatar === 'pending') {
        return NULL;
      }

      // Normalizar a clave canónica.
      return self::JOURNEY_TO_CANONICAL[$rawAvatar] ?? $rawAvatar;
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

  /**
   * Resuelve contextId según scope: uid para user-scoped, tenantId para tenant.
   */
  protected function resolveContextId(string $scope, int $uid): int {
    if ($scope === 'tenant' && $this->tenantContext) {
      $tenantId = $this->tenantContext->getCurrentTenantId();
      if ($tenantId !== NULL && $tenantId > 0) {
        return $tenantId;
      }
    }
    return $uid;
  }

  /**
   * Resuelve el vertical label desde el avatar canónico.
   */
  protected function resolveVertical(string $canonicalAvatar): ?string {
    $map = [
      'jobseeker' => 'empleabilidad',
      'recruiter' => 'empleabilidad',
      'entrepreneur' => 'emprendimiento',
      'producer' => 'agroconecta',
      'merchant' => 'comercioconecta',
      'profesional' => 'serviciosconecta',
      'student' => 'formacion',
      'instructor' => 'formacion',
      'mentor' => 'empleabilidad',
      'legal_professional' => 'jarabalex',
      'coordinador_ei' => 'andalucia_ei',
      'orientador_ei' => 'andalucia_ei',
      'beneficiario_ei' => 'andalucia_ei',
    ];
    return $map[$canonicalAvatar] ?? NULL;
  }

}
