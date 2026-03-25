<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_job_board\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_job_board.
 *
 * @group jaraba_job_board
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_job_board'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_job_board'));
  }

}
