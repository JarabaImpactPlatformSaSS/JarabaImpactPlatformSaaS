<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agent_market\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies service definitions for jaraba_agent_market.
 *
 * @group jaraba_agent_market
 */
class ServiceExistsTest extends TestCase {

  public function testServiceFileExists(): void {
    $servicesFile = dirname(__DIR__, 3) . '/jaraba_agent_market.services.yml';
    $this->assertFileExists($servicesFile);
  }

}
