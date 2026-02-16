<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Kernel;

use Drupal\jaraba_einvoice_b2b\Service\EInvoiceDeliveryService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceFormatConverterService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoicePaymentStatusService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceValidationService;
use Drupal\jaraba_einvoice_b2b\Service\SPFEClient\SPFEClientInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for service registration in the DI container.
 *
 * Verifies all 6 services are properly registered and instantiated.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoiceServiceRegistrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'jaraba_einvoice_b2b',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('einvoice_document');
    $this->installEntitySchema('einvoice_delivery_log');
    $this->installEntitySchema('einvoice_tenant_config');
    $this->installEntitySchema('einvoice_payment_event');
    $this->installConfig(['jaraba_einvoice_b2b']);
  }

  /**
   * Tests UBL service is registered.
   */
  public function testUblServiceExists(): void {
    $service = $this->container->get('jaraba_einvoice_b2b.ubl_service');
    $this->assertInstanceOf(EInvoiceUblService::class, $service);
  }

  /**
   * Tests format converter service is registered.
   */
  public function testFormatConverterServiceExists(): void {
    $service = $this->container->get('jaraba_einvoice_b2b.format_converter');
    $this->assertInstanceOf(EInvoiceFormatConverterService::class, $service);
  }

  /**
   * Tests validation service is registered.
   */
  public function testValidationServiceExists(): void {
    $service = $this->container->get('jaraba_einvoice_b2b.validation_service');
    $this->assertInstanceOf(EInvoiceValidationService::class, $service);
  }

  /**
   * Tests SPFE client service is registered (stub).
   */
  public function testSpfeClientExists(): void {
    $service = $this->container->get('jaraba_einvoice_b2b.spfe_client');
    $this->assertInstanceOf(SPFEClientInterface::class, $service);
  }

  /**
   * Tests delivery service is registered.
   */
  public function testDeliveryServiceExists(): void {
    $service = $this->container->get('jaraba_einvoice_b2b.delivery_service');
    $this->assertInstanceOf(EInvoiceDeliveryService::class, $service);
  }

  /**
   * Tests payment status service is registered.
   */
  public function testPaymentStatusServiceExists(): void {
    $service = $this->container->get('jaraba_einvoice_b2b.payment_status_service');
    $this->assertInstanceOf(EInvoicePaymentStatusService::class, $service);
  }

}
