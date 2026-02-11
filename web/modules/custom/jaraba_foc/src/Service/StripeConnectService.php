<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de integración con Stripe Connect.
 *
 * PROPÓSITO:
 * Gestiona la integración con Stripe Connect usando el modelo de
 * Destination Charges para split payments automáticos.
 *
 * MODELO DE CUENTAS:
 * ═══════════════════════════════════════════════════════════════════════════
 * Usamos Standard Accounts (decisión aprobada):
 * - Más control sobre el proceso de onboarding
 * - Los vendedores tienen su propio dashboard de Stripe
 * - La plataforma retiene application_fee (comisión configurable)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * FLUJO DE FONDOS (Destination Charges):
 * 1. Cliente paga €100 al vendedor
 * 2. Stripe cobra 2.9% + €0.25 (fees de Stripe)
 * 3. Plataforma retiene 5% (application_fee configurable)
 * 4. Vendedor recibe €94.76 (€100 - €2.65 Stripe - €5 comisión)
 *
 * @see https://stripe.com/docs/connect/destination-charges
 */
class StripeConnectService
{

    /**
     * URL base de la API de Stripe.
     */
    protected const STRIPE_API_BASE = 'https://api.stripe.com/v1';

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   El factory de configuración.
     * @param \GuzzleHttp\ClientInterface $httpClient
     *   El cliente HTTP.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger del módulo.
     */
    public function __construct(
        protected ConfigFactoryInterface $configFactory,
        protected ClientInterface $httpClient,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Obtiene la configuración de Stripe.
     *
     * @return \Drupal\Core\Config\ImmutableConfig
     *   La configuración inmutable.
     */
    protected function getConfig()
    {
        return $this->configFactory->get('jaraba_foc.settings');
    }

    /**
     * Obtiene la clave secreta de Stripe.
     *
     * Método público para permitir su uso por servicios de verticales
     * (AgroConecta, Mentoring, etc.) que necesitan realizar operaciones
     * Stripe específicas no cubiertas por los métodos de alto nivel.
     *
     * @return string|null
     *   La clave secreta o NULL si no está configurada.
     */
    public function getSecretKey(): ?string
    {
        return $this->getConfig()->get('stripe_secret_key');
    }

    /**
     * Obtiene el porcentaje de comisión de la plataforma.
     *
     * @return float
     *   Porcentaje de application_fee (ej: 5.0 para 5%).
     */
    protected function getPlatformFeePercent(): float
    {
        return (float) ($this->getConfig()->get('stripe_platform_fee') ?? 5.0);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ONBOARDING DE VENDEDORES (STANDARD ACCOUNTS)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Crea un enlace de onboarding para un nuevo vendedor.
     *
     * FLUJO:
     * 1. Crear cuenta Connected Account en Stripe (tipo standard)
     * 2. Generar Account Link para que el vendedor complete KYC
     * 3. Redirigir al vendedor a Stripe para verificación
     * 4. Stripe redirige de vuelta con estado de onboarding
     *
     * @param array $vendorData
     *   Datos del vendedor:
     *   - email: Email del vendedor
     *   - business_name: Nombre del negocio
     *   - tenant_id: ID del tenant al que pertenece
     *
     * @return array
     *   Array con 'account_id' y 'onboarding_url'.
     *
     * @throws \Exception
     *   Si falla la creación de la cuenta.
     */
    public function createVendorAccount(array $vendorData): array
    {
        $secretKey = $this->getSecretKey();
        if (!$secretKey) {
            throw new \Exception('Stripe secret key no configurada.');
        }

        try {
            // Paso 1: Crear Connected Account
            $accountResponse = $this->stripeRequest('POST', '/accounts', [
                'type' => 'standard',
                'email' => $vendorData['email'],
                'business_profile' => [
                    'name' => $vendorData['business_name'] ?? '',
                ],
                'metadata' => [
                    'tenant_id' => $vendorData['tenant_id'] ?? '',
                    'platform' => 'jaraba_impact',
                ],
            ]);

            $accountId = $accountResponse['id'];

            // Paso 2: Crear Account Link para onboarding
            $linkResponse = $this->stripeRequest('POST', '/account_links', [
                'account' => $accountId,
                'refresh_url' => $vendorData['refresh_url'] ?? $this->getDefaultRefreshUrl(),
                'return_url' => $vendorData['return_url'] ?? $this->getDefaultReturnUrl(),
                'type' => 'account_onboarding',
            ]);

            $this->logger->info('Cuenta Stripe Connect creada: @id para @email', [
                '@id' => $accountId,
                '@email' => $vendorData['email'],
            ]);

            return [
                'account_id' => $accountId,
                'onboarding_url' => $linkResponse['url'],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error creando cuenta Stripe Connect: @error', [
                '@error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verifica el estado de onboarding de un vendedor.
     *
     * @param string $accountId
     *   ID de la cuenta de Stripe Connect.
     *
     * @return array
     *   Estado de la cuenta con 'charges_enabled', 'payouts_enabled', etc.
     */
    public function getAccountStatus(string $accountId): array
    {
        try {
            $account = $this->stripeRequest('GET', '/accounts/' . $accountId);

            return [
                'charges_enabled' => $account['charges_enabled'] ?? FALSE,
                'payouts_enabled' => $account['payouts_enabled'] ?? FALSE,
                'details_submitted' => $account['details_submitted'] ?? FALSE,
                'requirements' => $account['requirements'] ?? [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error verificando cuenta @id: @error', [
                '@id' => $accountId,
                '@error' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DESTINATION CHARGES (PAGOS CON SPLIT)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Crea un PaymentIntent con Destination Charge.
     *
     * DESTINATION CHARGES:
     * El pago va directamente a la cuenta del vendedor, y la plataforma
     * retiene automáticamente el application_fee.
     *
     * @param int $amountCents
     *   Monto en centavos (ej: 1000 = €10.00).
     * @param string $currency
     *   Código ISO 4217 (ej: 'eur').
     * @param string $destinationAccountId
     *   ID de la cuenta Stripe Connect del vendedor.
     * @param array $metadata
     *   Metadatos opcionales (order_id, tenant_id, etc.).
     *
     * @return array
     *   Respuesta del PaymentIntent con 'client_secret' para frontend.
     */
    public function createDestinationCharge(
        int $amountCents,
        string $currency,
        string $destinationAccountId,
        array $metadata = []
    ): array {
        // Calcular application_fee (comisión de plataforma)
        $feePercent = $this->getPlatformFeePercent();
        $applicationFee = (int) round($amountCents * ($feePercent / 100));

        try {
            $paymentIntent = $this->stripeRequest('POST', '/payment_intents', [
                'amount' => $amountCents,
                'currency' => strtolower($currency),
                'application_fee_amount' => $applicationFee,
                'transfer_data' => [
                    'destination' => $destinationAccountId,
                ],
                'metadata' => array_merge($metadata, [
                    'platform_fee_percent' => $feePercent,
                    'platform' => 'jaraba_impact',
                ]),
            ]);

            $this->logger->info('PaymentIntent creado: @id, monto: @amount, fee: @fee', [
                '@id' => $paymentIntent['id'],
                '@amount' => $amountCents,
                '@fee' => $applicationFee,
            ]);

            return [
                'payment_intent_id' => $paymentIntent['id'],
                'client_secret' => $paymentIntent['client_secret'],
                'application_fee' => $applicationFee,
                'amount' => $amountCents,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error creando PaymentIntent: @error', [
                '@error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // WEBHOOKS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Verifica la firma del webhook de Stripe.
     *
     * @param string $payload
     *   El cuerpo raw del request.
     * @param string $sigHeader
     *   El header Stripe-Signature.
     *
     * @return bool
     *   TRUE si la firma es válida.
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): bool
    {
        $secret = $this->getConfig()->get('stripe_webhook_secret');
        if (!$secret) {
            $this->logger->warning('Webhook secret no configurado.');
            return FALSE;
        }

        // Extraer timestamp y firmas del header
        $elements = explode(',', $sigHeader);
        $timestamp = NULL;
        $signatures = [];

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if (!$timestamp || empty($signatures)) {
            return FALSE;
        }

        // Calcular firma esperada
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        // Comparar con firmas recibidas
        foreach ($signatures as $signature) {
            if (hash_equals($expectedSignature, $signature)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MÉTODOS UTILITARIOS (públicos para uso por verticales)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Realiza una petición a la API de Stripe.
     *
     * Método público para permitir operaciones Stripe específicas por vertical
     * que no están cubiertas por los métodos de alto nivel de este servicio.
     * Centraliza la autenticación, serialización y logging de errores.
     *
     * @param string $method
     *   Método HTTP (GET, POST, etc.).
     * @param string $endpoint
     *   Endpoint de la API (ej: '/accounts').
     * @param array $data
     *   Datos a enviar (para POST).
     *
     * @return array
     *   Respuesta decodificada de la API.
     */
    public function stripeRequest(string $method, string $endpoint, array $data = []): array
    {
        $secretKey = $this->getSecretKey();
        if (!$secretKey) {
            throw new \Exception('Stripe API key no configurada.');
        }

        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ];

        if (!empty($data)) {
            $options['form_params'] = $this->flattenArray($data);
        }

        $response = $this->httpClient->request(
            $method,
            self::STRIPE_API_BASE . $endpoint,
            $options
        );

        return json_decode($response->getBody()->getContents(), TRUE);
    }

    /**
     * Aplana un array anidado para form_params de Stripe.
     *
     * Stripe espera arrays en formato: key[nested]=value
     * Público para permitir a verticales construir payloads complejos
     * cuando necesitan operaciones Stripe no cubiertas por alto nivel.
     *
     * @param array $array
     *   Array a aplanar.
     * @param string $prefix
     *   Prefijo para claves anidadas.
     *
     * @return array
     *   Array aplanado.
     */
    public function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Obtiene la URL de refresh por defecto.
     */
    protected function getDefaultRefreshUrl(): string
    {
        return \Drupal::request()->getSchemeAndHttpHost() . '/admin/foc/vendor/onboarding/refresh';
    }

    /**
     * Obtiene la URL de retorno por defecto.
     */
    protected function getDefaultReturnUrl(): string
    {
        return \Drupal::request()->getSchemeAndHttpHost() . '/admin/foc/vendor/onboarding/complete';
    }

}
