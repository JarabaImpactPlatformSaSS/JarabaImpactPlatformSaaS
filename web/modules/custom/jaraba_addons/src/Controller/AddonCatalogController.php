<?php

namespace Drupal\jaraba_addons\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_addons\Service\AddonCatalogService;
use Drupal\jaraba_addons\Service\AddonSubscriptionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador frontend del catálogo de add-ons.
 *
 * ESTRUCTURA:
 * Controlador que renderiza las páginas públicas del catálogo de add-ons:
 * la vista de catálogo con filtrado por tipo y la vista de detalle con
 * precios, features y límites. Usa inyección de dependencias con
 * patrón static create() y try-catch para servicios opcionales.
 *
 * LÓGICA:
 * - catalog(): Lista todos los add-ons activos, aplica filtro por tipo
 *   si se pasa como query param, y cruza con las suscripciones del
 *   tenant actual para mostrar el badge "Suscrito".
 * - detail(): Carga un add-on por ID, decodifica features/limits JSON,
 *   y verifica si el tenant actual está suscrito.
 *
 * RELACIONES:
 * - AddonCatalogController -> AddonCatalogService (dependencia)
 * - AddonCatalogController -> AddonSubscriptionService (dependencia)
 * - AddonCatalogController -> TenantContextService (dependencia opcional)
 * - AddonCatalogController -> jaraba_addons_catalog template (renderiza)
 * - AddonCatalogController -> jaraba_addons_detail template (renderiza)
 * - AddonCatalogController <- jaraba_addons.routing.yml (registrado en)
 *
 * @package Drupal\jaraba_addons\Controller
 */
class AddonCatalogController extends ControllerBase {

  /**
   * Servicio de catálogo de add-ons.
   *
   * @var \Drupal\jaraba_addons\Service\AddonCatalogService
   */
  protected AddonCatalogService $catalogService;

  /**
   * Servicio de suscripciones a add-ons.
   *
   * @var \Drupal\jaraba_addons\Service\AddonSubscriptionService
   */
  protected AddonSubscriptionService $subscriptionService;

  /**
   * Servicio de contexto de tenant (opcional).
   *
   * @var object|null
   */
  protected $tenantContext;

  /**
   * Pila de peticiones para acceder a la request actual.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructor del controlador de catálogo.
   *
   * @param \Drupal\jaraba_addons\Service\AddonCatalogService $catalog_service
   *   Servicio de catálogo de add-ons.
   * @param \Drupal\jaraba_addons\Service\AddonSubscriptionService $subscription_service
   *   Servicio de suscripciones a add-ons.
   * @param object|null $tenant_context
   *   Servicio de contexto de tenant (opcional, puede ser NULL).
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Pila de peticiones HTTP.
   */
  public function __construct(
    AddonCatalogService $catalog_service,
    AddonSubscriptionService $subscription_service,
    $tenant_context,
    RequestStack $request_stack,
  ) {
    $this->catalogService = $catalog_service;
    $this->subscriptionService = $subscription_service;
    $this->tenantContext = $tenant_context;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $tenant_context = NULL;
    try {
      $tenant_context = $container->get('ecosistema_jaraba_core.tenant_context');
    }
    catch (\Exception $e) {
      // Servicio de tenant no disponible; se opera sin contexto de tenant.
    }

    return new static(
      $container->get('jaraba_addons.catalog'),
      $container->get('jaraba_addons.subscription'),
      $tenant_context,
      $container->get('request_stack'),
    );
  }

