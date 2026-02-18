<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimitInterface;
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

    $this->service = new ComercioConectaFeatureGateService(
      $this->upgradeTriggerService,
      $this->database,
      $this->currentUser,
      $this->logger,
      $this->tenantContext,
    );
  }

  /**
   * Tests checking a limit that has been reached.
   *
   * @covers ::check
   */
  public function testCheckLimitReached(): void {
    $userId = 123;
    $featureKey = 'product';
    $plan = 'free';

    // Mock FreemiumLimit
    $limit = $this->createMock(FreemiumVerticalLimitInterface::class);
    $limit->method('getLimitValue')->willReturn(10);
    $limit->method('status')->willReturn(TRUE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('comercio_free_product')->willReturn($limit);
    $this->entityTypeManager->method('getStorage')->with('freemium_vertical_limit')->willReturn($storage);

    // Mock DB usage check
    $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
    $statement->method('fetchField')->willReturn(10);

    $query = $this->createMock(\Drupal\Core\Database\Query\SelectInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($query);

    // Expect upgrade trigger fire
    $this->upgradeTriggerService->expects($this->once())
      ->method('fire')
      ->with('comercio_product_limit_reached');

    // Act
    $result = $this->service->check($userId, $featureKey, $plan);

    // Assert
    $this->assertFalse($result->isAllowed());
  }

}
