<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Controller\FiscalDashboardController;
use Drupal\ecosistema_jaraba_core\Service\FiscalComplianceService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FiscalDashboardController.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Controller\FiscalDashboardController
 */
class FiscalDashboardControllerTest extends UnitTestCase {

  /**
   * Tests dashboard returns proper render array structure.
   *
   * @covers ::dashboard
   */
  public function testDashboardReturnsRenderArray(): void {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $complianceService = $this->getMockBuilder(FiscalComplianceService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $complianceService->method('calculateScore')->willReturn([
      'score' => 100,
      'grade' => 'A',
      'factors' => [],
      'alerts' => [],
      'tenant_id' => '0',
      'calculated_at' => '2026-02-16T00:00:00+00:00',
    ]);
    $complianceService->method('getInstalledModules')->willReturn([
      'verifactu' => FALSE,
      'facturae' => FALSE,
      'einvoice_b2b' => FALSE,
    ]);

    $tenantContext = $this->createMock(TenantContextService::class);

    $controller = new FiscalDashboardController(
      $complianceService,
      $logger,
      $tenantContext,
    );

    // Use reflection to set the entityTypeManager.
    $reflection = new \ReflectionClass($controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($controller, $entityTypeManager);

    $controller->setStringTranslation($this->getStringTranslationStub());

    $result = $controller->dashboard();

    // Verify render array structure.
    $this->assertArrayHasKey('#attached', $result);
    $this->assertArrayHasKey('#fiscal_compliance', $result);
    $this->assertArrayHasKey('#fiscal_installed_modules', $result);
    $this->assertArrayHasKey('#fiscal_verifactu_stats', $result);
    $this->assertArrayHasKey('#fiscal_facturae_stats', $result);
    $this->assertArrayHasKey('#fiscal_einvoice_stats', $result);
    $this->assertArrayHasKey('#fiscal_certificate_status', $result);
    $this->assertArrayHasKey('content', $result);

    // Verify attached library.
    $this->assertContains(
      'ecosistema_jaraba_core/fiscal-styles',
      $result['#attached']['library']
    );

    // Verify drupalSettings.
    $this->assertArrayHasKey('fiscalDashboard', $result['#attached']['drupalSettings']);
    $this->assertSame(100, $result['#attached']['drupalSettings']['fiscalDashboard']['compliance']['score']);
  }

  /**
   * Tests dashboard with modules not installed returns installed=FALSE stats.
   *
   * @covers ::dashboard
   */
  public function testDashboardStatsWhenModulesNotInstalled(): void {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $complianceService = $this->getMockBuilder(FiscalComplianceService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $complianceService->method('calculateScore')->willReturn([
      'score' => 100,
      'grade' => 'A',
      'factors' => [],
      'alerts' => [],
      'tenant_id' => '0',
      'calculated_at' => '2026-02-16T00:00:00+00:00',
    ]);
    $complianceService->method('getInstalledModules')->willReturn([
      'verifactu' => FALSE,
      'facturae' => FALSE,
      'einvoice_b2b' => FALSE,
    ]);

    $tenantContext = $this->createMock(TenantContextService::class);

    $controller = new FiscalDashboardController(
      $complianceService,
      $logger,
      $tenantContext,
    );

    $reflection = new \ReflectionClass($controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($controller, $entityTypeManager);

    $controller->setStringTranslation($this->getStringTranslationStub());

    $result = $controller->dashboard();

    // All module stats should report installed=FALSE.
    $this->assertFalse($result['#fiscal_verifactu_stats']['installed']);
    $this->assertFalse($result['#fiscal_facturae_stats']['installed']);
    $this->assertFalse($result['#fiscal_einvoice_stats']['installed']);
  }

  /**
   * Tests VeriFactu stats load when module is installed.
   *
   * @covers ::dashboard
   */
  public function testVerifactuStatsLoadedWhenInstalled(): void {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $hashService = $this->createMock(\stdClass::class);

    // Mock entity storage for verifactu_invoice_record.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(42);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $complianceService = $this->getMockBuilder(FiscalComplianceService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $complianceService->method('calculateScore')->willReturn([
      'score' => 80,
      'grade' => 'B',
      'factors' => [],
      'alerts' => [],
      'tenant_id' => '0',
      'calculated_at' => '2026-02-16T00:00:00+00:00',
    ]);
    $complianceService->method('getInstalledModules')->willReturn([
      'verifactu' => TRUE,
      'facturae' => FALSE,
      'einvoice_b2b' => FALSE,
    ]);

    $tenantContext = $this->createMock(TenantContextService::class);

    $controller = new FiscalDashboardController(
      $complianceService,
      $logger,
      $tenantContext,
      $hashService,
    );

    $reflection = new \ReflectionClass($controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($controller, $entityTypeManager);

    $controller->setStringTranslation($this->getStringTranslationStub());

    $result = $controller->dashboard();

    $this->assertTrue($result['#fiscal_verifactu_stats']['installed']);
    $this->assertSame(42, $result['#fiscal_verifactu_stats']['total_records']);
  }

}
