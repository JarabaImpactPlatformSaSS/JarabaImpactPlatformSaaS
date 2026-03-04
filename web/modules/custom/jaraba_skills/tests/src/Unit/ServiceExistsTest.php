<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_skills\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_skills.
 *
 * @group jaraba_skills
 */
class ServiceExistsTest extends TestCase {

  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_skills.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
