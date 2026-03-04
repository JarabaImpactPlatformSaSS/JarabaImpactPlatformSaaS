<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_notifications\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_notifications.
 *
 * @group jaraba_notifications
 */
class ServiceExistsTest extends TestCase {

  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_notifications.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
