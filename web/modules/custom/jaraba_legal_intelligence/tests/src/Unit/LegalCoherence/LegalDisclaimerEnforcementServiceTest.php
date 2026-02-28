<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\LegalCoherence;

use Drupal\jaraba_legal_intelligence\LegalCoherence\LegalDisclaimerEnforcementService;
use Drupal\jaraba_legal_knowledge\Service\LegalDisclaimerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for LegalDisclaimerEnforcementService (LCIS Layer 8).
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\LegalCoherence\LegalDisclaimerEnforcementService
 * @group jaraba_legal_intelligence
 */
class LegalDisclaimerEnforcementServiceTest extends TestCase {

  // ---------------------------------------------------------------
  // With NULL disclaimerService (fallback).
  // ---------------------------------------------------------------

  /**
   * @covers ::enforce
   */
  public function testEnforceAddsFallbackDisclaimer(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());
    $output = 'La Ley 39/2015 regula el procedimiento administrativo.';

    $result = $service->enforce($output);

    $this->assertStringContainsString('no constituye asesoramiento', $result);
    $this->assertStringContainsString($output, $result);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDoesNotDuplicateDisclaimer(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());
    $output = 'Resultado legal. Esta informacion no constituye asesoramiento juridico profesional.';

    $result = $service->enforce($output);

    // Count occurrences — should be exactly 1.
    $count = substr_count(mb_strtolower($result), 'no constituye asesoramiento');
    $this->assertSame(1, $count);
  }

  /**
   * @covers ::enforce
   */
  public function testDetectsMultipleDisclaimerMarkers(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());

    // Each of these should be detected as existing disclaimer.
    $markers = [
      'Esta informacion tiene caracter orientativo y es solo una guia.',
      'Consulte con un abogado para su caso concreto.',
      'Consulte con un profesional cualificado antes de actuar.',
      'Esto no sustituye el criterio profesional de un letrado.',
      'informacion orientativa proporcionada por IA.',
    ];

    foreach ($markers as $marker) {
      $result = $service->enforce($marker);
      // Should not add another disclaimer since one is detected.
      $this->assertStringNotContainsString(
        '---',
        $result,
        "Disclaimer should not be added when marker exists: {$marker}",
      );
    }
  }

  // ---------------------------------------------------------------
  // Coherence score display.
  // ---------------------------------------------------------------

  /**
   * @covers ::enforce
   */
  public function testAppendsLowCoherenceScore(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());
    $coherenceResult = ['score' => 0.55];

    $result = $service->enforce('Texto legal.', $coherenceResult);

    $this->assertStringContainsString('Indice de confianza juridica: 55/100', $result);
  }

  /**
   * @covers ::enforce
   */
  public function testDoesNotAppendHighCoherenceScore(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());
    $coherenceResult = ['score' => 0.85];

    $result = $service->enforce('Texto legal.', $coherenceResult);

    $this->assertStringNotContainsString('Indice de confianza', $result);
  }

  /**
   * @covers ::enforce
   */
  public function testDoesNotAppendScoreWhenEmpty(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());

    $result = $service->enforce('Texto legal.');

    $this->assertStringNotContainsString('Indice de confianza', $result);
  }

  /**
   * @covers ::enforce
   */
  public function testDoesNotAppendScoreWhenNull(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());
    $coherenceResult = ['score' => NULL];

    $result = $service->enforce('Texto legal.', $coherenceResult);

    $this->assertStringNotContainsString('Indice de confianza', $result);
  }

  /**
   * @covers ::enforce
   */
  public function testCoherenceScoreThresholdBoundary(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());

    // Score 69/100 — should show warning.
    $result69 = $service->enforce('Test.', ['score' => 0.69]);
    $this->assertStringContainsString('Indice de confianza juridica: 69/100', $result69);

    // Score 70/100 — should NOT show warning.
    $result70 = $service->enforce('Test.', ['score' => 0.70]);
    $this->assertStringNotContainsString('Indice de confianza', $result70);
  }

  // ---------------------------------------------------------------
  // With mocked LegalDisclaimerService.
  // ---------------------------------------------------------------

  /**
   * @covers ::enforce
   */
  public function testUsesInjectedDisclaimerService(): void {
    $mock = $this->createMock(LegalDisclaimerService::class);
    $mock->method('getDisclaimer')
      ->willReturn('Disclaimer personalizado del tenant.');

    $service = new LegalDisclaimerEnforcementService($mock, new NullLogger());

    $result = $service->enforce('Texto legal.');

    $this->assertStringContainsString('Disclaimer personalizado del tenant.', $result);
  }

  /**
   * @covers ::enforce
   */
  public function testFallsBackOnDisclaimerServiceException(): void {
    $mock = $this->createMock(LegalDisclaimerService::class);
    $mock->method('getDisclaimer')
      ->willThrowException(new \RuntimeException('Service error'));

    $service = new LegalDisclaimerEnforcementService($mock, new NullLogger());

    $result = $service->enforce('Texto legal.');

    // Should use fallback.
    $this->assertStringContainsString('no constituye asesoramiento', $result);
  }

  /**
   * @covers ::enforce
   */
  public function testFallsBackOnEmptyDisclaimerFromService(): void {
    $mock = $this->createMock(LegalDisclaimerService::class);
    $mock->method('getDisclaimer')
      ->willReturn('');

    $service = new LegalDisclaimerEnforcementService($mock, new NullLogger());

    $result = $service->enforce('Texto legal.');

    $this->assertStringContainsString('no constituye asesoramiento', $result);
  }

  // ---------------------------------------------------------------
  // Disclaimer format.
  // ---------------------------------------------------------------

  /**
   * @covers ::enforce
   */
  public function testDisclaimerFormattedWithSeparator(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());

    $result = $service->enforce('Texto legal.');

    // Should have --- separator and italics.
    $this->assertStringContainsString('---', $result);
    $this->assertStringContainsString('*', $result);
  }

  /**
   * @covers ::enforce
   */
  public function testDisclaimerAppearsAtEnd(): void {
    $service = new LegalDisclaimerEnforcementService(NULL, new NullLogger());
    $original = 'Respuesta juridica completa.';

    $result = $service->enforce($original);

    // Original text should come before disclaimer.
    $originalPos = strpos($result, $original);
    $disclaimerPos = strpos($result, 'no constituye asesoramiento');
    $this->assertLessThan($disclaimerPos, $originalPos);
  }

}
