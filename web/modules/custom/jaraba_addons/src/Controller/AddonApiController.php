<?php

namespace Drupal\jaraba_addons\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_addons\Service\AddonCatalogService;
use Drupal\jaraba_addons\Service\AddonSubscriptionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controlador de la API REST de add-ons.
 *
 * ESTRUCTURA:
 * Controlador que expone endpoints REST para la gestión programática
 * del catálogo de add-ons y las suscripciones. Todos los endpoints
 * devuelven JsonResponse con la estructura estándar:
 * { data: ..., meta: { ... }, errors: [] }
 *
 * LÓGICA:
 * - listAddons(): GET /api/v1/addons — listado filtrable por ?type=
 * - subscribe(): POST /api/v1/addons/{addon_id}/subscribe — suscripción
 * - cancel(): POST /api/v1/addons/subscriptions/{subscription_id}/cancel
 * - listSubscriptions(): GET /api/v1/addons/subscriptions
 *
 * RELACIONES:
 * - AddonApiController -> AddonCatalogService (dependencia)
 * - AddonApiController -> AddonSubscriptionService (dependencia)
 * - AddonApiController -> TenantContextService (dependencia opcional)
 * - AddonApiController <- jaraba_addons.routing.yml (registrado en)
 *
 * @package Drupal\jaraba_addons\Controller
 */
