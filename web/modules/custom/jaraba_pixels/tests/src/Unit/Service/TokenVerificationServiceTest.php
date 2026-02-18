<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_pixels\Service\CredentialManagerService;
use Drupal\jaraba_pixels\Service\TokenVerificationService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests para TokenVerificationService.
 *
 * Verifica la logica de verificacion de tokens/credenciales,
 * comprobacion de ejecucion diaria y envio de alertas de expiracion.
 *
 * @covers \Drupal\jaraba_pixels\Service\TokenVerificationService
 * @group jaraba_pixels
 */
class TokenVerificationServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected TokenVerificationService $service;

  /**
   * Mock del gestor de credenciales.
   */
  protected CredentialManagerService $credentialManager;

  /**
   * Mock del gestor de mail.
   */
  protected MailManagerInterface $mailManager;

  /**
   * Mock del servicio de estado.
   */
  protected StateInterface $state;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock del cliente HTTP.
   */
  protected ClientInterface $httpClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->credentialManager = $this->createMock(CredentialManagerService::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);

    $loggerFactory = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get'])
      ->getMock();
    $loggerFactory->method('get')
      ->with('jaraba_pixels.tokens')
      ->willReturn($this->logger);

    $this->service = new TokenVerificationService(
      $this->credentialManager,
      $this->mailManager,
      $this->state,
      $loggerFactory,
      $this->httpClient,
    );

    // Set up a mock container for \Drupal::config() calls in the service.
    $siteConfig = $this->createMock(ImmutableConfig::class);
    $siteConfig->method('get')
      ->with('mail')
      ->willReturn('admin@example.com');

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('system.site')
      ->willReturn($siteConfig);

    $container = new ContainerBuilder();
    $container->set('config.factory', $configFactory);
    \Drupal::setContainer($container);
  }

  /**
   * Tests que verifyAllCredentials devuelve array vacio sin credenciales.
   */
  public function testVerifyAllCredentialsReturnsEmptyWithNoCredentials(): void {
    $this->credentialManager->method('getAllCredentials')
      ->willReturn([]);

    $result = $this->service->verifyAllCredentials();

    $this->assertIsArray($result);
    $this->assertSame(0, $result['checked']);
    $this->assertEmpty($result['expired']);
    $this->assertEmpty($result['expiring_soon']);
    $this->assertEmpty($result['valid']);
  }

  /**
   * Tests que shouldRunDailyCheck devuelve TRUE si no se ha ejecutado hoy.
   */
  public function testShouldRunDailyCheckReturnsTrueFirstRun(): void {
    $this->state->method('get')
      ->willReturn(NULL);

    $result = $this->service->shouldRunDailyCheck();

    $this->assertTrue($result);
  }

  /**
   * Tests que shouldRunDailyCheck devuelve FALSE si ya se ejecuto hoy.
   */
  public function testShouldRunDailyCheckReturnsFalseIfAlreadyRun(): void {
    // Simular que la ultima ejecucion fue hace 1 hora.
    $this->state->method('get')
      ->willReturn(time() - 3600);

    $result = $this->service->shouldRunDailyCheck();

    $this->assertFalse($result);
  }

  /**
   * Tests que sendExpirationAlert no envia mail si no hay email configurado.
   */
  public function testSendExpirationAlertNoMailWhenEmpty(): void {
    // Override the container with an empty admin email.
    $siteConfig = $this->createMock(ImmutableConfig::class);
    $siteConfig->method('get')
      ->with('mail')
      ->willReturn('');

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('system.site')
      ->willReturn($siteConfig);

    $container = new ContainerBuilder();
    $container->set('config.factory', $configFactory);
    \Drupal::setContainer($container);

    $this->mailManager->expects($this->never())
      ->method('mail');

    $this->service->sendExpirationAlert([]);
  }

  /**
   * Tests que sendExpirationAlert envia mail cuando hay tokens expirados.
   */
  public function testSendExpirationAlertSendsMailWhenExpired(): void {
    $results = [
      'expired' => [
        ['tenant_id' => 1, 'platform' => 'meta'],
      ],
      'expiring_soon' => [],
    ];

    $this->mailManager->expects($this->once())
      ->method('mail')
      ->willReturn(['result' => TRUE]);

    $this->service->sendExpirationAlert($results);
  }

}
