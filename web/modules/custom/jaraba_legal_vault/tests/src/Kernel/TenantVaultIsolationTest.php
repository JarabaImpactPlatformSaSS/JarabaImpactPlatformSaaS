<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_vault\Kernel;

use Drupal\Tests\ecosistema_jaraba_core\Kernel\TenantIsolationBaseTest;

/**
 * Tests tenant isolation for SecureDocument entity.
 *
 * Verifies that legal vault documents are scoped to their owner
 * and not accessible by users from other tenants.
 *
 * @group tenant_isolation
 * @group jaraba_legal_vault
 */
class TenantVaultIsolationTest extends TenantIsolationBaseTest {

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
    'taxonomy',
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_legal_cases',
    'jaraba_legal_vault',
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
    if (!isset($definitions['secure_document'])) {
      $this->markTestSkipped('secure_document entity type not available.');
    }

    $this->installEntitySchema('secure_document');
    $this->installConfig(['jaraba_legal_vault']);
  }

  /**
   * Tests owner can view their own document.
   */
  public function testOwnerCanViewOwnDocument(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('secure_document');
    $doc = $storage->create([
      'label' => 'Secret Doc A',
      'owner_id' => $this->userA->id(),
    ]);
    $doc->save();

    $this->assertTrue($doc->access('view', $this->userA));
  }

  /**
   * Tests non-owner from other tenant cannot view document.
   */
  public function testCrossTenantCannotView(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('secure_document');
    $doc = $storage->create([
      'label' => 'Secret Doc A',
      'owner_id' => $this->userA->id(),
    ]);
    $doc->save();

    $this->assertFalse($doc->access('view', $this->userB));
  }

  /**
   * Tests non-owner cannot update document.
   */
  public function testCrossTenantCannotUpdate(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('secure_document');
    $doc = $storage->create([
      'label' => 'Secret Doc A',
      'owner_id' => $this->userA->id(),
    ]);
    $doc->save();

    $this->assertFalse($doc->access('update', $this->userB));
  }

  /**
   * Tests non-owner cannot delete document.
   */
  public function testCrossTenantCannotDelete(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('secure_document');
    $doc = $storage->create([
      'label' => 'Secret Doc A',
      'owner_id' => $this->userA->id(),
    ]);
    $doc->save();

    $this->assertFalse($doc->access('delete', $this->userB));
  }

}
