<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_site_builder\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_site_builder.
 *
 * @group jaraba_site_builder
 */
class ServiceExistsTest extends TestCase {

  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_site_builder.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
