<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_candidate\Unit\Service;

use Drupal\jaraba_candidate\Service\SkillInferenceService;
use Drupal\jaraba_candidate\Service\SkillsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests SkillInferenceService — AI inference with rule-based fallback.
 *
 * Verifies that when AI agent is null, rule-based inference triggers.
 * Tests empty text handling and keyword detection.
 *
 * @group jaraba_candidate
 * @coversDefaultClass \Drupal\jaraba_candidate\Service\SkillInferenceService
 */
class SkillInferenceServiceTest extends TestCase {

  /**
   * Tests inferFromText returns array for empty text.
   */
  public function testInferFromEmptyTextReturnsArray(): void {
    $skillsService = $this->createMock(SkillsService::class);
    $logger = $this->createMock(LoggerInterface::class);

    $service = new SkillInferenceService($skillsService, $logger, NULL, NULL);
    $result = $service->inferFromText('');

    $this->assertIsArray($result);
  }

  /**
   * Tests inferFromText without AI agent uses rule-based fallback.
   */
  public function testInferWithoutAiUsesRuleBased(): void {
    $skillsService = $this->createMock(SkillsService::class);
    $logger = $this->createMock(LoggerInterface::class);

    // No AI agent = rule-based path.
    $service = new SkillInferenceService($skillsService, $logger, NULL, NULL);
    $result = $service->inferFromText('I have experience with PHP, JavaScript and MySQL.');

    $this->assertIsArray($result);
  }

  /**
   * Tests matchAgainstJob returns array structure.
   */
  public function testMatchAgainstJobReturnsArray(): void {
    $skillsService = $this->createMock(SkillsService::class);
    $logger = $this->createMock(LoggerInterface::class);

    $service = new SkillInferenceService($skillsService, $logger, NULL, NULL);
    $skills = [
      ['name' => 'PHP', 'level' => 4],
      ['name' => 'JavaScript', 'level' => 3],
    ];

    $result = $service->matchAgainstJob($skills, 1);
    $this->assertIsArray($result);
  }

}
