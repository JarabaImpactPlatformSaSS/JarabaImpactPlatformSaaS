<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_resources\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_resources.
 *
 * @group jaraba_resources
 */
class ServiceExistsTest extends TestCase {

  /**
   *
   */
  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_resources.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
