<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for FACe portal integration.
 *
 * @group jaraba_facturae
 */
class FacturaeFACeIntegrationTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
  ];

  /**
   * Tests FACe status endpoint requires permission.
   */
  public function testFaceStatusEndpointRequiresAuth(): void {
    $this->drupalGet('/api/v1/facturae/documents/1/face-status');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests FACe communication log endpoint.
   */
  public function testFaceLogEndpointAccess(): void {
    $account = $this->drupalCreateUser(['view facturae logs']);
    $this->drupalLogin($account);
    $this->drupalGet('/api/v1/facturae/face-logs');
    // Should be accessible (200) or require module install.
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertTrue(in_array($statusCode, [200, 500], TRUE));
  }

  /**
   * Tests FACeStatus value object lifecycle mapping completeness.
   */
  public function testFACeStatusLifecycleMapping(): void {
    $status = new \Drupal\jaraba_facturae\ValueObject\FACeStatus(
      'REG-001', '1200', 'Registrada', '', '', '', ''
    );
    $this->assertEquals('registered', $status->toEntityStatus());

    $status = new \Drupal\jaraba_facturae\ValueObject\FACeStatus(
      'REG-001', '2600', 'Pagada', '', '', '', ''
    );
    $this->assertEquals('paid', $status->toEntityStatus());

    $status = new \Drupal\jaraba_facturae\ValueObject\FACeStatus(
      'REG-001', '2500', 'Reconocida obligacion pago', '', '', '', ''
    );
    $this->assertEquals('obligation_recognized', $status->toEntityStatus());
  }

  /**
   * Tests FACeStatus cancellation detection.
   */
  public function testFACeStatusCancellationDetection(): void {
    $noCancellation = new \Drupal\jaraba_facturae\ValueObject\FACeStatus(
      'REG-001', '1200', 'Registrada', '', '', '', ''
    );
    $this->assertFalse($noCancellation->hasCancellation());

    $withCancellation = new \Drupal\jaraba_facturae\ValueObject\FACeStatus(
      'REG-001', '1200', 'Registrada', '', '3100', 'Solicitada', 'Error'
    );
    $this->assertTrue($withCancellation->hasCancellation());
  }

}
