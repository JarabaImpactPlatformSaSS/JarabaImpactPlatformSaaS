<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jaraba_facturae\Plugin\QueueWorker\FACeSyncWorker;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for FACe sync queue worker.
 *
 * @group jaraba_facturae
 */
class FacturaeQueueWorkerTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
  ];

  /**
   * Tests that FACeSyncWorker plugin class exists.
   */
  public function testFACeSyncWorkerExists(): void {
    $this->assertTrue(
      class_exists(FACeSyncWorker::class),
      'FACeSyncWorker class should exist.'
    );
  }

  /**
   * Tests that FACeSyncWorker implements ContainerFactoryPluginInterface.
   */
  public function testFACeSyncWorkerImplementsContainerFactory(): void {
    $reflection = new \ReflectionClass(FACeSyncWorker::class);
    $this->assertTrue(
      $reflection->implementsInterface(ContainerFactoryPluginInterface::class),
      'FACeSyncWorker should implement ContainerFactoryPluginInterface.'
    );
  }

}
