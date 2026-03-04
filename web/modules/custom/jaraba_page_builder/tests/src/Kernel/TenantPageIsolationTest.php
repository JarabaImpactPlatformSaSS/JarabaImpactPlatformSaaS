<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_page_builder\Kernel;

use Drupal\Tests\ecosistema_jaraba_core\Kernel\TenantIsolationBaseTest;

/**
 * Tests tenant isolation for PageContent entity.
 *
 * TENANT-ISOLATION-ACCESS-001: PageContentAccessControlHandler enforces
 * hard forbidden() on cross-tenant update/delete.
 *
 * @group tenant_isolation
 * @group jaraba_page_builder
 */
class TenantPageIsolationTest extends TenantIsolationBaseTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_page_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['page_content'])) {
      $this->markTestSkipped('page_content entity type not available.');
    }

    $this->installEntitySchema('page_content');
    $this->installConfig(['jaraba_page_builder']);
  }

  /**
   * Tests published pages are publicly viewable.
   */
  public function testPublishedPagePubliclyViewable(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('page_content');
    $page = $storage->create([
      'title' => 'Public Page',
      'uid' => $this->userA->id(),
      'status' => TRUE,
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $page->save();

    // Both users can view published pages.
    $this->assertTrue($page->access('view', $this->userA));
    $this->assertTrue($page->access('view', $this->userB));
  }

  /**
   * Tests non-owner from different tenant cannot update pages.
   */
  public function testCrossTenantCannotUpdate(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('page_content');
    $page = $storage->create([
      'title' => 'Tenant A Page',
      'uid' => $this->userA->id(),
      'status' => TRUE,
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $page->save();

    $this->assertFalse($page->access('update', $this->userB));
  }

  /**
   * Tests non-owner from different tenant cannot delete pages.
   */
  public function testCrossTenantCannotDelete(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('page_content');
    $page = $storage->create([
      'title' => 'Tenant A Page',
      'uid' => $this->userA->id(),
      'status' => TRUE,
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $page->save();

    $this->assertFalse($page->access('delete', $this->userB));
  }

}
