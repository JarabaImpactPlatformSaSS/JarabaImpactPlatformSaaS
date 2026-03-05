<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\jaraba_billing\Service\CheckoutSessionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller para el flujo de Stripe Checkout embebido.
 *
 * Rutas:
 * - GET  /checkout/{saas_plan}   → checkoutPage()
 * - POST /api/v1/billing/checkout-session → createCheckoutSession()
 * - GET  /checkout/success       → checkoutSuccess()
 * - GET  /checkout/cancel        → checkoutCancel()
 *
 * CONTROLLER-READONLY-001: No usa readonly en promotion para $entityTypeManager.
 * ZERO-REGION-001/003: drupalSettings via #attached, no via preprocess.
 * ROUTE-LANGPREFIX-001: URLs via Url::fromRoute().
 *
 * STRIPE-CHECKOUT-001 §5.2 / §8.3
 */
class CheckoutController extends ControllerBase {

  /**
   * The checkout session service.
   */
  protected CheckoutSessionService $checkoutSession;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    // CONTROLLER-READONLY-001: asignar manualmente, no readonly promotion.
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->checkoutSession = $container->get('jaraba_billing.checkout_session');
    return $instance;
  }

  /**
   * Renderiza la pagina de checkout con Stripe Embedded Checkout.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $saas_plan
   *   El plan SaaS (resuelto via ParamConverter).
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La request HTTP.
   *
   * @return array
   *   Render array con template y drupalSettings.
   */
  public function checkoutPage(SaasPlanInterface $saas_plan, Request $request): array {
    $cycle = $request->query->get('cycle', 'monthly');
    if (!in_array($cycle, ['monthly', 'yearly'], TRUE)) {
      $cycle = 'monthly';
    }

    // Verificar que el plan tiene precio sincronizado con Stripe.
    $priceId = $cycle === 'yearly'
      ? $saas_plan->getStripePriceYearlyId()
      : $saas_plan->getStripePriceId();

    if (empty($priceId)) {
      throw new NotFoundHttpException();
    }

    // Precio a mostrar en el resumen.
    $price = $cycle === 'yearly'
      ? $saas_plan->getPriceYearly()
      : $saas_plan->getPriceMonthly();

    // Stripe public key desde config (STRIPE-ENV-UNIFY-001).
    $stripePublicKey = $this->config('ecosistema_jaraba_core.stripe')->get('public_key')
      ?: (getenv('STRIPE_PUBLIC_KEY') ?: '');

    return [
      '#theme' => 'checkout_page',
      '#plan' => [
        'id' => $saas_plan->id(),
        'name' => $saas_plan->getName(),
        'price' => $price,
        'cycle' => $cycle,
        'features' => $saas_plan->getFeatures(),
        'vertical' => $saas_plan->getVertical()?->label() ?? '',
        'is_yearly' => $cycle === 'yearly',
      ],
      '#attached' => [
        'library' => [
          'jaraba_billing/stripe-checkout',
        ],
        'drupalSettings' => [
          'stripeCheckout' => [
            'publicKey' => $stripePublicKey,
            'sessionUrl' => Url::fromRoute('jaraba_billing.checkout_session.create')->toString(),
            'planId' => $saas_plan->id(),
            'cycle' => $cycle,
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Crea una Checkout Session via API (llamado desde JS).
   *
   * POST /api/v1/billing/checkout-session
   * Body: {planId, cycle, email, businessName, vertical}
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La request HTTP con JSON body.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con {clientSecret, sessionId} o {error}.
   */
  public function createCheckoutSession(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    // Validar campos requeridos.
    $required = ['planId', 'cycle', 'email', 'businessName'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        return new JsonResponse([
          'error' => sprintf('Campo requerido: %s', $field),
        ], 400);
      }
    }

    // Cargar el plan.
    $plan = $this->entityTypeManager()
      ->getStorage('saas_plan')
      ->load($data['planId']);

    if (!$plan instanceof SaasPlanInterface || !$plan->get('status')->value) {
      return new JsonResponse(['error' => 'Plan no encontrado o inactivo.'], 404);
    }

    $cycle = in_array($data['cycle'], ['monthly', 'yearly'], TRUE)
      ? $data['cycle']
      : 'monthly';

    $vertical = $data['vertical'] ?? ($plan->getVertical()?->id() ?? '_default');

    try {
      $result = $this->checkoutSession->createSession(
        $plan,
        $cycle,
        $data['email'],
        $data['businessName'],
        $vertical,
        $data['customerId'] ?? NULL,
      );

      return new JsonResponse([
        'clientSecret' => $result['client_secret'],
        'sessionId' => $result['session_id'],
      ]);
    }
    catch (\Throwable $e) {
      $this->getLogger('jaraba_billing')->error(
        'Checkout session creation failed: @msg',
        ['@msg' => $e->getMessage()]
      );

      return new JsonResponse([
        'error' => $this->t('No se pudo crear la sesion de pago. Intentelo de nuevo.')->render(),
      ], 500);
    }
  }

  /**
   * Pagina de exito post-checkout.
   *
   * GET /checkout/success?session_id={CHECKOUT_SESSION_ID}
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La request HTTP.
   *
   * @return array
   *   Render array con template de exito.
   */
  public function checkoutSuccess(Request $request): array {
    $sessionId = $request->query->get('session_id', '');
    $sessionData = [];

    if ($sessionId) {
      try {
        $sessionData = $this->checkoutSession->getSession($sessionId);
      }
      catch (\Throwable $e) {
        $this->getLogger('jaraba_billing')->warning(
          'Could not retrieve checkout session @id: @msg',
          ['@id' => $sessionId, '@msg' => $e->getMessage()]
        );
      }
    }

    return [
      '#theme' => 'checkout_success',
      '#session' => [
        'status' => $sessionData['status'] ?? 'unknown',
        'customer_email' => $sessionData['customer_details']['email'] ?? '',
        'plan_name' => $sessionData['metadata']['drupal_plan_id'] ?? '',
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Pagina de cancelacion de checkout.
   *
   * GET /checkout/cancel
   *
   * @return array
   *   Render array con template de cancelacion.
   */
  public function checkoutCancel(): array {
    return [
      '#theme' => 'checkout_cancel',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Titulo dinamico para la pagina de checkout.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $saas_plan
   *   El plan SaaS.
   *
   * @return string
   *   El titulo de la pagina.
   */
  public function checkoutTitle(SaasPlanInterface $saas_plan): string {
    return $this->t('Contratar @plan', ['@plan' => $saas_plan->getName()])->render();
  }

}
