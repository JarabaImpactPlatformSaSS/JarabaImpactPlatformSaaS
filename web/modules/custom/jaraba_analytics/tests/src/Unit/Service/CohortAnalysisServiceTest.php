<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Entity\CohortDefinition;
use Drupal\jaraba_analytics\Service\CohortAnalysisService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the CohortAnalysisService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\CohortAnalysisService
 */
class CohortAnalysisServiceTest extends TestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_analytics\Service\CohortAnalysisService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new CohortAnalysisService(
      $this->entityTypeManager,
      $this->database,
    );
  }

  /**
   * Helper to create a mock CohortDefinition entity.
   *
   * @param array $options
   *   Overrides: cohort_type, date_range_start, date_range_end, tenant_id,
   *   filters, name.
   *
   * @return \Drupal\jaraba_analytics\Entity\CohortDefinition|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked cohort definition.
   */
  protected function createMockCohort(array $options = []) {
    $cohort = $this->createMock(CohortDefinition::class);

    $cohort->method('getCohortType')
      ->willReturn($options['cohort_type'] ?? CohortDefinition::TYPE_REGISTRATION_DATE);
    $cohort->method('getDateRangeStart')
      ->willReturn($options['date_range_start'] ?? NULL);
    $cohort->method('getDateRangeEnd')
      ->willReturn($options['date_range_end'] ?? NULL);
    $cohort->method('getTenantId')
      ->willReturn($options['tenant_id'] ?? NULL);
    $cohort->method('getFilters')
      ->willReturn($options['filters'] ?? []);
    $cohort->method('getName')
      ->willReturn($options['name'] ?? 'Test Cohort');

    return $cohort;
  }

  /**
   * Helper to create a mock Select query that returns given column values.
   *
   * @param array $fetchColValues
   *   Values to return from fetchCol().
   * @param mixed $fetchFieldValue
   *   Value to return from fetchField(), or NULL.
   *
   * @return \Drupal\Core\Database\Query\Select|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked select query.
   */
  protected function createMockSelectQuery(array $fetchColValues = [], $fetchFieldValue = NULL) {
    $query = $this->createMock(Select::class);
    $query->method('fields')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('addField')->willReturnSelf();
    $query->method('isNotNull')->willReturnSelf();
    $query->method('distinct')->willReturnSelf();
    $query->method('groupBy')->willReturnSelf();
    $query->method('innerJoin')->willReturn('alias');

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchCol')->willReturn($fetchColValues);
    $statement->method('fetchField')->willReturn($fetchFieldValue);

    $query->method('execute')->willReturn($statement);

    return $query;
  }

  /**
   * Tests buildRetentionCurve returns zeroes when getCohortMembers is empty.
   *
   * When the cohort has no members (e.g. the registration date query returns
   * no user IDs), buildRetentionCurve should return an array of 0.0 values
   * with length equal to the requested number of weeks.
   *
   * @covers ::buildRetentionCurve
   */
  public function testBuildRetentionCurveReturnsEmptyForNoCohort(): void {
    $cohort = $this->createMockCohort([
      'cohort_type' => CohortDefinition::TYPE_REGISTRATION_DATE,
      'date_range_start' => '2025-01-01',
      'date_range_end' => '2025-01-31',
    ]);

    // Query returns no user IDs.
    $emptyQuery = $this->createMockSelectQuery([]);
    $this->database->method('select')
      ->willReturn($emptyQuery);

    $result = $this->service->buildRetentionCurve($cohort, 6);

    $this->assertCount(6, $result);
    foreach ($result as $value) {
      $this->assertSame(0.0, $value);
    }
  }

  /**
   * Tests buildRetentionCurve queries the correct date ranges from cohort.
   *
   * Verifies that when a cohort has members and a start date, the service
   * queries analytics_event with week-based timestamp boundaries derived
   * from the cohort's date_range_start. Week 0 should always be 100%.
   *
   * @covers ::buildRetentionCurve
   */
  public function testBuildRetentionCurveQueriesCorrectDateRange(): void {
    $cohort = $this->createMockCohort([
      'cohort_type' => CohortDefinition::TYPE_REGISTRATION_DATE,
      'date_range_start' => '2025-01-01',
      'date_range_end' => '2025-01-31',
      'tenant_id' => NULL,
    ]);

    $memberUserIds = ['1', '2', '3', '4', '5'];
    $memberQuery = $this->createMockSelectQuery($memberUserIds);

    // For the retention weeks, return decreasing active user counts.
    $weekQueries = [];
    $activeCounts = [4, 3, 2];
    foreach ($activeCounts as $count) {
      $weekQueries[] = $this->createMockSelectQuery([], $count);
    }

    $callIndex = 0;
    $allQueries = array_merge([$memberQuery], $weekQueries);
    $this->database->method('select')
      ->willReturnCallback(function () use (&$callIndex, $allQueries) {
        $query = $allQueries[$callIndex] ?? $allQueries[0];
        $callIndex++;
        return $query;
      });

    $result = $this->service->buildRetentionCurve($cohort, 4);

    // Week 0 is always 100%.
    $this->assertSame(100.0, $result[0]);

    // Weeks 1-3 should be calculated as (activeCount / totalMembers) * 100.
    // 4/5 = 80%, 3/5 = 60%, 2/5 = 40%.
    $this->assertSame(80.0, $result[1]);
    $this->assertSame(60.0, $result[2]);
    $this->assertSame(40.0, $result[3]);

    $this->assertCount(4, $result);
  }

  /**
   * Tests compareCohorts builds retention curves for each cohort ID.
   *
   * When comparing two cohorts, the service should load both entities via
   * storage->loadMultiple and return a result array keyed by cohort ID,
   * each containing name, type, members_count, and retention data.
   *
   * @covers ::compareCohorts
   */
  public function testCompareCohortsBuildsTwoCurves(): void {
    $cohortA = $this->createMockCohort([
      'name' => 'Cohort A',
      'cohort_type' => CohortDefinition::TYPE_REGISTRATION_DATE,
      'date_range_start' => '2025-01-01',
      'date_range_end' => '2025-01-31',
    ]);

    $cohortB = $this->createMockCohort([
      'name' => 'Cohort B',
      'cohort_type' => CohortDefinition::TYPE_FIRST_PURCHASE,
      'date_range_start' => '2025-02-01',
      'date_range_end' => '2025-02-28',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')
      ->with([10, 20])
      ->willReturn([10 => $cohortA, 20 => $cohortB]);

    $this->entityTypeManager->method('getStorage')
      ->with('cohort_definition')
      ->willReturn($storage);

    // Both cohorts return empty members, so retention will be all zeroes.
    $emptyQuery = $this->createMockSelectQuery([]);
    $this->database->method('select')
      ->willReturn($emptyQuery);

    $results = $this->service->compareCohorts([10, 20]);

    // Should have two entries keyed by ID.
    $this->assertCount(2, $results);
    $this->assertArrayHasKey(10, $results);
    $this->assertArrayHasKey(20, $results);

    // Each entry has the expected structure.
    $this->assertSame('Cohort A', $results[10]['name']);
    $this->assertSame(CohortDefinition::TYPE_REGISTRATION_DATE, $results[10]['type']);
    $this->assertArrayHasKey('members_count', $results[10]);
    $this->assertArrayHasKey('retention', $results[10]);

    $this->assertSame('Cohort B', $results[20]['name']);
    $this->assertSame(CohortDefinition::TYPE_FIRST_PURCHASE, $results[20]['type']);
    $this->assertArrayHasKey('members_count', $results[20]);
    $this->assertArrayHasKey('retention', $results[20]);
  }

  /**
   * Tests getCohortMembers dispatches to the correct method based on type.
   *
   * Each cohort_type value should route to a different internal method
   * that queries different database tables/fields. We verify the type
   * routing by checking registration_date queries users_field_data
   * while first_purchase queries analytics_event with event_type = purchase.
   *
   * @covers ::getCohortMembers
   */
  public function testGetCohortMembersQueriesByType(): void {
    // Test TYPE_REGISTRATION_DATE queries users_field_data.
    $registrationCohort = $this->createMockCohort([
      'cohort_type' => CohortDefinition::TYPE_REGISTRATION_DATE,
      'date_range_start' => '2025-01-01',
      'date_range_end' => '2025-03-31',
    ]);

    $registrationQuery = $this->createMockSelectQuery(['10', '20', '30']);
    $tablesCalled = [];
    $this->database->method('select')
      ->willReturnCallback(function ($table) use (&$tablesCalled, $registrationQuery) {
        $tablesCalled[] = is_string($table) ? $table : 'subquery';
        return $registrationQuery;
      });

    $members = $this->service->getCohortMembers($registrationCohort);

    $this->assertSame([10, 20, 30], $members);
    $this->assertContains('users_field_data', $tablesCalled);

    // Reset and test TYPE_FIRST_PURCHASE queries analytics_event.
    $tablesCalled = [];
    $purchaseCohort = $this->createMockCohort([
      'cohort_type' => CohortDefinition::TYPE_FIRST_PURCHASE,
      'date_range_start' => '2025-01-01',
      'date_range_end' => '2025-06-30',
    ]);

    $purchaseSubquery = $this->createMockSelectQuery(['5', '15']);
    $this->database = $this->createMock(Connection::class);
    $purchaseTablesCalled = [];
    $this->database->method('select')
      ->willReturnCallback(function ($table) use (&$purchaseTablesCalled, $purchaseSubquery) {
        $purchaseTablesCalled[] = is_string($table) ? $table : 'subquery';
        return $purchaseSubquery;
      });

    // Recreate service with new database mock.
    $this->service = new CohortAnalysisService(
      $this->entityTypeManager,
      $this->database,
    );

    $members = $this->service->getCohortMembers($purchaseCohort);

    $this->assertSame([5, 15], $members);
    // First purchase queries analytics_event first (subquery), then wraps it.
    $this->assertContains('analytics_event', $purchaseTablesCalled);
  }

  /**
   * Tests getCohortMembers for TYPE_VERTICAL includes vertical filtering.
   *
   * @covers ::getCohortMembers
   */
  public function testGetCohortMembersVerticalQueriesAnalyticsEvent(): void {
    $verticalCohort = $this->createMockCohort([
      'cohort_type' => CohortDefinition::TYPE_VERTICAL,
      'date_range_start' => '2025-01-01',
      'date_range_end' => '2025-06-30',
      'filters' => ['vertical' => 'empleabilidad'],
    ]);

    // First query (analytics_event) returns user IDs.
    $analyticsQuery = $this->createMockSelectQuery(['100', '200']);
    // Second query (group_relationship_field_data) filters by vertical.
    $groupQuery = $this->createMockSelectQuery(['100']);

    $callIndex = 0;
    $this->database->method('select')
      ->willReturnCallback(function () use (&$callIndex, $analyticsQuery, $groupQuery) {
        $callIndex++;
        return $callIndex === 1 ? $analyticsQuery : $groupQuery;
      });

    $members = $this->service->getCohortMembers($verticalCohort);

    $this->assertSame([100], $members);
  }

  /**
   * Tests getCohortMembers for TYPE_CUSTOM applies custom filter conditions.
   *
   * @covers ::getCohortMembers
   */
  public function testGetCohortMembersCustomAppliesFilters(): void {
    $customCohort = $this->createMockCohort([
      'cohort_type' => CohortDefinition::TYPE_CUSTOM,
      'date_range_start' => '2025-01-01',
      'date_range_end' => '2025-12-31',
      'filters' => [
        'event_type' => 'purchase',
        'country' => 'ES',
      ],
    ]);

    $customQuery = $this->createMockSelectQuery(['7', '14', '21']);
    $this->database->method('select')
      ->willReturn($customQuery);

    // Verify that condition is called with the filter values.
    $conditionCalls = [];
    $customQuery->method('condition')
      ->willReturnCallback(function ($field, $value = NULL, $operator = NULL) use ($customQuery, &$conditionCalls) {
        $conditionCalls[] = [$field, $value, $operator];
        return $customQuery;
      });

    $members = $this->service->getCohortMembers($customCohort);

    $this->assertSame([7, 14, 21], $members);

    // Verify that filter-based conditions were applied.
    $fieldNames = array_column($conditionCalls, 0);
    $this->assertContains('ae.event_type', $fieldNames);
    $this->assertContains('ae.country', $fieldNames);
  }

  /**
   * Tests buildRetentionCurve returns zeroes when cohort has no start date.
   *
   * @covers ::buildRetentionCurve
   */
  public function testBuildRetentionCurveReturnsZeroesForNoStartDate(): void {
    $cohort = $this->createMockCohort([
      'cohort_type' => CohortDefinition::TYPE_REGISTRATION_DATE,
      'date_range_start' => NULL,
      'date_range_end' => '2025-01-31',
    ]);

    // Members query returns results, but start date is NULL.
    $memberQuery = $this->createMockSelectQuery(['1', '2']);
    $this->database->method('select')
      ->willReturn($memberQuery);

    $result = $this->service->buildRetentionCurve($cohort, 4);

    $this->assertCount(4, $result);
    foreach ($result as $value) {
      $this->assertSame(0.0, $value);
    }
  }

}
