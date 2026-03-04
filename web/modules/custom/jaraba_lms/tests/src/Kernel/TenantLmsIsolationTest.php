<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_lms\Kernel;

use Drupal\Tests\ecosistema_jaraba_core\Kernel\TenantIsolationBaseTest;

/**
 * Tests tenant isolation for LMS Course entity.
 *
 * Verifies that courses are properly scoped and that
 * cross-tenant modification is prevented.
 *
 * @group tenant_isolation
 * @group jaraba_lms
 */
class TenantLmsIsolationTest extends TenantIsolationBaseTest {

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
    'taxonomy',
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_lms',
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
    if (!isset($definitions['lms_course'])) {
      $this->markTestSkipped('lms_course entity type not available.');
    }

    $this->installEntitySchema('lms_course');
    $this->installConfig(['jaraba_lms']);
  }

  /**
   * Tests unprivileged user cannot update courses.
   */
  public function testUnprivilegedUserCannotUpdate(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('lms_course');
    $course = $storage->create([
      'label' => 'Course Tenant A',
      'is_published' => TRUE,
    ]);
    $course->save();

    $this->assertFalse($course->access('update', $this->userA));
    $this->assertFalse($course->access('update', $this->userB));
  }

  /**
   * Tests unprivileged user cannot delete courses.
   */
  public function testUnprivilegedUserCannotDelete(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('lms_course');
    $course = $storage->create([
      'label' => 'Course Tenant A',
      'is_published' => TRUE,
    ]);
    $course->save();

    $this->assertFalse($course->access('delete', $this->userA));
    $this->assertFalse($course->access('delete', $this->userB));
  }

}
