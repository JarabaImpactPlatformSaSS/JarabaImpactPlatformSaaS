<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\Plugin\QueueWorker;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_intelligence\Plugin\QueueWorker\NormVectorizationWorker;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for NormVectorizationWorker queue worker.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\Plugin\QueueWorker\NormVectorizationWorker
 * @group jaraba_legal_intelligence
 */
class NormVectorizationWorkerTest extends TestCase {

  /**
   * Tests that items without norm_id are discarded.
   *
   * @covers ::processItem
   */
  public function testMissingNormIdDiscarded(): void {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->never())->method('getStorage');

    $worker = new NormVectorizationWorker(
      [],
      'jaraba_legal_intelligence_vectorization',
      ['cron' => ['time' => 120]],
      $entityTypeManager,
      new NullLogger(),
    );

    $worker->processItem([]);
    $worker->processItem(['reason' => 'test']);
  }

  /**
   * Tests that missing chunking service marks norm as pending.
   *
   * MOCK-DYNPROP-001: Use anonymous class for field value.
   *
   * @covers ::processItem
   */
  public function testMissingServicesMarksPending(): void {
    $fullTextField = new class {
      public ?string $value = 'Some legal text content.';
    };

    $emptyField = new class {
      public ?string $value = NULL;
    };

    $norm = $this->createMock(ContentEntityInterface::class);
    $norm->method('get')
      ->willReturnCallback(function (string $field) use ($fullTextField, $emptyField): object {
        return $field === 'full_text' ? $fullTextField : $emptyField;
      });

    // Norm should be saved with pending status.
    $norm->expects($this->once())->method('set')
      ->with('embedding_status', 'pending');
    $norm->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(5)->willReturn($norm);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->with('legal_norm')->willReturn($storage);

    // No chunking/embedding services (NULL).
    $worker = new NormVectorizationWorker(
      [],
      'jaraba_legal_intelligence_vectorization',
      ['cron' => ['time' => 120]],
      $entityTypeManager,
      new NullLogger(),
      NULL,
      NULL,
    );

    $worker->processItem(['norm_id' => 5]);
  }

  /**
   * Tests that non-existent norm is handled gracefully.
   *
   * @covers ::processItem
   */
  public function testNonExistentNormHandled(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->with('legal_norm')->willReturn($storage);

    $worker = new NormVectorizationWorker(
      [],
      'jaraba_legal_intelligence_vectorization',
      ['cron' => ['time' => 120]],
      $entityTypeManager,
      new NullLogger(),
    );

    $worker->processItem(['norm_id' => 999]);
  }

  /**
   * Tests successful vectorization with mock services.
   *
   * MOCK-DYNPROP-001: PHP 8.4 prohibits dynamic properties on mocks.
   * Use anonymous classes with typed properties instead.
   *
   * @covers ::processItem
   */
  public function testSuccessfulVectorization(): void {
    // Anonymous class for field value (MOCK-DYNPROP-001).
    $fullTextField = new class {
      public ?string $value = 'Articulo 1. El derecho a la educacion...';
    };

    $titleField = new class {
      public ?string $value = 'Ley Organica 2/2006, de 3 de mayo, de Educacion';
    };

    $emptyField = new class {
      public ?string $value = NULL;
    };

    $setCallArgs = [];
    $norm = $this->createMock(ContentEntityInterface::class);
    $norm->method('get')
      ->willReturnCallback(function (string $field) use ($fullTextField, $titleField, $emptyField): object {
        return match ($field) {
          'full_text' => $fullTextField,
          'title' => $titleField,
          default => $emptyField,
        };
      });
    $norm->method('set')
      ->willReturnCallback(function (string $field, mixed $value) use (&$setCallArgs, $norm): ContentEntityInterface {
        $setCallArgs[$field] = $value;
        return $norm;
      });
    $norm->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(7)->willReturn($norm);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->with('legal_norm')->willReturn($storage);

    // Mock chunking service (anonymous class).
    $chunkingService = new class {

      /**
       * Chunks text into fragments.
       *
       * @return list<array{text: string, index: int}>
       */
      public function chunk(string $text, array $options): array {
        return [
          ['text' => 'chunk1', 'index' => 0],
          ['text' => 'chunk2', 'index' => 1],
        ];
      }

    };

    // Mock embedding service (anonymous class).
    $embeddingService = new class {

      /**
       * Embeds and stores chunks.
       */
      public function embedAndStore(array $chunks, array $options): void {
        // No-op for test.
      }

    };

    $worker = new NormVectorizationWorker(
      [],
      'jaraba_legal_intelligence_vectorization',
      ['cron' => ['time' => 120]],
      $entityTypeManager,
      new NullLogger(),
      $chunkingService,
      $embeddingService,
    );

    $worker->processItem(['norm_id' => 7, 'reason' => 'text_change']);

    $this->assertSame('completed', $setCallArgs['embedding_status'] ?? '');
    $this->assertSame(2, $setCallArgs['chunk_count'] ?? 0);
  }

}
