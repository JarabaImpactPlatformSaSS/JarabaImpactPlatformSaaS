<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Servicio de navegacion contextual por avatar.
 *
 * PROPOSITO:
 * Genera items de navegacion contextuales segun el avatar detectado
 * del usuario actual. Generaliza EmployabilityMenuService (que solo
 * cubre 1 vertical) para cubrir los 10 avatares del ecosistema.
 *
 * DEPENDENCIAS:
 * - AvatarDetectionService: Detecta avatar via cascada 4 niveles
 * - CurrentRouteMatch: Determina ruta activa para highlight
 * - AccountProxy: Verifica autenticacion
 *
 * PATRON:
 * Cada avatar tiene un array estatico de items de navegacion.
 * Las URLs se resuelven via Url::fromRoute() con try/catch para
 * omitir items cuyo modulo no esta instalado.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService
 * @see \Drupal\jaraba_job_board\Service\EmployabilityMenuService
 */
class AvatarNavigationService {

  use StringTranslationTrait;

  /**
   * Mapeo de avatares a etiquetas legibles traducibles.
   */
  protected const AVATAR_LABELS = [
    'jobseeker' => 'Candidato',
    'recruiter' => 'Empleador',
    'entrepreneur' => 'Emprendedor',
    'producer' => 'Productor',
    'buyer' => 'Comprador',
    'merchant' => 'Comerciante',
    'service_provider' => 'Proveedor',
    'student' => 'Estudiante',
    'mentor' => 'Mentor',
    // Plan Elevacion JarabaLex v1 — Fase 12.
    'legal_professional' => 'Profesional Juridico',
    'tenant_admin' => 'Administrador',
    'admin' => 'Administrador',
    'anonymous' => 'Visitante',
  ];

