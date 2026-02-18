<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\ComercioConectaFeatureGateService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ComercioConectaFeatureGateService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ComercioConectaFeatureGateService
 */
class ComercioConectaFeatureGateServiceTest extends UnitTestCase {

  protected UpgradeTriggerService $upgradeTriggerService;
  protected Connection $database;
  protected AccountProxyInterface $currentUser;
  protected LoggerInterface $logger;
  protected TenantContextService $tenantContext;
  protected ComercioConectaFeatureGateService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->upgradeTriggerService = $this->createMock(UpgradeTriggerService::class);
    $this->database = $this->createMock(Connection::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);

    // Mock container for t()
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // Mock database schema for ensureTable().
    $schema = $this->createMock(Schema::class);
    $schema->method('tableExists')->willReturn(TRUE);
    $this->database->method('schema')->willReturn($schema);

    $this->service = new ComercioConectaFeatureGateService(
      $this->upgradeTriggerService,
      $this->database,
      $this->currentUser,
      $this->logger,
      $this->tenantContext,
    );
  }

  /**
   * Tests checking a feature when no limit entity exists (allowed by default).
   *
   * @covers ::check
   */
  public function testCheckNoLimitEntity(): void {
    $userId = 123;
    $featureKey = 'products';
    $plan = 'free';

    $this->upgradeTriggerService->method('getVerticalLimit')
      ->with('comercioconecta', $plan, $featureKey)
      ->willReturn(NULL);

    $result = $this->service->check($userId, $featureKey, $plan);

    $this->assertInstanceOf(FeatureGateResult::class, $result);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * Tests checking an unlimited feature (-1).
   *
   * @covers ::check
   */
  public function testCheckUnlimited(): void {
    $userId = 123;
    $featureKey = 'products';
    $plan = 'profesional';

    $limitEntity = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimitInterface::class);
    $limitEntity->method('get')->with('limit_value')->willReturn(-1);
    $limitEntity->method('status')->willReturn(TRUE);

    $this->upgradeTriggerService->method('getVerticalLimit')
      ->with('comercioconecta', $plan, $featureKey)
      ->willReturn($limitEntity);

    $result = $this->service->check($userId, $featureKey, $plan);

    $this->assertInstanceOf(FeatureGateResult::class, $result);
    $this->assertTrue($result->isAllowed());
    $this->assertEquals(-1, $result->remaining);
  }

  /**
   * Tests checking a feature not included in the plan (limit = 0).
   *
   * @covers ::check
   */
  public function testCheckFeatureNotIncluded(): void {
    $userId = 123;
    $featureKey = 'pos_integration';
    $plan = 'free';

    $limitEntity = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimitInterface::class);
    $limitEntity->method('get')->willReturnMap([
      ['limit_value', 0],
      ['upgrade_message', 'Upgrade to access POS integration.'],
    ]);
    $limitEntity->method('status')->willReturn(TRUE);

    $this->upgradeTriggerService->method('getVerticalLimit')
      ->with('comercioconecta', $plan, $featureKey)
      ->willReturn($limitEntity);

    $result = $this->service->check($userId, $featureKey, $plan);

    $this->assertInstanceOf(FeatureGateResult::class, $result);
    $this->assertFalse($result->isAllowed());
    $this->assertEquals(0, $result->remaining);
  }

}
