<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\Plugin\QueueWorker;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_legal_intelligence\Plugin\QueueWorker\NormativeUpdateWorker;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for NormativeUpdateWorker queue worker.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\Plugin\QueueWorker\NormativeUpdateWorker
 * @group jaraba_legal_intelligence
 */
class NormativeUpdateWorkerTest extends TestCase {

  /**
   * Tests that items without required IDs are discarded.
   *
   * @covers ::processItem
   */
  public function testMissingIdsDiscarded(): void {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->never())->method('getStorage');

    $worker = new NormativeUpdateWorker(
      [],
      'jaraba_legal_intelligence_normative_update',
      ['cron' => ['time' => 60]],
      $entityTypeManager,
      new NullLogger(),
    );

    // Missing affected_norm_id.
    $worker->processItem(['source_norm_id' => 1]);
    // Missing source_norm_id.
    $worker->processItem(['affected_norm_id' => 1]);
    // Both missing.
    $worker->processItem([]);
  }

  /**
   * Tests that non-existent norms are handled gracefully.
   *
   * @covers ::processItem
   */
  public function testNonExistentNormHandledGracefully(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->with('legal_norm')->willReturn($storage);

    $worker = new NormativeUpdateWorker(
      [],
      'jaraba_legal_intelligence_normative_update',
      ['cron' => ['time' => 60]],
      $entityTypeManager,
      new NullLogger(),
    );

    // Should not throw.
    $worker->processItem([
      'affected_norm_id' => 999,
      'source_norm_id' => 1,
    ]);
  }

  /**
   * Tests successful status update of affected norm.
   *
   * @covers ::processItem
   */
  public function testStatusUpdateOnDerogation(): void {
    // Create affected norm mock.
    $tenantField = $this->createMock(FieldItemListInterface::class);
    $tenantField->target_id = 42;

    $affectedNorm = $this->createMock(ContentEntityInterface::class);
    $affectedNorm->method('get')
      ->willReturnCallback(function (string $field) use ($tenantField) {
        if ($field === 'tenant_id') {
          return $tenantField;
        }
        return $this->createMock(FieldItemListInterface::class);
      });
    $affectedNorm->expects($this->atLeastOnce())->method('set');
    $affectedNorm->expects($this->once())->method('save');

    $normStorage = $this->createMock(EntityStorageInterface::class);
    $normStorage->method('load')
      ->with(10)
      ->willReturn($affectedNorm);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->with('legal_norm')
      ->willReturn($normStorage);
    $entityTypeManager->method('hasDefinition')
      ->willReturn(FALSE);

    $worker = new NormativeUpdateWorker(
      [],
      'jaraba_legal_intelligence_normative_update',
      ['cron' => ['time' => 60]],
      $entityTypeManager,
      new NullLogger(),
    );

    $worker->processItem([
      'affected_norm_id' => 10,
      'source_norm_id' => 20,
      'relation_type' => 'deroga_total',
      'new_status' => 'derogada',
      'tenant_id' => 42,
    ]);
  }

}
