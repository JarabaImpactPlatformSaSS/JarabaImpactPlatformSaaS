<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_zkp\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_zkp.
 *
 * @group jaraba_zkp
 */
class ServiceExistsTest extends TestCase {

  /**
   *
   */
  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_zkp.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
