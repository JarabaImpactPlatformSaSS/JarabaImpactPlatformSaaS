<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_pixels\Service\CredentialManagerService;
use Drupal\jaraba_pixels\Service\TokenVerificationService;
use Drupal\Tests\UnitTestCase;
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->credentialManager = $this->createMock(CredentialManagerService::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get'])
      ->getMock();
    $loggerFactory->method('get')
      ->with('jaraba_pixels')
      ->willReturn($this->logger);

    $this->service = new TokenVerificationService(
      $this->credentialManager,
      $this->mailManager,
      $this->state,
      $loggerFactory,
    );
  }

  /**
   * Tests que verifyAllCredentials devuelve array vacio sin credenciales.
   */
  public function testVerifyAllCredentialsReturnsEmptyWithNoCredentials(): void {
    $this->credentialManager->method('getAllCredentials')
      ->willReturn([]);

    $result = $this->service->verifyAllCredentials();

    $this->assertIsArray($result);
    $this->assertEmpty($result);
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
   * Tests que sendExpirationAlert no envia mail si no hay resultados.
   */
  public function testSendExpirationAlertNoMailWhenEmpty(): void {
    $this->mailManager->expects($this->never())
      ->method('mail');

    $this->service->sendExpirationAlert([]);
  }

  /**
   * Tests que sendExpirationAlert envia mail cuando hay tokens expirados.
   */
  public function testSendExpirationAlertSendsMailWhenExpired(): void {
    $results = [
      'meta' => [
        'platform' => 'meta',
        'status' => 'expired',
        'expires_at' => time() - 86400,
      ],
    ];

    $this->mailManager->expects($this->once())
      ->method('mail');

    $this->service->sendExpirationAlert($results);
  }

}
