<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_foc\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para StripeConnectService.
 *
 * ESTRUCTURA:
 * Verifica la lógica del servicio centralizado de Stripe Connect
 * sin realizar llamadas HTTP reales (mocks de Guzzle).
 *
 * COBERTURA:
 * - Creación de cuentas vendedor (Standard accounts)
 * - Destination Charges con cálculo de comisiones
 * - Verificación de firmas webhook (HMAC-SHA256)
 * - Estado de onboarding de cuentas
 * - Manejo de errores y configuración faltante
 *
 * @group jaraba_foc
 * @coversDefaultClass \Drupal\jaraba_foc\Service\StripeConnectService
 */
class StripeConnectServiceTest extends UnitTestCase
{

    /**
     * El servicio bajo prueba.
     */
    protected StripeConnectService $service;

    /**
     * Mock del HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * Mock del config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Mock de la configuración inmutable.
     */
    protected ImmutableConfig $config;

    /**
     * Mock del logger.
     */
    protected LoggerInterface $logger;

    /**
     * Mock del entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ImmutableConfig::class);
        $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
        $this->configFactory->method('get')
            ->with('jaraba_foc.settings')
            ->willReturn($this->config);

        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new StripeConnectService(
            $this->configFactory,
            $this->httpClient,
            $this->entityTypeManager,
            $this->logger
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // TESTS: getSecretKey()
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Verifica que getSecretKey() devuelve la clave desde config.
     *
     * @covers ::getSecretKey
     */
    public function testGetSecretKeyReturnsConfiguredKey(): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_secret_key', 'sk_test_mock_secret_key'],
            ]);

        $result = $this->service->getSecretKey();
        $this->assertEquals('sk_test_mock_secret_key', $result);
    }

    /**
     * Verifica que getSecretKey() devuelve NULL si no está configurada.
     *
     * @covers ::getSecretKey
     */
    public function testGetSecretKeyReturnsNullWhenNotConfigured(): void
    {
        $this->config->method('get')
            ->willReturn(NULL);

        $result = $this->service->getSecretKey();
        $this->assertNull($result);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // TESTS: createVendorAccount()
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Verifica creación exitosa de cuenta Standard.
     *
     * @covers ::createVendorAccount
     */
    public function testCreateVendorAccountSuccess(): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_secret_key', 'sk_test_mock_key'],
            ]);

        // Mock: respuesta de creación de cuenta.
        $accountResponse = new Response(200, [], json_encode([
            'id' => 'acct_test_vendor',
            'type' => 'standard',
        ]));

        // Mock: respuesta de Account Link.
        $linkResponse = new Response(200, [], json_encode([
            'url' => 'https://connect.stripe.com/setup/s/test_link',
        ]));

        $this->httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($accountResponse, $linkResponse);

        $result = $this->service->createVendorAccount([
            'email' => 'vendor@test.com',
            'business_name' => 'Test Farm',
            'tenant_id' => '42',
            'refresh_url' => 'https://test.com/refresh',
            'return_url' => 'https://test.com/return',
        ]);

        $this->assertIsArray($result);
        $this->assertEquals('acct_test_vendor', $result['account_id']);
        $this->assertEquals('https://connect.stripe.com/setup/s/test_link', $result['onboarding_url']);
    }

    /**
     * Verifica que falla sin clave API configurada.
     *
     * @covers ::createVendorAccount
     */
    public function testCreateVendorAccountFailsWithoutApiKey(): void
    {
        $this->config->method('get')
            ->willReturn(NULL);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stripe secret key no configurada.');

        $this->service->createVendorAccount([
            'email' => 'vendor@test.com',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // TESTS: getAccountStatus()
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Verifica consulta de estado de cuenta completada.
     *
     * @covers ::getAccountStatus
     */
    public function testGetAccountStatusComplete(): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_secret_key', 'sk_test_mock_key'],
            ]);

        $response = new Response(200, [], json_encode([
            'charges_enabled' => TRUE,
            'payouts_enabled' => TRUE,
            'details_submitted' => TRUE,
            'requirements' => [],
        ]));

        $this->httpClient->method('request')
            ->willReturn($response);

        $status = $this->service->getAccountStatus('acct_test_123');

        $this->assertTrue($status['charges_enabled']);
        $this->assertTrue($status['payouts_enabled']);
        $this->assertTrue($status['details_submitted']);
    }

    /**
     * Verifica consulta de estado con onboarding pendiente.
     *
     * @covers ::getAccountStatus
     */
    public function testGetAccountStatusPending(): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_secret_key', 'sk_test_mock_key'],
            ]);

        $response = new Response(200, [], json_encode([
            'charges_enabled' => FALSE,
            'payouts_enabled' => FALSE,
            'details_submitted' => FALSE,
            'requirements' => ['currently_due' => ['individual.verification.document']],
        ]));

        $this->httpClient->method('request')
            ->willReturn($response);

        $status = $this->service->getAccountStatus('acct_test_pending');

        $this->assertFalse($status['charges_enabled']);
        $this->assertFalse($status['payouts_enabled']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // TESTS: createDestinationCharge()
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Verifica creación de Destination Charge con cálculo de comisión.
     *
     * @covers ::createDestinationCharge
     */
    public function testCreateDestinationChargeSuccess(): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_secret_key', 'sk_test_mock_key'],
                ['stripe_platform_fee', '5.0'],
            ]);

        $response = new Response(200, [], json_encode([
            'id' => 'pi_test_destination',
            'client_secret' => 'pi_test_destination_secret_abc',
        ]));

        $this->httpClient->method('request')
            ->willReturn($response);

        $result = $this->service->createDestinationCharge(
            10000,
            'eur',
            'acct_vendor_1',
            ['order_id' => '42']
        );

        $this->assertEquals('pi_test_destination', $result['payment_intent_id']);
        $this->assertEquals('pi_test_destination_secret_abc', $result['client_secret']);
        // 5% de 10000 = 500
        $this->assertEquals(500, $result['application_fee']);
        $this->assertEquals(10000, $result['amount']);
    }

    /**
     * Verifica cálculo de comisión personalizada.
     *
     * @dataProvider platformFeeDataProvider
     * @covers ::createDestinationCharge
     */
    public function testPlatformFeeCalculation(string $feePercent, int $amount, int $expectedFee): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_secret_key', 'sk_test_mock_key'],
                ['stripe_platform_fee', $feePercent],
            ]);

        $response = new Response(200, [], json_encode([
            'id' => 'pi_test',
            'client_secret' => 'secret',
        ]));

        $this->httpClient->method('request')
            ->willReturn($response);

        $result = $this->service->createDestinationCharge($amount, 'eur', 'acct_1');

        $this->assertEquals($expectedFee, $result['application_fee']);
    }

    /**
     * Data provider para cálculos de comisión.
     *
     * @return array
     *   Casos: [porcentaje_comision, monto_centimos, fee_esperada].
     */
    public static function platformFeeDataProvider(): array
    {
        return [
            'comisión estándar 5%' => ['5.0', 10000, 500],
            'comisión reducida 2.5%' => ['2.5', 10000, 250],
            'comisión alta 10%' => ['10.0', 10000, 1000],
            'comisión con redondeo' => ['7.5', 3333, 250],
            'monto pequeño' => ['5.0', 100, 5],
            'monto cero' => ['5.0', 0, 0],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // TESTS: verifyWebhookSignature()
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Verifica que una firma válida es aceptada.
     *
     * @covers ::verifyWebhookSignature
     */
    public function testVerifyWebhookSignatureValid(): void
    {
        $webhookSecret = 'whsec_test_secret_123';
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_webhook_secret', $webhookSecret],
            ]);

        $payload = '{"type": "payment_intent.succeeded"}';
        $timestamp = (string) time();
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSig = hash_hmac('sha256', $signedPayload, $webhookSecret);

        $sigHeader = 't=' . $timestamp . ',v1=' . $expectedSig;

        $result = $this->service->verifyWebhookSignature($payload, $sigHeader);
        $this->assertTrue($result);
    }

    /**
     * Verifica que una firma inválida es rechazada.
     *
     * @covers ::verifyWebhookSignature
     */
    public function testVerifyWebhookSignatureInvalid(): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_webhook_secret', 'whsec_test_secret_123'],
            ]);

        $payload = '{"type": "payment_intent.succeeded"}';
        $sigHeader = 't=' . time() . ',v1=invalid_signature_here';

        $result = $this->service->verifyWebhookSignature($payload, $sigHeader);
        $this->assertFalse($result);
    }

    /**
     * Verifica que falla sin webhook secret configurado.
     *
     * @covers ::verifyWebhookSignature
     */
    public function testVerifyWebhookSignatureNoSecret(): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_webhook_secret', NULL],
            ]);

        $result = $this->service->verifyWebhookSignature('payload', 't=123,v1=sig');
        $this->assertFalse($result);
    }

    /**
     * Verifica que falla con header malformado (sin timestamp).
     *
     * @covers ::verifyWebhookSignature
     */
    public function testVerifyWebhookSignatureMalformedHeader(): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_webhook_secret', 'whsec_test'],
            ]);

        $result = $this->service->verifyWebhookSignature('payload', 'invalid_header');
        $this->assertFalse($result);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // TESTS: stripeRequest()
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Verifica que stripeRequest() lanza excepción sin API key.
     *
     * @covers ::stripeRequest
     */
    public function testStripeRequestFailsWithoutApiKey(): void
    {
        $this->config->method('get')
            ->willReturn(NULL);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stripe API key no configurada.');

        $this->service->stripeRequest('GET', '/accounts/acct_test');
    }

    /**
     * Verifica que stripeRequest() hace GET correctamente.
     *
     * @covers ::stripeRequest
     */
    public function testStripeRequestGet(): void
    {
        $this->config->method('get')
            ->willReturnMap([
                ['stripe_secret_key', 'sk_test_mock_key'],
            ]);

        $response = new Response(200, [], json_encode([
            'id' => 'acct_test',
            'type' => 'standard',
        ]));

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.stripe.com/v1/accounts/acct_test',
                $this->callback(function ($options) {
                    return $options['headers']['Authorization'] === 'Bearer sk_test_mock_key';
                })
            )
            ->willReturn($response);

        $result = $this->service->stripeRequest('GET', '/accounts/acct_test');

        $this->assertEquals('acct_test', $result['id']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // TESTS: flattenArray()
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Verifica aplanamiento de arrays para Stripe form_params.
     *
     * @covers ::flattenArray
     */
    public function testFlattenArraySimple(): void
    {
        $result = $this->service->flattenArray([
            'amount' => 1000,
            'currency' => 'eur',
        ]);

        $this->assertEquals(['amount' => 1000, 'currency' => 'eur'], $result);
    }

    /**
     * Verifica aplanamiento de arrays anidados.
     *
     * @covers ::flattenArray
     */
    public function testFlattenArrayNested(): void
    {
        $result = $this->service->flattenArray([
            'transfer_data' => [
                'destination' => 'acct_test',
            ],
            'metadata' => [
                'order_id' => '42',
                'platform' => 'jaraba',
            ],
        ]);

        $this->assertEquals([
            'transfer_data[destination]' => 'acct_test',
            'metadata[order_id]' => '42',
            'metadata[platform]' => 'jaraba',
        ], $result);
    }

    /**
     * Verifica aplanamiento con múltiples niveles de anidación.
     *
     * @covers ::flattenArray
     */
    public function testFlattenArrayDeeplyNested(): void
    {
        $result = $this->service->flattenArray([
            'capabilities' => [
                'card_payments' => [
                    'requested' => 'true',
                ],
            ],
        ]);

        $this->assertEquals([
            'capabilities[card_payments][requested]' => 'true',
        ], $result);
    }

}