  /**
   * Renderiza el catálogo de add-ons con filtro por tipo.
   *
   * ESTRUCTURA: Método de acción para la ruta /addons.
   *
   * LÓGICA:
   * 1. Obtiene el filtro de tipo desde el query param ?type=
   * 2. Carga los add-ons (todos o filtrados por tipo)
   * 3. Obtiene las suscripciones del tenant actual
   * 4. Construye el array de IDs suscritos para marcar en la UI
   * 5. Renderiza la plantilla jaraba_addons_catalog
   *
   * RELACIONES: Consume AddonCatalogService, AddonSubscriptionService.
   *
   * @return array
   *   Render array con el tema jaraba_addons_catalog.
   */
  public function catalog(): array {
    $request = $this->requestStack->getCurrentRequest();
    $active_type = $request ? $request->query->get('type', '') : '';

    // Tipos válidos de add-on para los filtros.
    $addon_types = [
      'feature' => $this->t('Feature'),
      'storage' => $this->t('Storage'),
      'api_calls' => $this->t('API Calls'),
      'support' => $this->t('Support'),
      'custom' => $this->t('Custom'),
    ];

    // Cargar add-ons: filtrados por tipo o todos.
    try {
      if ($active_type && isset($addon_types[$active_type])) {
        $addons = $this->catalogService->getAddonsByType($active_type);
      }
      else {
        $addons = $this->catalogService->getAvailableAddons();
        $active_type = '';
      }
    }
    catch (\Exception $e) {
      $addons = [];
    }

    // Obtener suscripciones del tenant actual.
    $tenant_subscriptions = [];
    $subscribed_addon_ids = [];
    try {
      $tenant_id = $this->getCurrentTenantId();
      if ($tenant_id) {
        $subscriptions = $this->subscriptionService->getTenantSubscriptions($tenant_id);
        foreach ($subscriptions as $subscription) {
          $addon_ref_id = $subscription->get('addon_id')->target_id;
          $status = $subscription->get('status')->value;
          $tenant_subscriptions[$addon_ref_id] = [
            'subscription_id' => $subscription->id(),
            'status' => $status,
            'billing_cycle' => $subscription->get('billing_cycle')->value,
          ];
          if (in_array($status, ['active', 'trial'])) {
            $subscribed_addon_ids[] = (int) $addon_ref_id;
          }
        }
      }
    }
    catch (\Exception $e) {
      // Sin contexto de tenant, se muestra catálogo sin estado de suscripción.
    }

    // Preparar datos de add-ons para la plantilla.
    $addons_data = [];
    foreach ($addons as $addon) {
      $addon_id = (int) $addon->id();
      $addons_data[] = [
        'id' => $addon_id,
        'label' => $addon->label(),
        'description' => $addon->get('description')->value ?? '',
        'addon_type' => $addon->get('addon_type')->value ?? 'custom',
        'addon_type_label' => $addon_types[$addon->get('addon_type')->value] ?? $this->t('Custom'),
        'price_monthly' => (float) ($addon->get('price_monthly')->value ?? 0),
        'price_yearly' => (float) ($addon->get('price_yearly')->value ?? 0),
        'is_subscribed' => in_array($addon_id, $subscribed_addon_ids),
      ];
    }

    return [
      '#theme' => 'jaraba_addons_catalog',
      '#addons' => $addons_data,
      '#addon_types' => $addon_types,
      '#active_type' => $active_type,
      '#tenant_subscriptions' => $tenant_subscriptions,
      '#attached' => [
        'library' => [
          'jaraba_addons/addons-catalog',
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.query_args'],
        'tags' => ['addon_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Renderiza el detalle de un add-on.
   *
   * ESTRUCTURA: Método de acción para la ruta /addons/{addon_id}.
   *
   * LÓGICA:
   * 1. Carga el add-on por ID
   * 2. Verifica que existe y está activo
   * 3. Decodifica features y limits JSON
   * 4. Obtiene precios mensual y anual
   * 5. Verifica si el tenant actual está suscrito
   * 6. Renderiza la plantilla jaraba_addons_detail
   *
   * RELACIONES: Consume AddonCatalogService, AddonSubscriptionService.
   *
   * @param int $addon_id
   *   ID del add-on a mostrar.
   *
   * @return array
   *   Render array con el tema jaraba_addons_detail.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Si el add-on no existe o no está activo.
   */
  public function detail(int $addon_id): array {
    // Cargar el add-on.
    try {
      $storage = $this->entityTypeManager()->getStorage('addon');
      /** @var \Drupal\jaraba_addons\Entity\Addon|null $addon */
      $addon = $storage->load($addon_id);
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException();
    }

    if (!$addon || !$addon->isActive()) {
      throw new NotFoundHttpException();
    }

    // Decodificar features y limits.
    $features = $addon->getFeaturesIncluded();
    $limits = $addon->getLimits();

    // Precios.
    $price_monthly = $addon->getPrice('monthly');
    $price_yearly = $addon->getPrice('yearly');

    // Verificar suscripción del tenant actual.
    $is_subscribed = FALSE;
    try {
      $tenant_id = $this->getCurrentTenantId();
      if ($tenant_id) {
        $is_subscribed = $this->subscriptionService->isAddonActive($addon_id, $tenant_id);
      }
    }
    catch (\Exception $e) {
      // Sin contexto de tenant, no se puede verificar suscripción.
    }

    // Tipos para badge.
    $addon_types = [
      'feature' => $this->t('Feature'),
      'storage' => $this->t('Storage'),
      'api_calls' => $this->t('API Calls'),
      'support' => $this->t('Support'),
      'custom' => $this->t('Custom'),
    ];

    // Datos del add-on para la plantilla.
    $addon_data = [
      'id' => (int) $addon->id(),
      'label' => $addon->label(),
      'description' => $addon->get('description')->value ?? '',
      'addon_type' => $addon->get('addon_type')->value ?? 'custom',
      'addon_type_label' => $addon_types[$addon->get('addon_type')->value] ?? $this->t('Custom'),
      'machine_name' => $addon->get('machine_name')->value ?? '',
    ];

    return [
      '#theme' => 'jaraba_addons_detail',
      '#addon' => $addon_data,
      '#features' => $features,
      '#limits' => $limits,
      '#price_monthly' => $price_monthly,
      '#price_yearly' => $price_yearly,
      '#is_subscribed' => $is_subscribed,
      '#attached' => [
        'library' => [
          'jaraba_addons/addons-detail',
        ],
        'drupalSettings' => [
          'jarabaAddons' => [
            'addonId' => (int) $addon->id(),
            'priceMonthly' => $price_monthly,
            'priceYearly' => $price_yearly,
            'isSubscribed' => $is_subscribed,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['addon:' . $addon_id],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Obtiene el ID del tenant actual desde el servicio de contexto.
   *
   * ESTRUCTURA: Método helper privado para resolución de tenant.
   * LÓGICA: Delega al TenantContextService si está disponible.
   * RELACIONES: Consume TenantContextService (opcional).
   *
   * @return int|null
   *   ID del tenant actual o NULL si no hay contexto de tenant.
   */
  protected function getCurrentTenantId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }

    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
