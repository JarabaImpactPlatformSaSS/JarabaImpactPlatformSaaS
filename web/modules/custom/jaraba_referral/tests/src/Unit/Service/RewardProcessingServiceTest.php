<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_referral\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Drupal\jaraba_referral\Service\RewardProcessingService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para RewardProcessingService.
 *
 * Verifica la creacion, aprobacion, rechazo y procesamiento de pagos
 * de recompensas del programa de referidos.
 *
 * @covers \Drupal\jaraba_referral\Service\RewardProcessingService
 * @group jaraba_referral
 */
class RewardProcessingServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected StripeConnectService $stripeConnect;
  protected LoggerInterface $logger;
  protected RewardProcessingService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->stripeConnect = $this->createMock(StripeConnectService::class);
    $this->stripeConnect->method('stripeRequest')->willReturn([
      'data' => ['id' => 'po_ref_test_stripe_123'],
    ]);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new RewardProcessingService(
      $this->entityTypeManager,
      $this->stripeConnect,
      $this->logger,
    );
  }

  /**
   * Tests que createReward crea una recompensa correctamente.
   */
  public function testCreateRewardSuccess(): void {
    // Simular codigo de referido existente.
    $codeEntity = $this->createMock(ContentEntityInterface::class);
    $tenantField = (object) ['target_id' => 1];
    $codeEntity->method('get')
      ->with('tenant_id')
      ->willReturn($tenantField);

    $codeStorage = $this->createMock(EntityStorageInterface::class);
    $codeStorage->method('load')
      ->with(10)
      ->willReturn($codeEntity);

    // Simular creacion de recompensa.
    $rewardEntity = $this->createMock(ContentEntityInterface::class);
    $rewardEntity->method('id')->willReturn(42);
    $rewardEntity->method('save')->willReturn(1);

    $rewardStorage = $this->createMock(EntityStorageInterface::class);
    $rewardStorage->method('create')->willReturn($rewardEntity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['referral_code', $codeStorage],
        ['referral_reward', $rewardStorage],
      ]);

    $result = $this->service->createReward(5, 10, 'credit', 25.00);

    $this->assertTrue($result['success']);
    $this->assertEquals(42, $result['reward_id']);
  }

  /**
   * Tests que createReward falla con codigo inexistente.
   */
  public function testCreateRewardCodeNotFound(): void {
    $codeStorage = $this->createMock(EntityStorageInterface::class);
    $codeStorage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_code')
      ->willReturn($codeStorage);

    $result = $this->service->createReward(5, 999, 'credit', 25.00);

    $this->assertFalse($result['success']);
    $this->assertEquals(0, $result['reward_id']);
    $this->assertStringContainsString('no encontrado', $result['message']);
  }

  /**
   * Tests que approveReward aprueba una recompensa pendiente.
   */
  public function testApproveRewardSuccess(): void {
    $statusField = (object) ['value' => 'pending'];

    $reward = $this->createMock(ContentEntityInterface::class);
    $reward->method('get')
      ->with('status')
      ->willReturn($statusField);
    $reward->expects($this->once())
      ->method('set')
      ->with('status', 'approved');
    $reward->method('save')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(42)->willReturn($reward);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_reward')
      ->willReturn($storage);

    $this->assertTrue($this->service->approveReward(42));
  }

  /**
   * Tests que approveReward falla si la recompensa no esta pendiente.
   */
  public function testApproveRewardNotPending(): void {
    $statusField = (object) ['value' => 'paid'];

    $reward = $this->createMock(ContentEntityInterface::class);
    $reward->method('get')
      ->with('status')
      ->willReturn($statusField);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(42)->willReturn($reward);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_reward')
      ->willReturn($storage);

    $this->assertFalse($this->service->approveReward(42));
  }

  /**
   * Tests que approveReward falla si la recompensa no existe.
   */
  public function testApproveRewardNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_reward')
      ->willReturn($storage);

    $this->assertFalse($this->service->approveReward(999));
  }

  /**
   * Tests que rejectReward rechaza con motivo.
   */
  public function testRejectRewardSuccess(): void {
    $statusField = (object) ['value' => 'pending'];

    $reward = $this->createMock(ContentEntityInterface::class);
    $reward->method('get')
      ->with('status')
      ->willReturn($statusField);
    $reward->expects($this->exactly(2))
      ->method('set')
      ->willReturnCallback(function (string $field, $value) use ($reward): void {
        // Verificar que se setean status y notes correctamente.
        $this->assertContains($field, ['status', 'notes']);
      });
    $reward->method('save')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(42)->willReturn($reward);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_reward')
      ->willReturn($storage);

    $this->assertTrue($this->service->rejectReward(42, 'Datos incompletos'));
  }

  /**
   * Tests que rejectReward falla si no esta pendiente.
   */
  public function testRejectRewardNotPending(): void {
    $statusField = (object) ['value' => 'approved'];

    $reward = $this->createMock(ContentEntityInterface::class);
    $reward->method('get')
      ->with('status')
      ->willReturn($statusField);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(42)->willReturn($reward);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_reward')
      ->willReturn($storage);

    $this->assertFalse($this->service->rejectReward(42, 'Motivo'));
  }

  /**
   * Tests que processStripePayout procesa el pago correctamente.
   */
  public function testProcessStripePayoutSuccess(): void {
    $statusField = (object) ['value' => 'approved'];
    $rewardValueField = (object) ['value' => '25.00'];
    $currencyField = (object) ['value' => 'EUR'];

    $reward = $this->createMock(ContentEntityInterface::class);
    $reward->method('get')
      ->willReturnMap([
        ['status', $statusField],
        ['reward_value', $rewardValueField],
        ['currency', $currencyField],
      ]);
    $reward->method('set')->willReturnSelf();
    $reward->method('save')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(42)->willReturn($reward);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_reward')
      ->willReturn($storage);

    $result = $this->service->processStripePayout(42);

    $this->assertTrue($result['success']);
    $this->assertEquals('po_ref_test_stripe_123', $result['payout_id']);
    $this->assertNotEmpty($result['message']);
  }

  /**
   * Tests que processStripePayout falla si no esta aprobada.
   */
  public function testProcessStripePayoutNotApproved(): void {
    $statusField = (object) ['value' => 'pending'];

    $reward = $this->createMock(ContentEntityInterface::class);
    $reward->method('get')
      ->with('status')
      ->willReturn($statusField);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(42)->willReturn($reward);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_reward')
      ->willReturn($storage);

    $result = $this->service->processStripePayout(42);

    $this->assertFalse($result['success']);
    $this->assertEmpty($result['payout_id']);
  }

  /**
   * Tests que getPendingRewards devuelve array vacio si no hay resultados.
   */
  public function testGetPendingRewardsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_reward')
      ->willReturn($storage);

    $result = $this->service->getPendingRewards(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que getRewardsForUser devuelve array vacio si no hay resultados.
   */
  public function testGetRewardsForUserEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('referral_reward')
      ->willReturn($storage);

    $result = $this->service->getRewardsForUser(5);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}