class AddonApiController extends ControllerBase {

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
   * Constructor del controlador API.
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
   * GET /api/v1/addons — Lista add-ons disponibles con filtro opcional.
   *
   * ESTRUCTURA: Endpoint de listado del catálogo.
   *
   * LÓGICA:
   * 1. Lee el query param ?type= para filtrar por tipo
   * 2. Carga los add-ons (todos o filtrados)
   * 3. Serializa los datos a formato JSON estándar
   *
   * RELACIONES: Consume AddonCatalogService.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con la estructura { data, meta, errors }.
   */
  public function listAddons(): JsonResponse {
    try {
      $request = $this->requestStack->getCurrentRequest();
      $type = $request ? $request->query->get('type', '') : '';

      $valid_types = ['feature', 'storage', 'api_calls', 'support', 'custom'];

      if ($type && in_array($type, $valid_types)) {
        $addons = $this->catalogService->getAddonsByType($type);
      }
      else {
        $addons = $this->catalogService->getAvailableAddons();
        $type = '';
      }

      $data = [];
      foreach ($addons as $addon) {
        $data[] = $this->serializeAddon($addon);
      }

      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => [
          'total' => count($data),
          'filter_type' => $type ?: NULL,
        ],
        'errors' => []]);
    }
    catch (\Exception $e) {
      return $this->errorResponse(
        $this->t('Error loading add-ons catalog.')->__toString(),
        500
      );
    }
  }

  /**
   * POST /api/v1/addons/{addon_id}/subscribe — Suscribe al tenant actual.
   *
   * ESTRUCTURA: Endpoint de suscripción a un add-on.
   *
   * LÓGICA:
   * 1. Valida que hay contexto de tenant
   * 2. Lee billing_cycle del body JSON (default: monthly)
   * 3. Delega al AddonSubscriptionService.subscribe()
   * 4. Devuelve la suscripción creada
   *
   * RELACIONES: Consume AddonSubscriptionService, TenantContextService.
   *
   * @param int $addon_id
   *   ID del add-on al que suscribirse.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con la suscripción creada o error.
   */
  public function subscribe(int $addon_id): JsonResponse {
    // Verificar contexto de tenant.
    $tenant_id = $this->getCurrentTenantId();
    if (!$tenant_id) {
      return $this->errorResponse(
        $this->t('Tenant context not found. Please log in with a tenant account.')->__toString(),
        403
      );
    }

    // Leer billing_cycle del body.
    $request = $this->requestStack->getCurrentRequest();
    $body = [];
    if ($request) {
      $content = $request->getContent();
      if ($content) {
        $body = json_decode($content, TRUE) ?: [];
      }
    }
    $billing_cycle = $body['billing_cycle'] ?? 'monthly';

    // Validar billing_cycle.
    if (!in_array($billing_cycle, ['monthly', 'yearly'])) {
      return $this->errorResponse(
        $this->t('Invalid billing cycle. Use "monthly" or "yearly".')->__toString(),
        422
      );
    }

    try {
      $subscription = $this->subscriptionService->subscribe($addon_id, $tenant_id, $billing_cycle);

      return new JsonResponse(['success' => TRUE, 'data' => $this->serializeSubscription($subscription), 'meta' => [
          'message' => $this->t('Subscription created successfully.')->__toString(),
        ],
        'errors' => [],
      ], 201);
    }
    catch (\RuntimeException $e) {
      return $this->errorResponse($e->getMessage(), 422);
    }
    catch (\Exception $e) {
      return $this->errorResponse(
        $this->t('An error occurred while processing the subscription.')->__toString(),
        500
      );
    }
  }

  /**
   * POST /api/v1/addons/subscriptions/{subscription_id}/cancel — Cancela.
   *
   * ESTRUCTURA: Endpoint de cancelación de suscripción.
   *
   * LÓGICA:
   * 1. Valida que hay contexto de tenant
   * 2. Delega al AddonSubscriptionService.cancel()
   * 3. Devuelve la suscripción cancelada
   *
   * RELACIONES: Consume AddonSubscriptionService.
   *
   * @param int $subscription_id
   *   ID de la suscripción a cancelar.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con la suscripción cancelada o error.
   */
  public function cancel(int $subscription_id): JsonResponse {
    // Verificar contexto de tenant.
    $tenant_id = $this->getCurrentTenantId();
    if (!$tenant_id) {
      return $this->errorResponse(
        $this->t('Tenant context not found. Please log in with a tenant account.')->__toString(),
        403
      );
    }

    try {
      // Verificar que la suscripción existe y pertenece al tenant ANTES de cancelar.
      $sub_storage = $this->entityTypeManager()->getStorage('addon_subscription');
      $subscription = $sub_storage->load($subscription_id);

      if (!$subscription) {
        return $this->errorResponse(
          $this->t('Subscription not found.')->__toString(),
          404
        );
      }

      $sub_tenant_id = $subscription->get('tenant_id')->target_id;
      if ((int) $sub_tenant_id !== $tenant_id) {
        return $this->errorResponse(
          $this->t('You do not have permission to cancel this subscription.')->__toString(),
          403
        );
      }

      // Ahora sí cancelar — la ownership ya fue verificada.
      $subscription = $this->subscriptionService->cancel($subscription_id);

      return new JsonResponse([
        'data' => $this->serializeSubscription($subscription),
        'meta' => [
          'message' => $this->t('Subscription cancelled successfully.')->__toString(),
        ],
        'errors' => []]);
    }
    catch (\Exception $e) {
      return $this->errorResponse(
        $this->t('An error occurred while cancelling the subscription.')->__toString(),
        500
      );
    }
  }

  /**
   * GET /api/v1/addons/subscriptions — Lista suscripciones del tenant.
   *
   * ESTRUCTURA: Endpoint de listado de suscripciones del tenant actual.
   *
   * LÓGICA:
   * 1. Obtiene el tenant actual
   * 2. Carga todas sus suscripciones
   * 3. Serializa cada suscripción con datos del add-on asociado
   *
   * RELACIONES: Consume AddonSubscriptionService, TenantContextService.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con las suscripciones del tenant.
   */
  public function listSubscriptions(): JsonResponse {
    $tenant_id = $this->getCurrentTenantId();
    if (!$tenant_id) {
      return $this->errorResponse(
        $this->t('Tenant context not found. Please log in with a tenant account.')->__toString(),
        403
      );
    }

    try {
      $subscriptions = $this->subscriptionService->getTenantSubscriptions($tenant_id);

      $data = [];
      foreach ($subscriptions as $subscription) {
        $data[] = $this->serializeSubscription($subscription);
      }

      return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => [
          'total' => count($data),
          'tenant_id' => $tenant_id,
        ],
        'errors' => []]);
    }
    catch (\Exception $e) {
      return $this->errorResponse(
        $this->t('Error loading subscriptions.')->__toString(),
        500
      );
    }
  }

  /**
   * Serializa una entidad Addon a array para respuesta JSON.
   *
   * ESTRUCTURA: Método helper de serialización.
   * LÓGICA: Extrae los campos relevantes de la entidad Addon.
   * RELACIONES: Consume Addon entity.
   *
   * @param \Drupal\jaraba_addons\Entity\Addon $addon
   *   Entidad add-on a serializar.
   *
   * @return array
   *   Array asociativo con los datos del add-on.
   */
  protected function serializeAddon($addon): array {
    return [
      'id' => (int) $addon->id(),
      'label' => $addon->label(),
      'machine_name' => $addon->get('machine_name')->value ?? '',
      'description' => $addon->get('description')->value ?? '',
      'addon_type' => $addon->get('addon_type')->value ?? 'custom',
      'price_monthly' => (float) ($addon->get('price_monthly')->value ?? 0),
      'price_yearly' => (float) ($addon->get('price_yearly')->value ?? 0),
      'is_active' => $addon->isActive(),
      'features_included' => $addon->getFeaturesIncluded(),
      'limits' => $addon->getLimits(),
    ];
  }

  /**
   * Serializa una entidad AddonSubscription a array para respuesta JSON.
   *
   * ESTRUCTURA: Método helper de serialización.
   * LÓGICA: Extrae los campos relevantes de la suscripción e incluye
   *   el label del add-on asociado si está disponible.
   * RELACIONES: Consume AddonSubscription entity, Addon entity.
   *
   * @param \Drupal\jaraba_addons\Entity\AddonSubscription $subscription
   *   Entidad suscripción a serializar.
   *
   * @return array
   *   Array asociativo con los datos de la suscripción.
   */
  protected function serializeSubscription($subscription): array {
    $addon = $subscription->get('addon_id')->entity;

    return [
      'id' => (int) $subscription->id(),
      'addon_id' => (int) ($subscription->get('addon_id')->target_id ?? 0),
      'addon_label' => $addon ? $addon->label() : '',
      'tenant_id' => (int) ($subscription->get('tenant_id')->target_id ?? 0),
      'status' => $subscription->get('status')->value ?? 'pending',
      'billing_cycle' => $subscription->get('billing_cycle')->value ?? 'monthly',
      'start_date' => $subscription->get('start_date')->value ?? NULL,
      'end_date' => $subscription->get('end_date')->value ?? NULL,
      'price_paid' => (float) ($subscription->get('price_paid')->value ?? 0),
    ];
  }

  /**
   * Genera una respuesta JSON de error estandarizada.
   *
   * ESTRUCTURA: Método helper para respuestas de error.
   * LÓGICA: Construye la estructura { data: null, meta: {}, errors: [...] }.
   *
   * @param string $message
   *   Mensaje de error.
   * @param int $status_code
   *   Código de estado HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON de error.
   */
  protected function errorResponse(string $message, int $status_code): JsonResponse {
    return new JsonResponse([
      'data' => NULL,
      'meta' => [],
      'errors' => [
        [
          'status' => $status_code,
          'message' => $message,
        ],
      ],
    ], $status_code);
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
