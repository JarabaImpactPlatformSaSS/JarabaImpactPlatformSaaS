<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\ecosistema_jaraba_core\Service\FiscalComplianceService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for fiscal compliance service registration.
 *
 * Verifies the FiscalComplianceService is properly registered in the
 * container and its optional dependencies are NULL when fiscal modules
 * are not installed.
 *
 * @group ecosistema_jaraba_core
 */
class FiscalComplianceServiceRegistrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'datetime',
    'flexible_permissions',
    'group',
    'ecosistema_jaraba_core',
  ];

  /**
   * Tests service exists in container.
   */
  public function testServiceExists(): void {
    $this->assertTrue(
      $this->container->has('ecosistema_jaraba_core.fiscal_compliance'),
      'FiscalComplianceService should be registered in the container.'
    );
  }

  /**
   * Tests service is correct class.
   */
  public function testServiceClass(): void {
    $service = $this->container->get('ecosistema_jaraba_core.fiscal_compliance');
    $this->assertInstanceOf(FiscalComplianceService::class, $service);
  }

  /**
   * Tests installed modules reports FALSE when no fiscal modules present.
   */
  public function testNoFiscalModulesInstalled(): void {
    $service = $this->container->get('ecosistema_jaraba_core.fiscal_compliance');
    $modules = $service->getInstalledModules();

    $this->assertFalse($modules['verifactu']);
    $this->assertFalse($modules['facturae']);
    $this->assertFalse($modules['einvoice_b2b']);
  }

  /**
   * Tests perfect score when no fiscal modules installed.
   */
  public function testPerfectScoreWithoutFiscalModules(): void {
    $service = $this->container->get('ecosistema_jaraba_core.fiscal_compliance');
    $result = $service->calculateScore('0');

    $this->assertSame(100, $result['score']);
    $this->assertSame('A', $result['grade']);
    $this->assertEmpty($result['alerts']);
  }

  /**
   * Tests compliance summary format.
   */
  public function testComplianceSummaryFormat(): void {
    $service = $this->container->get('ecosistema_jaraba_core.fiscal_compliance');
    $summary = $service->getComplianceSummary('0');

    $this->assertArrayHasKey('score', $summary);
    $this->assertArrayHasKey('grade', $summary);
    $this->assertArrayHasKey('alert_count', $summary);
  }

}
