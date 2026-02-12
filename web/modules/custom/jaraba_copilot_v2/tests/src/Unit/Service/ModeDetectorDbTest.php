<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_copilot_v2\Unit\Service;

use Drupal\jaraba_copilot_v2\Service\ModeDetectorService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ModeDetectorService with database triggers fallback.
 *
 * Verifica carga de triggers desde BD (fallback al const),
 * deteccion de modo y estructura de respuesta.
 *
 * @covers \Drupal\jaraba_copilot_v2\Service\ModeDetectorService
 * @group jaraba_copilot_v2
 */
class ModeDetectorDbTest extends TestCase {

  /**
   * Tests that ModeDetectorService class exists.
   */
  public function testServiceClassExists(): void {
    $this->assertTrue(
      class_exists(ModeDetectorService::class),
      'ModeDetectorService class should exist'
    );
  }

  /**
   * Tests that MODE_TRIGGERS constant is still present as fallback.
   */
  public function testModeTriggersConstantExistsAsFallback(): void {
    $triggers = ModeDetectorService::MODE_TRIGGERS;

    $this->assertIsArray($triggers);
    $this->assertNotEmpty($triggers);

    $expectedModes = ['coach', 'consultor', 'sparring', 'cfo', 'fiscal', 'laboral', 'devil'];
    foreach ($expectedModes as $mode) {
      $this->assertArrayHasKey($mode, $triggers,
        "MODE_TRIGGERS should contain mode: {$mode}");
    }
  }

  /**
   * Tests fallback to const when no database connection.
   */
  public function testFallbackToConstWithoutDatabase(): void {
    $service = new ModeDetectorService(NULL, NULL);

    $triggers = $service->loadTriggersFromDb();

    $this->assertIsArray($triggers);
    $this->assertNotEmpty($triggers);
    $this->assertArrayHasKey('coach', $triggers);
    $this->assertArrayHasKey('consultor', $triggers);
  }

  /**
   * Tests that detectMode returns expected structure.
   */
  public function testDetectModeReturnsExpectedStructure(): void {
    $service = new ModeDetectorService(NULL, NULL);

    $result = $service->detectMode('tengo miedo de fracasar con mi negocio');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('mode', $result);
    $this->assertArrayHasKey('score', $result);
    $this->assertArrayHasKey('confidence', $result);
    $this->assertArrayHasKey('emotion_score', $result);
    $this->assertArrayHasKey('all_scores', $result);
  }

  /**
   * Tests that coach mode is detected for emotional messages.
   */
  public function testCoachModeDetectedForEmotionalMessage(): void {
    $service = new ModeDetectorService(NULL, NULL);

    $result = $service->detectMode('no puedo más, estoy bloqueado, tengo miedo de fracasar, me siento impostor');

    $this->assertEquals('coach', $result['mode'],
      'Should detect coach mode for emotional message');
    $this->assertGreaterThan(0, $result['score']);
  }

  /**
   * Tests that fiscal mode is detected for tax-related messages.
   */
  public function testFiscalModeDetectedForTaxMessage(): void {
    $service = new ModeDetectorService(NULL, NULL);

    $result = $service->detectMode('como presento el modelo 303 de hacienda para el IVA');

    $this->assertEquals('fiscal', $result['mode'],
      'Should detect fiscal mode for tax-related message');
  }

  /**
   * Tests that consultor is default mode for low-signal messages.
   */
  public function testConsultorIsDefaultForLowSignal(): void {
    $service = new ModeDetectorService(NULL, NULL);

    $result = $service->detectMode('hola buenas tardes');

    $this->assertEquals('consultor', $result['mode'],
      'Should default to consultor for low-signal messages');
    $this->assertEquals('low', $result['confidence']);
  }

  /**
   * Tests that getAvailableModes returns all modes.
   */
  public function testGetAvailableModesReturnsModes(): void {
    $service = new ModeDetectorService(NULL, NULL);

    $modes = $service->getAvailableModes();

    $this->assertIsArray($modes);
    $this->assertNotEmpty($modes);
    $this->assertContains('coach', $modes);
    $this->assertContains('consultor', $modes);
    $this->assertContains('fiscal', $modes);
  }

  /**
   * Tests that getTriggersForMode returns triggers for valid mode.
   */
  public function testGetTriggersForValidMode(): void {
    $service = new ModeDetectorService(NULL, NULL);

    $triggers = $service->getTriggersForMode('coach');

    $this->assertIsArray($triggers);
    $this->assertNotEmpty($triggers);
    $this->assertArrayHasKey('word', $triggers[0]);
    $this->assertArrayHasKey('weight', $triggers[0]);
  }

  /**
   * Tests that getTriggersForMode returns empty for invalid mode.
   */
  public function testGetTriggersForInvalidMode(): void {
    $service = new ModeDetectorService(NULL, NULL);

    $triggers = $service->getTriggersForMode('nonexistent_mode');

    $this->assertIsArray($triggers);
    $this->assertEmpty($triggers);
  }

  /**
   * Tests that carril modifiers are still present.
   */
  public function testCarrilModifiersExist(): void {
    $modifiers = ModeDetectorService::CARRIL_MODIFIERS;

    $this->assertIsArray($modifiers);
    $this->assertArrayHasKey('IMPULSO', $modifiers);
    $this->assertArrayHasKey('LANZADERA', $modifiers);
    $this->assertArrayHasKey('ACELERA', $modifiers);
  }

  /**
   * Tests that detectMode applies carril context modifiers.
   */
  public function testDetectModeWithCarrilContext(): void {
    $service = new ModeDetectorService(NULL, NULL);

    $result = $service->detectMode('me siento inseguro con mi idea', ['carril' => 'IMPULSO']);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('mode', $result);
  }

  /**
   * Tests that constructor accepts nullable database and cache.
   */
  public function testConstructorAcceptsNullDependencies(): void {
    $service = new ModeDetectorService(NULL, NULL);
    $this->assertInstanceOf(ModeDetectorService::class, $service);

    $service2 = new ModeDetectorService();
    $this->assertInstanceOf(ModeDetectorService::class, $service2);
  }

  /**
   * Tests trigger word count matches expected total (~157+).
   */
  public function testTriggerCountMatchesExpected(): void {
    $triggers = ModeDetectorService::MODE_TRIGGERS;

    $totalTriggers = 0;
    foreach ($triggers as $modeTriggers) {
      $totalTriggers += count($modeTriggers);
    }

    $this->assertGreaterThanOrEqual(100, $totalTriggers,
      'Should have at least 100 triggers defined');
  }

}
