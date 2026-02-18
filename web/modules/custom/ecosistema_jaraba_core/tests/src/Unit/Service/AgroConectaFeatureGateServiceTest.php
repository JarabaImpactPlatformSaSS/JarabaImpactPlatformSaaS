<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimitInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
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

    $this->service = new AgroConectaFeatureGateService(
      $this->upgradeTriggerService,
      $this->database,
      $this->currentUser,
      $this->logger,
      $this->tenantContext,
    );
  }

  /**
   * Tests checking a limit that has not been reached.
   *
   * @covers ::check
   */
  public function testCheckLimitNotReached(): void {
    $userId = 123;
    $featureKey = 'products';
    $plan = 'free'; // Limit 5

    // Mock FreemiumLimit
    $limit = $this->createMock(FreemiumVerticalLimitInterface::class);
    $limit->method('getLimitValue')->willReturn(5);
    $limit->method('status')->willReturn(TRUE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('agroconecta_free_products')->willReturn($limit);
    $this->entityTypeManager->method('getStorage')->with('freemium_vertical_limit')->willReturn($storage);

    // Mock DB usage check
    $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
    $statement->method('fetchField')->willReturn(3); // Usage is 3

    $query = $this->createMock(\Drupal\Core\Database\Query\SelectInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($query);

    // Act
    $result = $this->service->check($userId, $featureKey, $plan);

    // Assert
    $this->assertInstanceOf(FeatureGateResult::class, $result);
    $this->assertTrue($result->isAllowed());
    $this->assertEquals(2, $result->getRemaining());
  }

  /**
   * Tests checking a limit that has been reached.
   *
   * @covers ::check
   */
  public function testCheckLimitReached(): void {
    $userId = 123;
    $featureKey = 'products';
    $plan = 'free'; // Limit 5

    // Mock FreemiumLimit
    $limit = $this->createMock(FreemiumVerticalLimitInterface::class);
    $limit->method('getLimitValue')->willReturn(5);
    $limit->method('status')->willReturn(TRUE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('agroconecta_free_products')->willReturn($limit);
    $this->entityTypeManager->method('getStorage')->with('freemium_vertical_limit')->willReturn($storage);

    // Mock DB usage check
    $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
    $statement->method('fetchField')->willReturn(5); // Usage is 5 (reached)

    $query = $this->createMock(\Drupal\Core\Database\Query\SelectInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($query);

    // Expect upgrade trigger fire
    $this->upgradeTriggerService->expects($this->once())
      ->method('fire')
      ->with('agro_products_limit_reached');

    // Act
    $result = $this->service->check($userId, $featureKey, $plan);

    // Assert
    $this->assertInstanceOf(FeatureGateResult::class, $result);
    $this->assertFalse($result->isAllowed());
    $this->assertEquals(0, $result->getRemaining());
  }

  /**
   * Tests checking an unlimited feature (-1).
   *
   * @covers ::check
   */
  public function testCheckUnlimited(): void {
    $userId = 123;
    $featureKey = 'products';
    $plan = 'profesional'; // Unlimited

    // Mock FreemiumLimit
    $limit = $this->createMock(FreemiumVerticalLimitInterface::class);
    $limit->method('getLimitValue')->willReturn(-1);
    $limit->method('status')->willReturn(TRUE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('agroconecta_profesional_products')->willReturn($limit);
    $this->entityTypeManager->method('getStorage')->with('freemium_vertical_limit')->willReturn($storage);

    // Act
    $result = $this->service->check($userId, $featureKey, $plan);

    // Assert
    $this->assertTrue($result->isAllowed());
    $this->assertEquals(-1, $result->getRemaining());
  }

}
