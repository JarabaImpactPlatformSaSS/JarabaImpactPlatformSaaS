<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_messaging\Kernel;

use Drupal\jaraba_messaging\Model\IntegrityReport;
use Drupal\jaraba_messaging\Service\MessageAuditService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the SHA-256 hash chain audit trail.
 *
 * @coversDefaultClass \Drupal\jaraba_messaging\Service\MessageAuditService
 * @group jaraba_messaging
 */
class MessageAuditServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'group',
    'ecosistema_jaraba_core',
    'jaraba_messaging',
  ];

  protected MessageAuditService $auditService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('jaraba_messaging', [
      'secure_message',
      'message_audit_log',
      'message_read_receipt',
    ]);
    $this->installEntitySchema('user');

    $this->auditService = new MessageAuditService(
      $this->container->get('database'),
      $this->container->get('current_user'),
      $this->container->get('request_stack'),
      $this->container->get('logger.channel.jaraba_messaging'),
    );
  }

  /**
   * Tests that a single log entry can be created and verified.
   */
  public function testSingleEntryIntegrity(): void {
    $this->auditService->log(1, 100, 'conversation.created', NULL, ['title' => 'Test']);

    $report = $this->auditService->verifyIntegrity(1);

    $this->assertInstanceOf(IntegrityReport::class, $report);
    $this->assertTrue($report->valid);
    $this->assertSame(1, $report->total_entries);
    $this->assertNull($report->broken_at);
  }

  /**
   * Tests hash chain integrity over multiple entries.
   */
  public function testMultiEntryChainIntegrity(): void {
    $this->auditService->log(1, 100, 'conversation.created');
    $this->auditService->log(1, 100, 'message.sent', 1, ['preview' => 'Hello']);
    $this->auditService->log(1, 100, 'message.read', 1);
    $this->auditService->log(1, 100, 'message.sent', 2, ['preview' => 'Reply']);
    $this->auditService->log(1, 100, 'participant.added', 5);

    $report = $this->auditService->verifyIntegrity(1);
    $this->assertTrue($report->valid);
    $this->assertSame(5, $report->total_entries);
  }

  /**
   * Tests that each conversation has an independent chain.
   */
  public function testConversationIsolation(): void {
    // Conversation 1 entries.
    $this->auditService->log(1, 100, 'conversation.created');
    $this->auditService->log(1, 100, 'message.sent');

    // Conversation 2 entries.
    $this->auditService->log(2, 100, 'conversation.created');

    $report1 = $this->auditService->verifyIntegrity(1);
    $report2 = $this->auditService->verifyIntegrity(2);

    $this->assertTrue($report1->valid);
    $this->assertSame(2, $report1->total_entries);
    $this->assertTrue($report2->valid);
    $this->assertSame(1, $report2->total_entries);
  }

  /**
   * Tests tamper detection: direct DB modification is caught.
   */
  public function testTamperDetection(): void {
    $this->auditService->log(1, 100, 'conversation.created');
    $this->auditService->log(1, 100, 'message.sent', 1, ['preview' => 'Original']);
    $this->auditService->log(1, 100, 'message.read', 1);

    // Directly tamper with the second entry in the database.
    $db = $this->container->get('database');
    $entries = $db->select('message_audit_log', 'a')
      ->fields('a', ['id'])
      ->condition('conversation_id', 1)
      ->orderBy('id', 'ASC')
      ->execute()
      ->fetchCol();

    // Modify the details of the second entry (simulates tampering).
    $db->update('message_audit_log')
      ->fields(['details' => json_encode(['preview' => 'TAMPERED'])])
      ->condition('id', $entries[1])
      ->execute();

    $report = $this->auditService->verifyIntegrity(1);
    $this->assertFalse($report->valid);
    $this->assertSame(2, $report->broken_at);
    $this->assertStringContainsString('tampered', strtolower($report->details));
  }

  /**
   * Tests genesis hash: first entry uses all-zeros previous_hash.
   */
  public function testGenesisHash(): void {
    $this->auditService->log(1, 100, 'conversation.created');

    $db = $this->container->get('database');
    $firstEntry = $db->select('message_audit_log', 'a')
      ->fields('a', ['previous_hash'])
      ->condition('conversation_id', 1)
      ->orderBy('id', 'ASC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    $this->assertSame(str_repeat('0', 64), $firstEntry);
  }

  /**
   * Tests empty conversation verification.
   */
  public function testEmptyConversationIntegrity(): void {
    $report = $this->auditService->verifyIntegrity(999);
    $this->assertTrue($report->valid);
    $this->assertSame(0, $report->total_entries);
  }

  /**
   * Tests getLog returns entries in descending order.
   */
  public function testGetLogOrdering(): void {
    $this->auditService->log(1, 100, 'conversation.created');
    $this->auditService->log(1, 100, 'message.sent');
    $this->auditService->log(1, 100, 'message.read');

    $entries = $this->auditService->getLog(1, 10, 0);
    $this->assertCount(3, $entries);

    // Descending order: newest first.
    $this->assertSame('message.read', $entries[0]['action']);
    $this->assertSame('conversation.created', $entries[2]['action']);
  }

  /**
   * Tests getLog respects limit and offset.
   */
  public function testGetLogPagination(): void {
    for ($i = 0; $i < 10; $i++) {
      $this->auditService->log(1, 100, 'message.sent', $i);
    }

    $page1 = $this->auditService->getLog(1, 3, 0);
    $page2 = $this->auditService->getLog(1, 3, 3);

    $this->assertCount(3, $page1);
    $this->assertCount(3, $page2);
    $this->assertNotSame($page1[0]['id'], $page2[0]['id']);
  }

}
