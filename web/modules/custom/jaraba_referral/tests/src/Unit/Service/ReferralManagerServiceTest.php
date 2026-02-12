<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_referral\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_referral\Entity\Referral;
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
    $referralEntity = $this->createMock(Referral::class);
    $referralEntity->method('id')->willReturn(42);
    $referralEntity->method('save')->willReturn(1);

    // Query mock for checking existing codes (returns empty = no existing code).
    $existingQuery = $this->createMock(QueryInterface::class);
    $existingQuery->method('accessCheck')->willReturnSelf();
    $existingQuery->method('condition')->willReturnSelf();
    $existingQuery->method('range')->willReturnSelf();
    $existingQuery->method('count')->willReturnSelf();
    $existingQuery->method('execute')->willReturn(0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')->willReturn($referralEntity);
    $storage->method('getQuery')->willReturn($existingQuery);

    $this->entityTypeManager->method('getStorage')
      ->with('referral')
      ->willReturn($storage);

    $result = $this->service->generateCode(5);

    $this->assertNotNull($result);
  }

  /**
   * Tests que processReferral throws exception with invalid code.
   */
  public function testProcessReferralCodeNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with([
        'referral_code' => 'INVALID1',
        'status' => 'pending',
      ])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral')
      ->willReturn($storage);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Código de referido no válido o ya utilizado.');

    $this->service->processReferral('INVALID1', 42);
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
      ->with('referral')
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
      ->with('referral')
      ->willReturn($storage);

    $stats = $this->service->getReferralStats(1);

    $this->assertIsArray($stats);
    $this->assertArrayHasKey('total_codes', $stats);
    $this->assertArrayHasKey('total_confirmed', $stats);
    $this->assertArrayHasKey('total_rewarded', $stats);
    $this->assertArrayHasKey('total_expired', $stats);
    $this->assertArrayHasKey('conversion_rate', $stats);
    $this->assertArrayHasKey('total_reward_value', $stats);
    $this->assertEquals(0, $stats['total_codes']);
  }

  /**
   * Tests que processReferral procesa correctamente un codigo valido.
   */
  public function testProcessReferralSuccessWithValidCode(): void {
    $referrerUidField = new \stdClass();
    $referrerUidField->target_id = 10;

    $codeEntity = $this->createMock(Referral::class);
    $codeEntity->method('id')->willReturn(10);
    $codeEntity->method('get')
      ->willReturnMap([
        ['referrer_uid', $referrerUidField],
      ]);
    $codeEntity->method('set')->willReturnSelf();
    $codeEntity->method('save')->willReturn(1);

    // Query for checking if user was already referred (returns 0).
    $alreadyReferredQuery = $this->createMock(QueryInterface::class);
    $alreadyReferredQuery->method('accessCheck')->willReturnSelf();
    $alreadyReferredQuery->method('condition')->willReturnSelf();
    $alreadyReferredQuery->method('count')->willReturnSelf();
    $alreadyReferredQuery->method('execute')->willReturn(0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with([
        'referral_code' => 'VALID001',
        'status' => 'pending',
      ])
      ->willReturn([1 => $codeEntity]);
    $storage->method('getQuery')->willReturn($alreadyReferredQuery);

    $this->entityTypeManager->method('getStorage')
      ->with('referral')
      ->willReturn($storage);

    // referred_uid=42 is different from referrer_uid=10, so no self-referral.
    $result = $this->service->processReferral('VALID001', 42);

    $this->assertNotNull($result);
  }

}
