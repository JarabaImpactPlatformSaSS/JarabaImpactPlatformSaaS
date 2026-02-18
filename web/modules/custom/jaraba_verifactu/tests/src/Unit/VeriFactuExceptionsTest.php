<?php

declare(strict_types=1);

namespace {
  if (!class_exists('SoapFault')) {
    /**
     * Dummy SoapFault for environments without ext-soap.
     */
    class SoapFault extends \Exception {
      public function __construct($code, $message) {
        parent::__construct($message);
      }
    }
  }
}

namespace Drupal\Tests\jaraba_verifactu\Unit {

  use Drupal\jaraba_verifactu\Exception\VeriFactuAeatCommunicationException;
  use Drupal\jaraba_verifactu\Exception\VeriFactuChainBreakException;
  use Drupal\Tests\UnitTestCase;

  /**
   * Tests the custom exceptions for VeriFactu.
   *
   * @group jaraba_verifactu
   */
  class VeriFactuExceptionsTest extends UnitTestCase {

    /**
     * Tests VeriFactuChainBreakException.
     */
    public function testChainBreakException(): void {
      $e = new VeriFactuChainBreakException(42, 101, 'hash-a', 'hash-b');
      $this->assertStringContainsString('chain break detected', $e->getMessage());
      $this->assertEquals(42, $e->tenantId);
      $this->assertEquals(101, $e->recordId);
      $this->assertEquals('hash-a', $e->expectedHash);
      $this->assertEquals('hash-b', $e->actualHash);
    }

    /**
     * Tests VeriFactuAeatCommunicationException.
     */
    public function testAeatCommunicationException(): void {
      $e = new VeriFactuAeatCommunicationException('AEAT Timeout', 'SubmitInvoice');
      $this->assertEquals('AEAT Timeout', $e->getMessage());
      $this->assertEquals('SubmitInvoice', $e->soapAction);
    }

    /**
     * Tests exception wrapping.
     */
    public function testExceptionWrapping(): void {
      $previous = new \Exception('Original error');
      $e = new VeriFactuAeatCommunicationException('Wrapped error', 'Action', '', '', 0, $previous);

      $this->assertEquals('Wrapped error', $e->getMessage());
      $this->assertSame($previous, $e->getPrevious());
    }

    /**
     * Tests VeriFactuAeatCommunicationException with SoapFault simulation.
     */
    public function testAeatCommunicationExceptionWithPrevious(): void {
      $soapFault = new \SoapFault('Server', 'SOAP Error');
      $e = new VeriFactuAeatCommunicationException('Communication failed', 'Action', '', '', 0, $soapFault);

      $this->assertEquals('Communication failed', $e->getMessage());
      $this->assertSame($soapFault, $e->getPrevious());
    }

  }
}