  /**
   * Construye el AvatarNavigationService.
   *
   * @param \Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService $avatarDetection
   *   Servicio de deteccion de avatar.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Ruta actual.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Proxy del usuario actual.
   */
  public function __construct(
    protected AvatarDetectionService $avatarDetection,
    protected RouteMatchInterface $routeMatch,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Devuelve el avatar detectado del usuario actual.
   *
   * @return string
   *   Tipo de avatar (ej: 'jobseeker', 'recruiter', 'anonymous').
   */
  public function getAvatar(): string {
    if ($this->currentUser->isAnonymous()) {
      return 'anonymous';
    }
    $result = $this->avatarDetection->detect();
    return $result->avatarType ?? 'anonymous';
  }

  /**
   * Devuelve la etiqueta traducible del avatar actual.
   *
   * @return string
   *   Etiqueta del avatar (ej: 'Candidato', 'Empleador').
   */
  public function getAvatarLabel(): string {
    $avatar = $this->getAvatar();
    $label = self::AVATAR_LABELS[$avatar] ?? 'Usuario';
    return (string) $this->t($label);
  }

  /**
   * Devuelve los items de navegacion segun el avatar detectado.
   *
   * Cada item tiene la estructura:
   * - id: string — identificador unico del item
   * - label: TranslatableMarkup — texto traducible
   * - url: string — URL resuelta
   * - icon_category: string — categoria del icono (ui, analytics, etc.)
   * - icon_name: string — nombre del icono dentro de la categoria
   * - weight: int — orden de presentacion
   * - active: bool — TRUE si la ruta actual coincide con el item
   * - cross_vertical: bool — TRUE si es un item cross-vertical (opcional)
   *
   * @return array
   *   Array de items de navegacion. Vacio si no hay items.
   */
  public function getNavigationItems(): array {
    $avatar = $this->getAvatar();
    $definitions = $this->getItemDefinitions($avatar);
    $currentRoute = $this->routeMatch->getRouteName();
    $items = [];

    foreach ($definitions as $weight => $def) {
      try {
        $url = Url::fromRoute($def['route'])->toString();
        $items[] = [
          'id' => $def['id'],
          'label' => $this->t($def['label']),
          'url' => $url,
          'icon_category' => $def['icon_category'],
          'icon_name' => $def['icon_name'],
          'weight' => $weight,
          'active' => ($currentRoute === $def['route']),
        ];
      }
      catch (\Exception $e) {
        // Ruta no existe (modulo no instalado) — omitir item.
        continue;
      }
    }

    // Evaluar items cross-vertical condicionales.
    $crossVerticalItems = $this->getCrossVerticalItems($avatar);
    foreach ($crossVerticalItems as $def) {
      try {
        $url = Url::fromRoute($def['route'])->toString();
        $items[] = [
          'id' => $def['id'],
          'label' => $this->t($def['label']),
          'url' => $url,
          'icon_category' => $def['icon_category'],
          'icon_name' => $def['icon_name'],
          'weight' => $def['weight'],
          'active' => ($currentRoute === $def['route']),
          'cross_vertical' => TRUE,
        ];
      }
      catch (\Exception $e) {
        continue;
      }
    }

    return $items;
  }

  /**
   * Devuelve items cross-vertical condicionales para un avatar.
   *
   * Evalua condiciones del perfil del usuario (RIASEC, journey state)
   * para sugerir navegacion hacia otros verticales.
   *
   * @param string $avatar
   *   Tipo de avatar actual.
   *
   * @return array
   *   Array de definiciones de items cross-vertical.
   */
  protected function getCrossVerticalItems(string $avatar): array {
    $items = [];

    try {
      // Jobseeker con perfil emprendedor: sugerir emprendimiento.
      if ($avatar === 'jobseeker' && \Drupal::hasService('jaraba_self_discovery.riasec')) {
        /** @var \Drupal\jaraba_self_discovery\Service\RiasecService $riasec */
        $riasec = \Drupal::service('jaraba_self_discovery.riasec');
        $potential = $riasec->evaluateEntrepreneurPotential();

        if ($potential['recommend_emprendimiento']) {
          $items[] = [
            'id' => 'cross_emprender',
            'label' => 'Emprender',
            'route' => 'ecosistema_jaraba_core.landing_emprender',
            'icon_category' => 'verticals',
            'icon_name' => 'rocket',
            'weight' => 100,
          ];
        }
      }

      // Entrepreneur en riesgo: sugerir empleabilidad.
      if ($avatar === 'entrepreneur' && \Drupal::hasService('jaraba_journey.engine')) {
        $uid = (int) $this->currentUser->id();
        /** @var \Drupal\jaraba_journey\Service\JourneyEngineService $journeyEngine */
        $journeyEngine = \Drupal::service('jaraba_journey.engine');
        $state = $journeyEngine->getState($uid);

        if ($state && $state->getJourneyState() === 'at_risk') {
          $items[] = [
            'id' => 'cross_empleo',
            'label' => 'Explorar empleo',
            'route' => 'jaraba_job_board.search',
            'icon_category' => 'verticals',
            'icon_name' => 'briefcase',
            'weight' => 100,
          ];
        }
      }
    }
    catch (\Exception $e) {
      // Cross-vertical evaluation is non-critical.
    }

    return $items;
  }

  /**
   * Devuelve las definiciones de items para un avatar dado.
   *
   * @param string $avatar
   *   Tipo de avatar.
   *
   * @return array
   *   Array de definiciones con id, label, route, icon_category, icon_name.
   */
  protected function getItemDefinitions(string $avatar): array {
    $map = [
      'jobseeker' => [
        ['id' => 'profile', 'label' => 'Mi perfil', 'route' => 'jaraba_candidate.my_profile', 'icon_category' => 'ui', 'icon_name' => 'user'],
        ['id' => 'jobs', 'label' => 'Ofertas', 'route' => 'jaraba_job_board.search', 'icon_category' => 'verticals', 'icon_name' => 'briefcase'],
        ['id' => 'applications', 'label' => 'Candidaturas', 'route' => 'jaraba_job_board.my_applications', 'icon_category' => 'actions', 'icon_name' => 'check'],
        ['id' => 'training', 'label' => 'Formación', 'route' => 'jaraba_lms.my_learning', 'icon_category' => 'ui', 'icon_name' => 'book'],
        ['id' => 'paths', 'label' => 'Itinerarios', 'route' => 'jaraba_paths.catalog', 'icon_category' => 'ui', 'icon_name' => 'rocket'],
      ],
      'recruiter' => [
        ['id' => 'dashboard', 'label' => 'Panel', 'route' => 'jaraba_job_board.employer_dashboard', 'icon_category' => 'analytics', 'icon_name' => 'gauge'],
        ['id' => 'my_jobs', 'label' => 'Mis ofertas', 'route' => 'jaraba_job_board.employer_jobs', 'icon_category' => 'business', 'icon_name' => 'diagnostic'],
        ['id' => 'candidates', 'label' => 'Candidatos', 'route' => 'jaraba_job_board.employer_applications', 'icon_category' => 'ui', 'icon_name' => 'users'],
        ['id' => 'company', 'label' => 'Mi empresa', 'route' => 'jaraba_job_board.my_company', 'icon_category' => 'business', 'icon_name' => 'company'],
      ],
      'entrepreneur' => [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'route' => 'jaraba_business_tools.entrepreneur_dashboard', 'icon_category' => 'analytics', 'icon_name' => 'dashboard'],
        ['id' => 'canvas', 'label' => 'Canvas', 'route' => 'jaraba_business_tools.canvas_list', 'icon_category' => 'business', 'icon_name' => 'canvas'],
        ['id' => 'paths', 'label' => 'Itinerarios', 'route' => 'jaraba_paths.catalog', 'icon_category' => 'ui', 'icon_name' => 'rocket'],
        ['id' => 'training', 'label' => 'Formación', 'route' => 'jaraba_lms.my_learning', 'icon_category' => 'ui', 'icon_name' => 'book'],
        ['id' => 'mentors', 'label' => 'Mentores', 'route' => 'jaraba_mentoring.mentor_catalog', 'icon_category' => 'ui', 'icon_name' => 'users'],
      ],
      'producer' => [
        ['id' => 'company', 'label' => 'Mi empresa', 'route' => 'jaraba_job_board.my_company', 'icon_category' => 'business', 'icon_name' => 'company'],
        ['id' => 'marketplace', 'label' => 'Marketplace', 'route' => 'jaraba_agroconecta.marketplace', 'icon_category' => 'commerce', 'icon_name' => 'store'],
        ['id' => 'producer_dashboard', 'label' => 'Mi Tienda', 'route' => 'jaraba_agroconecta.producer.dashboard', 'icon_category' => 'commerce', 'icon_name' => 'store'],
        ['id' => 'producer_orders', 'label' => 'Pedidos', 'route' => 'jaraba_agroconecta.producer.orders', 'icon_category' => 'commerce', 'icon_name' => 'shopping-bag'],
        ['id' => 'producer_products', 'label' => 'Productos', 'route' => 'jaraba_agroconecta.producer.products', 'icon_category' => 'commerce', 'icon_name' => 'barcode'],
        ['id' => 'producer_settings', 'label' => 'Configuracion', 'route' => 'jaraba_agroconecta.producer.settings', 'icon_category' => 'ui', 'icon_name' => 'settings'],
        ['id' => 'training', 'label' => 'Formación', 'route' => 'jaraba_lms.my_learning', 'icon_category' => 'ui', 'icon_name' => 'book'],
      ],
      'buyer' => [
        ['id' => 'marketplace', 'label' => 'Marketplace', 'route' => 'jaraba_agroconecta.marketplace', 'icon_category' => 'commerce', 'icon_name' => 'store'],
        ['id' => 'customer_dashboard', 'label' => 'Mis Pedidos', 'route' => 'jaraba_agroconecta.customer.dashboard', 'icon_category' => 'commerce', 'icon_name' => 'shopping-bag'],
        ['id' => 'customer_favorites', 'label' => 'Favoritos', 'route' => 'jaraba_agroconecta.customer.favorites', 'icon_category' => 'ui', 'icon_name' => 'heart'],
      ],
      'merchant' => [
        ['id' => 'portal', 'label' => 'Mi comercio', 'route' => 'jaraba_comercio_conecta.merchant_portal', 'icon_category' => 'commerce', 'icon_name' => 'store'],
        ['id' => 'products', 'label' => 'Productos', 'route' => 'jaraba_comercio_conecta.merchant_portal.products', 'icon_category' => 'commerce', 'icon_name' => 'shopping-bag'],
        ['id' => 'stock', 'label' => 'Stock', 'route' => 'jaraba_comercio_conecta.merchant_portal.stock', 'icon_category' => 'commerce', 'icon_name' => 'barcode'],
        ['id' => 'analytics', 'label' => 'Analíticas', 'route' => 'jaraba_comercio_conecta.merchant_portal.analytics', 'icon_category' => 'analytics', 'icon_name' => 'chart-bar'],
      ],
      'service_provider' => [
        ['id' => 'portal', 'label' => 'Mi servicio', 'route' => 'jaraba_servicios_conecta.provider_portal', 'icon_category' => 'business', 'icon_name' => 'company'],
        ['id' => 'offerings', 'label' => 'Servicios', 'route' => 'jaraba_servicios_conecta.provider_portal.offerings', 'icon_category' => 'ui', 'icon_name' => 'settings'],
        ['id' => 'bookings', 'label' => 'Reservas', 'route' => 'jaraba_servicios_conecta.provider_portal.bookings', 'icon_category' => 'ui', 'icon_name' => 'calendar'],
        ['id' => 'calendar', 'label' => 'Calendario', 'route' => 'jaraba_servicios_conecta.provider_portal.calendar', 'icon_category' => 'ui', 'icon_name' => 'clock'],
      ],
      'student' => [
        ['id' => 'my_courses', 'label' => 'Mis cursos', 'route' => 'jaraba_lms.my_learning', 'icon_category' => 'ui', 'icon_name' => 'book'],
        ['id' => 'catalog', 'label' => 'Catálogo', 'route' => 'jaraba_lms.catalog', 'icon_category' => 'ui', 'icon_name' => 'search'],
        ['id' => 'certificates', 'label' => 'Certificados', 'route' => 'jaraba_lms.my_certificates', 'icon_category' => 'business', 'icon_name' => 'achievement'],
        ['id' => 'paths', 'label' => 'Itinerarios', 'route' => 'jaraba_paths.catalog', 'icon_category' => 'ui', 'icon_name' => 'rocket'],
      ],
      'mentor' => [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'route' => 'jaraba_mentoring.mentor_dashboard', 'icon_category' => 'analytics', 'icon_name' => 'dashboard'],
        ['id' => 'mentors', 'label' => 'Mentores', 'route' => 'jaraba_mentoring.mentor_catalog', 'icon_category' => 'ui', 'icon_name' => 'users'],
        ['id' => 'training', 'label' => 'Formación', 'route' => 'jaraba_lms.my_learning', 'icon_category' => 'ui', 'icon_name' => 'book'],
      ],
      'legal_professional' => [
        ['id' => 'search', 'label' => 'Búsqueda', 'route' => 'jaraba_legal.search', 'icon_category' => 'legal', 'icon_name' => 'search-legal'],
        ['id' => 'expedientes', 'label' => 'Expedientes', 'route' => 'jaraba_legal_cases.dashboard', 'icon_category' => 'legal', 'icon_name' => 'briefcase'],
        ['id' => 'agenda', 'label' => 'Agenda', 'route' => 'jaraba_legal_calendar.dashboard', 'icon_category' => 'legal', 'icon_name' => 'calendar-legal'],
        ['id' => 'dashboard', 'label' => 'Dashboard', 'route' => 'jaraba_legal.dashboard', 'icon_category' => 'analytics', 'icon_name' => 'dashboard'],
        ['id' => 'alerts', 'label' => 'Alertas', 'route' => 'jaraba_legal.dashboard', 'icon_category' => 'legal', 'icon_name' => 'alert-bell'],
        ['id' => 'eu_sources', 'label' => 'Fuentes UE', 'route' => 'jaraba_legal.search', 'icon_category' => 'legal', 'icon_name' => 'eu-flag'],
      ],
      'tenant_admin' => [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'route' => 'ecosistema_jaraba_core.tenant.dashboard', 'icon_category' => 'analytics', 'icon_name' => 'dashboard'],
        ['id' => 'plan', 'label' => 'Plan', 'route' => 'ecosistema_jaraba_core.tenant.change_plan', 'icon_category' => 'business', 'icon_name' => 'money'],
        ['id' => 'help', 'label' => 'Ayuda', 'route' => 'jaraba_tenant_knowledge.help_center', 'icon_category' => 'ui', 'icon_name' => 'help-circle'],
      ],
      'admin' => [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'route' => 'ecosistema_jaraba_core.tenant.dashboard', 'icon_category' => 'analytics', 'icon_name' => 'dashboard'],
        ['id' => 'plan', 'label' => 'Plan', 'route' => 'ecosistema_jaraba_core.tenant.change_plan', 'icon_category' => 'business', 'icon_name' => 'money'],
        ['id' => 'help', 'label' => 'Ayuda', 'route' => 'jaraba_tenant_knowledge.help_center', 'icon_category' => 'ui', 'icon_name' => 'help-circle'],
      ],
      'anonymous' => [
        ['id' => 'empleo', 'label' => 'Empleo', 'route' => 'ecosistema_jaraba_core.landing_empleo', 'icon_category' => 'verticals', 'icon_name' => 'briefcase'],
        ['id' => 'emprender', 'label' => 'Emprender', 'route' => 'ecosistema_jaraba_core.landing_emprender', 'icon_category' => 'verticals', 'icon_name' => 'rocket'],
        ['id' => 'comercio', 'label' => 'Comercio', 'route' => 'ecosistema_jaraba_core.landing_comercio', 'icon_category' => 'commerce', 'icon_name' => 'store'],
        ['id' => 'agroconecta', 'label' => 'AgroConecta', 'route' => 'ecosistema_jaraba_core.landing.agroconecta', 'icon_category' => 'commerce', 'icon_name' => 'leaf'],
        ['id' => 'courses', 'label' => 'Cursos', 'route' => 'jaraba_lms.catalog', 'icon_category' => 'ui', 'icon_name' => 'book'],
        ['id' => 'register', 'label' => 'Registrarse', 'route' => 'user.register', 'icon_category' => 'ui', 'icon_name' => 'user'],
      ],
    ];

    return $map[$avatar] ?? $map['anonymous'];
  }

}
