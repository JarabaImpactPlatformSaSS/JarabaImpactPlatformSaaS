<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Servicio de integración con Stripe Connect para split payments.
 *
 * PROPÓSITO:
 * Este servicio gestiona la integración con Stripe Connect para permitir
 * pagos divididos entre la plataforma y los tenants (productores).
 *
 * ARQUITECTURA:
 * - Usa Destination Charges: el pago va directamente al tenant conectado
 * - La plataforma cobra una Application Fee (comisión)
 * - Stripe gestiona automáticamente las transferencias
 *
 * FLUJO:
 * 1. Tenant se registra → Se crea cuenta Connect Express
 * 2. Tenant completa onboarding KYC → Cuenta verificada
 * 3. Cliente compra → Pago va al tenant, comisión a la plataforma
 *
 * @see https://stripe.com/docs/connect
 */
class JarabaStripeConnect
{

    /**
     * Configuración de Stripe.
     *
     * @var \Drupal\Core\Config\ImmutableConfig
     */
    protected $stripeConfig;

    /**
     * Canal de log.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected LoggerChannelInterface $logger;

    /**
     * Constructor del servicio.
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        LoggerChannelInterface $logger
    ) {
        $this->stripeConfig = $configFactory->get('ecosistema_jaraba_core.stripe');
        $this->logger = $logger;
    }

    /**
     * Inicializa el cliente de Stripe con las credenciales configuradas.
     *
     * @throws \Exception
     *   Si las claves de Stripe no están configuradas.
     */
    protected function initStripe(): void
    {
        $secretKey = $this->stripeConfig->get('secret_key');
        if (!$secretKey) {
            throw new \Exception('Stripe secret key not configured');
        }
        \Stripe\Stripe::setApiKey($secretKey);
    }

    /**
     * Crea una cuenta Connect Express para un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant para el que crear la cuenta.
     * @param string $email
     *   Email del responsable de la cuenta.
     * @param string $country
     *   Código de país ISO 3166-1 alpha-2 (ej: ES, MX).
     *
     * @return string
     *   El ID de la cuenta creada (acct_XXXXX).
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createConnectedAccount(TenantInterface $tenant, string $email, string $country = 'ES'): string
    {
        $this->initStripe();

        $account = \Stripe\Account::create([
            'type' => 'express',
            'country' => $country,
            'email' => $email,
            'capabilities' => [
                'card_payments' => ['requested' => TRUE],
                'transfers' => ['requested' => TRUE],
            ],
            'business_type' => 'individual',
            'metadata' => [
                'tenant_id' => $tenant->id(),
                'tenant_name' => $tenant->getname(),
                'platform' => 'jaraba_impact',
            ],
        ]);

        $this->logger->info(
            '✅ Stripe Connect: Cuenta creada para tenant @tenant: @account',
            [
                '@tenant' => $tenant->getName(),
                '@account' => $account->id,
            ]
        );

        return $account->id;
    }

    /**
     * Genera un link de onboarding para que el tenant complete KYC.
     *
     * @param string $accountId
     *   El ID de la cuenta Connect (acct_XXXXX).
     * @param string $returnUrl
     *   URL a la que redirigir tras completar.
     * @param string $refreshUrl
     *   URL para reintentar si el link expira.
     *
     * @return string
     *   La URL del onboarding de Stripe.
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createAccountLink(string $accountId, string $returnUrl, string $refreshUrl): string
    {
        $this->initStripe();

        $accountLink = \Stripe\AccountLink::create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        return $accountLink->url;
    }

    /**
     * Verifica el estado de una cuenta Connect.
     *
     * @param string $accountId
     *   El ID de la cuenta Connect.
     *
     * @return array
     *   Array con información del estado:
     *   - 'verified': bool - Si la cuenta puede recibir pagos
     *   - 'payouts_enabled': bool - Si puede recibir transferencias
     *   - 'details_submitted': bool - Si completó el onboarding
     *   - 'requirements': array - Requisitos pendientes
     */
    public function getAccountStatus(string $accountId): array
    {
        $this->initStripe();

        $account = \Stripe\Account::retrieve($accountId);

        return [
            'verified' => $account->charges_enabled && $account->payouts_enabled,
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
            'details_submitted' => $account->details_submitted,
            'requirements' => $account->requirements->currently_due ?? [],
        ];
    }

