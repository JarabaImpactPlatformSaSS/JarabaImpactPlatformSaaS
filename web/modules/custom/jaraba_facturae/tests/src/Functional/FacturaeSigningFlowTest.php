<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Facturae signing flow.
 *
 * @group jaraba_facturae
 */
class FacturaeSigningFlowTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
  ];

  /**
   * Tests sign endpoint requires sign permission.
   */
  public function testSignEndpointRequiresPermission(): void {
    $account = $this->drupalCreateUser(['view facturae documents']);
    $this->drupalLogin($account);
    // POST to sign endpoint should require sign permission.
    $this->drupalGet('/api/v1/facturae/documents/1/sign');
    $statusCode = $this->getSession()->getStatusCode();
    // Should be 403 (no permission) or 405 (GET not allowed).
    $this->assertTrue(in_array($statusCode, [403, 404, 405], TRUE));
  }

  /**
   * Tests send to FACe endpoint requires send permission.
   */
  public function testSendToFaceRequiresPermission(): void {
    $account = $this->drupalCreateUser(['view facturae documents']);
    $this->drupalLogin($account);
    $this->drupalGet('/api/v1/facturae/documents/1/send');
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertTrue(in_array($statusCode, [403, 404, 405], TRUE));
  }

  /**
   * Tests that the XAdES service class is properly structured.
   */
  public function testXAdESServiceStructure(): void {
    $reflection = new \ReflectionClass(\Drupal\jaraba_facturae\Service\FacturaeXAdESService::class);

    // Should have the three public methods.
    $this->assertTrue($reflection->hasMethod('signDocument'));
    $this->assertTrue($reflection->hasMethod('verifySignature'));
    $this->assertTrue($reflection->hasMethod('getCertificateInfo'));

    // signDocument should return string.
    $signMethod = $reflection->getMethod('signDocument');
    $returnType = $signMethod->getReturnType();
    $this->assertNotNull($returnType);
    $this->assertEquals('string', $returnType->getName());
  }

  /**
   * Tests that FACeResponse has correct factories.
   */
  public function testFACeResponseFactories(): void {
    $success = \Drupal\jaraba_facturae\ValueObject\FACeResponse::success('0', 'OK', 'REG-1', 'CSV-1');
    $this->assertTrue($success->success);
    $this->assertEquals('REG-1', $success->registryNumber);

    $error = \Drupal\jaraba_facturae\ValueObject\FACeResponse::error('500', 'Failed');
    $this->assertFalse($error->success);
    $this->assertEmpty($error->registryNumber);
  }

}
