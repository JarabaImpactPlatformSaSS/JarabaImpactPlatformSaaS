<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\Schema;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService;
use Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for JarabaLexFeatureGateService.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService
 * @group ecosistema_jaraba_core
 */
class JarabaLexFeatureGateServiceTest extends UnitTestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService
   */
  protected JarabaLexFeatureGateService $service;

  /**
   * Mock UpgradeTriggerService.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $upgradeTrigger;

  /**
   * Mock database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * Mock current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock database schema.
   *
   * @var \Drupal\Core\Database\Schema|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $schema;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->upgradeTrigger = $this->createMock(UpgradeTriggerService::class);
    $this->database = $this->createMock(Connection::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Schema mock: table always exists to avoid CREATE TABLE calls.
    $this->schema = $this->createMock(Schema::class);
    $this->schema->method('tableExists')->willReturn(TRUE);
    $this->database->method('schema')->willReturn($this->schema);

    $this->service = new JarabaLexFeatureGateService(
      $this->upgradeTrigger,
      $this->database,
      $this->currentUser,
      $this->logger,
    );
  }

  /**
   * @covers ::check
   */
  public function testCheckReturnsAllowedWhenNoLimitEntityConfigured(): void {
    $this->upgradeTrigger->method('getVerticalLimit')
      ->with('jarabalex', 'free', 'searches_per_month')
      ->willReturn(NULL);

    $result = $this->service->check(1, 'searches_per_month', 'free');

    $this->assertTrue($result->allowed);
    $this->assertSame('searches_per_month', $result->featureKey);
    $this->assertSame('free', $result->currentPlan);
  }

  /**
   * @covers ::check
   */
  public function testCheckReturnsAllowedForUnlimitedFeature(): void {
    $limitEntity = $this->createLimitEntityMock(-1, '');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $result = $this->service->check(1, 'max_alerts', 'profesional');

    $this->assertTrue($result->allowed);
    $this->assertSame(-1, $result->remaining);
    $this->assertSame(-1, $result->limit);
  }

  /**
   * @covers ::check
   */
  public function testCheckReturnsDeniedForFeatureNotIncludedInPlan(): void {
    $limitEntity = $this->createLimitEntityMock(0, 'Upgrade to access citations.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $result = $this->service->check(1, 'citation_insert', 'free');

    $this->assertFalse($result->allowed);
    $this->assertSame(0, $result->remaining);
    $this->assertSame('starter', $result->upgradePlan);
    $this->assertSame('Upgrade to access citations.', $result->upgradeMessage);
  }

  /**
   * @covers ::check
   */
  public function testCheckReturnsDeniedWhenUsageLimitReached(): void {
    $limitEntity = $this->createLimitEntityMock(10, 'Has alcanzado el limite de tu plan.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    // Mock getUsageCount: user has used 10 out of 10.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn(10);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('jarabalex_feature_usage', 'u')
      ->willReturn($select);

    $result = $this->service->check(1, 'searches_per_month', 'free');

    $this->assertFalse($result->allowed);
    $this->assertSame(0, $result->remaining);
    $this->assertSame(10, $result->limit);
  }

  /**
   * @covers ::check
   */
  public function testCheckReturnsAllowedWithRemainingWhenUnderLimit(): void {
    $limitEntity = $this->createLimitEntityMock(10, '');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    // Mock getUsageCount: user has used 3 out of 10.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn(3);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('jarabalex_feature_usage', 'u')
      ->willReturn($select);

    $result = $this->service->check(1, 'searches_per_month', 'free');

    $this->assertTrue($result->allowed);
    $this->assertSame(7, $result->remaining);
    $this->assertSame(10, $result->limit);
    $this->assertSame(3, $result->used);
  }

  /**
   * @covers ::check
   */
  public function testCheckUpgradePlanMapping(): void {
    // For 'free' plan, upgrade should be 'starter'.
    $limitEntity = $this->createLimitEntityMock(0, 'Upgrade required.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $result = $this->service->check(1, 'api_access', 'free');
    $this->assertSame('starter', $result->upgradePlan);

    // For 'starter' plan, upgrade should be 'profesional'.
    $result = $this->service->check(1, 'api_access', 'starter');
    $this->assertSame('profesional', $result->upgradePlan);

    // For 'profesional' plan, upgrade should be 'business'.
    $result = $this->service->check(1, 'api_access', 'profesional');
    $this->assertSame('business', $result->upgradePlan);
  }

  /**
   * @covers ::recordUsage
   */
  public function testRecordUsageInsertsNewRecordWhenNoneExists(): void {
    // Mock select returning no existing record.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchObject')->willReturn(FALSE);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('jarabalex_feature_usage', 'u')
      ->willReturn($select);

    // Mock insert.
    $insert = $this->createMock(Insert::class);
    $insert->method('fields')->willReturnSelf();
    $insert->expects($this->once())->method('execute');

    $this->database->method('insert')
      ->with('jarabalex_feature_usage')
      ->willReturn($insert);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Feature usage recorded'),
        $this->anything(),
      );

    $this->service->recordUsage(1, 'searches_per_month');
  }

  /**
   * @covers ::recordUsage
   */
  public function testRecordUsageUpdatesExistingRecord(): void {
    // Mock select returning an existing record with usage_count = 5.
    $existing = (object) ['id' => 42, 'usage_count' => 5];
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchObject')->willReturn($existing);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('jarabalex_feature_usage', 'u')
      ->willReturn($select);

    // Mock update: should set usage_count to 6.
    $update = $this->createMock(Update::class);
    $update->method('fields')
      ->with(['usage_count' => 6])
      ->willReturnSelf();
    $update->method('condition')->willReturnSelf();
    $update->expects($this->once())->method('execute');

    $this->database->method('update')
      ->with('jarabalex_feature_usage')
      ->willReturn($update);

    $this->service->recordUsage(1, 'searches_per_month');
  }

  /**
   * @covers ::check
   */
  public function testPlanLimitsEnforcedForAllFeatureKeys(): void {
    $featureKeys = [
      'searches_per_month',
      'max_alerts',
      'max_bookmarks',
      'citation_insert',
      'digest_access',
      'api_access',
    ];

    foreach ($featureKeys as $featureKey) {
      $limitEntity = $this->createLimitEntityMock(0, 'Not available.');
      $this->upgradeTrigger->expects($this->atLeastOnce())
        ->method('getVerticalLimit')
        ->willReturn($limitEntity);

      $result = $this->service->check(99, $featureKey, 'free');
      $this->assertFalse(
        $result->allowed,
        "Feature '$featureKey' should be denied for limit_value=0.",
      );
    }
  }

  /**
   * Creates a mock limit entity that responds to get().
   *
   * @param int $limitValue
   *   The limit_value to return.
   * @param string $upgradeMessage
   *   The upgrade_message to return.
   *
   * @return object
   *   A mock limit entity.
   */
  protected function createLimitEntityMock(int $limitValue, string $upgradeMessage): object {
    $entity = new \stdClass();
    $entity->_limitValue = $limitValue;
    $entity->_upgradeMessage = $upgradeMessage;

    // Use anonymous class so get() method works.
    return new class($limitValue, $upgradeMessage) {

      public function __construct(
        protected int $limitValue,
        protected string $upgradeMessage,
      ) {}

      public function get(string $field): int|string {
        return match ($field) {
          'limit_value' => $this->limitValue,
          'upgrade_message' => $this->upgradeMessage,
          default => '',
        };
      }

    };
  }

}
