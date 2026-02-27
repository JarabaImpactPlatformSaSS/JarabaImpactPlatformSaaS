<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_support\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Drupal\jaraba_support\Service\BusinessHoursService;
use Drupal\jaraba_support\Service\SlaEngineService;
use Drupal\jaraba_support\Service\SupportHealthScoreService;
use Drupal\jaraba_support\Service\TicketNotificationService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SlaEngineService.
 *
 * Tests SLA status checking (on_track, breached, paused),
 * pause/resume logic, and deadline extension calculations.
 */
#[CoversClass(SlaEngineService::class)]
#[Group('jaraba_support')]
class SlaEngineServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected SlaEngineService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // BusinessHoursService, TicketNotificationService, and SupportHealthScoreService
    // are declared final and cannot be mocked by PHPUnit. Use reflection to create
    // constructor-less instances. These dependencies are only used by
    // calculateDeadlines() and processSlaCron(), not by the methods under test.
    $businessHours = (new \ReflectionClass(BusinessHoursService::class))
      ->newInstanceWithoutConstructor();
    $notification = (new \ReflectionClass(TicketNotificationService::class))
      ->newInstanceWithoutConstructor();
    $healthScore = (new \ReflectionClass(SupportHealthScoreService::class))
      ->newInstanceWithoutConstructor();

    $this->service = new SlaEngineService(
      $this->entityTypeManager,
      $this->logger,
      $businessHours,
      $notification,
      $healthScore,
    );
  }

  /**
   * Creates a mock ticket with the specified field values.
   *
   * Builds a SupportTicketInterface mock where $ticket->get($field)->value
   * returns the appropriate value and $ticket->get($field)->isEmpty()
   * returns based on whether the value is null/empty/zero.
   *
   * @param array $fieldValues
   *   An associative array of field_name => value.
   * @param bool $isResolved
   *   Whether isResolved() should return true.
   *
   * @return \Drupal\jaraba_support\Entity\SupportTicketInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function createMockTicket(array $fieldValues, bool $isResolved = FALSE): SupportTicketInterface|MockObject {
    $ticket = $this->createMock(SupportTicketInterface::class);
    $ticket->method('id')->willReturn('100');
    $ticket->method('isResolved')->willReturn($isResolved);

    $ticket->method('get')
      ->willReturnCallback(function (string $fieldName) use ($fieldValues) {
        $value = $fieldValues[$fieldName] ?? NULL;

        // Return an anonymous class that behaves like a field item list
        // with ->value property access and ->isEmpty() method.
        return new class ($value) {

          public mixed $value;

          private bool $empty;

          public function __construct(mixed $value) {
            $this->value = $value;
            $this->empty = $value === NULL || $value === '' || $value === 0;
          }

          public function isEmpty(): bool {
            return $this->empty;
          }

        };
      });

    return $ticket;
  }

  /**
   * Tests checkSlaStatus returns on_track when deadlines are in the future.
   *
   * With response due in 1 hour and resolution due in 2 hours,
   * and no first response yet, the status should be 'on_track'.
   */
  #[Test]
  public function testCheckSlaStatusOnTrack(): void {
    $now = time();
    $ticket = $this->createMockTicket([
      'sla_first_response_due' => $now + 3600,
      'sla_resolution_due' => $now + 7200,
      'first_responded_at' => 0,
      'sla_paused_at' => NULL,
      'created' => $now - 600,
    ]);

    $result = $this->service->checkSlaStatus($ticket);

    $this->assertSame('on_track', $result['status']);
    $this->assertFalse($result['response_breached']);
    $this->assertFalse($result['resolution_breached']);
    $this->assertNotNull($result['response_due']);
    $this->assertNotNull($result['resolution_due']);
  }

  /**
   * Tests checkSlaStatus returns breached when resolution is past due.
   *
   * With resolution due 1 hour in the past, the status should be
   * 'breached' and resolution_breached should be TRUE.
   */
  #[Test]
  public function testCheckSlaStatusBreached(): void {
    $now = time();
    $ticket = $this->createMockTicket([
      'sla_first_response_due' => $now - 7200,
      'sla_resolution_due' => $now - 3600,
      'first_responded_at' => 0,
      'sla_paused_at' => NULL,
      'created' => $now - 14400,
    ]);

    $result = $this->service->checkSlaStatus($ticket);

    $this->assertSame('breached', $result['status']);
    $this->assertTrue($result['resolution_breached']);
    $this->assertTrue($result['response_breached']);
  }

  /**
   * Tests checkSlaStatus returns paused when sla_paused_at is set.
   *
   * When the SLA is paused, the status should immediately return
   * 'paused' without evaluating deadlines.
   */
  #[Test]
  public function testCheckSlaStatusPaused(): void {
    $now = time();
    $ticket = $this->createMockTicket([
      'sla_first_response_due' => $now - 3600,
      'sla_resolution_due' => $now - 1800,
      'first_responded_at' => 0,
      'sla_paused_at' => $now - 600,
      'created' => $now - 7200,
    ]);

    $result = $this->service->checkSlaStatus($ticket);

    $this->assertSame('paused', $result['status']);
    // When paused, breaches are not evaluated.
    $this->assertFalse($result['response_breached']);
    $this->assertFalse($result['resolution_breached']);
  }

  /**
   * Tests checkSlaStatus returns met for resolved tickets.
   *
   * Resolved tickets should return status='met' regardless of deadlines.
   */
  #[Test]
  public function testCheckSlaStatusMetForResolved(): void {
    $now = time();
    $ticket = $this->createMockTicket([
      'sla_first_response_due' => $now + 3600,
      'sla_resolution_due' => $now + 7200,
      'first_responded_at' => $now - 1800,
      'sla_paused_at' => NULL,
      'created' => $now - 3600,
    ], isResolved: TRUE);

    $result = $this->service->checkSlaStatus($ticket);

    $this->assertSame('met', $result['status']);
  }

  /**
   * Tests pauseSla sets the sla_paused_at timestamp.
   *
   * Calling pauseSla on a ticket without a current pause should
   * set sla_paused_at to the current timestamp.
   */
  #[Test]
  public function testPauseSla(): void {
    $ticket = $this->createMockTicket([
      'sla_paused_at' => NULL,
    ]);

    // Expect set('sla_paused_at', <timestamp>) to be called.
    $ticket->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('sla_paused_at'),
        $this->isType('int'),
      );

    $this->service->pauseSla($ticket);
  }

  /**
   * Tests pauseSla does nothing when already paused.
   *
   * If sla_paused_at already has a value, calling pauseSla should
   * be a no-op and not call set().
   */
  #[Test]
  public function testPauseSlaSkipsIfAlreadyPaused(): void {
    $ticket = $this->createMockTicket([
      'sla_paused_at' => time() - 300,
    ]);

    // set() should NOT be called when already paused.
    $ticket->expects($this->never())->method('set');

    $this->service->pauseSla($ticket);
  }

  /**
   * Tests resumeSla extends deadlines and clears paused state.
   *
   * When resuming after 10 minutes of pause (with 5 minutes prior paused
   * duration), deadlines should be extended by the additional pause time
   * and sla_paused_at should be cleared to NULL.
   */
  #[Test]
  public function testResumeSla(): void {
    $now = time();
    $pausedAt = $now - 600; // Paused 10 minutes ago.
    $responseDue = $now + 3600;
    $resolutionDue = $now + 7200;

    $ticket = $this->createMockTicket([
      'sla_paused_at' => $pausedAt,
      'sla_paused_duration' => 300,
      'sla_first_response_due' => $responseDue,
      'sla_resolution_due' => $resolutionDue,
    ]);

    // Collect all set() calls.
    $setCalls = [];
    $ticket->expects($this->atLeast(4))
      ->method('set')
      ->willReturnCallback(function (string $field, mixed $value) use (&$setCalls) {
        $setCalls[$field] = $value;
      });

    $this->service->resumeSla($ticket);

    // sla_paused_at should be cleared.
    $this->assertArrayHasKey('sla_paused_at', $setCalls);
    $this->assertNull($setCalls['sla_paused_at']);

    // Total paused duration should be 300 (prior) + ~600 (additional).
    $this->assertArrayHasKey('sla_paused_duration', $setCalls);
    $totalPaused = $setCalls['sla_paused_duration'];
    // Allow 2-second tolerance for test execution time.
    $this->assertGreaterThanOrEqual(898, $totalPaused);
    $this->assertLessThanOrEqual(910, $totalPaused);

    // Deadlines should be extended by the additional pause (~600 seconds).
    $this->assertArrayHasKey('sla_first_response_due', $setCalls);
    $this->assertArrayHasKey('sla_resolution_due', $setCalls);
    $responseExtension = $setCalls['sla_first_response_due'] - $responseDue;
    $resolutionExtension = $setCalls['sla_resolution_due'] - $resolutionDue;
    // Extension should be ~600 seconds (10 min), with 2s tolerance.
    $this->assertGreaterThanOrEqual(598, $responseExtension);
    $this->assertLessThanOrEqual(610, $responseExtension);
    $this->assertGreaterThanOrEqual(598, $resolutionExtension);
    $this->assertLessThanOrEqual(610, $resolutionExtension);
  }

  /**
   * Tests resumeSla does nothing when not paused.
   *
   * If sla_paused_at is 0 (not paused), calling resumeSla should
   * be a no-op.
   */
  #[Test]
  public function testResumeSlaSkipsIfNotPaused(): void {
    $ticket = $this->createMockTicket([
      'sla_paused_at' => 0,
    ]);

    $ticket->expects($this->never())->method('set');

    $this->service->resumeSla($ticket);
  }

}
