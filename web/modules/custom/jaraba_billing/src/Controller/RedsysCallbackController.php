<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\jaraba_billing\Service\RedsysGatewayService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles Redsys/Bizum payment callbacks and initiation.
 *
 * Endpoints:
 * - notification(): POST server-to-server callback from Redsys.
 * - returnSuccess(): GET redirect after successful payment.
 * - returnFailure(): GET redirect after failed payment.
 * - initiateBizum(): POST from frontend to start a Bizum payment flow.
 *
 * CONTROLLER-READONLY-001: No readonly on inherited ControllerBase properties.
 */
class RedsysCallbackController extends ControllerBase {

  /**
   * The Redsys gateway service.
   */
  protected ?RedsysGatewayService $redsysGateway;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * The tenant subscription service (optional).
   */
  protected ?TenantSubscriptionService $tenantSubscription;

  /**
   * Constructs a RedsysCallbackController.
   *
   * CONTROLLER-READONLY-001: No readonly on inherited ControllerBase properties.
   *
   * @param \Drupal\jaraba_billing\Service\RedsysGatewayService|null $redsysGateway
   *   The Redsys gateway service (optional, may not be configured).
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\jaraba_billing\Service\TenantSubscriptionService|null $tenantSubscription
   *   The tenant subscription service (optional — OPTIONAL-CROSSMODULE-001).
   */
  public function __construct(
    ?RedsysGatewayService $redsysGateway,
    LoggerInterface $logger,
    ?TenantSubscriptionService $tenantSubscription = NULL,
  ) {
    $this->redsysGateway = $redsysGateway;
    $this->logger = $logger;
    $this->tenantSubscription = $tenantSubscription;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $redsysGateway = NULL;
    try {
      if ($container->has('jaraba_billing.redsys_gateway')) {
        $redsysGateway = $container->get('jaraba_billing.redsys_gateway');
      }
    }
    catch (\Throwable $e) {
      // Service not available — continue without it.
    }

    // OPTIONAL-CROSSMODULE-001: TenantSubscriptionService es interno al módulo.
    $tenantSubscription = NULL;
    try {
      if ($container->has('jaraba_billing.tenant_subscription')) {
        $tenantSubscription = $container->get('jaraba_billing.tenant_subscription');
      }
    }
    catch (\Throwable) {
      // Service not available in test environments.
    }

    return new static(
      $redsysGateway,
      $container->get('logger.channel.jaraba_billing'),
      $tenantSubscription,
    );
  }

