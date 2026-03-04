<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_content_hub\Kernel;

use Drupal\Tests\ecosistema_jaraba_core\Kernel\TenantIsolationBaseTest;

/**
 * Tests tenant isolation for ContentArticle entity.
 *
 * Verifies that articles belonging to Tenant A are not
 * accessible to Tenant B users without permissions.
 *
 * @group tenant_isolation
 * @group jaraba_content_hub
 */
class TenantContentIsolationTest extends TenantIsolationBaseTest {

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
    'file',
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_content_hub',
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
    if (!isset($definitions['content_article'])) {
      $this->markTestSkipped('content_article entity type not available.');
    }

    $this->installEntitySchema('content_article');
    $this->installConfig(['jaraba_content_hub']);
  }

  /**
   * Tests owner can access their own article.
   */
  public function testOwnerCanAccessOwnArticle(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $article = $storage->create([
      'title' => 'Article Tenant A',
      'author' => $this->userA->id(),
      'status' => 'published',
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $article->save();

    $this->assertTrue($article->access('view', $this->userA));
  }

  /**
   * Tests non-owner cannot update articles of another tenant.
   */
  public function testNonOwnerCannotUpdateCrossTenant(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $article = $storage->create([
      'title' => 'Article Tenant A',
      'author' => $this->userA->id(),
      'status' => 'published',
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $article->save();

    // User B from Tenant B should not be able to update.
    $this->assertFalse($article->access('update', $this->userB));
  }

  /**
   * Tests non-owner cannot delete articles of another tenant.
   */
  public function testNonOwnerCannotDeleteCrossTenant(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $article = $storage->create([
      'title' => 'Article Tenant A',
      'author' => $this->userA->id(),
      'status' => 'published',
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $article->save();

    $this->assertFalse($article->access('delete', $this->userB));
  }

  /**
   * Tests entity query with tenant filter returns only tenant articles.
   */
  public function testQueryFiltersByTenant(): void {
    if (!$this->tenantA || !$this->tenantB) {
      $this->markTestSkipped('Tenant entities not available.');
    }

    $storage = \Drupal::entityTypeManager()->getStorage('content_article');

    $articleA = $storage->create([
      'title' => 'Article A',
      'author' => $this->userA->id(),
      'status' => 'published',
      'tenant_id' => $this->tenantA->id(),
    ]);
    $articleA->save();

    $articleB = $storage->create([
      'title' => 'Article B',
      'author' => $this->userB->id(),
      'status' => 'published',
      'tenant_id' => $this->tenantB->id(),
    ]);
    $articleB->save();

    // Query for Tenant A articles only.
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $this->tenantA->id())
      ->execute();

    $this->assertCount(1, $ids);
    $this->assertContains((string) $articleA->id(), array_values($ids));
  }

}
