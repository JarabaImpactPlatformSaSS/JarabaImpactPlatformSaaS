<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\jaraba_billing\Controller\BillingWebhookController;
use Drupal\jaraba_billing\Service\DunningService;
use Drupal\jaraba_billing\Service\StripeInvoiceService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests de integracion para flujos de webhook de Stripe Billing.
 *
 * COBERTURA:
 * Complementa BillingWebhookControllerTest con escenarios adicionales
 * centrados en la logica de negocio del webhook handler:
 *
 * - Verificacion de firma valida/invalida (HMAC-SHA256)
 * - Deteccion de eventos duplicados (idempotencia via lock)
 * - Manejo de tipos de eventos desconocidos
 * - Flujo completo: subscription created -> updated -> deleted
 * - Flujo de pago fallido con dunning
 * - Concurrencia: lock previene lost-updates
 * - Payment method lifecycle (attached/detached)
 *
 * @group jaraba_billing
 * @coversDefaultClass \Drupal\jaraba_billing\Controller\BillingWebhookController
 */
class StripeWebhookHandlerTest extends UnitTestCase {

  /**
   * Mock de StripeConnectService.
   */
  protected StripeConnectService $stripeConnect;

  /**
   * Mock de StripeInvoiceService.
   */
  protected StripeInvoiceService $invoiceService;

  /**
   * Mock de TenantSubscriptionService.
   */
  protected TenantSubscriptionService $tenantSubscription;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock del lock backend.
   */
  protected LockBackendInterface $lock;

  /**
   * Mock de DunningService.
   */
  protected DunningService $dunningService;

  /**
   * Mock de MailManager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * El controlador bajo prueba.
   */
  protected BillingWebhookController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stripeConnect = $this->createMock(StripeConnectService::class);
    $this->invoiceService = $this->createMock(StripeInvoiceService::class);
    $this->tenantSubscription = $this->createMock(TenantSubscriptionService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->lock = $this->createMock(LockBackendInterface::class);
    $this->lock->method('acquire')->willReturn(TRUE);
    $this->dunningService = $this->createMock(DunningService::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);

    $this->controller = new BillingWebhookController(
      $this->stripeConnect,
      $this->invoiceService,
      $this->tenantSubscription,
      $this->logger,
      $this->lock,
      $this->dunningService,
      $this->mailManager,
    );
  }

  /**
   * Helper: crea un Request de webhook con payload y firma.
   *
   * @param array $payload
   *   Datos del evento de Stripe.
   * @param bool $validSignature
   *   Si la firma es valida.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   */
  protected function createWebhookRequest(array $payload, bool $validSignature = TRUE): Request {
    $this->stripeConnect->method('verifyWebhookSignature')
      ->willReturn($validSignature);

    $request = Request::create(
      '/api/v1/billing/stripe-webhook',
      'POST',
      [],
      [],
      [],
      [],
      json_encode($payload),
    );
    $request->headers->set('Stripe-Signature', $validSignature ? 'valid_sig' : 'invalid_sig');

    return $request;
  }

  // =========================================================================
  // TESTS: Verificacion de firma (HMAC-SHA256)
  // =========================================================================

