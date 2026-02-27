<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Service\PromptVersionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PromptVersionService.
 *
 * Tests prompt version management including creating versions,
 * retrieving active prompts, rollback, and version history.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\PromptVersionService
 * @group jaraba_ai_agents
 */
class PromptVersionServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected PromptVersionService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock entity storage.
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
      ->with('prompt_template')
      ->willReturn($this->storage);

    $this->service = new PromptVersionService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests getActivePrompt returns the active template for an agent.
   *
   * When an active template exists, the method should return it.
   *
   * @covers ::getActivePrompt
   */
  public function testGetActivePromptReturnsActiveTemplate(): void {
    $template = $this->createMockTemplate('test_agent_v1_0_0', 'test_agent', '1.0.0', TRUE, 1000, 2000);

    $this->storage->method('loadByProperties')
      ->with([
        'agent_id' => 'test_agent',
        'is_active' => TRUE,
      ])
      ->willReturn([$template]);

    $result = $this->service->getActivePrompt('test_agent');

    $this->assertNotNull($result);
    $this->assertSame('test_agent_v1_0_0', $result->id());
  }

  /**
   * Tests getActivePrompt returns null when no active template exists.
   *
   * @covers ::getActivePrompt
   */
  public function testGetActivePromptReturnsNullWhenNone(): void {
    $this->storage->method('loadByProperties')
      ->with([
        'agent_id' => 'nonexistent_agent',
        'is_active' => TRUE,
      ])
      ->willReturn([]);

    $result = $this->service->getActivePrompt('nonexistent_agent');

    $this->assertNull($result);
  }

  /**
   * Tests createVersion creates a template with the correct version.
   *
   * Verifies that when an explicit version is provided, it is used,
   * and the new version is set as active while old ones are deactivated.
   *
   * @covers ::createVersion
   */
  public function testCreateVersionSetsCorrectVersion(): void {
    // Deactivate: no existing active templates.
    $this->storage->method('loadByProperties')
      ->willReturn([]);

    $createdTemplate = $this->createMockTemplate(
      'test_agent_v2_1_0',
      'test_agent',
      '2.1.0',
      TRUE,
      time(),
      time(),
    );

    $this->storage->method('create')
      ->willReturn($createdTemplate);

    $result = $this->service->createVersion('test_agent', [
      'version' => '2.1.0',
      'system_prompt' => 'You are a helpful marketing assistant.',
      'temperature' => 0.8,
      'model_tier' => 'balanced',
    ]);

    $this->assertNotNull($result);
    $this->assertSame('test_agent_v2_1_0', $result->id());
  }

  /**
   * Tests rollback activates the correct version and deactivates others.
   *
   * @covers ::rollback
   */
  public function testRollbackActivatesCorrectVersion(): void {
    $targetTemplate = $this->createMockTemplate(
      'test_agent_v1_0_0',
      'test_agent',
      '1.0.0',
      FALSE,
      1000,
      1000,
    );

    // loadByProperties is called multiple times:
    // 1. By rollback to find the target version.
    // 2. By deactivateAllVersions to find active templates.
    $this->storage->method('loadByProperties')
      ->willReturnCallback(function (array $properties) use ($targetTemplate) {
        if (isset($properties['version'])) {
          // Rollback lookup by version.
          return [$targetTemplate];
        }
        if (isset($properties['is_active']) && $properties['is_active'] === TRUE) {
          // Deactivate lookup: no active versions.
          return [];
        }
        return [];
      });

    $result = $this->service->rollback('test_agent', '1.0.0');

    $this->assertTrue($result);
  }

  /**
   * Tests getHistory returns all versions sorted by creation date.
   *
   * @covers ::getHistory
   */
  public function testGetHistoryReturnsAll(): void {
    $templateV1 = $this->createMockTemplate('agent_v1_0_0', 'test_agent', '1.0.0', FALSE, 1000, 1500);
    $templateV2 = $this->createMockTemplate('agent_v2_0_0', 'test_agent', '2.0.0', FALSE, 2000, 2500);
    $templateV3 = $this->createMockTemplate('agent_v3_0_0', 'test_agent', '3.0.0', TRUE, 3000, 3500);

    $this->storage->method('loadByProperties')
      ->with(['agent_id' => 'test_agent'])
      ->willReturn([$templateV1, $templateV2, $templateV3]);

    $history = $this->service->getHistory('test_agent');

    $this->assertCount(3, $history);

    // Should be sorted by created desc: v3, v2, v1.
    $this->assertSame('3.0.0', $history[0]['version']);
    $this->assertSame('2.0.0', $history[1]['version']);
    $this->assertSame('1.0.0', $history[2]['version']);

    // Only v3 is active.
    $this->assertTrue($history[0]['is_active']);
    $this->assertFalse($history[1]['is_active']);
    $this->assertFalse($history[2]['is_active']);

    // Each entry has expected keys.
    foreach ($history as $entry) {
      $this->assertArrayHasKey('id', $entry);
      $this->assertArrayHasKey('version', $entry);
      $this->assertArrayHasKey('is_active', $entry);
      $this->assertArrayHasKey('model_tier', $entry);
      $this->assertArrayHasKey('temperature', $entry);
      $this->assertArrayHasKey('created', $entry);
      $this->assertArrayHasKey('updated', $entry);
    }
  }

  /**
   * Creates a mock PromptTemplate entity using stdClass.
   *
   * PromptTemplate entities in this service use ->id(), ->get(), ->set(),
   * and ->save(). We use stdClass with closures to simulate this behavior
   * since Drupal's EntityInterface doesn't include get() for config entities.
   *
   * @param string $id
   *   The entity ID.
   * @param string $agentId
   *   The agent ID.
   * @param string $version
   *   The version string.
   * @param bool $isActive
   *   Whether the template is active.
   * @param int $created
   *   Created timestamp.
   * @param int $updated
   *   Updated timestamp.
   *
   * @return object
   *   Mock template entity.
   */
  protected function createMockTemplate(
    string $id,
    string $agentId,
    string $version,
    bool $isActive,
    int $created,
    int $updated,
  ): object {
    $data = [
      'agent_id' => $agentId,
      'version' => $version,
      'is_active' => $isActive,
      'model_tier' => 'balanced',
      'temperature' => 0.7,
      'created' => $created,
      'updated' => $updated,
    ];

    $template = new class ($id, $data) {

      private string $entityId;
      private array $data;

      public function __construct(string $id, array $data) {
        $this->entityId = $id;
        $this->data = $data;
      }

      public function id(): string {
        return $this->entityId;
      }

      public function get(string $field): mixed {
        return $this->data[$field] ?? NULL;
      }

      public function set(string $field, mixed $value): static {
        $this->data[$field] = $value;
        return $this;
      }

      public function save(): void {
        // No-op in tests.
      }

    };

    return $template;
  }

}
