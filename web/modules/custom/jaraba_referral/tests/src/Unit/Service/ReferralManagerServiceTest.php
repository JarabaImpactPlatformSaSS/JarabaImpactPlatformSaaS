<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_referral\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_referral\Service\ReferralManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ReferralManagerService.
 *
 * Verifica la logica de generacion de codigos de referido,
 * procesamiento de referidos, listado de referidos por usuario
 * y estadisticas por tenant.
 *
 * @covers \Drupal\jaraba_referral\Service\ReferralManagerService
 * @group jaraba_referral
 */
class ReferralManagerServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected $tenantContext;
  protected LoggerInterface $logger;
  protected ReferralManagerService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getCurrentTenantId'])
      ->getMock();
    $this->tenantContext->method('getCurrentTenantId')->willReturn(1);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new ReferralManagerService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * Tests que generateCode crea un codigo de referido exitosamente.
   */
  public function testGenerateCodeSuccess(): void {
    $referralEntity = $this->createMock(ContentEntityInterface::class);
    $referralEntity->method('id')->willReturn(42);
    $referralEntity->method('save')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')->willReturn($referralEntity);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->generateCode(5);

    $this->assertNotNull($result);
  }

  /**
   * Tests que processReferral devuelve NULL con codigo inexistente.
   */
  public function testProcessReferralCodeNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'INVALID1'])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->processReferral('INVALID1', 42);

    $this->assertNull($result);
  }

  /**
   * Tests que getMyReferrals devuelve array vacio sin referidos.
   */
  public function testGetMyReferralsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->getMyReferrals(5);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que getReferralStats devuelve estructura correcta sin datos.
   */
  public function testGetReferralStatsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $stats = $this->service->getReferralStats(1);

    $this->assertIsArray($stats);
  }

  /**
   * Tests que processReferral procesa correctamente un codigo valido.
   */
  public function testProcessReferralSuccessWithValidCode(): void {
    $isActiveField = new \stdClass();
    $isActiveField->value = TRUE;

    $expiresAtField = new \stdClass();
    $expiresAtField->value = NULL;

    $codeEntity = $this->createMock(ContentEntityInterface::class);
    $codeEntity->method('id')->willReturn(10);
    $codeEntity->method('get')
      ->willReturnMap([
        ['is_active', $isActiveField],
        ['expires_at', $expiresAtField],
      ]);
    $codeEntity->method('set')->willReturnSelf();
    $codeEntity->method('save')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'VALID001'])
      ->willReturn([1 => $codeEntity]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->processReferral('VALID001', 42);

    $this->assertNotNull($result);
  }

}
