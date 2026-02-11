<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Service;

use Drupal\jaraba_foc\Service\StripeConnectService as FocStripeConnect;
use Psr\Log\LoggerInterface;

/**
 * Servicio de pagos Stripe Connect para Mentoring.
 *
 * ESTRUCTURA:
 * Wrapper sobre jaraba_foc.stripe_connect que adapta la API centralizada
 * para las necesidades específicas del marketplace de mentorías.
 *
 * LÓGICA:
 * El módulo de mentoring usa Express Accounts (onboarding simplificado
 * gestionado por Stripe) mientras que FOC define Standard Accounts.
 * Este wrapper pasa el tipo correcto al crear cuentas y calcula
 * la comisión específica del vertical de mentorías.
 *
 * CONSOLIDACIÓN (v2.2.0):
 * Antes de esta versión, este servicio duplicaba toda la lógica HTTP
 * de comunicación con Stripe. Ahora delega en jaraba_foc.stripe_connect
 * para centralizar autenticación, serialización y manejo de errores.
 *
 * @see \Drupal\jaraba_foc\Service\StripeConnectService
 */
class StripeConnectService
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\jaraba_foc\Service\StripeConnectService $focStripe
     *   Servicio centralizado de Stripe Connect (jaraba_foc).
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger del módulo de mentoring.
     */
    public function __construct(
        protected FocStripeConnect $focStripe,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Crea una cuenta Express de Stripe Connect para un mentor.
     *
     * DIFERENCIA CON FOC:
     * FOC crea Standard accounts (dashboard propio de Stripe).
     * Mentoring usa Express accounts (onboarding simplificado,
     * la plataforma controla la experiencia).
     *
     * @param string $email
     *   Email del mentor.
     * @param string $country
     *   Código de país ISO de dos letras (ej: 'ES', 'PT').
     *
     * @return array|null
     *   Datos de la cuenta de Stripe o NULL si falla.
     */
    public function createConnectedAccount(string $email, string $country = 'ES'): ?array
    {
        try {
            $data = $this->focStripe->stripeRequest('POST', '/accounts', [
                'type' => 'express',
                'country' => $country,
                'email' => $email,
                'capabilities' => [
                    'card_payments' => ['requested' => 'true'],
                    'transfers' => ['requested' => 'true'],
                ],
            ]);

            $this->logger->info('Cuenta Stripe Express creada para mentor: @id', [
                '@id' => $data['id'] ?? 'desconocido',
            ]);

            return $data;
        }
        catch (\Exception $e) {
            $this->logger->error('Error al crear cuenta Stripe para mentor: @error', [
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Genera un enlace de onboarding para el mentor.
     *
     * @param string $accountId
     *   ID de la cuenta Stripe Connect.
     * @param string $returnUrl
     *   URL de retorno tras completar el onboarding.
     * @param string $refreshUrl
     *   URL si el enlace expira.
     *
     * @return string|null
     *   URL de onboarding o NULL si falla.
     */
    public function generateOnboardingLink(string $accountId, string $returnUrl, string $refreshUrl): ?string
    {
        try {
            $data = $this->focStripe->stripeRequest('POST', '/account_links', [
                'account' => $accountId,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
            ]);

            return $data['url'] ?? NULL;
        }
        catch (\Exception $e) {
            $this->logger->error('Error al generar enlace de onboarding: @error', [
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Procesa un pago con Destination Charges para una sesión de mentoría.
     *
     * @param int $amount
     *   Importe en céntimos.
     * @param string $currency
     *   Código de moneda (ej: 'eur').
     * @param string $destinationAccount
     *   ID de la cuenta Stripe del mentor.
     * @param int $platformFee
     *   Comisión de la plataforma en céntimos.
     * @param string $paymentMethod
     *   ID del método de pago de Stripe.
     * @param array $metadata
     *   Metadatos adicionales para el pago.
     *
     * @return array|null
     *   Datos del PaymentIntent o NULL si falla.
     */
    public function processPayment(
        int $amount,
        string $currency,
        string $destinationAccount,
        int $platformFee,
        string $paymentMethod,
        array $metadata = []
    ): ?array {
        try {
            $data = $this->focStripe->stripeRequest('POST', '/payment_intents', [
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'confirm' => 'true',
                'transfer_data' => [
                    'destination' => $destinationAccount,
                ],
                'application_fee_amount' => $platformFee,
                'metadata' => $metadata,
            ]);

            $this->logger->info('Pago de mentoría procesado: @id por @amount céntimos.', [
                '@id' => $data['id'] ?? 'desconocido',
                '@amount' => $amount,
            ]);

            return $data;
        }
        catch (\Exception $e) {
            $this->logger->error('Error al procesar pago de mentoría: @error', [
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Crea un PaymentIntent sin confirmar (para completar en frontend).
     *
     * PROPÓSITO:
     * PackageApiController necesita un PaymentIntent con client_secret
     * para que el frontend complete el pago con Stripe Elements.
     *
     * @param int $amount
     *   Importe en céntimos.
     * @param string $currency
     *   Código de moneda (ej: 'eur').
     * @param string $destinationAccount
     *   ID de la cuenta Stripe Connect del mentor.
     * @param float $applicationFeePercent
     *   Porcentaje de comisión de la plataforma.
     * @param array $metadata
     *   Metadatos adicionales.
     *
     * @return array
     *   Datos del PaymentIntent: id, client_secret, amount.
     *
     * @throws \Exception
     *   Si falla la creación del PaymentIntent.
     */
    public function createPaymentIntent(
        int $amount,
        string $currency,
        string $destinationAccount,
        float $applicationFeePercent,
        array $metadata = []
    ): array {
        $applicationFee = (int) round($amount * ($applicationFeePercent / 100));

        $data = $this->focStripe->stripeRequest('POST', '/payment_intents', [
            'amount' => $amount,
            'currency' => strtolower($currency),
            'transfer_data' => [
                'destination' => $destinationAccount,
            ],
            'application_fee_amount' => $applicationFee,
            'metadata' => array_merge($metadata, [
                'platform' => 'jaraba_mentoring',
                'platform_fee_percent' => $applicationFeePercent,
            ]),
        ]);

        $this->logger->info('PaymentIntent de mentoría creado: @id, monto: @amount.', [
            '@id' => $data['id'] ?? 'desconocido',
            '@amount' => $amount,
        ]);

        return [
            'id' => $data['id'],
            'client_secret' => $data['client_secret'] ?? '',
            'amount' => $amount,
            'application_fee' => $applicationFee,
        ];
    }

    /**
     * Verifica si el mentor ha completado el onboarding de Stripe.
     *
     * @param string $accountId
     *   ID de la cuenta Stripe.
     *
     * @return bool
     *   TRUE si el onboarding está completo (puede cobrar y recibir pagos).
     */
    public function isOnboardingComplete(string $accountId): bool
    {
        try {
            $status = $this->focStripe->getAccountStatus($accountId);

            return ($status['charges_enabled'] ?? FALSE)
                && ($status['payouts_enabled'] ?? FALSE);
        }
        catch (\Exception $e) {
            $this->logger->error('Error al verificar estado de cuenta de mentor: @error', [
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

}
