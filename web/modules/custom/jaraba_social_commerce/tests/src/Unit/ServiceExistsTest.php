<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_social_commerce\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_social_commerce.
 *
 * @group jaraba_social_commerce
 */
class ServiceExistsTest extends TestCase {

  /**
   *
   */
  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_social_commerce.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
