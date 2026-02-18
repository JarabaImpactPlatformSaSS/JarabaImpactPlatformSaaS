<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_security_compliance\Unit\Service;

use Drupal\jaraba_security_compliance\Service\Soc2ControlMapperService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for Soc2ControlMapperService.
 *
 * @group jaraba_security_compliance
 * @coversDefaultClass \Drupal\jaraba_security_compliance\Service\Soc2ControlMapperService
 */
class Soc2ControlMapperServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected Soc2ControlMapperService $service;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->service = new Soc2ControlMapperService($this->logger);
  }

  /**
   * Tests getControlMapping returns all required controls.
   *
   * @covers ::getControlMapping
   */
  public function testGetControlMapping(): void {
    $mapping = $this->service->getControlMapping();

    // Should have a substantial number of controls.
    $this->assertGreaterThanOrEqual(15, count($mapping));

    // Verify the required controls exist.
    $requiredControls = [
      'CC6.1',
      'CC6.2',
      'CC6.3',
      'CC7.2',
      'CC8.1',
      'A1.2',
      'P1.1',
    ];

    foreach ($requiredControls as $controlId) {
      $this->assertArrayHasKey($controlId, $mapping, "Missing required control: $controlId");
    }

    // Verify CC6.1 has MFA and RBAC in platform_features.
    $cc61 = $mapping['CC6.1'];
    $this->assertEquals('CC6.1', $cc61['id']);
    $this->assertNotEmpty($cc61['platform_features']);
    $featuresStr = implode(' ', $cc61['platform_features']);
    $this->assertStringContainsStringIgnoringCase('MFA', $featuresStr);
    $this->assertStringContainsStringIgnoringCase('RBAC', $featuresStr);

    // Verify CC7.2 references audit logging.
    $cc72 = $mapping['CC7.2'];
    $featuresStr = implode(' ', $cc72['platform_features']);
    $this->assertStringContainsStringIgnoringCase('audit', $featuresStr);

    // Verify CC8.1 references change management.
    $cc81 = $mapping['CC8.1'];
    $featuresStr = implode(' ', $cc81['platform_features']);
    $this->assertStringContainsStringIgnoringCase('git', strtolower($featuresStr));
  }

  /**
   * Tests getControlMapping has required fields in each control.
   *
   * @covers ::getControlMapping
   */
  public function testGetControlMappingHasRequiredFields(): void {
    $mapping = $this->service->getControlMapping();

    foreach ($mapping as $controlId => $control) {
      $this->assertArrayHasKey('id', $control, "Missing id in $controlId");
      $this->assertArrayHasKey('name', $control, "Missing name in $controlId");
      $this->assertArrayHasKey('category', $control, "Missing category in $controlId");
      $this->assertArrayHasKey('description', $control, "Missing description in $controlId");
      $this->assertArrayHasKey('platform_features', $control, "Missing platform_features in $controlId");
      $this->assertArrayHasKey('status', $control, "Missing status in $controlId");
      $this->assertArrayHasKey('evidence', $control, "Missing evidence in $controlId");

      // Validate status values.
      $this->assertContains($control['status'], ['satisfied', 'partial', 'gap'],
        "Invalid status for $controlId: {$control['status']}");

      // Platform features should be a non-empty array.
      $this->assertIsArray($control['platform_features']);
      $this->assertNotEmpty($control['platform_features'], "Empty platform_features for $controlId");
    }
  }

  /**
   * Tests getControlMapping covers all SOC 2 trust service categories.
   *
   * @covers ::getControlMapping
   */
  public function testGetControlMappingCoversAllCategories(): void {
    $mapping = $this->service->getControlMapping();
    $categories = array_unique(array_column($mapping, 'category'));

    $requiredCategories = ['security', 'availability', 'confidentiality', 'privacy', 'processing_integrity'];
    foreach ($requiredCategories as $cat) {
      $this->assertContains($cat, $categories, "Missing category: $cat");
    }
  }

  /**
   * Tests assessControl for a known control.
   *
   * @covers ::assessControl
   */
  public function testAssessControlKnownControl(): void {
    $result = $this->service->assessControl('CC6.1');

    $this->assertTrue($result['found']);
    $this->assertEquals('CC6.1', $result['control_id']);
    $this->assertEquals('satisfied', $result['status']);
    $this->assertNotEmpty($result['name']);
    $this->assertNotEmpty($result['platform_features']);
    $this->assertNotEmpty($result['evidence']);
  }

  /**
   * Tests assessControl for an unknown control returns gap.
   *
   * @covers ::assessControl
   */
  public function testAssessControlUnknownControl(): void {
    $result = $this->service->assessControl('NONEXISTENT.99');

    $this->assertFalse($result['found']);
    $this->assertEquals('gap', $result['status']);
    $this->assertEquals('NONEXISTENT.99', $result['control_id']);
  }

  /**
   * Tests getComplianceGaps returns only non-satisfied controls.
   *
   * @covers ::getComplianceGaps
   */
  public function testGetComplianceGaps(): void {
    $gaps = $this->service->getComplianceGaps();
    $mapping = $this->service->getControlMapping();

    // Count expected gaps.
    $expectedGapCount = 0;
    foreach ($mapping as $control) {
      if ($control['status'] !== 'satisfied') {
        $expectedGapCount++;
      }
    }

    $this->assertCount($expectedGapCount, $gaps);

    // Verify all gaps have required fields.
    foreach ($gaps as $gap) {
      $this->assertArrayHasKey('id', $gap);
      $this->assertArrayHasKey('name', $gap);
      $this->assertArrayHasKey('status', $gap);
      $this->assertArrayHasKey('description', $gap);
      $this->assertArrayHasKey('recommendation', $gap);

      // Should NOT be satisfied.
      $this->assertNotEquals('satisfied', $gap['status']);
      $this->assertContains($gap['status'], ['partial', 'gap']);
    }
  }

  /**
   * Tests getComplianceGaps includes partial controls.
   *
   * @covers ::getComplianceGaps
   */
  public function testGetComplianceGapsIncludesPartialControls(): void {
    $gaps = $this->service->getComplianceGaps();
    $partialGaps = array_filter($gaps, fn($g) => $g['status'] === 'partial');

    // There should be at least some partial controls (CC7.3, A1.2).
    $this->assertNotEmpty($partialGaps, 'Should have at least one partial gap');

    // Verify CC7.3 and A1.2 are in the gaps.
    $gapIds = array_column($gaps, 'id');
    $this->assertContains('CC7.3', $gapIds, 'CC7.3 should be a gap');
    $this->assertContains('A1.2', $gapIds, 'A1.2 should be a gap');
  }

  /**
   * Tests that satisfied controls are not included in gaps.
   *
   * @covers ::getComplianceGaps
   */
  public function testGetComplianceGapsExcludesSatisfied(): void {
    $gaps = $this->service->getComplianceGaps();
    $gapIds = array_column($gaps, 'id');

    // CC6.1 should NOT be in gaps (it's satisfied).
    $this->assertNotContains('CC6.1', $gapIds);
    $this->assertNotContains('CC8.1', $gapIds);
    $this->assertNotContains('P1.1', $gapIds);
  }

}
