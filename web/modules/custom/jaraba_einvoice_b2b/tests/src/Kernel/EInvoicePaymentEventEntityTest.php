<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for EInvoicePaymentEvent entity.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoicePaymentEventEntityTest extends KernelTestBase {

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
    $this->installEntitySchema('einvoice_payment_event');
    $this->installConfig(['jaraba_einvoice_b2b']);
  }

  /**
   * Tests creating a payment event.
   */
  public function testCreatePaymentEvent(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_payment_event');

    $event = $storage->create([
      'tenant_id' => 1,
      'einvoice_document_id' => 42,
      'event_type' => 'payment_received',
      'amount' => '500.00',
      'payment_date' => '2026-02-01',
      'payment_method' => 'transfer',
      'payment_reference' => 'REF-001',
    ]);
    $event->save();

    $loaded = $storage->load($event->id());
    $this->assertNotNull($loaded);
    $this->assertSame('payment_received', $loaded->get('event_type')->value);
    $this->assertSame('500.00', $loaded->get('amount')->value);
    $this->assertSame('2026-02-01', $loaded->get('payment_date')->value);
    $this->assertSame('transfer', $loaded->get('payment_method')->value);
    $this->assertSame('REF-001', $loaded->get('payment_reference')->value);
  }

  /**
   * Tests SPFE communication fields.
   */
  public function testSpfeCommunicationFields(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_payment_event');

    $event = $storage->create([
      'einvoice_document_id' => 1,
      'event_type' => 'payment_received',
      'amount' => '100.00',
      'communicated_to_spfe' => FALSE,
    ]);
    $event->save();

    // Initially not communicated.
    $loaded = $storage->load($event->id());
    $this->assertFalse((bool) $loaded->get('communicated_to_spfe')->value);

    // Mark as communicated.
    $loaded->set('communicated_to_spfe', TRUE);
    $loaded->set('communication_timestamp', '2026-02-15T10:00:00');
    $loaded->set('communication_response', '{"success":true}');
    $loaded->save();

    $reloaded = $storage->load($event->id());
    $this->assertTrue((bool) $reloaded->get('communicated_to_spfe')->value);
    $this->assertSame('2026-02-15T10:00:00', $reloaded->get('communication_timestamp')->value);
  }

  /**
   * Tests querying payment events by document.
   */
  public function testQueryByDocument(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_payment_event');

    $storage->create(['einvoice_document_id' => 10, 'event_type' => 'payment_partial', 'amount' => '200.00'])->save();
    $storage->create(['einvoice_document_id' => 10, 'event_type' => 'payment_received', 'amount' => '800.00'])->save();
    $storage->create(['einvoice_document_id' => 20, 'event_type' => 'payment_received', 'amount' => '500.00'])->save();

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('einvoice_document_id', 10)
      ->sort('created', 'ASC')
      ->execute();

    $this->assertCount(2, $ids);
  }

  /**
   * Tests cumulative payment amount calculation.
   */
  public function testCumulativePaymentAmount(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_payment_event');

    $storage->create(['einvoice_document_id' => 5, 'event_type' => 'payment_partial', 'amount' => '300.00'])->save();
    $storage->create(['einvoice_document_id' => 5, 'event_type' => 'payment_partial', 'amount' => '200.00'])->save();

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('einvoice_document_id', 5)
      ->condition('event_type', ['payment_received', 'payment_partial'], 'IN')
      ->execute();

    $total = 0.0;
    foreach ($storage->loadMultiple($ids) as $event) {
      $total += (float) ($event->get('amount')->value ?? 0);
    }

    $this->assertSame(500.0, $total);
  }

}
