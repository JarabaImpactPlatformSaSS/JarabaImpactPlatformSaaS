<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ambient_ux\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_ambient_ux.
 *
 * @group jaraba_ambient_ux
 */
class ServiceExistsTest extends TestCase {

  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_ambient_ux.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
