<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_candidate\Kernel;

use Drupal\Tests\ecosistema_jaraba_core\Kernel\TenantIsolationBaseTest;

/**
 * Tests tenant isolation for CandidateProfile entity.
 *
 * Verifies that candidate profiles are owner-scoped:
 * only the profile owner can update their profile.
 *
 * @group tenant_isolation
 * @group jaraba_candidate
 */
class TenantCandidateIsolationTest extends TenantIsolationBaseTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'ecosistema_jaraba_core',
    'jaraba_candidate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['candidate_profile'])) {
      $this->markTestSkipped('candidate_profile entity type not available.');
    }

    $this->installEntitySchema('candidate_profile');
    $this->installConfig(['jaraba_candidate']);
  }

  /**
   * Tests owner can update their own profile.
   */
  public function testOwnerCanUpdate(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $profile = $storage->create([
      'user_id' => $this->userA->id(),
    ]);
    $profile->save();

    $this->assertTrue($profile->access('update', $this->userA));
  }

  /**
   * Tests non-owner from other tenant cannot update profile.
   */
  public function testCrossTenantCannotUpdate(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $profile = $storage->create([
      'user_id' => $this->userA->id(),
    ]);
    $profile->save();

    $this->assertFalse($profile->access('update', $this->userB));
  }

  /**
   * Tests private profile not viewable by non-owner.
   */
  public function testPrivateProfileNotViewableCrossTenant(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $profile = $storage->create([
      'user_id' => $this->userA->id(),
      'is_public' => FALSE,
    ]);
    $profile->save();

    $this->assertFalse($profile->access('view', $this->userB));
  }

}
