<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\Plugin;

use Drupal\jaraba_interactive\Plugin\InteractiveType\BranchingScenario;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para el plugin BranchingScenario.
 *
 * Verifica esquema, calculo de puntuacion por decisiones de camino,
 * verbos xAPI y renderizado del escenario ramificado.
 *
 * @coversDefaultClass \Drupal\jaraba_interactive\Plugin\InteractiveType\BranchingScenario
 * @group jaraba_interactive
 */
class BranchingScenarioTest extends TestCase {

  /**
   * El plugin bajo prueba.
   *
   * @var \Drupal\jaraba_interactive\Plugin\InteractiveType\BranchingScenario&\PHPUnit\Framework\MockObject\MockObject
   */
  private BranchingScenario&MockObject $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = $this->getMockBuilder(BranchingScenario::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();
  }

  // ==========================================================================
  // Helper para construir datos de escenario.
  // ==========================================================================

  /**
   * Construye un escenario con nodos y opciones configurables.
   *
   * @param array $nodes
   *   Lista de nodos del escenario.
   * @param array $settings
   *   Configuracion del escenario (max_optimal_score, passing_score, etc).
   *
   * @return array
   *   Datos completos del escenario ramificado.
   */
  private function buildScenarioData(array $nodes = [], array $settings = []): array {
    return [
      'start_node' => 'node_1',
      'nodes' => $nodes,
      'settings' => $settings,
    ];
  }

  /**
   * Construye un nodo con opciones.
   *
   * @param string $id
   *   ID del nodo.
   * @param array $options
   *   Opciones del nodo, cada una con id, text, target_node, points, feedback.
   * @param bool $isEnd
   *   Si es nodo terminal.
   *
   * @return array
   *   Definicion del nodo.
   */
  private function buildNode(string $id, array $options = [], bool $isEnd = FALSE): array {
    return [
      'id' => $id,
      'title' => "Nodo $id",
      'content' => ['text' => "Contenido del nodo $id"],
      'options' => $options,
      'is_end' => $isEnd,
    ];
  }

  /**
   * Construye una opcion de decision.
   *
   * @param string $id
   *   ID de la opcion.
   * @param int $points
   *   Puntos de la opcion.
   * @param string $targetNode
   *   Nodo destino.
   * @param string $feedback
   *   Retroalimentacion de la opcion.
   *
   * @return array
   *   Definicion de la opcion.
   */
  private function buildOption(string $id, int $points, string $targetNode = 'node_2', string $feedback = ''): array {
    return [
      'id' => $id,
      'text' => "Opcion $id",
      'target_node' => $targetNode,
      'points' => $points,
      'feedback' => $feedback,
    ];
  }

  // ==========================================================================
  // SCHEMA TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSchemaReturnsArrayWithRequiredKeys(): void {
    $schema = $this->plugin->getSchema();

    $this->assertIsArray($schema);
    $this->assertArrayHasKey('start_node', $schema);
    $this->assertArrayHasKey('nodes', $schema);
    $this->assertArrayHasKey('settings', $schema);

