<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_identity\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_identity.
 *
 * @group jaraba_identity
 */
class ServiceExistsTest extends TestCase {

  /**
   *
   */
  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_identity.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
