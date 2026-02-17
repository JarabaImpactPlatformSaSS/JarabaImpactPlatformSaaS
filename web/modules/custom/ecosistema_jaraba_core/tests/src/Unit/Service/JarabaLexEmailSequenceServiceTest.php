<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\JarabaLexEmailSequenceService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for JarabaLexEmailSequenceService.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\JarabaLexEmailSequenceService
 * @group ecosistema_jaraba_core
 */
class JarabaLexEmailSequenceServiceTest extends UnitTestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\JarabaLexEmailSequenceService
   */
  protected JarabaLexEmailSequenceService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new JarabaLexEmailSequenceService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests that SEQUENCES constant defines exactly 5 sequences.
   *
   * @covers ::getAvailableSequences
   */
  public function testSequencesCountIsFive(): void {
    $sequences = $this->service->getAvailableSequences();
    $this->assertCount(5, $sequences);
  }

  /**
   * Tests that all 5 sequence keys (SEQ_LEX_001 through SEQ_LEX_005) exist.
   *
   * @covers ::getAvailableSequences
   */
  public function testAllSequenceKeysExist(): void {
    $expectedKeys = [
      'SEQ_LEX_001',
      'SEQ_LEX_002',
      'SEQ_LEX_003',
      'SEQ_LEX_004',
      'SEQ_LEX_005',
    ];

    $sequences = $this->service->getAvailableSequences();

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $sequences, "Sequence '$key' must exist.");
    }
  }

  /**
   * Tests that each sequence has required metadata fields.
   *
   * @covers ::getAvailableSequences
   */
  public function testSequencesHaveRequiredFields(): void {
    $requiredFields = ['label', 'category', 'trigger_type'];
    $sequences = $this->service->getAvailableSequences();

    foreach ($sequences as $key => $sequence) {
      foreach ($requiredFields as $field) {
        $this->assertArrayHasKey(
          $field,
          $sequence,
          "Sequence '$key' must have field '$field'.",
        );
      }
    }
  }

  /**
   * Tests that sequence categories match expected values.
   *
   * @covers ::getAvailableSequences
   */
  public function testSequenceCategoriesAreValid(): void {
    $expectedCategories = [
      'SEQ_LEX_001' => 'onboarding',
      'SEQ_LEX_002' => 'nurture',
      'SEQ_LEX_003' => 'nurture',
      'SEQ_LEX_004' => 'reengagement',
      'SEQ_LEX_005' => 'sales',
    ];

    $sequences = $this->service->getAvailableSequences();

    foreach ($expectedCategories as $key => $expectedCategory) {
      $this->assertSame(
        $expectedCategory,
        $sequences[$key]['category'],
        "Sequence '$key' category must be '$expectedCategory'.",
      );
    }
  }

  /**
   * Tests that all sequences use 'event' trigger type.
   *
   * @covers ::getAvailableSequences
   */
  public function testAllSequencesUseEventTriggerType(): void {
    $sequences = $this->service->getAvailableSequences();

    foreach ($sequences as $key => $sequence) {
      $this->assertSame(
        'event',
        $sequence['trigger_type'],
        "Sequence '$key' must use 'event' trigger_type.",
      );
    }
  }

  /**
   * Tests that enroll returns false when jaraba_email.sequence_manager is unavailable.
   *
   * Since \Drupal::hasService() will fail in unit tests (no container),
   * enroll should return FALSE.
   *
   * @covers ::enroll
   */
  public function testEnrollReturnsFalseWithoutSequenceManagerService(): void {
    // Without a Drupal container, \Drupal::hasService() will throw or return
    // false. The method handles this gracefully.
    $result = $this->service->enroll(1, 'SEQ_LEX_001');
    $this->assertFalse($result);
  }

  /**
   * Tests that ensureSequences creates missing sequences.
   *
   * @covers ::ensureSequences
   */
  public function testEnsureSequencesCreatesWhenEmpty(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    // loadByProperties returns empty for all keys (nothing exists yet).
    $storage->method('loadByProperties')
      ->willReturn([]);

    // Create should be called 5 times (once per sequence).
    $entity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $entity->method('save')->willReturn(1);

    $storage->expects($this->exactly(5))
      ->method('create')
      ->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('email_sequence')
      ->willReturn($storage);

    $this->logger->expects($this->exactly(5))
      ->method('info')
      ->with(
        $this->stringContains('Created email sequence'),
        $this->anything(),
      );

    $this->service->ensureSequences();
  }

  /**
   * Tests that ensureSequences skips already existing sequences.
   *
   * @covers ::ensureSequences
   */
  public function testEnsureSequencesSkipsExisting(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    // All sequences already exist.
    $existingEntity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $storage->method('loadByProperties')
      ->willReturn([$existingEntity]);

    // create() should never be called.
    $storage->expects($this->never())
      ->method('create');

    $this->entityTypeManager->method('getStorage')
      ->with('email_sequence')
      ->willReturn($storage);

    $this->service->ensureSequences();
  }

  /**
   * Tests that ensureSequences handles entity type manager exceptions.
   *
   * @covers ::ensureSequences
   */
  public function testEnsureSequencesHandlesStorageException(): void {
    $this->entityTypeManager->method('getStorage')
      ->with('email_sequence')
      ->willThrowException(new \Exception('Entity type not found.'));

    // Should not throw; method returns silently.
    $this->service->ensureSequences();
    $this->assertTrue(TRUE, 'ensureSequences handled exception gracefully.');
  }

  /**
   * Tests that sequence labels are non-empty strings.
   *
   * @covers ::getAvailableSequences
   */
  public function testSequenceLabelsAreNonEmpty(): void {
    $sequences = $this->service->getAvailableSequences();

    foreach ($sequences as $key => $sequence) {
      $this->assertNotEmpty(
        $sequence['label'],
        "Sequence '$key' must have a non-empty label.",
      );
      $this->assertIsString($sequence['label']);
    }
  }

  /**
   * Tests specific sequence label content.
   *
   * @covers ::getAvailableSequences
   */
  public function testSequenceLabelsContent(): void {
    $expectedLabels = [
      'SEQ_LEX_001' => 'JarabaLex: Welcome Legal Professional',
      'SEQ_LEX_002' => 'JarabaLex: First Search Completed',
      'SEQ_LEX_003' => 'JarabaLex: Alert Configuration',
      'SEQ_LEX_004' => 'JarabaLex: Inactivity Reengagement',
      'SEQ_LEX_005' => 'JarabaLex: Upsell Free a Starter',
    ];

    $sequences = $this->service->getAvailableSequences();

    foreach ($expectedLabels as $key => $expectedLabel) {
      $this->assertSame(
        $expectedLabel,
        $sequences[$key]['label'],
        "Sequence '$key' label must match.",
      );
    }
  }

}
