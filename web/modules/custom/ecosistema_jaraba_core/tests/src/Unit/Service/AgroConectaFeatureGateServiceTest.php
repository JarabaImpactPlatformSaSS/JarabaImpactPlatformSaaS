<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\AgroConectaFeatureGateService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for AgroConectaFeatureGateService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\AgroConectaFeatureGateService
 */
class AgroConectaFeatureGateServiceTest extends UnitTestCase {

  protected UpgradeTriggerService $upgradeTriggerService;
  protected Connection $database;
  protected AccountProxyInterface $currentUser;
  protected LoggerInterface $logger;
  protected TenantContextService $tenantContext;
  protected AgroConectaFeatureGateService $service;

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

    $this->service = new AgroConectaFeatureGateService(
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

    // When no limit entity is found, the service returns allowed.
    $this->upgradeTriggerService->method('getVerticalLimit')
      ->with('agroconecta', $plan, $featureKey)
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

    // Mock a limit entity that returns -1 (unlimited).
    $limitEntity = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimitInterface::class);
    $limitEntity->method('get')->with('limit_value')->willReturn(-1);
    $limitEntity->method('status')->willReturn(TRUE);

    $this->upgradeTriggerService->method('getVerticalLimit')
      ->with('agroconecta', $plan, $featureKey)
      ->willReturn($limitEntity);

    $result = $this->service->check($userId, $featureKey, $plan);

    $this->assertInstanceOf(FeatureGateResult::class, $result);
    $this->assertTrue($result->isAllowed());
    $this->assertEquals(-1, $result->remaining);
  }

  /**
   * Tests checking a feature that is not included in the plan (limit = 0).
   *
   * @covers ::check
   */
  public function testCheckFeatureNotIncluded(): void {
    $userId = 123;
    $featureKey = 'partner_hub';
    $plan = 'free';

    // Mock a limit entity with value 0 (not included).
    $limitEntity = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimitInterface::class);
    $limitEntity->method('get')->willReturnMap([
      ['limit_value', 0],
      ['upgrade_message', 'Upgrade to access Partner Hub.'],
    ]);
    $limitEntity->method('status')->willReturn(TRUE);

    $this->upgradeTriggerService->method('getVerticalLimit')
      ->with('agroconecta', $plan, $featureKey)
      ->willReturn($limitEntity);

    $result = $this->service->check($userId, $featureKey, $plan);

    $this->assertInstanceOf(FeatureGateResult::class, $result);
    $this->assertFalse($result->isAllowed());
    $this->assertEquals(0, $result->remaining);
  }

}
