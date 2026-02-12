<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ai_agents\Entity\CollaborationSession;
use Drupal\jaraba_ai_agents\Service\AgentCollaborationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for AgentCollaborationService.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\AgentCollaborationService
 * @group jaraba_ai_agents
 */
class AgentCollaborationServiceTest extends TestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_ai_agents\Service\AgentCollaborationService
   */
  protected AgentCollaborationService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock entity storage for collaboration_session.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('collaboration_session')
      ->willReturn($this->storage);

    $this->service = new AgentCollaborationService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests that createSession() creates a CollaborationSession with correct fields.
   *
   * @covers ::createSession
   */
  public function testCreateSessionCreatesEntity(): void {
    $initiator = 'marketing_agent';
    $participants = ['support_agent', 'product_agent'];
    $taskDescription = 'Analyze customer feedback for product improvements';
    $tenantId = 5;

    $mockSession = $this->createMock(CollaborationSession::class);
    $mockSession->method('id')->willReturn(1);
    $mockSession->expects($this->once())->method('save');

    $this->storage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) use ($initiator, $taskDescription, $tenantId) {
        $allAgents = json_decode($values['participant_agents'], TRUE);
        return $values['initiator_agent'] === $initiator
          && $values['task_description'] === $taskDescription
          && $values['status'] === CollaborationSession::STATUS_ACTIVE
          && $values['messages'] === json_encode([])
          && $values['token_usage'] === 0
          && $values['tenant_id'] === $tenantId
          && in_array($initiator, $allAgents, TRUE)
          && in_array('support_agent', $allAgents, TRUE)
          && in_array('product_agent', $allAgents, TRUE);
      }))
      ->willReturn($mockSession);

    $result = $this->service->createSession($initiator, $participants, $taskDescription, $tenantId);

    $this->assertSame($mockSession, $result);
  }

  /**
   * Tests that addMessage() appends a message to the existing JSON array.
   *
   * @covers ::addMessage
   */
  public function testAddMessageAppendsToJsonArray(): void {
    $sessionId = 1;
    $agentId = 'support_agent';
    $role = 'response';
    $content = 'Here is the analysis result.';

    $existingMessages = [
      [
        'agent_id' => 'marketing_agent',
        'role' => 'request',
        'content' => 'Please analyze the feedback.',
        'timestamp' => 1700000000,
      ],
    ];

    $mockSession = $this->createMock(CollaborationSession::class);
    $mockSession->method('isActive')->willReturn(TRUE);
    $mockSession->method('getStatus')->willReturn(CollaborationSession::STATUS_ACTIVE);
    $mockSession->method('getMessages')->willReturn($existingMessages);

    // Expect setMessages to be called with the appended message.
    $mockSession
      ->expects($this->once())
      ->method('setMessages')
      ->with($this->callback(function (array $messages) use ($agentId, $role, $content) {
        return count($messages) === 2
          && $messages[1]['agent_id'] === $agentId
          && $messages[1]['role'] === $role
          && $messages[1]['content'] === $content
          && isset($messages[1]['timestamp']);
      }));

    $mockSession->expects($this->once())->method('save');

    $this->storage
      ->method('load')
      ->with($sessionId)
      ->willReturn($mockSession);

    // The addMessage method calls \Drupal::time() which requires a container.
    // We need to set up the Drupal container for this test.
    $timeMock = $this->createMock(\Drupal\Component\Datetime\TimeInterface::class);
    $timeMock->method('getRequestTime')->willReturn(1700000100);

    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
    $container->set('datetime.time', $timeMock);
    \Drupal::setContainer($container);

    $this->service->addMessage($sessionId, $agentId, $role, $content);
  }

  /**
   * Tests that addMessage() throws an exception for an invalid role.
   *
   * @covers ::addMessage
   */
  public function testAddMessageValidatesRole(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/Rol de mensaje inv.*invalid_role/');

    $this->service->addMessage(1, 'agent_1', 'invalid_role', 'Some content');
  }

  /**
   * Tests that completeSession() sets status to completed and stores the result.
   *
   * @covers ::completeSession
   */
  public function testCompleteSessionSetsStatusAndResult(): void {
    $sessionId = 1;
    $result = ['summary' => 'Task completed successfully', 'score' => 95];

    $mockSession = $this->createMock(CollaborationSession::class);
    $mockSession->method('getMessages')->willReturn([]);

    $mockSession
      ->expects($this->once())
      ->method('setStatus')
      ->with(CollaborationSession::STATUS_COMPLETED);

    $mockSession
      ->expects($this->once())
      ->method('setResult')
      ->with($result);

    $mockSession->expects($this->once())->method('save');

    $this->storage
      ->method('load')
      ->with($sessionId)
      ->willReturn($mockSession);

    $this->service->completeSession($sessionId, $result);
  }

  /**
   * Tests that failSession() sets status to failed and stores the reason.
   *
   * @covers ::failSession
   */
  public function testFailSessionSetsStatusAndReason(): void {
    $sessionId = 1;
    $reason = 'Agent timeout after 60 seconds';

    $mockSession = $this->createMock(CollaborationSession::class);

    $mockSession
      ->expects($this->once())
      ->method('setStatus')
      ->with(CollaborationSession::STATUS_FAILED);

    $mockSession
      ->expects($this->once())
      ->method('setResult')
      ->with($this->callback(function (array $resultData) use ($reason) {
        return $resultData['error'] === TRUE
          && $resultData['reason'] === $reason
          && isset($resultData['failed_at']);
      }));

    $mockSession->expects($this->once())->method('save');

    $this->storage
      ->method('load')
      ->with($sessionId)
      ->willReturn($mockSession);

    // failSession calls \Drupal::time() so set up the container.
    $timeMock = $this->createMock(\Drupal\Component\Datetime\TimeInterface::class);
    $timeMock->method('getRequestTime')->willReturn(1700000200);

    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
    $container->set('datetime.time', $timeMock);
    \Drupal::setContainer($container);

    $this->service->failSession($sessionId, $reason);
  }

  /**
   * Tests that getActiveSessions() returns only active sessions.
   *
   * @covers ::getActiveSessions
   */
  public function testGetActiveSessionsFiltersByStatus(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $this->storage
      ->method('getQuery')
      ->willReturn($query);

    $session1 = $this->createMock(CollaborationSession::class);
    $session2 = $this->createMock(CollaborationSession::class);

    $this->storage
      ->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([$session1, $session2]);

    $result = $this->service->getActiveSessions();

    $this->assertCount(2, $result);
    $this->assertSame($session1, $result[0]);
    $this->assertSame($session2, $result[1]);
  }

  /**
   * Tests that handoff() records a message with role 'handoff'.
   *
   * @covers ::handoff
   */
  public function testHandoffRecordsMessage(): void {
    $sessionId = 1;
    $fromAgent = 'marketing_agent';
    $toAgent = 'support_agent';
    $context = 'Customer needs technical support for integration issue';

    $mockSession = $this->createMock(CollaborationSession::class);
    $mockSession->method('isActive')->willReturn(TRUE);
    $mockSession->method('getStatus')->willReturn(CollaborationSession::STATUS_ACTIVE);
    $mockSession->method('getMessages')->willReturn([]);

    // Verify that setMessages is called with a handoff message.
    $mockSession
      ->expects($this->once())
      ->method('setMessages')
      ->with($this->callback(function (array $messages) use ($fromAgent) {
        if (count($messages) !== 1) {
          return FALSE;
        }
        $msg = $messages[0];
        $content = json_decode($msg['content'], TRUE);
        return $msg['agent_id'] === $fromAgent
          && $msg['role'] === 'handoff'
          && $content['from'] === 'marketing_agent'
          && $content['to'] === 'support_agent'
          && !empty($content['context']);
      }));

    $mockSession->expects($this->once())->method('save');

    $this->storage
      ->method('load')
      ->with($sessionId)
      ->willReturn($mockSession);

    // handoff calls addMessage which calls \Drupal::time().
    $timeMock = $this->createMock(\Drupal\Component\Datetime\TimeInterface::class);
    $timeMock->method('getRequestTime')->willReturn(1700000300);

    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
    $container->set('datetime.time', $timeMock);
    \Drupal::setContainer($container);

    $this->service->handoff($sessionId, $fromAgent, $toAgent, $context);
  }

}
