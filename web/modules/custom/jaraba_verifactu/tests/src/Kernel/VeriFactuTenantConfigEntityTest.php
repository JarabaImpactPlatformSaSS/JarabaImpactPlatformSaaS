<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests VeriFactuTenantConfig entity CRUD.
 *
 * @group jaraba_verifactu
 */
class VeriFactuTenantConfigEntityTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'jaraba_verifactu',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('ecosistema_jaraba_core.certificate_manager')
      ->setSynthetic(TRUE);
  }

  protected function setUp(): void {
    parent::setUp();
    $this->container->set(
      'ecosistema_jaraba_core.certificate_manager',
      $this->createMock(\Drupal\ecosistema_jaraba_core\Service\CertificateManagerService::class),
    );
    $this->installEntitySchema('user');
    $this->installEntitySchema('verifactu_tenant_config');
    $this->installConfig(['jaraba_verifactu']);
  }

  /**
   * Tests creating and reading tenant config.
   */
  public function testCreateTenantConfig(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_tenant_config');

    $config = $storage->create([
      'tenant_id' => 1,
      'modo_verifactu' => TRUE,
      'nif_empresa' => 'B12345678',
      'nombre_empresa' => 'Test Company SL',
      'aeat_environment' => 'testing',
      'remision_automatica' => TRUE,
      'serie_factura_prefijo' => 'VF-',
    ]);
    $config->save();

    $loaded = $storage->load($config->id());
    $this->assertNotNull($loaded);
    $this->assertTrue((bool) $loaded->get('modo_verifactu')->value);
    $this->assertSame('B12345678', $loaded->get('nif_empresa')->value);
    $this->assertSame('testing', $loaded->get('aeat_environment')->value);
  }

  /**
   * Tests updating tenant config.
   */
  public function testUpdateTenantConfig(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_tenant_config');

    $config = $storage->create([
      'tenant_id' => 1,
      'modo_verifactu' => FALSE,
      'nif_empresa' => 'B12345678',
      'aeat_environment' => 'testing',
    ]);
    $config->save();

    // Update.
    $config->set('modo_verifactu', TRUE);
    $config->set('aeat_environment', 'production');
    $config->save();

    $loaded = $storage->load($config->id());
    $this->assertTrue((bool) $loaded->get('modo_verifactu')->value);
    $this->assertSame('production', $loaded->get('aeat_environment')->value);
  }

  /**
   * Tests querying config by tenant with VeriFactu enabled.
   */
  public function testQueryEnabledTenants(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_tenant_config');

    $storage->create([
      'tenant_id' => 1,
      'modo_verifactu' => TRUE,
      'nif_empresa' => 'B12345678',
    ])->save();

    $storage->create([
      'tenant_id' => 2,
      'modo_verifactu' => FALSE,
      'nif_empresa' => 'A99999999',
    ])->save();

    $storage->create([
      'tenant_id' => 3,
      'modo_verifactu' => TRUE,
      'nif_empresa' => 'B99999999',
    ])->save();

    $enabled = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('modo_verifactu', TRUE)
      ->execute();

    $this->assertCount(2, $enabled);
  }

}
