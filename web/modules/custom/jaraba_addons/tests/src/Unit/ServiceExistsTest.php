<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_addons.
 *
 * @group jaraba_addons
 */
class ServiceExistsTest extends TestCase {

  /**
   *
   */
  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_addons.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
