<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_groups\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_groups.
 *
 * @group jaraba_groups
 */
class ServiceExistsTest extends TestCase {

  /**
   *
   */
  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_groups.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