    // Verificar que start_node y nodes son requeridos.
    $this->assertTrue($schema['start_node']['required']);
    $this->assertTrue($schema['nodes']['required']);
  }

  // ==========================================================================
  // CALCULATE SCORE TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreOptimalPath(): void {
    $nodes = [
      $this->buildNode('node_1', [
        $this->buildOption('opt_best_1', 30, 'node_2', 'Excelente decision'),
        $this->buildOption('opt_bad_1', 5, 'node_2'),
      ]),
      $this->buildNode('node_2', [
        $this->buildOption('opt_best_2', 40, 'node_3', 'Muy bien'),
        $this->buildOption('opt_bad_2', 10, 'node_3'),
      ]),
      $this->buildNode('node_3', [
        $this->buildOption('opt_best_3', 30, 'node_end', 'Perfecto'),
        $this->buildOption('opt_bad_3', 0, 'node_end'),
      ]),
      $this->buildNode('node_end', [], TRUE),
    ];

    $data = $this->buildScenarioData($nodes, ['max_optimal_score' => 100, 'passing_score' => 60]);

    // Camino optimo: todas las mejores opciones = 30 + 40 + 30 = 100 puntos.
    $responses = [
      'path' => [
        ['node_id' => 'node_1', 'option_id' => 'opt_best_1'],
        ['node_id' => 'node_2', 'option_id' => 'opt_best_2'],
        ['node_id' => 'node_3', 'option_id' => 'opt_best_3'],
      ],
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(100.0, $result['score']);
    $this->assertSame(100, $result['max_score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(100, $result['raw_score']);
    $this->assertSame(100, $result['raw_max']);
    $this->assertCount(3, $result['details']);
    $this->assertSame(3, $result['path_length']);

    // Verificar detalles del primer nodo.
    $this->assertSame('opt_best_1', $result['details']['node_1']['option_chosen']);
    $this->assertSame(30, $result['details']['node_1']['points_earned']);
    $this->assertSame('Excelente decision', $result['details']['node_1']['feedback']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreSuboptimalPath(): void {
    $nodes = [
      $this->buildNode('node_1', [
        $this->buildOption('opt_best', 30, 'node_2'),
        $this->buildOption('opt_bad', 10, 'node_2'),
      ]),
      $this->buildNode('node_2', [
        $this->buildOption('opt_best', 40, 'node_3'),
        $this->buildOption('opt_bad', 15, 'node_3'),
      ]),
      $this->buildNode('node_3', [
        $this->buildOption('opt_best', 30, 'node_end'),
        $this->buildOption('opt_bad', 5, 'node_end'),
      ]),
      $this->buildNode('node_end', [], TRUE),
    ];

    $data = $this->buildScenarioData($nodes, ['max_optimal_score' => 100, 'passing_score' => 60]);

    // Camino suboptimo: opciones malas = 10 + 15 + 5 = 30 puntos.
    $responses = [
      'path' => [
        ['node_id' => 'node_1', 'option_id' => 'opt_bad'],
        ['node_id' => 'node_2', 'option_id' => 'opt_bad'],
        ['node_id' => 'node_3', 'option_id' => 'opt_bad'],
      ],
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(30.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(30, $result['raw_score']);
    $this->assertSame(3, $result['path_length']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreEmptyPath(): void {
    $nodes = [
      $this->buildNode('node_1', [
        $this->buildOption('opt_1', 30, 'node_end'),
      ]),
      $this->buildNode('node_end', [], TRUE),
    ];

    $data = $this->buildScenarioData($nodes, ['max_optimal_score' => 100]);

    // Camino vacio: sin decisiones tomadas.
    $responses = ['path' => []];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(0.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(0, $result['raw_score']);
    $this->assertSame(0, $result['path_length']);
    $this->assertEmpty($result['details']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreWithNonExistentNodeIsSkipped(): void {
    $nodes = [
      $this->buildNode('node_1', [
        $this->buildOption('opt_1', 50, 'node_end'),
      ]),
      $this->buildNode('node_end', [], TRUE),
    ];

    $data = $this->buildScenarioData($nodes, ['max_optimal_score' => 100]);

    // Camino con un nodo inexistente: debe ignorarse sin error.
    $responses = [
      'path' => [
        ['node_id' => 'node_1', 'option_id' => 'opt_1'],
        ['node_id' => 'node_fantasma', 'option_id' => 'opt_x'],
      ],
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    // Solo el nodo_1 cuenta, el fantasma se ignora.
    $this->assertSame(50.0, $result['score']);
    $this->assertSame(50, $result['raw_score']);
    $this->assertCount(1, $result['details']);
    $this->assertArrayHasKey('node_1', $result['details']);
    $this->assertArrayNotHasKey('node_fantasma', $result['details']);
    // path_length cuenta ambas decisiones aunque una sea invalida.
    $this->assertSame(2, $result['path_length']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreCappedAt100Percent(): void {
    $nodes = [
      $this->buildNode('node_1', [
        $this->buildOption('opt_1', 80, 'node_2'),
      ]),
      $this->buildNode('node_2', [
        $this->buildOption('opt_2', 60, 'node_end'),
      ]),
      $this->buildNode('node_end', [], TRUE),
    ];

    // max_optimal_score = 100, pero se obtienen 140 puntos.
    $data = $this->buildScenarioData($nodes, ['max_optimal_score' => 100]);

    $responses = [
      'path' => [
        ['node_id' => 'node_1', 'option_id' => 'opt_1'],
        ['node_id' => 'node_2', 'option_id' => 'opt_2'],
      ],
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    // El porcentaje no debe superar 100.
    $this->assertSame(100.0, $result['score']);
    // raw_score refleja los puntos reales sin cap.
    $this->assertSame(140, $result['raw_score']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreWithMaxOptimalScoreZeroReturnsZero(): void {
    $nodes = [
      $this->buildNode('node_1', [
        $this->buildOption('opt_1', 50, 'node_end'),
      ]),
      $this->buildNode('node_end', [], TRUE),
    ];

    $data = $this->buildScenarioData($nodes, ['max_optimal_score' => 0]);

    $responses = [
      'path' => [
        ['node_id' => 'node_1', 'option_id' => 'opt_1'],
      ],
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    // Division por cero controlada: porcentaje = 0.
    $this->assertSame(0.0, $result['score']);
    $this->assertSame(0, $result['raw_max']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreResultIncludesPathLength(): void {
    $nodes = [
      $this->buildNode('node_1', [
        $this->buildOption('opt_1', 20, 'node_2'),
      ]),
      $this->buildNode('node_2', [
        $this->buildOption('opt_2', 20, 'node_3'),
      ]),
      $this->buildNode('node_3', [
        $this->buildOption('opt_3', 20, 'node_4'),
      ]),
      $this->buildNode('node_4', [
        $this->buildOption('opt_4', 20, 'node_end'),
      ]),
      $this->buildNode('node_end', [], TRUE),
    ];

    $data = $this->buildScenarioData($nodes, ['max_optimal_score' => 100]);

    $responses = [
      'path' => [
        ['node_id' => 'node_1', 'option_id' => 'opt_1'],
        ['node_id' => 'node_2', 'option_id' => 'opt_2'],
        ['node_id' => 'node_3', 'option_id' => 'opt_3'],
        ['node_id' => 'node_4', 'option_id' => 'opt_4'],
      ],
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertArrayHasKey('path_length', $result);
    $this->assertSame(4, $result['path_length']);
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
  public function testRenderReturnsThemeBranchingScenario(): void {
    $data = [
      'start_node' => 'node_1',
      'nodes' => [
        ['id' => 'node_1', 'title' => 'Inicio', 'options' => []],
      ],
      'settings' => ['allow_restart' => TRUE],
    ];

    $result = $this->plugin->render($data);

    $this->assertIsArray($result);
    $this->assertSame('interactive_branching_scenario', $result['#theme']);
    $this->assertSame('node_1', $result['#start_node']);
    $this->assertSame($data['nodes'], $result['#nodes']);
    $this->assertArrayHasKey('#settings', $result);
  }

}
