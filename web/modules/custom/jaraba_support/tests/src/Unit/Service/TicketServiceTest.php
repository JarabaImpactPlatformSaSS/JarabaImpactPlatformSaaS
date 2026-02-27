<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_support\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Drupal\jaraba_support\Entity\TicketMessageInterface;
use Drupal\jaraba_support\Service\TicketService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Unit tests for TicketService.
 *
 * Tests ticket state machine transitions, message creation with
 * first_responded_at tracking, and ticket creation defaults.
 */
#[CoversClass(TicketService::class)]
#[Group('jaraba_support')]
class TicketServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected TicketService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock current user.
   */
  protected AccountProxyInterface|MockObject $currentUser;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock database connection.
   */
  protected Connection|MockObject $database;

  /**
   * Mock tenant context service.
   */
  protected TenantContextService|MockObject $tenantContext;

  /**
   * Mock event dispatcher.
   */
  protected EventDispatcherInterface|MockObject $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->currentUser->method('id')->willReturn('7');
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $this->service = new TicketService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->logger,
      $this->database,
      $this->tenantContext,
      $this->eventDispatcher,
    );
  }

  /**
   * Tests valid state machine transitions.
   *
   * Verifies that transitions defined in the VALID_TRANSITIONS constant
   * are accepted: new->open, open->resolved, resolved->closed.
   */
  #[Test]
  public function testIsValidTransitionAcceptsValid(): void {
    // new -> open is allowed.
    $this->assertTrue($this->service->isValidTransition('new', 'open'));

    // new -> ai_handling is allowed.
    $this->assertTrue($this->service->isValidTransition('new', 'ai_handling'));

    // open -> resolved is allowed.
    $this->assertTrue($this->service->isValidTransition('open', 'resolved'));

    // resolved -> closed is allowed.
    $this->assertTrue($this->service->isValidTransition('resolved', 'closed'));

    // closed -> reopened is allowed.
    $this->assertTrue($this->service->isValidTransition('closed', 'reopened'));

    // reopened -> open is allowed.
    $this->assertTrue($this->service->isValidTransition('reopened', 'open'));
  }

  /**
   * Tests invalid state machine transitions.
   *
   * Verifies that transitions not defined in VALID_TRANSITIONS are
   * rejected: closed->open, new->resolved, open->new.
   */
  #[Test]
  public function testIsValidTransitionRejectsInvalid(): void {
    // closed -> open is NOT allowed (must go through reopened).
    $this->assertFalse($this->service->isValidTransition('closed', 'open'));

    // new -> resolved is NOT allowed (must go through open or ai_handling).
    $this->assertFalse($this->service->isValidTransition('new', 'resolved'));

    // open -> new is NOT allowed (no backward transition to new).
    $this->assertFalse($this->service->isValidTransition('open', 'new'));

    // resolved -> open is NOT allowed (must go through reopened).
    $this->assertFalse($this->service->isValidTransition('resolved', 'open'));

    // Unknown status returns false.
    $this->assertFalse($this->service->isValidTransition('nonexistent', 'open'));
  }

  /**
   * Tests that addMessage sets first_responded_at on first agent response.
   *
   * When a ticket has no first_responded_at and an agent message is added,
   * the service should set the first_responded_at timestamp on the ticket.
   */
  #[Test]
  public function testAddMessageSetsFirstRespondedAt(): void {
    // Mock ticket_message storage.
    $messageStorage = $this->createMock(EntityStorageInterface::class);
    $mockMessage = $this->createMock(TicketMessageInterface::class);
    $messageStorage->method('create')->willReturn($mockMessage);

    // Mock ticket_event_log storage for logEvent().
    $eventLogStorage = $this->createMock(EntityStorageInterface::class);
    $mockEventLog = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $eventLogStorage->method('create')->willReturn($mockEventLog);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($messageStorage, $eventLogStorage) {
        return match ($entityType) {
          'ticket_message' => $messageStorage,
          'ticket_event_log' => $eventLogStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    // Mock ticket: first_responded_at is empty (no previous response).
    $firstRespondedField = new \stdClass();
    $firstRespondedField->value = NULL;

    $ticket = $this->createMock(SupportTicketInterface::class);
    $ticket->method('id')->willReturn('42');
    $ticket->method('get')
      ->willReturnCallback(function (string $fieldName) use ($firstRespondedField) {
        if ($fieldName === 'first_responded_at') {
          return $firstRespondedField;
        }
        $field = new \stdClass();
        $field->value = NULL;
        return $field;
      });

    // Expect that set('first_responded_at', ...) is called on the ticket.
    $ticket->expects($this->atLeastOnce())
      ->method('set')
      ->with(
        $this->equalTo('first_responded_at'),
        $this->matchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/'),
      );

    $ticket->expects($this->atLeastOnce())->method('save');

    $result = $this->service->addMessage($ticket, 'Hello, how can I help?', 'agent');

    $this->assertInstanceOf(TicketMessageInterface::class, $result);
  }

  /**
   * Tests that addMessage does NOT set first_responded_at for customer messages.
   *
   * Only agent and AI messages should trigger the first_responded_at update.
   * Customer messages should leave it unchanged.
   */
  #[Test]
  public function testAddMessageDoesNotSetFirstRespondedAtForCustomer(): void {
    // Mock ticket_message storage.
    $messageStorage = $this->createMock(EntityStorageInterface::class);
    $mockMessage = $this->createMock(TicketMessageInterface::class);
    $messageStorage->method('create')->willReturn($mockMessage);

    $this->entityTypeManager->method('getStorage')
      ->with('ticket_message')
      ->willReturn($messageStorage);

    // Mock ticket with empty first_responded_at.
    $firstRespondedField = new \stdClass();
    $firstRespondedField->value = NULL;

    $ticket = $this->createMock(SupportTicketInterface::class);
    $ticket->method('id')->willReturn('42');
    $ticket->method('get')
      ->willReturnCallback(function (string $fieldName) use ($firstRespondedField) {
        if ($fieldName === 'first_responded_at') {
          return $firstRespondedField;
        }
        $field = new \stdClass();
        $field->value = NULL;
        return $field;
      });

    // set() should NOT be called with 'first_responded_at' for customer messages.
    $ticket->expects($this->never())->method('set');
    $ticket->expects($this->never())->method('save');

    $this->service->addMessage($ticket, 'I have a problem', 'customer');
  }

  /**
   * Tests that createTicket sets proper default values.
   *
   * Verifies that status='new', priority='medium', and channel='portal'
   * are set as defaults when not provided in the data array.
   */
  #[Test]
  public function testCreateTicketSetsDefaults(): void {
    $capturedValues = NULL;

    // Mock support_ticket storage.
    $ticketStorage = $this->createMock(EntityStorageInterface::class);
    $mockTicket = $this->createMock(SupportTicketInterface::class);
    $mockTicket->method('id')->willReturn('1');
    $mockTicket->method('getTicketNumber')->willReturn('JRB-202602-0001');

    $ticketStorage->method('create')
      ->willReturnCallback(function (array $values) use (&$capturedValues, $mockTicket) {
        $capturedValues = $values;
        return $mockTicket;
      });

    // Mock ticket_event_log storage for logEvent().
    $eventLogStorage = $this->createMock(EntityStorageInterface::class);
    $mockEventLog = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $eventLogStorage->method('create')->willReturn($mockEventLog);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($ticketStorage, $eventLogStorage) {
        return match ($entityType) {
          'support_ticket' => $ticketStorage,
          'ticket_event_log' => $eventLogStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->tenantContext->method('getCurrentTenantId')->willReturn(5);

    $result = $this->service->createTicket([
      'subject' => 'My page is broken',
      'description' => 'I cannot load my dashboard.',
    ]);

    $this->assertNotNull($capturedValues, 'Storage::create() should have been called.');
    $this->assertSame('new', $capturedValues['status']);
    $this->assertSame('medium', $capturedValues['priority']);
    $this->assertSame('portal', $capturedValues['channel']);
    $this->assertSame('My page is broken', $capturedValues['subject']);
    $this->assertSame('I cannot load my dashboard.', $capturedValues['description']);
    $this->assertSame(7, $capturedValues['reporter_uid']);
    $this->assertSame(5, $capturedValues['tenant_id']);
    $this->assertSame('platform', $capturedValues['vertical']);
    $this->assertInstanceOf(SupportTicketInterface::class, $result);
  }

  /**
   * Tests that createTicket respects provided values over defaults.
   *
   * When priority, channel, and vertical are explicitly provided,
   * the service should use those instead of the defaults.
   */
  #[Test]
  public function testCreateTicketRespectsProvidedValues(): void {
    $capturedValues = NULL;

    $ticketStorage = $this->createMock(EntityStorageInterface::class);
    $mockTicket = $this->createMock(SupportTicketInterface::class);
    $mockTicket->method('id')->willReturn('2');
    $mockTicket->method('getTicketNumber')->willReturn('JRB-202602-0002');

    $ticketStorage->method('create')
      ->willReturnCallback(function (array $values) use (&$capturedValues, $mockTicket) {
        $capturedValues = $values;
        return $mockTicket;
      });

    $eventLogStorage = $this->createMock(EntityStorageInterface::class);
    $mockEventLog = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $eventLogStorage->method('create')->willReturn($mockEventLog);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($ticketStorage, $eventLogStorage) {
        return match ($entityType) {
          'support_ticket' => $ticketStorage,
          'ticket_event_log' => $eventLogStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $result = $this->service->createTicket([
      'subject' => 'Urgent billing issue',
      'description' => 'Double charged.',
      'priority' => 'urgent',
      'channel' => 'email',
      'vertical' => 'comercioconecta',
      'category' => 'billing',
      'tenant_id' => 10,
    ], 99);

    $this->assertSame('urgent', $capturedValues['priority']);
    $this->assertSame('email', $capturedValues['channel']);
    $this->assertSame('comercioconecta', $capturedValues['vertical']);
    $this->assertSame('billing', $capturedValues['category']);
    $this->assertSame(10, $capturedValues['tenant_id']);
    $this->assertSame(99, $capturedValues['reporter_uid']);
  }

}
