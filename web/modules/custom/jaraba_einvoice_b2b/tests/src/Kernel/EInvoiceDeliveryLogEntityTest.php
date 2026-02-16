<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for EInvoiceDeliveryLog entity (append-only).
 *
 * Verifies that the delivery log can be created and queried,
 * and that the entity stores all audit fields correctly.
 * Access control (update/delete denial) is tested in functional tests.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoiceDeliveryLogEntityTest extends KernelTestBase {

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
    'jaraba_einvoice_b2b',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('einvoice_delivery_log');
    $this->installConfig(['jaraba_einvoice_b2b']);
  }

  /**
   * Tests creating a delivery log entry.
   */
  public function testCreateDeliveryLog(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_delivery_log');

    $log = $storage->create([
      'tenant_id' => 1,
      'einvoice_document_id' => 42,
      'operation' => 'send',
      'channel' => 'spfe',
      'request_payload' => '{"document_id":42}',
      'response_payload' => '{"success":true}',
      'response_code' => 'OK',
      'http_status' => 200,
      'duration_ms' => 350,
    ]);
    $log->save();

    $loaded = $storage->load($log->id());
    $this->assertNotNull($loaded);
    $this->assertSame('send', $loaded->get('operation')->value);
    $this->assertSame('spfe', $loaded->get('channel')->value);
    $this->assertSame('OK', $loaded->get('response_code')->value);
    $this->assertSame('200', $loaded->get('http_status')->value);
    $this->assertSame('350', $loaded->get('duration_ms')->value);
  }

  /**
   * Tests querying delivery logs by document.
   */
  public function testQueryByDocument(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_delivery_log');

    // Create logs for different documents.
    $storage->create([
      'einvoice_document_id' => 10,
      'channel' => 'spfe',
      'operation' => 'send',
    ])->save();
    $storage->create([
      'einvoice_document_id' => 10,
      'channel' => 'email',
      'operation' => 'send',
    ])->save();
    $storage->create([
      'einvoice_document_id' => 20,
      'channel' => 'platform',
      'operation' => 'send',
    ])->save();

    $logsForDoc10 = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('einvoice_document_id', 10)
      ->execute();
    $this->assertCount(2, $logsForDoc10);
  }

  /**
   * Tests error detail field stores error messages.
   */
  public function testErrorDetailField(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_delivery_log');

    $log = $storage->create([
      'einvoice_document_id' => 5,
      'operation' => 'send',
      'channel' => 'email',
      'response_code' => 'ERROR',
      'http_status' => 500,
      'error_detail' => 'SMTP timeout after 30 seconds.',
    ]);
    $log->save();

    $loaded = $storage->load($log->id());
    $this->assertSame('SMTP timeout after 30 seconds.', $loaded->get('error_detail')->value);
    $this->assertSame('ERROR', $loaded->get('response_code')->value);
  }

  /**
   * Tests chronological ordering of log entries.
   */
  public function testChronologicalOrdering(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_delivery_log');

    $log1 = $storage->create(['einvoice_document_id' => 1, 'operation' => 'send', 'channel' => 'a']);
    $log1->save();
    $log2 = $storage->create(['einvoice_document_id' => 1, 'operation' => 'send', 'channel' => 'b']);
    $log2->save();

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('einvoice_document_id', 1)
      ->sort('created', 'ASC')
      ->execute();

    $sorted = array_values($ids);
    $this->assertTrue((int) $sorted[0] < (int) $sorted[1]);
  }

}
