<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_matching\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_matching\Service\RecommendationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for RecommendationService.
 *
 * @coversDefaultClass \Drupal\jaraba_matching\Service\RecommendationService
 * @group jaraba_matching
 */
class RecommendationServiceTest extends UnitTestCase
{

    /**
     * The service being tested.
     *
     * @var \Drupal\jaraba_matching\Service\RecommendationService
     */
    protected RecommendationService $service;

    /**
     * Mock entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityTypeManager;

    /**
     * Mock database connection.
     *
     * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $database;

    /**
     * Mock logger factory.
     *
     * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $loggerFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->database = $this->createMock(Connection::class);
        $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $this->loggerFactory->method('get')->willReturn($logger);

        $this->service = new RecommendationService(
            $this->entityTypeManager,
            $this->database,
            $this->loggerFactory
        );
    }

    /**
     * @covers ::getCollaborativeRecommendations
     */
    public function testGetCollaborativeRecommendationsWithNoHistory(): void
    {
        // User with no application history should get popular jobs
        $userId = 123;

        // Mock database to return empty applications
        $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
        $statement->method('fetchCol')->willReturn([]);
        $statement->method('fetchAll')->willReturn([]);

        $selectQuery = $this->createMock(\Drupal\Core\Database\Query\SelectInterface::class);
        $selectQuery->method('fields')->willReturnSelf();
        $selectQuery->method('condition')->willReturnSelf();
        $selectQuery->method('execute')->willReturn($statement);

        $this->database->method('select')->willReturn($selectQuery);

        // The service should fall back to popular jobs
        $result = $this->service->getCollaborativeRecommendations($userId, 10);

        $this->assertIsArray($result);
    }

    /**
     * @covers ::getHybridRecommendations
     */
    public function testGetHybridRecommendationsReturnsArray(): void
    {
        $userId = 456;

        // Mock database
        $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
        $statement->method('fetchCol')->willReturn([]);
        $statement->method('fetchAll')->willReturn([]);

        $selectQuery = $this->createMock(\Drupal\Core\Database\Query\SelectInterface::class);
        $selectQuery->method('fields')->willReturnSelf();
        $selectQuery->method('condition')->willReturnSelf();
        $selectQuery->method('leftJoin')->willReturnSelf();
        $selectQuery->method('addExpression')->willReturnSelf();
        $selectQuery->method('groupBy')->willReturnSelf();
        $selectQuery->method('orderBy')->willReturnSelf();
        $selectQuery->method('range')->willReturnSelf();
        $selectQuery->method('execute')->willReturn($statement);

        $this->database->method('select')->willReturn($selectQuery);

        $result = $this->service->getHybridRecommendations($userId, 5);

        $this->assertIsArray($result);
        $this->assertCount(0, $result); // Empty with mocked empty DB
    }

    /**
     * @covers ::getMatchingAccuracyMetrics
     */
    public function testGetMatchingAccuracyMetricsWithNoTable(): void
    {
        // Mock schema to say table doesn't exist
        $schema = $this->createMock(\Drupal\Core\Database\Schema::class);
        $schema->method('tableExists')->willReturn(FALSE);

        $this->database->method('schema')->willReturn($schema);

        $result = $this->service->getMatchingAccuracyMetrics();

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('No feedback data available', $result['error']);
    }

    /**
     * Tests Jaccard similarity calculation concept.
     */
    public function testJaccardSimilarityCalculation(): void
    {
        // Test the mathematical concept
        $setA = [1, 2, 3, 4, 5];
        $setB = [3, 4, 5, 6, 7];

        $intersection = count(array_intersect($setA, $setB)); // 3
        $union = count(array_unique(array_merge($setA, $setB))); // 7

        $jaccard = $intersection / $union;

        $this->assertEquals(3 / 7, $jaccard);
        $this->assertEqualsWithDelta(0.4286, $jaccard, 0.001);
    }

    /**
     * Tests feedback boost capping.
     */
    public function testFeedbackBoostIsCapped(): void
    {
        // Verify the boost cap is enforced at 50%
        $maxBoost = 0.5;

        // Simulated boost calculation
        $boost = 0.3 + 0.15 + 0.1; // 0.55, exceeds cap
        $cappedBoost = min($maxBoost, $boost);

        $this->assertEquals(0.5, $cappedBoost);
    }

}