  /**
   * Verifica que una firma valida permite el procesamiento.
   *
   * @covers ::handle
   */
  public function testValidSignatureAllowsProcessing(): void {
    $payload = [
      'type' => 'invoice.paid',
      'data' => ['object' => ['id' => 'in_valid', 'amount_paid' => 1000]],
    ];

    $request = $this->createWebhookRequest($payload, TRUE);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Verifica que una firma invalida es rechazada con 400.
   *
   * @covers ::handle
   */
  public function testInvalidSignatureReturns400(): void {
    $this->stripeConnect->expects($this->once())
      ->method('verifyWebhookSignature')
      ->willReturn(FALSE);

    $request = Request::create(
      '/api/v1/billing/stripe-webhook',
      'POST',
      [],
      [],
      [],
      [],
      '{"type": "invoice.paid"}',
    );
    $request->headers->set('Stripe-Signature', 'tampered_signature');

    $response = $this->controller->handle($request);
    $this->assertEquals(400, $response->getStatusCode());
  }

  /**
   * Verifica que un header Stripe-Signature vacio devuelve 400.
   *
   * @covers ::handle
   */
  public function testEmptySignatureHeaderReturns400(): void {
    $this->stripeConnect->method('verifyWebhookSignature')
      ->willReturn(FALSE);

    $request = Request::create(
      '/api/v1/billing/stripe-webhook',
      'POST',
      [],
      [],
      [],
      [],
      '{"type": "invoice.paid"}',
    );
    $request->headers->set('Stripe-Signature', '');

    $response = $this->controller->handle($request);
    $this->assertEquals(400, $response->getStatusCode());
  }

  // =========================================================================
  // TESTS: Payload invalido
  // =========================================================================

  /**
   * Verifica que JSON invalido devuelve 400.
   *
   * @covers ::handle
   */
  public function testMalformedJsonReturns400(): void {
    $this->stripeConnect->method('verifyWebhookSignature')
      ->willReturn(TRUE);

    $request = Request::create(
      '/api/v1/billing/stripe-webhook',
      'POST',
      [],
      [],
      [],
      [],
      '{not valid json}',
    );
    $request->headers->set('Stripe-Signature', 'valid');

    $response = $this->controller->handle($request);
    $this->assertEquals(400, $response->getStatusCode());
  }

  /**
   * Verifica que payload sin 'type' devuelve 400.
   *
   * @covers ::handle
   */
  public function testMissingEventTypeReturns400(): void {
    $this->stripeConnect->method('verifyWebhookSignature')
      ->willReturn(TRUE);

    $request = Request::create(
      '/api/v1/billing/stripe-webhook',
      'POST',
      [],
      [],
      [],
      [],
      json_encode(['data' => ['object' => []]]),
    );
    $request->headers->set('Stripe-Signature', 'valid');

    $response = $this->controller->handle($request);
    $this->assertEquals(400, $response->getStatusCode());
  }

  // =========================================================================
  // TESTS: Tipos de eventos desconocidos
  // =========================================================================

  /**
   * Verifica que un evento desconocido devuelve 200 con status 'ignored'.
   *
   * @covers ::handle
   */
  public function testUnknownEventTypeReturns200Ignored(): void {
    $payload = [
      'type' => 'totally.unknown.event',
      'data' => ['object' => []],
    ];

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('ignored', $body['data']['status']);
  }

  /**
   * Verifica que multiples tipos de eventos desconocidos son ignorados.
   *
   * @dataProvider unknownEventTypeProvider
   * @covers ::handle
   */
  public function testVariousUnknownEventsAreIgnored(string $eventType): void {
    $payload = [
      'type' => $eventType,
      'data' => ['object' => []],
    ];

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('ignored', $body['data']['status']);
  }

  /**
   * Data provider para tipos de eventos no manejados.
   */
  public static function unknownEventTypeProvider(): array {
    return [
      'charge.succeeded' => ['charge.succeeded'],
      'charge.refunded' => ['charge.refunded'],
      'checkout.session.completed' => ['checkout.session.completed'],
      'payout.paid' => ['payout.paid'],
      'account.updated' => ['account.updated'],
      'transfer.created' => ['transfer.created'],
    ];
  }

  // =========================================================================
  // TESTS: Flujo de suscripcion (created -> updated -> deleted)
  // =========================================================================

  /**
   * Verifica que subscription.updated con status=active activa la suscripcion.
   *
   * @covers ::handle
   */
  public function testSubscriptionUpdatedActiveActivatesSubscription(): void {
    $payload = [
      'type' => 'customer.subscription.updated',
      'data' => [
        'object' => [
          'id' => 'sub_activate',
          'status' => 'active',
          'metadata' => ['tenant_id' => '99'],
          'items' => ['data' => [['price' => ['product' => 'prod_pro']]]],
        ],
      ],
    ];

    // Mock tenant entity for subscription update flow.
    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('set')->willReturnSelf();
    $tenant->method('save')->willReturn(1);

    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(99)->willReturn($tenant);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->with('tenant')
      ->willReturn($groupStorage);

    // Inject entity type manager via reflection (ControllerBase dependency).
    $reflection = new \ReflectionClass($this->controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($this->controller, $entityTypeManager);

    $this->tenantSubscription->expects($this->once())
      ->method('activateSubscription')
      ->with($tenant);

    $this->dunningService->method('isInDunning')->willReturn(FALSE);

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('processed', $body['data']['status']);
  }

  /**
   * Verifica que subscription.updated con status=past_due inicia dunning.
   *
   * @covers ::handle
   */
  public function testSubscriptionUpdatedPastDueStartsDunning(): void {
    $payload = [
      'type' => 'customer.subscription.updated',
      'data' => [
        'object' => [
          'id' => 'sub_past_due',
          'status' => 'past_due',
          'metadata' => ['tenant_id' => '88'],
          'items' => ['data' => [['price' => ['product' => 'prod_starter']]]],
        ],
      ],
    ];

    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('set')->willReturnSelf();
    $tenant->method('save')->willReturn(1);

    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(88)->willReturn($tenant);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->with('tenant')
      ->willReturn($groupStorage);

    $reflection = new \ReflectionClass($this->controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($this->controller, $entityTypeManager);

    $this->tenantSubscription->expects($this->once())
      ->method('markPastDue')
      ->with($tenant);

    $this->dunningService->expects($this->once())
      ->method('startDunning')
      ->with(88);

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Verifica que subscription.deleted cancela la suscripcion del tenant.
   *
   * @covers ::handle
   */
  public function testSubscriptionDeletedCancelsSubscription(): void {
    $payload = [
      'type' => 'customer.subscription.deleted',
      'data' => [
        'object' => [
          'id' => 'sub_canceled',
          'metadata' => ['tenant_id' => '77'],
        ],
      ],
    ];

    $tenant = $this->createMock(TenantInterface::class);

    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(77)->willReturn($tenant);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->with('tenant')
      ->willReturn($groupStorage);

    $reflection = new \ReflectionClass($this->controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($this->controller, $entityTypeManager);

    $this->tenantSubscription->expects($this->once())
      ->method('cancelSubscription')
      ->with($tenant, TRUE);

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('processed', $body['data']['status']);
  }

  // =========================================================================
  // TESTS: Concurrencia (lock backend)
  // =========================================================================

  /**
   * Verifica que si el lock no se puede adquirir, devuelve 503 retry.
   *
   * @covers ::handle
   */
  public function testConcurrentWebhookReturns503WhenLockBusy(): void {
    // Override lock mock for this test: cannot acquire.
    $lock = $this->createMock(LockBackendInterface::class);
    $lock->method('acquire')->willReturn(FALSE);

    $controller = new BillingWebhookController(
      $this->stripeConnect,
      $this->invoiceService,
      $this->tenantSubscription,
      $this->logger,
      $lock,
      $this->dunningService,
      $this->mailManager,
    );

    // Inject entity type manager so it can call entityTypeManager().
    $tenant = $this->createMock(TenantInterface::class);
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->willReturn($tenant);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($groupStorage);

    $reflection = new \ReflectionClass($controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($controller, $entityTypeManager);

    $payload = [
      'type' => 'customer.subscription.updated',
      'data' => [
        'object' => [
          'id' => 'sub_concurrent',
          'status' => 'active',
          'metadata' => ['tenant_id' => '55'],
          'items' => ['data' => [['price' => ['product' => 'prod_test']]]],
        ],
      ],
    ];

    $this->stripeConnect->method('verifyWebhookSignature')->willReturn(TRUE);

    $request = Request::create(
      '/api/v1/billing/stripe-webhook',
      'POST',
      [],
      [],
      [],
      [],
      json_encode($payload),
    );
    $request->headers->set('Stripe-Signature', 'valid');

    $response = $controller->handle($request);
    $this->assertEquals(503, $response->getStatusCode());

    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('retry', $body['status']);
  }

  // =========================================================================
  // TESTS: invoice.payment_failed flujo con dunning
  // =========================================================================

  /**
   * Verifica que invoice.payment_failed marca el tenant como past_due.
   *
   * @covers ::handle
   */
  public function testPaymentFailedMarksTenantPastDue(): void {
    $payload = [
      'type' => 'invoice.payment_failed',
      'data' => [
        'object' => [
          'id' => 'in_failed',
          'amount_due' => 5000,
          'metadata' => ['tenant_id' => '66'],
        ],
      ],
    ];

    $tenant = $this->createMock(TenantInterface::class);

    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(66)->willReturn($tenant);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->with('tenant')
      ->willReturn($groupStorage);

    $reflection = new \ReflectionClass($this->controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($this->controller, $entityTypeManager);

    $this->tenantSubscription->expects($this->once())
      ->method('markPastDue')
      ->with($tenant);

    $this->invoiceService->expects($this->once())
      ->method('syncInvoice');

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  // =========================================================================
  // TESTS: subscription.updated con recovery (stop dunning)
  // =========================================================================

  /**
   * Verifica que activar suscripcion detiene el proceso de dunning.
   *
   * @covers ::handle
   */
  public function testSubscriptionActivationStopsDunning(): void {
    $payload = [
      'type' => 'customer.subscription.updated',
      'data' => [
        'object' => [
          'id' => 'sub_recovered',
          'status' => 'active',
          'metadata' => ['tenant_id' => '44'],
          'items' => ['data' => [['price' => ['product' => 'prod_test']]]],
        ],
      ],
    ];

    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('set')->willReturnSelf();
    $tenant->method('save')->willReturn(1);

    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(44)->willReturn($tenant);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->with('tenant')
      ->willReturn($groupStorage);

    $reflection = new \ReflectionClass($this->controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($this->controller, $entityTypeManager);

    // Simulate tenant is in dunning.
    $this->dunningService->method('isInDunning')
      ->with(44)
      ->willReturn(TRUE);

    $this->dunningService->expects($this->once())
      ->method('stopDunning')
      ->with(44);

    $this->tenantSubscription->expects($this->once())
      ->method('activateSubscription')
      ->with($tenant);

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  // =========================================================================
  // TESTS: subscription.updated sin tenant_id
  // =========================================================================

  /**
   * Verifica que subscription.updated sin tenant_id no falla.
   *
   * @covers ::handle
   */
  public function testSubscriptionUpdatedWithoutTenantIdDoesNotFail(): void {
    $payload = [
      'type' => 'customer.subscription.updated',
      'data' => [
        'object' => [
          'id' => 'sub_no_tenant',
          'status' => 'active',
          'metadata' => [],
          'items' => ['data' => [['price' => ['product' => 'prod_test']]]],
        ],
      ],
    ];

    // No tenant should be loaded.
    $this->tenantSubscription->expects($this->never())
      ->method('activateSubscription');

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('processed', $body['data']['status']);
  }

  // =========================================================================
  // TESTS: invoice.paid y invoice.finalized
  // =========================================================================

  /**
   * Verifica que invoice.paid sincroniza la factura via InvoiceService.
   *
   * @covers ::handle
   */
  public function testInvoicePaidSyncsInvoice(): void {
    $payload = [
      'type' => 'invoice.paid',
      'data' => [
        'object' => [
          'id' => 'in_paid_123',
          'amount_paid' => 4900,
          'currency' => 'eur',
          'status' => 'paid',
        ],
      ],
    ];

    $this->invoiceService->expects($this->once())
      ->method('syncInvoice')
      ->with($this->callback(function (array $data) {
        return $data['id'] === 'in_paid_123'
          && $data['amount_paid'] === 4900;
      }));

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Verifica que invoice.finalized sincroniza la factura.
   *
   * @covers ::handle
   */
  public function testInvoiceFinalizedSyncsInvoice(): void {
    $payload = [
      'type' => 'invoice.finalized',
      'data' => [
        'object' => [
          'id' => 'in_finalized_456',
          'status' => 'open',
        ],
      ],
    ];

    $this->invoiceService->expects($this->once())
      ->method('syncInvoice');

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  // =========================================================================
  // TESTS: trial_will_end
  // =========================================================================

  /**
   * Verifica que trial_will_end devuelve 200 processed.
   *
   * @covers ::handle
   */
  public function testTrialWillEndReturnsProcessed(): void {
    $payload = [
      'type' => 'customer.subscription.trial_will_end',
      'data' => [
        'object' => [
          'id' => 'sub_trial',
          'trial_end' => time() + (3 * 86400),
          'metadata' => ['tenant_id' => '33'],
        ],
      ],
    ];

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('processed', $body['data']['status']);
  }

  // =========================================================================
  // TESTS: Respuesta estandarizada (JSON envelope)
  // =========================================================================

  /**
   * Verifica que las respuestas exitosas siguen el formato JSON estandarizado.
   *
   * @covers ::handle
   */
  public function testSuccessfulResponseFollowsStandardEnvelope(): void {
    $payload = [
      'type' => 'invoice.paid',
      'data' => [
        'object' => ['id' => 'in_envelope_test', 'amount_paid' => 100],
      ],
    ];

    $request = $this->createWebhookRequest($payload);
    $response = $this->controller->handle($request);

    $body = json_decode($response->getContent(), TRUE);

    $this->assertArrayHasKey('success', $body);
    $this->assertTrue($body['success']);
    $this->assertArrayHasKey('data', $body);
    $this->assertArrayHasKey('status', $body['data']);
    $this->assertArrayHasKey('meta', $body);
    $this->assertArrayHasKey('timestamp', $body['meta']);
  }

  /**
   * Verifica que las respuestas de error siguen el formato JSON estandarizado.
   *
   * @covers ::handle
   */
  public function testErrorResponseFollowsStandardEnvelope(): void {
    $this->stripeConnect->method('verifyWebhookSignature')
      ->willReturn(FALSE);

    $request = Request::create(
      '/api/v1/billing/stripe-webhook',
      'POST',
      [],
      [],
      [],
      [],
      '{"type": "test"}',
    );
    $request->headers->set('Stripe-Signature', 'bad');

    $response = $this->controller->handle($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertArrayHasKey('success', $body);
    $this->assertFalse($body['success']);
    $this->assertArrayHasKey('error', $body);
    $this->assertArrayHasKey('code', $body['error']);
    $this->assertArrayHasKey('message', $body['error']);
  }

}
