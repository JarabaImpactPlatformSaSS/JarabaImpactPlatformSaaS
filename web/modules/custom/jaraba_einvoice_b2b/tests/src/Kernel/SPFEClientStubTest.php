<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Kernel;

use Drupal\jaraba_einvoice_b2b\Service\SPFEClient\SPFEClientInterface;
use Drupal\jaraba_einvoice_b2b\Service\SPFEClient\SPFEClientStub;
use Drupal\jaraba_einvoice_b2b\ValueObject\SPFEStatus;
use Drupal\jaraba_einvoice_b2b\ValueObject\SPFESubmission;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the SPFE client stub.
 *
 * Tests the stub implementation with real DI from the container.
 *
 * @group jaraba_einvoice_b2b
 * @coversDefaultClass \Drupal\jaraba_einvoice_b2b\Service\SPFEClient\SPFEClientStub
 */
class SPFEClientStubTest extends KernelTestBase {

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
    'file',
    'jaraba_einvoice_b2b',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['jaraba_einvoice_b2b']);
  }

  /**
   * Tests stub implements the interface.
   */
  public function testImplementsInterface(): void {
    $client = $this->container->get('jaraba_einvoice_b2b.spfe_client');
    $this->assertInstanceOf(SPFEClientInterface::class, $client);
    $this->assertInstanceOf(SPFEClientStub::class, $client);
  }

  /**
   * Tests submitInvoice returns accepted submission.
   *
   * @covers ::submitInvoice
   */
  public function testSubmitInvoiceReturnsAccepted(): void {
    $client = $this->container->get('jaraba_einvoice_b2b.spfe_client');

    $result = $client->submitInvoice('<Invoice/>', 1);

    $this->assertInstanceOf(SPFESubmission::class, $result);
    $this->assertTrue($result->success);
    $this->assertSame('accepted', $result->status);
    $this->assertNotNull($result->submissionId);
    $this->assertStringStartsWith('SPFE-STUB-', $result->submissionId);
  }

  /**
   * Tests submitInvoice generates deterministic IDs.
   *
   * @covers ::submitInvoice
   */
  public function testSubmitInvoiceDeterministicId(): void {
    $client = $this->container->get('jaraba_einvoice_b2b.spfe_client');

    $result1 = $client->submitInvoice('<Invoice>A</Invoice>', 1);
    $result2 = $client->submitInvoice('<Invoice>A</Invoice>', 1);

    // Same input = same submission ID.
    $this->assertSame($result1->submissionId, $result2->submissionId);

    // Different input = different submission ID.
    $result3 = $client->submitInvoice('<Invoice>B</Invoice>', 1);
    $this->assertNotSame($result1->submissionId, $result3->submissionId);
  }

  /**
   * Tests querySubmission returns accepted status.
   *
   * @covers ::querySubmission
   */
  public function testQuerySubmission(): void {
    $client = $this->container->get('jaraba_einvoice_b2b.spfe_client');

    $status = $client->querySubmission('SPFE-STUB-TEST');

    $this->assertInstanceOf(SPFEStatus::class, $status);
    $this->assertSame('SPFE-STUB-TEST', $status->submissionId);
    $this->assertSame('accepted', $status->status);
    $this->assertTrue($status->isAccepted());
  }

  /**
   * Tests submitPaymentStatus returns accepted.
   *
   * @covers ::submitPaymentStatus
   */
  public function testSubmitPaymentStatus(): void {
    $client = $this->container->get('jaraba_einvoice_b2b.spfe_client');

    $result = $client->submitPaymentStatus(42, 1);

    $this->assertInstanceOf(SPFESubmission::class, $result);
    $this->assertTrue($result->success);
    $this->assertStringStartsWith('SPFE-PAY-STUB-', $result->submissionId);
  }

  /**
   * Tests queryReceivedInvoices returns empty array.
   *
   * @covers ::queryReceivedInvoices
   */
  public function testQueryReceivedInvoicesEmpty(): void {
    $client = $this->container->get('jaraba_einvoice_b2b.spfe_client');
    $this->assertSame([], $client->queryReceivedInvoices(1));
  }

  /**
   * Tests testConnection always returns TRUE.
   *
   * @covers ::testConnection
   */
  public function testConnectionAlwaysTrue(): void {
    $client = $this->container->get('jaraba_einvoice_b2b.spfe_client');
    $this->assertTrue($client->testConnection(1));
    $this->assertTrue($client->testConnection(999));
  }

}