  /**
   * POST: Asynchronous notification from Redsys (server-to-server).
   *
   * This endpoint has _access: 'TRUE' because Redsys calls it directly
   * without authentication. Security is enforced via HMAC signature
   * verification (AUDIT-SEC-001).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   'OK' (200) if valid, 'KO' (400) if invalid.
   */
  public function notification(Request $request): Response {
    if ($this->redsysGateway === NULL) {
      $this->logger->error('Redsys notification received but RedsysGatewayService is not available.');
      return new Response('KO', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    $dsSignatureVersion = $request->request->get('Ds_SignatureVersion', '');
    $dsMerchantParameters = $request->request->get('Ds_MerchantParameters', '');
    $dsSignature = $request->request->get('Ds_Signature', '');

    if ($dsMerchantParameters === '' || $dsSignature === '') {
      $this->logger->warning('Redsys notification: missing required POST parameters.');
      return new Response('KO', Response::HTTP_BAD_REQUEST);
    }

    $decoded = $this->redsysGateway->verifyNotification(
      (string) $dsSignatureVersion,
      (string) $dsMerchantParameters,
      (string) $dsSignature,
    );

    if ($decoded === NULL) {
      $this->logger->warning('Redsys notification: signature verification failed.');
      return new Response('KO', Response::HTTP_BAD_REQUEST);
    }

    // Extract response code. Codes 0000-0099 = approved.
    $responseCode = (int) ($decoded['Ds_Response'] ?? 9999);
    $orderNumber = $decoded['Ds_Order'] ?? 'unknown';
    $amount = $decoded['Ds_Amount'] ?? '0';

    if ($responseCode >= 0 && $responseCode <= 99) {
      $this->logger->info('Redsys payment approved: order=@order, amount=@amount, response=@response', [
        '@order' => $orderNumber,
        '@amount' => $amount,
        '@response' => $responseCode,
      ]);

      // Process successful Bizum payment: activate subscription or record payment.
      // Order format: "plan-{saas_plan_id}-{timestamp}" (from bizum-checkout.js).
      if ($this->tenantSubscription !== NULL && str_starts_with($orderNumber, 'plan-')) {
        try {
          $parts = explode('-', $orderNumber);
          $planId = $parts[1] ?? NULL;

          if ($planId !== NULL && $planId !== '') {
            // Resolve tenant from Ds_Merchant_MerchantData if available,
            // or from session context for server-to-server callbacks.
            $tenantId = $decoded['Ds_Merchant_MerchantData'] ?? NULL;

            $this->logger->info('Bizum payment: activating plan @plan for tenant @tenant (order @order)', [
              '@plan' => $planId,
              '@tenant' => $tenantId ?? 'unknown',
              '@order' => $orderNumber,
            ]);

            // Dispatch event for downstream processing (ECA, webhooks, etc.).
            if (\Drupal::hasService('event_dispatcher')) {
              /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher */
              $dispatcher = \Drupal::service('event_dispatcher');
              $event = new \Symfony\Component\EventDispatcher\GenericEvent($orderNumber, [
                'plan_id' => $planId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'payment_method' => 'bizum',
                'redsys_order' => $orderNumber,
                'redsys_response' => $responseCode,
              ]);
              $dispatcher->dispatch($event, 'jaraba_billing.payment_completed');
            }
          }
        }
        catch (\Throwable $e) {
          // PRESAVE-RESILIENCE-001: Log but don't break the OK response to Redsys.
          $this->logger->error('Bizum post-payment processing failed: @error', [
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }
    else {
      $this->logger->warning('Redsys payment declined: order=@order, response=@response', [
        '@order' => $orderNumber,
        '@response' => $responseCode,
      ]);
    }

    return new Response('OK', Response::HTTP_OK);
  }

  /**
   * GET: User redirect after successful payment at Redsys.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the billing confirmation page.
   */
  public function returnSuccess(Request $request): RedirectResponse {
    $dsOrder = $request->query->get('Ds_Order', '');

    $this->messenger()->addStatus($this->t('Tu pago se ha procesado correctamente. Referencia: @ref', [
      '@ref' => $dsOrder ?: 'N/A',
    ]));

    // ROUTE-LANGPREFIX-001: Always use Url::fromRoute().
    $url = Url::fromRoute('jaraba_billing.financial_dashboard')->toString();

    return new RedirectResponse($url);
  }

  /**
   * GET: User redirect after failed payment at Redsys.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the billing dashboard with error message.
   */
  public function returnFailure(Request $request): RedirectResponse {
    $dsResponse = $request->query->get('Ds_Response', '');

    $this->messenger()->addError($this->t('El pago no se ha podido completar. Código de error: @code. Inténtalo de nuevo o elige otro método de pago.', [
      '@code' => $dsResponse ?: 'desconocido',
    ]));

    // ROUTE-LANGPREFIX-001: Always use Url::fromRoute().
    $url = Url::fromRoute('jaraba_billing.financial_dashboard')->toString();

    return new RedirectResponse($url);
  }

  /**
   * POST: Initiate a Bizum payment from the frontend.
   *
   * Receives amount and order reference, returns Redsys form data that the
   * frontend uses to redirect the user to Redsys.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request with JSON body: {amount, order_ref, plan_id}.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON with form_url, ds_signature_version, ds_merchant_parameters,
   *   ds_signature, or error response.
   */
  public function initiateBizum(Request $request): JsonResponse {
    if ($this->redsysGateway === NULL) {
      return new JsonResponse(['error' => 'Bizum no está configurado.'], Response::HTTP_SERVICE_UNAVAILABLE);
    }

    try {
      $content = $request->getContent();
      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['error' => 'JSON inválido.'], Response::HTTP_BAD_REQUEST);
    }

    $amount = (float) ($data['amount'] ?? 0);
    $orderRef = (string) ($data['order_ref'] ?? '');

    if ($amount <= 0 || $orderRef === '') {
      return new JsonResponse(['error' => 'Faltan parámetros: amount y order_ref son obligatorios.'], Response::HTTP_BAD_REQUEST);
    }

    // ROUTE-LANGPREFIX-001: Build callback URLs via Url::fromRoute().
    $notificationUrl = Url::fromRoute('jaraba_billing.redsys.notification', [], ['absolute' => TRUE])->toString();
    $returnUrl = Url::fromRoute('jaraba_billing.redsys.return_success', [], ['absolute' => TRUE])->toString();

    try {
      $paymentData = $this->redsysGateway->initiatePayment(
        $amount,
        $orderRef,
        $notificationUrl,
        $returnUrl,
        'bizum',
      );

      return new JsonResponse($paymentData);
    }
    catch (\Throwable $e) {
      $this->logger->error('Bizum initiation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Error al iniciar el pago Bizum.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
