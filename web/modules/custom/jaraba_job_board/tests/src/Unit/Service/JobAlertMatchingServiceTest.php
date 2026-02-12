<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_job_board\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Schema;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\jaraba_job_board\Service\JobAlertMatchingService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for JobAlertMatchingService.
 *
 * @coversDefaultClass \Drupal\jaraba_job_board\Service\JobAlertMatchingService
 * @group jaraba_job_board
 */
class JobAlertMatchingServiceTest extends UnitTestCase
{

    /**
     * The service under test.
     */
    protected JobAlertMatchingService $service;

    /**
     * Mocked database connection.
     */
    protected Connection $database;

    /**
     * Mocked entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Mocked queue factory.
     */
    protected QueueFactory $queueFactory;

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
        $this->queueFactory = $this->createMock(QueueFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new JobAlertMatchingService(
            $this->database,
            $this->entityTypeManager,
            $this->queueFactory,
            $this->logger
        );
    }

    /**
     * @covers ::createAlertFromSearch
     */
    public function testCreateAlertFromSearch(): void
    {
        // Setup schema mock
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->any())
            ->method('tableExists')
            ->willReturn(TRUE);
        $schema->expects($this->any())
            ->method('createTable');

        $this->database->expects($this->any())
            ->method('schema')
            ->willReturn($schema);

        // Setup insert mock
        $this->database->expects($this->once())
            ->method('insert')
            ->with('job_alert')
            ->willReturnCallback(function () {
                $insert = $this->createMock(\Drupal\Core\Database\Query\Insert::class);
                $insert->expects($this->once())
                    ->method('fields')
                    ->willReturnSelf();
                $insert->expects($this->once())
                    ->method('execute')
                    ->willReturn(123);
                return $insert;
            });

        $filters = [
            'name' => 'Mi alerta de desarrollador',
            'keywords' => 'PHP Drupal',
            'locations' => [['city' => 'Madrid']],
            'remote_types' => ['remote', 'hybrid'],
        ];

        $alertId = $this->service->createAlertFromSearch(1, $filters, 'daily');

        $this->assertEquals(123, $alertId);
    }

    /**
     * @covers ::followCompany
     */
    public function testFollowCompany(): void
    {
        // Setup schema mock
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->any())
            ->method('tableExists')
            ->willReturn(TRUE);

        $this->database->expects($this->any())
            ->method('schema')
            ->willReturn($schema);

        // Setup merge mock
        $merge = $this->createMock(Merge::class);
        $merge->expects($this->once())
            ->method('keys')
            ->willReturnSelf();
        $merge->expects($this->once())
            ->method('fields')
            ->willReturnSelf();
        $merge->expects($this->once())
            ->method('execute')
            ->willReturn(1);

        $this->database->expects($this->once())
            ->method('merge')
            ->with('company_follow')
            ->willReturn($merge);

        $result = $this->service->followCompany(1, 100);

        $this->assertTrue($result);
    }

    /**
     * @covers ::unfollowCompany
     */
    public function testUnfollowCompany(): void
    {
        $delete = $this->createMock(\Drupal\Core\Database\Query\Delete::class);
        $delete->expects($this->exactly(2))
            ->method('condition')
            ->willReturnSelf();
        $delete->expects($this->once())
            ->method('execute')
            ->willReturn(1);

        $this->database->expects($this->once())
            ->method('delete')
            ->with('company_follow')
            ->willReturn($delete);

        $result = $this->service->unfollowCompany(1, 100);

        $this->assertTrue($result);
    }

    /**
     * @covers ::getUserAlerts
     */
    public function testGetUserAlertsReturnsEmptyWhenTableNotExists(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())
            ->method('tableExists')
            ->with('job_alert')
            ->willReturn(FALSE);

        $this->database->expects($this->once())
            ->method('schema')
            ->willReturn($schema);

        $result = $this->service->getUserAlerts(1);

        $this->assertEmpty($result);
    }

    /**
     * @covers ::getUserAlerts
     */
    public function testGetUserAlertsReturnsData(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())
            ->method('tableExists')
            ->with('job_alert')
            ->willReturn(TRUE);

        $this->database->expects($this->once())
            ->method('schema')
            ->willReturn($schema);

        $alertData = (object) [
            'id' => 1,
            'user_id' => 1,
            'name' => 'Desarrollador PHP',
            'alert_type' => 'saved_search',
        ];

        $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
        $statement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([$alertData]);

        $select = $this->createMock(Select::class);
        $select->expects($this->once())
            ->method('fields')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('condition')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('execute')
            ->willReturn($statement);

        $this->database->expects($this->once())
            ->method('select')
            ->with('job_alert', 'a')
            ->willReturn($select);

        $result = $this->service->getUserAlerts(1);

        $this->assertCount(1, $result);
        $this->assertEquals('Desarrollador PHP', $result[0]->name);
    }

}
