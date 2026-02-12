<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\Service;

use Drupal\jaraba_interactive\Plugin\InteractiveTypeInterface;
use Drupal\jaraba_interactive\Plugin\InteractiveTypeManager;
use Drupal\jaraba_interactive\Service\Scorer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para el servicio Scorer.
 *
 * Verifica el calculo de puntuaciones mediante plugins, el manejo
 * de excepciones con logging, y la logica de aprobacion por umbral.
 *
 * @coversDefaultClass \Drupal\jaraba_interactive\Service\Scorer
 * @group jaraba_interactive
 */
class ScorerTest extends TestCase {

  /**
   * El servicio bajo prueba.
   */
  private Scorer $service;

  /**
   * Mock del plugin manager de tipos interactivos.
   */
  private InteractiveTypeManager&MockObject $typeManager;

  /**
   * Mock del logger.
   */
  private LoggerInterface&MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->typeManager = $this->createMock(InteractiveTypeManager::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new Scorer(
      $this->typeManager,
      $this->logger,
    );
  }

  // =========================================================================
  // CALCULATE TESTS
  // =========================================================================

  /**
   * Verifica que calculate delega correctamente al plugin y devuelve su resultado.
   *
   * @covers ::calculate
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateSuccess(): void {
    $expectedResult = [
      'score' => 85,
      'max_score' => 100,
      'passed' => TRUE,
      'details' => ['correct' => 17, 'total' => 20],
    ];

    $plugin = $this->createMock(InteractiveTypeInterface::class);
    $plugin->expects($this->once())
      ->method('calculateScore')
      ->with(
        ['questions' => [1, 2, 3]],
        ['answers' => ['a', 'b', 'c']],
      )
      ->willReturn($expectedResult);

    $this->typeManager->expects($this->once())
      ->method('createInstance')
      ->with('question_set')
      ->willReturn($plugin);

    $result = $this->service->calculate(
      'question_set',
      ['questions' => [1, 2, 3]],
      ['answers' => ['a', 'b', 'c']],
    );

    $this->assertSame($expectedResult, $result);
  }

  /**
   * Verifica que calculate devuelve array de error cuando createInstance lanza excepcion.
   *
   * @covers ::calculate
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateExceptionReturnsErrorArray(): void {
    $this->typeManager->method('createInstance')
      ->with('invalid_type')
      ->willThrowException(new \RuntimeException('Plugin no encontrado'));

    $result = $this->service->calculate('invalid_type', [], []);

    $this->assertIsArray($result);
    $this->assertSame(0, $result['score']);
    $this->assertSame(100, $result['max_score']);
    $this->assertFalse($result['passed']);
    $this->assertSame('Plugin no encontrado', $result['error']);
  }

  /**
   * Verifica que calculate registra el error en el logger cuando hay excepcion.
   *
   * @covers ::calculate
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateExceptionLogsError(): void {
    $this->typeManager->method('createInstance')
      ->willThrowException(new \RuntimeException('Fallo de instanciacion'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Error calculando puntuación: @message',
        ['@message' => 'Fallo de instanciacion'],
      );

    $this->service->calculate('broken_plugin', [], []);
  }

  /**
   * Verifica que calculate propaga correctamente los datos al plugin.
   *
   * @covers ::calculate
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculatePassesContentDataAndResponsesToPlugin(): void {
    $contentData = ['video_markers' => [10, 30, 60], 'passing_score' => 80];
    $responses = ['marker_10' => 'correct', 'marker_30' => 'wrong'];

    $plugin = $this->createMock(InteractiveTypeInterface::class);
    $plugin->expects($this->once())
      ->method('calculateScore')
      ->with($contentData, $responses)
      ->willReturn(['score' => 50, 'max_score' => 100, 'passed' => FALSE]);

    $this->typeManager->method('createInstance')
      ->with('interactive_video')
      ->willReturn($plugin);

    $result = $this->service->calculate('interactive_video', $contentData, $responses);

    $this->assertSame(50, $result['score']);
    $this->assertFalse($result['passed']);
  }

  // =========================================================================
  // HAS PASSED TESTS
  // =========================================================================

  /**
   * Verifica que hasPassed devuelve TRUE cuando la puntuacion supera el umbral.
   *
   * @covers ::hasPassed
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testHasPassedAboveThreshold(): void {
    $this->assertTrue($this->service->hasPassed(80.0, 70.0));
  }

  /**
   * Verifica que hasPassed devuelve FALSE cuando la puntuacion esta debajo del umbral.
   *
   * @covers ::hasPassed
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testHasPassedBelowThreshold(): void {
    $this->assertFalse($this->service->hasPassed(50.0, 70.0));
  }

  /**
   * Verifica que hasPassed devuelve TRUE cuando la puntuacion es exactamente el umbral.
   *
   * @covers ::hasPassed
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testHasPassedExactThreshold(): void {
    $this->assertTrue($this->service->hasPassed(70.0, 70.0));
  }

  /**
   * Verifica que hasPassed respeta un umbral personalizado.
   *
   * @covers ::hasPassed
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testHasPassedCustomThreshold(): void {
    $this->assertFalse($this->service->hasPassed(80.0, 90.0));
  }

  /**
   * Verifica que hasPassed devuelve FALSE con puntuacion cero.
   *
   * @covers ::hasPassed
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testHasPassedZeroScore(): void {
    $this->assertFalse($this->service->hasPassed(0.0, 70.0));
  }

}
