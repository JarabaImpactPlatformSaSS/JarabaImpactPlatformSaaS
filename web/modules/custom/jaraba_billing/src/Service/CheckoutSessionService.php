<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Crea Stripe Checkout Sessions para suscripciones SaaS.
 *
 * Usa Stripe Embedded Checkout (ui_mode=embedded) para que el formulario
 * de pago se renderice dentro de la pagina del SaaS, no en redirect externo.
 *
 * Directrices aplicadas:
 * - OPTIONAL-CROSSMODULE-001: StripeConnectService como @?
 * - LOGGER-INJECT-001: LoggerInterface directo
 * - STRIPE-ENV-UNIFY-001: Keys via StripeConnectService
 * - ROUTE-LANGPREFIX-001: URLs generadas via Url::fromRoute()
 *
 * STRIPE-CHECKOUT-001 §5.1 / §8.2
 */
class CheckoutSessionService {

  /**
   * Dias de prueba por defecto para nuevas suscripciones.
   */
  protected const DEFAULT_TRIAL_DAYS = 14;

  public function __construct(
    protected ?StripeConnectService $stripeConnect,
    protected ?StripeCustomerService $stripeCustomer,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Crea una Stripe Checkout Session para suscripcion a un plan SaaS.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
   *   El plan a suscribir.
   * @param string $billingCycle
   *   'monthly' o 'yearly'.
   * @param string $customerEmail
   *   Email del prospecto.
   * @param string $businessName
   *   Nombre de la empresa (metadata para auto-provisioning).
   * @param string $vertical
   *   Machine name del vertical.
   * @param string|null $existingCustomerId
   *   Stripe Customer ID si ya existe (upgrade flow).
   *
   * @return array{client_secret: string, session_id: string}
   *   client_secret para Stripe.js initEmbeddedCheckout().
   *
   * @throws \RuntimeException
   *   Si el plan no tiene price_id sincronizado o Stripe no esta disponible.
   */
  public function createSession(
    SaasPlanInterface $plan,
    string $billingCycle,
    string $customerEmail,
    string $businessName,
    string $vertical,
    ?string $existingCustomerId = NULL,
  ): array {
    if (!$this->stripeConnect) {
      throw new \RuntimeException('StripeConnectService no disponible.');
    }

    // Resolver el price_id segun el ciclo de facturacion.
    $priceId = $billingCycle === 'yearly'
      ? $plan->getStripePriceYearlyId()
      : $plan->getStripePriceId();

    if (empty($priceId)) {
      throw new \RuntimeException(sprintf(
        'Plan "%s" no tiene stripe_price_id para ciclo %s. Ejecute drush stripe:sync-plans.',
        $plan->getName(),
        $billingCycle
      ));
    }

    // URL de retorno post-checkout (ROUTE-LANGPREFIX-001).
    $returnUrl = Url::fromRoute('jaraba_billing.checkout.success', [], [
      'absolute' => TRUE,
    ])->toString();

    // Parametros de la Checkout Session.
    // Stripe Embedded Checkout con Automatic Payment Methods (por defecto):
    // NO enviar payment_method_types — Stripe auto-detecta Apple Pay,
    // Google Pay, Link, SEPA y tarjeta según configuración del Dashboard
    // y capacidades del dispositivo del usuario.
    // Habilitar métodos en: Stripe Dashboard → Settings → Payment methods.
    $params = [
      'mode' => 'subscription',
      'ui_mode' => 'embedded',
      'line_items' => [
        [
          'price' => $priceId,
          'quantity' => 1,
        ],
      ],
      'return_url' => $returnUrl . '?session_id={CHECKOUT_SESSION_ID}',
      'subscription_data' => [
        'metadata' => [
          'drupal_plan_id' => (string) $plan->id(),
          'vertical' => $vertical,
          'business_name' => $businessName,
          'email' => $customerEmail,
          'billing_cycle' => $billingCycle,
        ],
      ],
      'metadata' => [
        'drupal_plan_id' => (string) $plan->id(),
        'vertical' => $vertical,
        'business_name' => $businessName,
        'source' => 'jaraba_saas',
      ],
    ];

    // Trial period.
    $trialDays = $this->getTrialDays();
    if ($trialDays > 0) {
      $params['subscription_data']['trial_period_days'] = $trialDays;
    }

    // Customer: usar existente o pre-rellenar email si disponible.
    if ($existingCustomerId) {
      $params['customer'] = $existingCustomerId;
    }
    elseif (!empty($customerEmail)) {
      $params['customer_email'] = $customerEmail;
    }

    // Permitir codigos promocionales.
    // Stripe API espera string 'true', no boolean (form_params encoding).
    $params['allow_promotion_codes'] = 'true';

    // Crear la session via Stripe API.
    $response = $this->stripeConnect->stripeRequest(
      'POST',
      '/checkout/sessions',
      $params
    );

    if (empty($response['client_secret'])) {
      throw new \RuntimeException('Stripe no devolvio client_secret en la Checkout Session.');
    }

    $this->logger->info(
      'Checkout session created for plan @plan (@cycle): session=@session',
      [
        '@plan' => $plan->getName(),
        '@cycle' => $billingCycle,
        '@session' => $response['id'] ?? 'unknown',
      ]
    );

    return [
      'client_secret' => $response['client_secret'],
      'session_id' => $response['id'] ?? '',
    ];
  }

  /**
   * Recupera el estado de una Checkout Session existente.
   *
   * Usado por /checkout/success para verificar el pago.
   *
   * @param string $sessionId
   *   El ID de la session (cs_xxx).
   *
   * @return array
   *   Datos de la session de Stripe.
   */
  public function getSession(string $sessionId): array {
    if (!$this->stripeConnect) {
      throw new \RuntimeException('StripeConnectService no disponible.');
    }

    return $this->stripeConnect->stripeRequest(
      'GET',
      '/checkout/sessions/' . $sessionId,
      ['expand' => ['subscription', 'customer']]
    );
  }

  /**
   * Obtiene los dias de trial configurados.
   *
   * @return int
   *   Numero de dias de trial (0 = sin trial).
   */
  protected function getTrialDays(): int {
    $config = $this->configFactory->get('ecosistema_jaraba_core.stripe');
    return (int) ($config->get('trial_days') ?? self::DEFAULT_TRIAL_DAYS);
  }

}
