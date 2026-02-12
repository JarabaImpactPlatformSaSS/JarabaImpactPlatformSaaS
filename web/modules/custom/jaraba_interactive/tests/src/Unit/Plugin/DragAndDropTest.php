<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\Plugin;

use Drupal\jaraba_interactive\Plugin\InteractiveType\DragAndDrop;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para el plugin DragAndDrop.
 *
 * Verifica esquema, calculo de puntuacion por colocacion de items
 * en zonas correctas, verbos xAPI y renderizado.
 *
 * @coversDefaultClass \Drupal\jaraba_interactive\Plugin\InteractiveType\DragAndDrop
 * @group jaraba_interactive
 */
class DragAndDropTest extends TestCase {

  /**
   * El plugin bajo prueba.
   *
   * @var \Drupal\jaraba_interactive\Plugin\InteractiveType\DragAndDrop&\PHPUnit\Framework\MockObject\MockObject
   */
  private DragAndDrop&MockObject $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = $this->getMockBuilder(DragAndDrop::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();
  }

  // ==========================================================================
  // Helper para construir datos de drag-and-drop.
  // ==========================================================================

  /**
   * Construye un item arrastrable con zonas correctas.
   *
   * @param string $id
   *   ID del item.
   * @param array $correctZones
   *   Lista de IDs de zonas correctas.
   * @param string $text
   *   Texto del item.
   *
   * @return array
   *   Definicion del item arrastrable.
   */
  private function buildDraggable(string $id, array $correctZones, string $text = ''): array {
    return [
      'id' => $id,
      'text' => $text ?: "Item $id",
      'correct_zones' => $correctZones,
      'feedback_correct' => 'Correcto!',
      'feedback_incorrect' => 'Incorrecto.',
    ];
  }