    /**
     * Procesa un pago con split automático plataforma/tenant.
     *
     * Usa Destination Charges: el pago va al tenant y la plataforma
     * cobra una comisión (Application Fee).
     *
     * @param int $amount
     *   Monto en céntimos (ej: 1000 = 10.00€).
     * @param string $currency
     *   Código de moneda ISO (ej: eur, usd).
     * @param string $tenantStripeId
     *   El ID de cuenta Connect del tenant (acct_XXXXX).
     * @param string $paymentMethodId
     *   El ID del método de pago del cliente.
     * @param string $customerId
     *   El ID del cliente en Stripe.
     * @param int $platformFeePercent
     *   Porcentaje de comisión de la plataforma (5 = 5%).
     * @param array $metadata
     *   Metadatos adicionales para el pago.
     *
     * @return \Stripe\PaymentIntent
     *   El PaymentIntent creado.
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function processPaymentWithSplit(
        int $amount,
        string $currency,
        string $tenantStripeId,
        string $paymentMethodId,
        string $customerId,
        int $platformFeePercent = 5,
        array $metadata = []
    ): \Stripe\PaymentIntent {
        $this->initStripe();

        // Calcular la comisión de la plataforma
        $applicationFee = (int) round($amount * ($platformFeePercent / 100));

        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customerId,
            'payment_method' => $paymentMethodId,
            'confirm' => TRUE,
            'application_fee_amount' => $applicationFee,
            'transfer_data' => [
                'destination' => $tenantStripeId,
            ],
            'metadata' => array_merge($metadata, [
                'platform_fee_percent' => $platformFeePercent,
                'tenant_stripe_id' => $tenantStripeId,
            ]),
        ]);

        $this->logger->info(
            '💰 Stripe Connect: Pago procesado - Total: @amount, Fee: @fee, Tenant: @tenant',
            [
                '@amount' => $amount / 100,
                '@fee' => $applicationFee / 100,
                '@tenant' => $tenantStripeId,
            ]
        );

        return $paymentIntent;
    }

    /**
     * Crea una sesión de Checkout con split para Commerce.
     *
     * @param int $amount
     *   Monto en céntimos.
     * @param string $currency
     *   Código de moneda.
     * @param string $tenantStripeId
     *   ID de cuenta Connect del tenant.
     * @param string $successUrl
     *   URL de redirección tras pago exitoso.
     * @param string $cancelUrl
     *   URL de redirección si cancela.
     * @param int $platformFeePercent
     *   Porcentaje de comisión.
     * @param array $lineItems
     *   Items del carrito para mostrar en Checkout.
     *
     * @return \Stripe\Checkout\Session
     *   La sesión de Checkout creada.
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCheckoutSessionWithSplit(
        int $amount,
        string $currency,
        string $tenantStripeId,
        string $successUrl,
        string $cancelUrl,
        int $platformFeePercent = 5,
        array $lineItems = []
    ): \Stripe\Checkout\Session {
        $this->initStripe();

        $applicationFee = (int) round($amount * ($platformFeePercent / 100));

        $sessionParams = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'payment_intent_data' => [
                'application_fee_amount' => $applicationFee,
                'transfer_data' => [
                    'destination' => $tenantStripeId,
                ],
            ],
        ];

        // Si hay line items, usarlos; si no, usar amount directo
        if (!empty($lineItems)) {
            $sessionParams['line_items'] = $lineItems;
        } else {
            $sessionParams['line_items'] = [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Compra en Jaraba Impact',
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ],
            ];
        }

        return \Stripe\Checkout\Session::create($sessionParams);
    }

    /**
     * Obtiene el enlace al dashboard de Stripe Express para un tenant.
     *
     * @param string $accountId
     *   El ID de cuenta Connect.
     *
     * @return string
     *   La URL del dashboard.
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createLoginLink(string $accountId): string
    {
        $this->initStripe();

        $loginLink = \Stripe\Account::createLoginLink($accountId);

        return $loginLink->url;
    }

}
