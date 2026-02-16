<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for EInvoiceDocument entity CRUD and field storage.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoiceDocumentEntityTest extends KernelTestBase {

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
    $this->installEntitySchema('einvoice_document');
    $this->installConfig(['jaraba_einvoice_b2b']);
  }

  /**
   * Tests creating and loading a document entity.
   */
  public function testCreateAndLoad(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_document');

    $document = $storage->create([
      'tenant_id' => 1,
      'direction' => 'outbound',
      'format' => 'ubl_2.1',
      'invoice_number' => 'KT-2026-001',
      'invoice_date' => '2026-01-15',
      'due_date' => '2026-02-14',
      'seller_nif' => 'B12345678',
      'seller_name' => 'Cooperativa Test',
      'buyer_nif' => 'A87654321',
      'buyer_name' => 'Buyer SL',
      'currency_code' => 'EUR',
      'total_without_tax' => '1000.00',
      'total_tax' => '210.00',
      'total_amount' => '1210.00',
      'status' => 'draft',
      'delivery_status' => 'pending',
      'payment_status' => 'pending',
    ]);
    $document->save();

    $loaded = $storage->load($document->id());
    $this->assertNotNull($loaded);
    $this->assertSame('KT-2026-001', $loaded->get('invoice_number')->value);
    $this->assertSame('outbound', $loaded->get('direction')->value);
    $this->assertSame('ubl_2.1', $loaded->get('format')->value);
    $this->assertSame('B12345678', $loaded->get('seller_nif')->value);
    $this->assertSame('A87654321', $loaded->get('buyer_nif')->value);
    $this->assertSame('1210.00', $loaded->get('total_amount')->value);
    $this->assertSame('draft', $loaded->get('status')->value);
    $this->assertSame('pending', $loaded->get('delivery_status')->value);
    $this->assertSame('pending', $loaded->get('payment_status')->value);
  }

  /**
   * Tests querying documents by direction.
   */
  public function testQueryByDirection(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_document');

    $storage->create([
      'direction' => 'outbound',
      'invoice_number' => 'OUT-001',
      'seller_nif' => 'B12345678',
      'buyer_nif' => 'A87654321',
    ])->save();

    $storage->create([
      'direction' => 'inbound',
      'invoice_number' => 'IN-001',
      'seller_nif' => 'A87654321',
      'buyer_nif' => 'B12345678',
    ])->save();

    $outbound = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('direction', 'outbound')
      ->execute();
    $this->assertCount(1, $outbound);

    $inbound = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('direction', 'inbound')
      ->execute();
    $this->assertCount(1, $inbound);
  }

  /**
   * Tests updating document status fields.
   */
  public function testUpdateStatus(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_document');

    $document = $storage->create([
      'invoice_number' => 'UPD-001',
      'status' => 'draft',
      'delivery_status' => 'pending',
      'payment_status' => 'pending',
    ]);
    $document->save();

    $document->set('status', 'sent');
    $document->set('delivery_status', 'delivered');
    $document->set('payment_status', 'partial');
    $document->save();

    $loaded = $storage->load($document->id());
    $this->assertSame('sent', $loaded->get('status')->value);
    $this->assertSame('delivered', $loaded->get('delivery_status')->value);
    $this->assertSame('partial', $loaded->get('payment_status')->value);
  }

  /**
   * Tests deleting a document.
   */
  public function testDelete(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_document');

    $document = $storage->create(['invoice_number' => 'DEL-001']);
    $document->save();
    $id = $document->id();

    $document->delete();
    $this->assertNull($storage->load($id));
  }

  /**
   * Tests XML content field can store large XML.
   */
  public function testXmlContentField(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_document');

    $largeXml = str_repeat('<line>Test content</line>', 1000);
    $document = $storage->create([
      'invoice_number' => 'XML-001',
      'xml_content' => $largeXml,
    ]);
    $document->save();

    $loaded = $storage->load($document->id());
    $this->assertSame($largeXml, $loaded->get('xml_content')->value);
  }

  /**
   * Tests querying by payment status.
   */
  public function testQueryByPaymentStatus(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_document');

    $storage->create(['invoice_number' => 'P-001', 'payment_status' => 'pending'])->save();
    $storage->create(['invoice_number' => 'P-002', 'payment_status' => 'paid'])->save();
    $storage->create(['invoice_number' => 'P-003', 'payment_status' => 'overdue'])->save();

    $pendingIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('payment_status', ['pending', 'overdue'], 'IN')
      ->execute();
    $this->assertCount(2, $pendingIds);
  }

}
