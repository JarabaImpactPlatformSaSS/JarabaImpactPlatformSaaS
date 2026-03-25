<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Stripe\BillingPortal\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Subscription;
use Stripe\PaymentMethod;
use Stripe\Customer;
use Stripe\Stripe;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantManager;
use Drupal\ecosistema_jaraba_core\Service\TenantOnboardingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para la API de integración con Stripe.
 *
 * Este controlador gestiona los endpoints necesarios para:
 * - Crear sesiones de Stripe Checkout
 * - Crear suscripciones con Stripe Elements
 * - Confirmar pagos con autenticación 3D Secure
 * - Gestionar el portal de clientes de Stripe.
 *
 * Seguridad:
 * - Todos los endpoints requieren autenticación de usuario
 * - Se valida la propiedad del tenant antes de operaciones
 * - Los errores de Stripe se loguean para diagnóstico
 *
 * @see https://stripe.com/docs/api
 */
class StripeController extends ControllerBase {

  /**
   * El gestor de tenants.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantManager
   */
  protected TenantManager $tenantManager;

  /**
   * El servicio de onboarding.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantOnboardingService
   */
  protected TenantOnboardingService $onboardingService;

  /**
   * Canal de log.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->tenantManager = $container->get('ecosistema_jaraba_core.tenant_manager');
    $instance->onboardingService = $container->get('ecosistema_jaraba_core.tenant_onboarding');
    $instance->logger = $container->get('logger.channel.ecosistema_jaraba_core');
    return $instance;
  }

  /**
   * Crea una suscripción con el método de pago proporcionado.
   *
   * Este endpoint recibe un payment_method_id de Stripe Elements y crea
   * la suscripción para el tenant actual.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP con los datos de pago.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el resultado de la operación.
   */
  public function createSubscription(Request $request): JsonResponse {
    // Obtener tenant del usuario actual.
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'No tienes un tenant asociado.',
      ], 403);
    }

    // BE-11: Parsear y validar datos de la petición.
    $rawContent = $request->getContent();
    $data = json_decode($rawContent, TRUE);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'JSON inválido en la petición.',
      ], 400);
    }

    // Validar campos requeridos y tipos.
    if (empty($data['payment_method_id']) || !is_string($data['payment_method_id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'payment_method_id es requerido y debe ser texto.',
      ], 400);
    }

    if (empty($data['plan_id']) || !is_scalar($data['plan_id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'plan_id es requerido.',
      ], 400);
    }

    // Validar formato de payment_method_id (Stripe usa prefijo pm_).
    if (!preg_match('/^pm_[a-zA-Z0-9]+$/', $data['payment_method_id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Formato de payment_method_id inválido.',
      ], 400);
    }

    // SEC-03: Obtener clave Stripe priorizando variables de entorno.
    $secretKey = getenv('STRIPE_SECRET_KEY')
            ?: $this->config('ecosistema_jaraba_core.stripe')->get('secret_key');

    if (!$secretKey) {
      $this->logger->error('Stripe: Clave secreta no configurada. Definir STRIPE_SECRET_KEY como variable de entorno.');
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error de configuración de pagos.',
      ], 500);
    }

    try {
      // Inicializar cliente de Stripe.
      Stripe::setApiKey($secretKey);

      // Cargar el plan seleccionado.
      $plan = $this->entityTypeManager()
        ->getStorage('saas_plan')
        ->load($data['plan_id']);

      if (!$plan) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Plan no encontrado.',
        ], 404);
      }

      // Obtener el price_id de Stripe según el periodo de facturación.
      $priceId = $data['billing_period'] === 'yearly'
                ? $plan->get('stripe_price_yearly_id')->value
                : $plan->getStripePriceId();

      if (!$priceId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Plan no disponible para suscripción.',
        ], 400);
      }

      // Crear o recuperar el cliente de Stripe.
      $customerId = $tenant->get('stripe_customer_id')->value;

      if (!$customerId) {
        // AUDIT-PERF-007: Idempotency key para prevenir clientes duplicados.
        $customer = Customer::create([
          'email' => $this->currentUser()->getEmail(),
          'name' => $tenant->getName(),
          'metadata' => [
            'tenant_id' => $tenant->id(),
            'drupal_user_id' => $this->currentUser()->id(),
          ],
        ], [
          'idempotency_key' => 'cust_create_' . $tenant->id() . '_' . $this->currentUser()->id(),
        ]);
        $customerId = $customer->id;

        // Guardar ID de cliente en el tenant.
        $tenant->set('stripe_customer_id', $customerId);
        $tenant->save();
      }

      // Adjuntar método de pago al cliente.
      PaymentMethod::retrieve($data['payment_method_id'])->attach([
        'customer' => $customerId,
      ]);

      // Establecer como método de pago por defecto.
      Customer::update($customerId, [
        'invoice_settings' => [
          'default_payment_method' => $data['payment_method_id'],
        ],
      ]);

      // Crear la suscripción.
      $subscriptionParams = [
        'customer' => $customerId,
        'items' => [
                ['price' => $priceId],
        ],
        'default_payment_method' => $data['payment_method_id'],
        'expand' => ['latest_invoice.payment_intent'],
        'metadata' => [
          'tenant_id' => $tenant->id(),
          'plan_id' => $plan->id(),
        ],
      ];

      // Si el tenant está en trial, configurar trial_end.
      if ($tenant->isOnTrial() && $tenant->getTrialEndsAt()) {
        $trialEnd = strtotime($tenant->getTrialEndsAt());
        if ($trialEnd > time()) {
          $subscriptionParams['trial_end'] = $trialEnd;
        }
      }

      // AUDIT-PERF-007: Idempotency key para prevenir suscripciones duplicadas en double-click.
      $subscription = Subscription::create($subscriptionParams, [
        'idempotency_key' => sprintf(
                'sub_create_%d_%s_%s_%d',
                $tenant->id(),
                $data['plan_id'],
                $data['payment_method_id'],
                floor(time() / 30)
        ),
      ]);

      // Verificar si requiere autenticación 3D Secure.
      $paymentIntent = $subscription->latest_invoice->payment_intent;

      if ($paymentIntent && $paymentIntent->status === 'requires_action') {
        return new JsonResponse([
          'success' => TRUE,
          'requires_action' => TRUE,
          'client_secret' => $paymentIntent->client_secret,
          'subscription_id' => $subscription->id,
        ]);
      }

      // Suscripción creada exitosamente.
      $this->completeSubscriptionSetup($tenant, $subscription->id, $plan);

      $this->logger->info(
            '✅ Suscripción creada para tenant @tenant: @subscription',
            [
              '@tenant' => $tenant->getName(),
              '@subscription' => $subscription->id,
            ]
        );

      return new JsonResponse([
        'success' => TRUE,
        'subscription_id' => $subscription->id,
        'redirect' => '/onboarding/bienvenida',
      ]);

    }
    catch (CardException $e) {
      // Error de tarjeta (declinada, fondos insuficientes, etc.)
      $this->logger->warning(
            '⚠️ Stripe: Error de tarjeta para tenant @tenant: @error',
            [
              '@tenant' => $tenant->getName(),
              '@error' => $e->getMessage(),
            ]
            );

      return new JsonResponse([
        'success' => FALSE,
        'error' => $this->getCardErrorMessage($e->getDeclineCode()),
      ], 400);
    }
    catch (ApiErrorException $e) {
      // Error de API de Stripe.
      $this->logger->error(
            '🚫 Stripe: Error de API para tenant @tenant: @error',
            [
              '@tenant' => $tenant->getName(),
              '@error' => $e->getMessage(),
            ]
            );

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error al procesar el pago. Por favor, inténtalo de nuevo.',
      ], 500);
    }
    catch (\Exception $e) {
      $this->logger->error(
            '🚫 Error inesperado en Stripe: @error',
            ['@error' => $e->getMessage()]
            );

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error interno. Por favor, contacta con soporte.',
      ], 500);
    }
  }

  /**
   * Confirma una suscripción después de autenticación 3D Secure.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON.
   */
  public function confirmSubscription(Request $request): JsonResponse {
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Sin acceso.'], 403);
    }

    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['subscription_id'])) {
      return new JsonResponse(['success' => FALSE, 'error' => 'ID de suscripción requerido.'], 400);
    }

    try {
      // SEC-03: Priorizar variables de entorno para claves API.
      $secretKey = getenv('STRIPE_SECRET_KEY')
                ?: $this->config('ecosistema_jaraba_core.stripe')->get('secret_key');
      Stripe::setApiKey($secretKey);

      // Verificar estado de la suscripción.
      $subscription = Subscription::retrieve($data['subscription_id']);

      if ($subscription->status === 'active' || $subscription->status === 'trialing') {
        // Buscar el plan asociado.
        $plans = $this->entityTypeManager()
          ->getStorage('saas_plan')
          ->loadByProperties(['stripe_price_id' => $subscription->items->data[0]->price->id]);

        $plan = !empty($plans) ? reset($plans) : NULL;

        $this->completeSubscriptionSetup($tenant, $subscription->id, $plan);

        return new JsonResponse([
          'success' => TRUE,
          'redirect' => '/onboarding/bienvenida',
        ]);
      }

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'La suscripción no está activa.',
      ], 400);

    }
    catch (\Exception $e) {
      $this->logger->error(
            '🚫 Error confirmando suscripción: @error',
            ['@error' => $e->getMessage()]
            );

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error al confirmar la suscripción.',
      ], 500);
    }
  }

  /**
   * Crea una sesión del portal de clientes de Stripe.
   *
   * Permite al usuario gestionar su suscripción, cambiar tarjeta, etc.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   URL del portal de clientes.
   */
  public function createPortalSession(Request $request): JsonResponse {
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Sin acceso.'], 403);
    }

    $customerId = $tenant->get('stripe_customer_id')->value;

    if (!$customerId) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'No tienes una suscripción activa.',
      ], 400);
    }

    try {
      // SEC-03: Priorizar variables de entorno para claves API.
      $secretKey = getenv('STRIPE_SECRET_KEY')
                ?: $this->config('ecosistema_jaraba_core.stripe')->get('secret_key');
      Stripe::setApiKey($secretKey);

      $returnUrl = $request->getSchemeAndHttpHost() . '/admin/config/subscription';

      // AUDIT-PERF-007: Idempotency key para deduplicar portal sessions.
      $portalSession = Session::create([
        'customer' => $customerId,
        'return_url' => $returnUrl,
      ], [
        'idempotency_key' => sprintf('portal_%s_%d', $customerId, floor(time() / 60)),
      ]);

      return new JsonResponse([
        'success' => TRUE,
        'url' => $portalSession->url,
      ]);

    }
    catch (\Exception $e) {
      $this->logger->error(
            '🚫 Error creando portal session: @error',
            ['@error' => $e->getMessage()]
        );

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error al acceder al portal de pagos.',
      ], 500);
    }
  }

  /**
   * Completa la configuración de suscripción en el tenant.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   El tenant.
   * @param string $subscriptionId
   *   ID de la suscripción en Stripe.
   * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface|null $plan
   *   El plan asociado.
   */
  protected function completeSubscriptionSetup($tenant, string $subscriptionId, $plan = NULL): void {
    $tenant->set('stripe_subscription_id', $subscriptionId);

    if ($plan) {
      $tenant->set('subscription_plan', $plan->id());
    }

    // Si no está en trial, activar inmediatamente.
    if (!$tenant->isOnTrial()) {
      $this->tenantManager->activateSubscription($tenant, $subscriptionId);
    }

    $tenant->save();
  }

  /**
   * Traduce códigos de error de tarjeta a mensajes legibles.
   *
   * @param string|null $declineCode
   *   Código de rechazo de Stripe.
   *
   * @return string
   *   Mensaje legible en español.
   */
  protected function getCardErrorMessage(?string $declineCode): string {
    $messages = [
      'card_declined' => 'Tu tarjeta ha sido rechazada. Por favor, usa otra tarjeta.',
      'expired_card' => 'Tu tarjeta ha expirado. Por favor, usa otra tarjeta.',
      'incorrect_cvc' => 'El código de seguridad (CVC) es incorrecto.',
      'insufficient_funds' => 'Fondos insuficientes. Por favor, usa otra tarjeta.',
      'processing_error' => 'Error al procesar la tarjeta. Inténtalo de nuevo.',
      'incorrect_number' => 'El número de tarjeta es incorrecto.',
    ];

    return $messages[$declineCode] ?? 'Tu tarjeta ha sido rechazada. Por favor, verifica los datos o usa otra tarjeta.';
  }

  // =========================================================================
  // STRIPE CONNECT - MARKETPLACE SPLIT PAYMENTS
  // =========================================================================

  /**
   * Inicia el onboarding de Stripe Connect para el tenant actual.
   *
   * Crea una cuenta Express y genera el link de onboarding KYC.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   URL de redirección al onboarding de Stripe.
   */
  public function startConnectOnboarding(Request $request): JsonResponse {
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Sin acceso.'], 403);
    }

    // Si ya tiene cuenta Connect, generar nuevo link.
    $connectId = $tenant->get('stripe_connect_id')->value;

    try {
      $stripeConnect = \Drupal::service('ecosistema_jaraba_core.stripe_connect');

      // Si no tiene cuenta, crearla.
      if (!$connectId) {
        $adminUser = $tenant->getAdminUser();
        $email = $adminUser ? $adminUser->getEmail() : '';

        $connectId = $stripeConnect->createConnectedAccount($tenant, $email, 'ES');

        // Guardar el ID en el tenant.
        $tenant->set('stripe_connect_id', $connectId);
        $tenant->save();
      }

      // Generar link de onboarding.
      $baseUrl = $request->getSchemeAndHttpHost();
      $returnUrl = $baseUrl . '/stripe/connect/return';
      $refreshUrl = $baseUrl . '/stripe/connect/refresh';

      $onboardingUrl = $stripeConnect->createAccountLink($connectId, $returnUrl, $refreshUrl);

      $this->logger->info(
            '🔗 Stripe Connect: Onboarding iniciado para tenant @tenant',
            ['@tenant' => $tenant->getName()]
        );

      return new JsonResponse([
        'success' => TRUE,
        'redirect_url' => $onboardingUrl,
      ]);

    }
    catch (\Exception $e) {
      $this->logger->error(
            '🚫 Stripe Connect: Error en onboarding para tenant @tenant: @error',
            [
              '@tenant' => $tenant->getName(),
              '@error' => $e->getMessage(),
            ]
            );

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error al iniciar la configuración de pagos.',
      ], 500);
    }
  }

  /**
   * Callback tras completar el onboarding de Stripe Connect.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return array
   *   Render array con mensaje de éxito.
   */
  public function connectOnboardingReturn(Request $request): array {
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant || !$tenant->get('stripe_connect_id')->value) {
      return [
        '#markup' => '<p>Error: No se pudo verificar la configuración.</p>',
      ];
    }

    try {
      $stripeConnect = \Drupal::service('ecosistema_jaraba_core.stripe_connect');
      $status = $stripeConnect->getAccountStatus($tenant->get('stripe_connect_id')->value);

      if ($status['verified']) {
        $this->logger->info(
              '✅ Stripe Connect: Onboarding completado para tenant @tenant',
              ['@tenant' => $tenant->getName()]
          );

        return [
          '#theme' => 'status_messages',
          '#message_list' => [
            'status' => ['¡Tu cuenta de pagos está configurada correctamente! Ya puedes recibir pagos de tus clientes.'],
          ],
          '#status_headings' => [
            'status' => $this->t('¡Configuración Completada!'),
          ],
        ];
      }
      else {
        return [
          '#markup' => '<p>Tu configuración está pendiente de verificación. Por favor, completa todos los requisitos en Stripe.</p>',
        ];
      }
    }
    catch (\Exception $e) {
      // SEC-07: Nunca exponer mensajes internos de excepción al usuario.
      $this->logger->error(
            'Error verificando estado Stripe Connect: @error',
            ['@error' => $e->getMessage()]
            );
      return [
        '#markup' => '<p>Error al verificar el estado de tu cuenta. Por favor, inténtalo más tarde o contacta con soporte.</p>',
      ];
    }
  }

  /**
   * Página para reintentar onboarding si el link expiró.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   Redirección o mensaje de error.
   */
  public function connectOnboardingRefresh(Request $request) {
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant || !$tenant->get('stripe_connect_id')->value) {
      return [
        '#markup' => '<p>Error: No tienes una cuenta de pagos configurada.</p>',
      ];
    }

    try {
      $stripeConnect = \Drupal::service('ecosistema_jaraba_core.stripe_connect');

      $baseUrl = $request->getSchemeAndHttpHost();
      $returnUrl = $baseUrl . '/stripe/connect/return';
      $refreshUrl = $baseUrl . '/stripe/connect/refresh';

      $onboardingUrl = $stripeConnect->createAccountLink(
            $tenant->get('stripe_connect_id')->value,
            $returnUrl,
            $refreshUrl
        );

      return new RedirectResponse($onboardingUrl);

    }
    catch (\Exception $e) {
      return [
        '#markup' => '<p>Error al generar nuevo enlace: ' . $e->getMessage() . '</p>',
      ];
    }
  }

  /**
   * Obtiene el estado de la cuenta Connect del tenant actual.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Estado de la cuenta.
   */
  public function getConnectStatus(): JsonResponse {
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Sin acceso.'], 403);
    }

    $connectId = $tenant->get('stripe_connect_id')->value;

    if (!$connectId) {
      return new JsonResponse([
        'success' => TRUE,
        'has_account' => FALSE,
        'message' => 'No tienes una cuenta de pagos configurada.',
      ]);
    }

    try {
      $stripeConnect = \Drupal::service('ecosistema_jaraba_core.stripe_connect');
      $status = $stripeConnect->getAccountStatus($connectId);

      return new JsonResponse([
        'success' => TRUE,
        'has_account' => TRUE,
        'connect_id' => $connectId,
        'status' => $status,
      ]);

    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error al obtener estado: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Obtiene el enlace al dashboard de Stripe Express.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   URL del dashboard.
   */
  public function getConnectDashboard(): JsonResponse {
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Sin acceso.'], 403);
    }

    $connectId = $tenant->get('stripe_connect_id')->value;

    if (!$connectId) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'No tienes una cuenta de pagos configurada.',
      ], 400);
    }

    try {
      $stripeConnect = \Drupal::service('ecosistema_jaraba_core.stripe_connect');
      $dashboardUrl = $stripeConnect->createLoginLink($connectId);

      return new JsonResponse([
        'success' => TRUE,
        'dashboard_url' => $dashboardUrl,
      ]);

    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error al generar enlace al dashboard.',
      ], 500);
    }
  }

}
