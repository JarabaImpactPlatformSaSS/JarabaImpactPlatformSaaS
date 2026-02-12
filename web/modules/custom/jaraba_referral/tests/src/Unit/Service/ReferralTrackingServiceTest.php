<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_referral\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_referral\Service\ReferralTrackingService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ReferralTrackingService.
 *
 * Verifica el tracking de clicks, signups, conversiones, búsqueda
 * de códigos y estadísticas agregadas por tenant.
 *
 * @covers \Drupal\jaraba_referral\Service\ReferralTrackingService
 * @group jaraba_referral
 */
class ReferralTrackingServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;
  protected ReferralTrackingService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new ReferralTrackingService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests que trackClick incrementa el contador correctamente.
   */
  public function testTrackClickSuccess(): void {
    $codeEntity = $this->createActiveCodeEntity(5, 0, 0, 0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'ABCD1234'])
      ->willReturn([1 => $codeEntity]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $codeEntity->expects($this->once())
      ->method('set')
      ->with('total_clicks', 6);
    $codeEntity->expects($this->once())
      ->method('save');

    $result = $this->service->trackClick('ABCD1234');

    $this->assertTrue($result);
  }

  /**
   * Tests que trackClick devuelve FALSE con código inexistente.
   */
  public function testTrackClickCodeNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'INVALID1'])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->trackClick('INVALID1');

    $this->assertFalse($result);
  }

  /**
   * Tests que trackClick devuelve FALSE con código inactivo.
   */
  public function testTrackClickInactiveCode(): void {
    $codeEntity = $this->createInactiveCodeEntity();

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'INACTIVE'])
      ->willReturn([1 => $codeEntity]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->trackClick('INACTIVE');

    $this->assertFalse($result);
  }

  /**
   * Tests que trackSignup incrementa el contador de signups.
   */
  public function testTrackSignupSuccess(): void {
    $codeEntity = $this->createActiveCodeEntity(10, 3, 0, 0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'SIGNUP01'])
      ->willReturn([1 => $codeEntity]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $codeEntity->expects($this->once())
      ->method('set')
      ->with('total_signups', 4);
    $codeEntity->expects($this->once())
      ->method('save');

    $result = $this->service->trackSignup('SIGNUP01', 42);

    $this->assertTrue($result);
  }

  /**
   * Tests que trackSignup devuelve FALSE con código inexistente.
   */
  public function testTrackSignupCodeNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'NOSIGNUP'])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->trackSignup('NOSIGNUP', 42);

    $this->assertFalse($result);
  }

  /**
   * Tests que trackConversion incrementa conversiones y revenue.
   */
  public function testTrackConversionSuccess(): void {
    $codeEntity = $this->createActiveCodeEntity(20, 5, 2, 150.00);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'CONVERT1'])
      ->willReturn([1 => $codeEntity]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    // Se llaman dos set(): total_conversions y total_revenue.
    $codeEntity->expects($this->exactly(2))
      ->method('set')
      ->willReturnCallback(function (string $field, $value) use ($codeEntity): void {
        if ($field === 'total_conversions') {
          $this->assertEquals(3, $value);
        }
        elseif ($field === 'total_revenue') {
          $this->assertEquals(199.99, $value);
        }
      });
    $codeEntity->expects($this->once())
      ->method('save');

    $result = $this->service->trackConversion('CONVERT1', 42, 49.99);

    $this->assertTrue($result);
  }

  /**
   * Tests que trackConversion devuelve FALSE con código inactivo.
   */
  public function testTrackConversionInactiveCode(): void {
    $codeEntity = $this->createInactiveCodeEntity();

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'NOCONV01'])
      ->willReturn([1 => $codeEntity]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->trackConversion('NOCONV01', 42, 100.00);

    $this->assertFalse($result);
  }

  /**
   * Tests que getCodeByString devuelve NULL con código inexistente.
   */
  public function testGetCodeByStringNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'NONEXIST'])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $this->assertNull($this->service->getCodeByString('NONEXIST'));
  }

  /**
   * Tests que getCodeByString devuelve la entidad cuando existe.
   */
  public function testGetCodeByStringFound(): void {
    $codeEntity = $this->createActiveCodeEntity(5, 0, 0, 0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['code' => 'FOUND001'])
      ->willReturn([1 => $codeEntity]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->getCodeByString('FOUND001');

    $this->assertNotNull($result);
  }

  /**
   * Tests que getTrackingStats devuelve estructura correcta vacía.
   */
  public function testGetTrackingStatsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $stats = $this->service->getTrackingStats(1);

    $this->assertEquals(0, $stats['total_codes']);
    $this->assertEquals(0, $stats['total_clicks']);
    $this->assertEquals(0, $stats['total_signups']);
    $this->assertEquals(0, $stats['total_conversions']);
    $this->assertEquals(0.0, $stats['total_revenue']);
    $this->assertEquals(0.0, $stats['click_to_signup_rate']);
    $this->assertEquals(0.0, $stats['signup_to_conversion_rate']);
  }

  /**
   * Tests que getTrackingStats calcula tasas correctamente.
   */
  public function testGetTrackingStatsWithData(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $code1 = $this->createStatsCodeEntity(100, 20, 5, 500.00);
    $code2 = $this->createStatsCodeEntity(200, 30, 10, 1000.00);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([1 => $code1, 2 => $code2]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $stats = $this->service->getTrackingStats(1);

    $this->assertEquals(2, $stats['total_codes']);
    $this->assertEquals(300, $stats['total_clicks']);
    $this->assertEquals(50, $stats['total_signups']);
    $this->assertEquals(15, $stats['total_conversions']);
    $this->assertEquals(1500.00, $stats['total_revenue']);
    // click_to_signup_rate = (50/300)*100 = 16.7
    $this->assertEquals(16.7, $stats['click_to_signup_rate']);
    // signup_to_conversion_rate = (15/50)*100 = 30.0
    $this->assertEquals(30.0, $stats['signup_to_conversion_rate']);
  }

  /**
   * Crea un mock de entidad ReferralCode activo con contadores.
   *
   * @param int $clicks
   *   Total de clicks.
   * @param int $signups
   *   Total de signups.
   * @param int $conversions
   *   Total de conversiones.
   * @param float $revenue
   *   Revenue total.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Mock de la entidad.
   */
  protected function createActiveCodeEntity(int $clicks, int $signups, int $conversions, float $revenue): object {
    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);

    $isActiveField = new \stdClass();
    $isActiveField->value = TRUE;

    $clicksField = new \stdClass();
    $clicksField->value = $clicks;

    $signupsField = new \stdClass();
    $signupsField->value = $signups;

    $conversionsField = new \stdClass();
    $conversionsField->value = $conversions;

    $revenueField = new \stdClass();
    $revenueField->value = $revenue;

    $expiresAtField = new \stdClass();
    $expiresAtField->value = NULL;

    $entity->method('get')
      ->willReturnMap([
        ['is_active', $isActiveField],
        ['total_clicks', $clicksField],
        ['total_signups', $signupsField],
        ['total_conversions', $conversionsField],
        ['total_revenue', $revenueField],
        ['expires_at', $expiresAtField],
      ]);

    return $entity;
  }

  /**
   * Crea un mock de entidad ReferralCode inactivo.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Mock de la entidad.
   */
  protected function createInactiveCodeEntity(): object {
    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);

    $isActiveField = new \stdClass();
    $isActiveField->value = FALSE;

    $entity->method('get')
      ->willReturnMap([
        ['is_active', $isActiveField],
      ]);

    return $entity;
  }

  /**
   * Crea un mock para estadísticas de tracking.
   *
   * @param int $clicks
   *   Total de clicks.
   * @param int $signups
   *   Total de signups.
   * @param int $conversions
   *   Total de conversiones.
   * @param float $revenue
   *   Revenue total.
   *
   * @return object
   *   Mock de la entidad.
   */
  protected function createStatsCodeEntity(int $clicks, int $signups, int $conversions, float $revenue): object {
    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);

    $clicksField = new \stdClass();
    $clicksField->value = $clicks;

    $signupsField = new \stdClass();
    $signupsField->value = $signups;

    $conversionsField = new \stdClass();
    $conversionsField->value = $conversions;

    $revenueField = new \stdClass();
    $revenueField->value = $revenue;

    $entity->method('get')
      ->willReturnMap([
        ['total_clicks', $clicksField],
        ['total_signups', $signupsField],
        ['total_conversions', $conversionsField],
        ['total_revenue', $revenueField],
      ]);

    return $entity;
  }

}
