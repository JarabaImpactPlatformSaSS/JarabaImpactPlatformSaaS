<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for EInvoiceTenantConfig entity.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoiceTenantConfigEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'jaraba_einvoice_b2b',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('einvoice_tenant_config');
    $this->installConfig(['jaraba_einvoice_b2b']);
  }

  /**
   * Tests creating and loading tenant config.
   */
  public function testCreateAndLoad(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_tenant_config');

    $config = $storage->create([
      'tenant_id' => 1,
      'nif_emisor' => 'B12345678',
      'active' => TRUE,
      'spfe_enabled' => TRUE,
      'spfe_environment' => 'stub',
      'inbound_email' => 'facturas@cooperativa.es',
    ]);
    $config->save();

    $loaded = $storage->load($config->id());
    $this->assertNotNull($loaded);
    $this->assertSame('B12345678', $loaded->get('nif_emisor')->value);
    $this->assertTrue((bool) $loaded->get('active')->value);
    $this->assertTrue((bool) $loaded->get('spfe_enabled')->value);
    $this->assertSame('stub', $loaded->get('spfe_environment')->value);
    $this->assertSame('facturas@cooperativa.es', $loaded->get('inbound_email')->value);
  }

  /**
   * Tests querying by NIF.
   */
  public function testQueryByNif(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_tenant_config');

    $storage->create(['nif_emisor' => 'B12345678', 'active' => TRUE])->save();
    $storage->create(['nif_emisor' => 'A87654321', 'active' => TRUE])->save();

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('nif_emisor', 'B12345678')
      ->condition('active', TRUE)
      ->execute();

    $this->assertCount(1, $ids);
  }

  /**
   * Tests updating tenant config.
   */
  public function testUpdateConfig(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('einvoice_tenant_config');

    $config = $storage->create([
      'nif_emisor' => 'B12345678',
      'spfe_enabled' => FALSE,
    ]);
    $config->save();

    $config->set('spfe_enabled', TRUE);
    $config->set('spfe_environment', 'production');
    $config->save();

    $loaded = $storage->load($config->id());
    $this->assertTrue((bool) $loaded->get('spfe_enabled')->value);
    $this->assertSame('production', $loaded->get('spfe_environment')->value);
  }

}
