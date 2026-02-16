<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\jaraba_verifactu\Exception\VeriFactuAeatCommunicationException;
use Drupal\jaraba_verifactu\Exception\VeriFactuChainBreakException;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para las excepciones VeriFactu.
 *
 * @group jaraba_verifactu
 */
class VeriFactuExceptionsTest extends UnitTestCase {

  /**
   * Tests VeriFactuChainBreakException construction and properties.
   */
  public function testChainBreakExceptionProperties(): void {
    $exception = new VeriFactuChainBreakException(
      tenantId: 42,
      recordId: 100,
      expectedHash: 'abc123def456abc123def456abc123def456abc123def456abc123def456abcd',
      actualHash: 'zzz999aaa111zzz999aaa111zzz999aaa111zzz999aaa111zzz999aaa111zzzz',
    );

    $this->assertSame(42, $exception->tenantId);
    $this->assertSame(100, $exception->recordId);
    $this->assertStringContainsString('tenant 42', $exception->getMessage());
    $this->assertStringContainsString('record 100', $exception->getMessage());
    $this->assertSame(0, $exception->getCode());
  }

  /**
   * Tests VeriFactuChainBreakException with previous exception.
   */
  public function testChainBreakExceptionWithPrevious(): void {
    $previous = new \RuntimeException('disk error');
    $exception = new VeriFactuChainBreakException(
      tenantId: 1,
      recordId: 5,
      expectedHash: 'aaa',
      actualHash: 'bbb',
      previous: $previous,
    );

    $this->assertSame($previous, $exception->getPrevious());
  }

  /**
   * Tests VeriFactuAeatCommunicationException construction.
   */
  public function testAeatCommunicationExceptionProperties(): void {
    $exception = new VeriFactuAeatCommunicationException(
      message: 'SOAP fault: connection timeout',
      soapAction: 'SuministroFactEmitidas',
      responseCode: '503',
      responseXml: '<error>timeout</error>',
      httpStatusCode: 503,
    );

    $this->assertSame('SOAP fault: connection timeout', $exception->getMessage());
    $this->assertSame('SuministroFactEmitidas', $exception->soapAction);
    $this->assertSame('503', $exception->responseCode);
    $this->assertSame('<error>timeout</error>', $exception->responseXml);
    $this->assertSame(503, $exception->httpStatusCode);
  }

  /**
   * Tests VeriFactuAeatCommunicationException with defaults.
   */
  public function testAeatCommunicationExceptionDefaults(): void {
    $exception = new VeriFactuAeatCommunicationException('Generic error');

    $this->assertSame('Generic error', $exception->getMessage());
    $this->assertSame('', $exception->soapAction);
    $this->assertSame('', $exception->responseCode);
    $this->assertSame('', $exception->responseXml);
    $this->assertSame(0, $exception->httpStatusCode);
    $this->assertNull($exception->getPrevious());
  }

  /**
   * Tests VeriFactuAeatCommunicationException with previous exception.
   */
  public function testAeatCommunicationExceptionWithPrevious(): void {
    $previous = new \SoapFault('Server', 'Internal');
    $exception = new VeriFactuAeatCommunicationException(
      message: 'SOAP fault',
      previous: $previous,
    );

    $this->assertSame($previous, $exception->getPrevious());
  }

}