  /**
   * Construye una zona de destino.
   *
   * @param string $id
   *   ID de la zona.
   * @param string $label
   *   Label de la zona.
   *
   * @return array
   *   Definicion de la zona.
   */
  private function buildDropZone(string $id, string $label = ''): array {
    return [
      'id' => $id,
      'label' => $label ?: "Zona $id",
      'position' => ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 100],
    ];
  }

  /**
   * Construye datos completos de ejercicio drag-and-drop.
   *
   * @param array $draggables
   *   Lista de items arrastrables.
   * @param array $dropZones
   *   Lista de zonas de destino.
   * @param array $settings
   *   Configuracion del ejercicio.
   *
   * @return array
   *   Datos completos del ejercicio.
   */
  private function buildExerciseData(array $draggables = [], array $dropZones = [], array $settings = []): array {
    return [
      'drop_zones' => $dropZones,
      'draggables' => $draggables,
      'settings' => $settings,
    ];
  }

  // ==========================================================================
  // SCHEMA TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSchemaReturnsArrayWithRequiredKeys(): void {
    $schema = $this->plugin->getSchema();

    $this->assertIsArray($schema);
    $this->assertArrayHasKey('drop_zones', $schema);
    $this->assertArrayHasKey('draggables', $schema);
    $this->assertArrayHasKey('settings', $schema);

    // Verificar que drop_zones y draggables son requeridos.
    $this->assertTrue($schema['drop_zones']['required']);
    $this->assertTrue($schema['draggables']['required']);
  }

  // ==========================================================================
  // CALCULATE SCORE TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreAllCorrectPlacements(): void {
    $draggables = [
      $this->buildDraggable('item_1', ['zone_a']),
      $this->buildDraggable('item_2', ['zone_b']),
      $this->buildDraggable('item_3', ['zone_c']),
    ];

    $dropZones = [
      $this->buildDropZone('zone_a'),
      $this->buildDropZone('zone_b'),
      $this->buildDropZone('zone_c'),
    ];

    $data = $this->buildExerciseData($draggables, $dropZones, ['passing_score' => 70]);

    // Todas las colocaciones correctas.
    $responses = [
      'item_1' => 'zone_a',
      'item_2' => 'zone_b',
      'item_3' => 'zone_c',
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(100.0, $result['score']);
    $this->assertSame(100, $result['max_score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(3, $result['raw_score']);
    $this->assertSame(3, $result['raw_max']);

    // Verificar detalles de cada item.
    $this->assertTrue($result['details']['item_1']['correct']);
    $this->assertTrue($result['details']['item_2']['correct']);
    $this->assertTrue($result['details']['item_3']['correct']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreAllIncorrectPlacements(): void {
    $draggables = [
      $this->buildDraggable('item_1', ['zone_a']),
      $this->buildDraggable('item_2', ['zone_b']),
    ];

    $dropZones = [
      $this->buildDropZone('zone_a'),
      $this->buildDropZone('zone_b'),
    ];

    $data = $this->buildExerciseData($draggables, $dropZones, ['passing_score' => 70]);

    // Colocaciones invertidas (ambas incorrectas).
    $responses = [
      'item_1' => 'zone_b',
      'item_2' => 'zone_a',
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(0.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(0, $result['raw_score']);
    $this->assertSame(2, $result['raw_max']);

    $this->assertFalse($result['details']['item_1']['correct']);
    $this->assertFalse($result['details']['item_2']['correct']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePartialPlacement(): void {
    $draggables = [
      $this->buildDraggable('item_1', ['zone_a']),
      $this->buildDraggable('item_2', ['zone_b']),
      $this->buildDraggable('item_3', ['zone_c']),
      $this->buildDraggable('item_4', ['zone_a']),
    ];

    $dropZones = [
      $this->buildDropZone('zone_a'),
      $this->buildDropZone('zone_b'),
      $this->buildDropZone('zone_c'),
    ];

    $data = $this->buildExerciseData($draggables, $dropZones, ['passing_score' => 70]);

    // 2 de 4 correctos = 50%.
    $responses = [
      'item_1' => 'zone_a',
      'item_2' => 'zone_c',
      'item_3' => 'zone_c',
      'item_4' => 'zone_b',
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(50.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(2, $result['raw_score']);
    $this->assertSame(4, $result['raw_max']);

    $this->assertTrue($result['details']['item_1']['correct']);
    $this->assertFalse($result['details']['item_2']['correct']);
    $this->assertTrue($result['details']['item_3']['correct']);
    $this->assertFalse($result['details']['item_4']['correct']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreWithUnplacedItemsNullResponses(): void {
    $draggables = [
      $this->buildDraggable('item_1', ['zone_a']),
      $this->buildDraggable('item_2', ['zone_b']),
      $this->buildDraggable('item_3', ['zone_c']),
    ];

    $dropZones = [
      $this->buildDropZone('zone_a'),
      $this->buildDropZone('zone_b'),
      $this->buildDropZone('zone_c'),
    ];

    $data = $this->buildExerciseData($draggables, $dropZones, ['passing_score' => 70]);

    // Solo se coloco item_1; los demas no tienen respuesta (null).
    $responses = [
      'item_1' => 'zone_a',
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    // 1 de 3 correcto = 33.33%.
    $this->assertSame(33.33, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(1, $result['raw_score']);

    // Items no colocados se marcan como incorrectos.
    $this->assertTrue($result['details']['item_1']['correct']);
    $this->assertFalse($result['details']['item_2']['correct']);
    $this->assertNull($result['details']['item_2']['user_zone']);
    $this->assertFalse($result['details']['item_3']['correct']);
    $this->assertNull($result['details']['item_3']['user_zone']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreWithItemHavingMultipleCorrectZones(): void {
    $draggables = [
      $this->buildDraggable('item_1', ['zone_a', 'zone_b']),
      $this->buildDraggable('item_2', ['zone_c']),
    ];

    $dropZones = [
      $this->buildDropZone('zone_a'),
      $this->buildDropZone('zone_b'),
      $this->buildDropZone('zone_c'),
    ];

    $data = $this->buildExerciseData($draggables, $dropZones, ['passing_score' => 70]);

    // item_1 acepta zone_a o zone_b; colocamos en zone_b.
    $responses = [
      'item_1' => 'zone_b',
      'item_2' => 'zone_c',
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(100.0, $result['score']);
    $this->assertTrue($result['passed']);
    $this->assertTrue($result['details']['item_1']['correct']);
    $this->assertSame('zone_b', $result['details']['item_1']['user_zone']);
    $this->assertSame(['zone_a', 'zone_b'], $result['details']['item_1']['correct_zones']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreEmptyDraggablesReturnsZero(): void {
    $data = $this->buildExerciseData([], [
      $this->buildDropZone('zone_a'),
    ], ['passing_score' => 70]);

    $responses = [];

    $result = $this->plugin->calculateScore($data, $responses);

    // Sin items arrastrables: 0 total, calculatePercentage devuelve 0.
    $this->assertSame(0.0, $result['score']);
    $this->assertSame(0, $result['raw_score']);
    $this->assertSame(0, $result['raw_max']);
    $this->assertEmpty($result['details']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePassingThresholdFromSettings(): void {
    $draggables = [
      $this->buildDraggable('item_1', ['zone_a']),
      $this->buildDraggable('item_2', ['zone_b']),
      $this->buildDraggable('item_3', ['zone_c']),
    ];

    $dropZones = [
      $this->buildDropZone('zone_a'),
      $this->buildDropZone('zone_b'),
      $this->buildDropZone('zone_c'),
    ];

    // passing_score configurado a 50 en vez del default 70.
    $data = $this->buildExerciseData($draggables, $dropZones, ['passing_score' => 50]);

    // 2 de 3 correctos = 66.67%.
    $responses = [
      'item_1' => 'zone_a',
      'item_2' => 'zone_b',
      'item_3' => 'zone_a',
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(66.67, $result['score']);
    // 66.67 >= 50 => aprueba.
    $this->assertTrue($result['passed']);
  }

  // ==========================================================================
  // XAPI VERBS TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetXapiVerbsReturnsFiveVerbsIncludingInteracted(): void {
    $verbs = $this->plugin->getXapiVerbs();

    $this->assertIsArray($verbs);
    $this->assertCount(5, $verbs);
    $this->assertContains('interacted', $verbs);
    $this->assertContains('attempted', $verbs);
    $this->assertContains('completed', $verbs);
    $this->assertContains('passed', $verbs);
    $this->assertContains('failed', $verbs);
  }

  // ==========================================================================
  // RENDER TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testRenderReturnsThemeDragAndDrop(): void {
    $data = [
      'drop_zones' => [
        ['id' => 'zone_a', 'label' => 'Zona A'],
      ],
      'draggables' => [
        ['id' => 'item_1', 'text' => 'Item 1', 'correct_zones' => ['zone_a']],
      ],
      'background_image' => 'https://example.com/bg.png',
      'settings' => ['snap_to_zone' => TRUE],
    ];

    $result = $this->plugin->render($data);

    $this->assertIsArray($result);
    $this->assertSame('interactive_drag_and_drop', $result['#theme']);
    $this->assertSame($data['drop_zones'], $result['#drop_zones']);
    $this->assertSame($data['draggables'], $result['#draggables']);
    $this->assertSame('https://example.com/bg.png', $result['#background_image']);
    $this->assertArrayHasKey('#settings', $result);
  }

}
