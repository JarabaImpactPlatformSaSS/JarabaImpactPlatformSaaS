<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_referral\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_referral\Service\LeaderboardService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para LeaderboardService.
 *
 * Verifica la generación de rankings, cálculo de niveles de embajador,
 * posición de usuarios y estadísticas del leaderboard.
 *
 * @covers \Drupal\jaraba_referral\Service\LeaderboardService
 * @group jaraba_referral
 */
class LeaderboardServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;
  protected LeaderboardService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new LeaderboardService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests que getLeaderboard devuelve array vacío sin códigos.
   */
  public function testGetLeaderboardEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->getLeaderboard(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que getLeaderboard ordena correctamente por conversiones.
   */
  public function testGetLeaderboardRanking(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    // Crear dos códigos con diferentes conversiones.
    $user1 = $this->createMock(AccountInterface::class);
    $user1->method('getDisplayName')->willReturn('User Alto');

    $user2 = $this->createMock(AccountInterface::class);
    $user2->method('getDisplayName')->willReturn('User Bajo');

    $code1 = $this->createCodeEntity(10, 25, 500.00, $user1);
    $code2 = $this->createCodeEntity(20, 5, 100.00, $user2);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->with([1, 2])->willReturn([1 => $code1, 2 => $code2]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->getLeaderboard(1, 10);

    $this->assertCount(2, $result);
    // El usuario con más conversiones debe estar primero.
    $this->assertEquals(1, $result[0]['rank']);
    $this->assertEquals(10, $result[0]['user_id']);
    $this->assertEquals(25, $result[0]['total_referrals']);
    $this->assertEquals(500.00, $result[0]['total_revenue']);

    $this->assertEquals(2, $result[1]['rank']);
    $this->assertEquals(20, $result[1]['user_id']);
    $this->assertEquals(5, $result[1]['total_referrals']);
  }

  /**
   * Tests que getLeaderboard respeta el límite.
   */
  public function testGetLeaderboardLimit(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3]);

    $user = $this->createMock(AccountInterface::class);
    $user->method('getDisplayName')->willReturn('Test User');

    $code1 = $this->createCodeEntity(10, 30, 600.00, $user);
    $code2 = $this->createCodeEntity(20, 20, 400.00, $user);
    $code3 = $this->createCodeEntity(30, 10, 200.00, $user);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([1 => $code1, 2 => $code2, 3 => $code3]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->getLeaderboard(1, 2);

    $this->assertCount(2, $result);
  }

  /**
   * Tests que getUserRank devuelve rank 0 para usuario no encontrado.
   */
  public function testGetUserRankNotFound(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $result = $this->service->getUserRank(999, 1);

    $this->assertEquals(0, $result['rank']);
    $this->assertEquals(0, $result['total_referrals']);
    $this->assertEquals('Bronce', $result['level']);
  }

  /**
   * Tests que getAmbassadorLevel devuelve el nivel correcto.
   */
  public function testGetAmbassadorLevelBronze(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $this->assertEquals('Bronce', $this->service->getAmbassadorLevel(5, 1));
  }

  /**
   * Tests que getAmbassadorLevel devuelve Plata con 5+ referidos.
   */
  public function testGetAmbassadorLevelSilver(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    $user = $this->createMock(AccountInterface::class);
    $code = $this->createCodeEntity(5, 7, 100.00, $user);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([1 => $code]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $this->assertEquals('Plata', $this->service->getAmbassadorLevel(5, 1));
  }

  /**
   * Tests que getAmbassadorLevel devuelve Diamante con 50+ referidos.
   */
  public function testGetAmbassadorLevelDiamond(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    $user = $this->createMock(AccountInterface::class);
    $code = $this->createCodeEntity(5, 55, 2000.00, $user);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([1 => $code]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $this->assertEquals('Diamante', $this->service->getAmbassadorLevel(5, 1));
  }

  /**
   * Tests que getLeaderboardStats devuelve estadísticas correctas.
   */
  public function testGetLeaderboardStatsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $stats = $this->service->getLeaderboardStats(1);

    $this->assertEquals(0, $stats['total_participants']);
    $this->assertEquals(0, $stats['total_referrals']);
    $this->assertEquals(0.0, $stats['total_revenue']);
    $this->assertEquals(0.0, $stats['avg_referrals_per_user']);
    $this->assertArrayHasKey('level_distribution', $stats);
  }

  /**
   * Tests que getLeaderboardStats calcula métricas con datos.
   */
  public function testGetLeaderboardStatsWithData(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $user1 = $this->createMock(AccountInterface::class);
    $user1->method('getDisplayName')->willReturn('User 1');

    $user2 = $this->createMock(AccountInterface::class);
    $user2->method('getDisplayName')->willReturn('User 2');

    $code1 = $this->createCodeEntity(10, 20, 400.00, $user1);
    $code2 = $this->createCodeEntity(20, 3, 60.00, $user2);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([1 => $code1, 2 => $code2]);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($storage);

    $stats = $this->service->getLeaderboardStats(1);

    $this->assertEquals(2, $stats['total_participants']);
    $this->assertEquals(23, $stats['total_referrals']);
    $this->assertEquals(460.00, $stats['total_revenue']);
    $this->assertEquals(11.5, $stats['avg_referrals_per_user']);
  }

  /**
   * Crea un mock de entidad ReferralCode con los datos especificados.
   *
   * @param int $userId
   *   ID del usuario propietario.
   * @param int $conversions
   *   Total de conversiones.
   * @param float $revenue
   *   Revenue total.
   * @param object $userEntity
   *   Mock de la entidad usuario.
   *
   * @return object
   *   Mock de ContentEntityInterface.
   */
  protected function createCodeEntity(int $userId, int $conversions, float $revenue, object $userEntity): object {
    $code = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);

    $userIdField = new \stdClass();
    $userIdField->target_id = $userId;
    $userIdField->entity = $userEntity;

    $conversionsField = new \stdClass();
    $conversionsField->value = $conversions;

    $revenueField = new \stdClass();
    $revenueField->value = $revenue;

    $isActiveField = new \stdClass();
    $isActiveField->value = TRUE;

    $code->method('get')
      ->willReturnMap([
        ['user_id', $userIdField],
        ['total_conversions', $conversionsField],
        ['total_revenue', $revenueField],
        ['is_active', $isActiveField],
      ]);

    return $code;
  }

}
