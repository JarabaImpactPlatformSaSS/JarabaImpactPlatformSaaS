<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_paths\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_paths.
 *
 * @group jaraba_paths
 */
class ServiceExistsTest extends TestCase {

  /**
   *
   */
  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_paths.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
