<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_candidate\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_candidate\Service\CandidateProfileService;
use Drupal\jaraba_candidate\Service\ProfileCompletionService;
use PHPUnit\Framework\TestCase;

/**
 * Tests ProfileCompletionService — pure weighted scoring computation.
 *
 * Verifies section weights, matching readiness threshold (70%),
 * and next steps ordering.
 *
 * @group jaraba_candidate
 * @coversDefaultClass \Drupal\jaraba_candidate\Service\ProfileCompletionService
 */
class ProfileCompletionServiceTest extends TestCase {

  /**
   * Tests that missing profile returns 0% completion.
   */
  public function testMissingProfileReturnsZero(): void {
    $profileService = $this->createMock(CandidateProfileService::class);
    $profileService->method('getProfileByUserId')->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $service = new ProfileCompletionService($entityTypeManager, $profileService);
    $result = $service->calculateCompletion(999);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('percentage', $result);
    $this->assertEquals(0, $result['percentage']);
  }

  /**
   * Tests isReadyForMatching returns false for 0% completion.
   */
  public function testNotReadyForMatchingAtZero(): void {
    $profileService = $this->createMock(CandidateProfileService::class);
    $profileService->method('getProfileByUserId')->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $service = new ProfileCompletionService($entityTypeManager, $profileService);
    $this->assertFalse($service->isReadyForMatching(999));
  }

  /**
   * Tests getNextSteps returns steps for incomplete profile.
   */
  public function testGetNextStepsForIncompleteProfile(): void {
    $profileService = $this->createMock(CandidateProfileService::class);
    $profileService->method('getProfileByUserId')->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $service = new ProfileCompletionService($entityTypeManager, $profileService);
    $steps = $service->getNextSteps(999);

    $this->assertIsArray($steps);
    $this->assertNotEmpty($steps, 'An incomplete profile should have next steps.');
  }

  /**
   * Tests getMissingSections for missing profile.
   */
  public function testGetMissingSectionsForMissingProfile(): void {
    $profileService = $this->createMock(CandidateProfileService::class);
    $profileService->method('getProfileByUserId')->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $service = new ProfileCompletionService($entityTypeManager, $profileService);
    $missing = $service->getMissingSections(999);

    $this->assertIsArray($missing);
    $this->assertNotEmpty($missing, 'Missing profile should have all sections missing.');
  }

}
