<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Unit;

use Drupal\jaraba_einvoice_b2b\Exception\EInvoiceDeliveryException;
use Drupal\jaraba_einvoice_b2b\Exception\EInvoiceValidationException;
use Drupal\jaraba_einvoice_b2b\Exception\SPFEConnectionException;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for custom exception classes.
 *
 * @group jaraba_einvoice_b2b
 */
class ExceptionsTest extends UnitTestCase {

  /**
   * Tests EInvoiceValidationException with errors and layer.
   */
  public function testValidationExceptionWithErrors(): void {
    $errors = ['BR-01: Missing ID.', 'BR-02: Missing date.'];
    $exception = new EInvoiceValidationException($errors, 'schematron');

    $this->assertSame($errors, $exception->getErrors());
    $this->assertSame('schematron', $exception->getLayer());
    $this->assertStringContainsString('schematron', $exception->getMessage());
    $this->assertStringContainsString('BR-01', $exception->getMessage());
    $this->assertInstanceOf(\RuntimeException::class, $exception);
  }

  /**
   * Tests EInvoiceValidationException with custom message.
   */
  public function testValidationExceptionCustomMessage(): void {
    $exception = new EInvoiceValidationException([], 'xsd', 'Custom message.');
    $this->assertSame('Custom message.', $exception->getMessage());
    $this->assertEmpty($exception->getErrors());
    $this->assertSame('xsd', $exception->getLayer());
  }

  /**
   * Tests EInvoiceDeliveryException with channel.
   */
  public function testDeliveryExceptionWithChannel(): void {
    $exception = new EInvoiceDeliveryException('email');

    $this->assertSame('email', $exception->getChannel());
    $this->assertStringContainsString('email', $exception->getMessage());
    $this->assertInstanceOf(\RuntimeException::class, $exception);
  }

  /**
   * Tests EInvoiceDeliveryException with custom message.
   */
  public function testDeliveryExceptionCustomMessage(): void {
    $exception = new EInvoiceDeliveryException('spfe', 'Connection timeout.');
    $this->assertSame('Connection timeout.', $exception->getMessage());
    $this->assertSame('spfe', $exception->getChannel());
  }

  /**
   * Tests SPFEConnectionException default message.
   */
  public function testSpfeConnectionExceptionDefault(): void {
    $exception = new SPFEConnectionException();
    $this->assertSame('SPFE connection failed.', $exception->getMessage());
    $this->assertInstanceOf(\RuntimeException::class, $exception);
  }

  /**
   * Tests SPFEConnectionException custom message.
   */
  public function testSpfeConnectionExceptionCustom(): void {
    $exception = new SPFEConnectionException('Timeout after 30s.', 504);
    $this->assertSame('Timeout after 30s.', $exception->getMessage());
    $this->assertSame(504, $exception->getCode());
  }

  /**
   * Tests exception chaining with previous.
   */
  public function testExceptionChaining(): void {
    $previous = new \RuntimeException('Root cause.');
    $exception = new EInvoiceDeliveryException('peppol', 'Delivery failed.', 0, $previous);

    $this->assertSame($previous, $exception->getPrevious());
    $this->assertSame('Root cause.', $exception->getPrevious()->getMessage());
  }

}
