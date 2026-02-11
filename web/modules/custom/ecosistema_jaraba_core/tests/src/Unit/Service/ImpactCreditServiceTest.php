<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\Schema;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\ImpactCreditService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ImpactCreditService.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ImpactCreditService
 * @group ecosistema_jaraba_core
 */
class ImpactCreditServiceTest extends UnitTestCase
{

    /**
     * The service under test.
     */
    protected ImpactCreditService $service;

    /**
     * Mocked database connection.
     */
    protected Connection $database;

    /**
     * Mocked entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Mocked logger.
     */
    protected LoggerInterface $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(Connection::class);
        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new ImpactCreditService(
            $this->database,
            $this->entityTypeManager,
            $this->logger
        );
    }

    /**
     * @covers ::getBalance
     */
    public function testGetBalanceReturnsZeroWhenTableNotExists(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())
            ->method('tableExists')
            ->with('impact_credits_balance')
            ->willReturn(FALSE);

        $this->database->expects($this->once())
            ->method('schema')
            ->willReturn($schema);

        $balance = $this->service->getBalance(1);

        $this->assertEquals(0, $balance);
    }

    /**
     * @covers ::getBalance
     */
    public function testGetBalanceReturnsCorrectValue(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())
            ->method('tableExists')
            ->with('impact_credits_balance')
            ->willReturn(TRUE);

        $this->database->expects($this->once())
            ->method('schema')
            ->willReturn($schema);

        $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
        $statement->expects($this->once())
            ->method('fetchField')
            ->willReturn(250);

        $select = $this->createMock(Select::class);
        $select->expects($this->once())
            ->method('fields')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('condition')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('execute')
            ->willReturn($statement);

        $this->database->expects($this->once())
            ->method('select')
            ->with('impact_credits_balance', 'b')
            ->willReturn($select);

        $balance = $this->service->getBalance(1);

        $this->assertEquals(250, $balance);
    }

    /**
     * @covers ::awardCredits
     */
    public function testCreditValuesContainsExpectedReasons(): void
    {
        // Verify CREDIT_VALUES constant has expected entries
        $this->assertEquals(20, ImpactCreditService::CREDIT_VALUES['apply_job']);
        $this->assertEquals(500, ImpactCreditService::CREDIT_VALUES['get_hired']);
        $this->assertEquals(50, ImpactCreditService::CREDIT_VALUES['complete_diagnostic']);
        $this->assertArrayHasKey('complete_course', ImpactCreditService::CREDIT_VALUES);
        $this->assertArrayHasKey('daily_login', ImpactCreditService::CREDIT_VALUES);
    }

    /**
     * @covers ::getLeaderboard
     */
    public function testGetLeaderboardReturnsEmptyWhenTableNotExists(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())
            ->method('tableExists')
            ->with('impact_credits_balance')
            ->willReturn(FALSE);

        $this->database->expects($this->once())
            ->method('schema')
            ->willReturn($schema);

        $leaderboard = $this->service->getLeaderboard(10);

        $this->assertEmpty($leaderboard);
    }

    /**
     * @covers ::getLeaderboard
     */
    public function testGetLeaderboardReturnsRankedUsers(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())
            ->method('tableExists')
            ->with('impact_credits_balance')
            ->willReturn(TRUE);

        $this->database->expects($this->once())
            ->method('schema')
            ->willReturn($schema);

        $leader1 = (object) ['user_id' => 1, 'total_credits' => 1000, 'name' => 'User 1'];
        $leader2 = (object) ['user_id' => 2, 'total_credits' => 750, 'name' => 'User 2'];
        $leader3 = (object) ['user_id' => 3, 'total_credits' => 500, 'name' => 'User 3'];

        $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
        $statement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([$leader1, $leader2, $leader3]);

        $select = $this->createMock(Select::class);
        $select->expects($this->any())
            ->method('fields')
            ->willReturnSelf();
        $select->expects($this->any())
            ->method('join')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('range')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('execute')
            ->willReturn($statement);

        $this->database->expects($this->once())
            ->method('select')
            ->with('impact_credits_balance', 'b')
            ->willReturn($select);

        $leaderboard = $this->service->getLeaderboard(10);

        $this->assertCount(3, $leaderboard);
        $this->assertEquals(1000, $leaderboard[0]['credits']);
    }

}
