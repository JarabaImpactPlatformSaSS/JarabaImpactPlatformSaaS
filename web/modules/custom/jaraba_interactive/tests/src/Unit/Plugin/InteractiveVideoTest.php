<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\Plugin;

use Drupal\jaraba_interactive\Plugin\InteractiveType\InteractiveVideo;
use PHPUnit\Framework\TestCase;

/**
 * Tests para el plugin InteractiveVideo.
 *
 * Verifica getSchema, calculateScore, getXapiVerbs y render
 * para video interactivo con checkpoints y quizzes.
 *
 * @coversDefaultClass \Drupal\jaraba_interactive\Plugin\InteractiveType\InteractiveVideo
 * @group jaraba_interactive
 */
class InteractiveVideoTest extends TestCase {

  /**
   * El plugin bajo prueba.
   */
  private InteractiveVideo $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = $this->getMockBuilder(InteractiveVideo::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();
  }

  // =========================================================================
  // GET SCHEMA TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSchemaReturnsArray(): void {
    $schema = $this->plugin->getSchema();

    $this->assertIsArray($schema);
    $this->assertArrayHasKey('video_url', $schema);
    $this->assertArrayHasKey('chapters', $schema);
    $this->assertArrayHasKey('checkpoints', $schema);
    $this->assertArrayHasKey('settings', $schema);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaVideoUrlRequired(): void {
    $schema = $this->plugin->getSchema();

    $this->assertTrue($schema['video_url']['required']);
    $this->assertSame('string', $schema['video_url']['type']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaCheckpointsRequired(): void {
    $schema = $this->plugin->getSchema();

    $this->assertTrue($schema['checkpoints']['required']);
    $this->assertSame('array', $schema['checkpoints']['type']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaCheckpointTypeEnum(): void {
    $schema = $this->plugin->getSchema();
    $typeEnum = $schema['checkpoints']['items']['type']['enum'];

    $this->assertContains('quiz', $typeEnum);
    $this->assertContains('overlay', $typeEnum);
    $this->assertContains('decision', $typeEnum);
    $this->assertCount(3, $typeEnum);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaSettingsDefaults(): void {
    $schema = $this->plugin->getSchema();
    $settings = $schema['settings']['properties'];

    $this->assertSame(70, $settings['passing_score']['default']);
    $this->assertFalse($settings['allow_skip_checkpoints']['default']);
    $this->assertTrue($settings['allow_rewind']['default']);
    $this->assertFalse($settings['autoplay']['default']);
    $this->assertTrue($settings['show_chapters']['default']);
  }

  // =========================================================================
  // CALCULATE SCORE TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePerfectQuizCheckpoints(): void {
    $data = [
      'checkpoints' => [
        [
          'id' => 'cp1',
          'timestamp' => 30.0,
          'type' => 'quiz',
          'title' => 'Quiz en segundo 30',
          'points' => 2,
          'content' => [
            'question' => 'Que vimos?',
            'options' => [
              ['id' => 'a', 'text' => 'Opcion A', 'correct' => TRUE],
              ['id' => 'b', 'text' => 'Opcion B', 'correct' => FALSE],
            ],
          ],
        ],
        [
          'id' => 'cp2',
          'timestamp' => 60.0,
          'type' => 'quiz',
          'title' => 'Quiz en segundo 60',
          'points' => 1,
          'content' => [
            'question' => 'Cual es correcto?',
            'options' => [
              ['id' => 'x', 'text' => 'Opcion X', 'correct' => FALSE],
              ['id' => 'y', 'text' => 'Opcion Y', 'correct' => TRUE],
            ],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    $responses = ['cp1' => 'a', 'cp2' => 'y'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(100.0, $result['score']);
    $this->assertSame(100, $result['max_score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(3, $result['raw_score']);
    $this->assertSame(3, $result['raw_max']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreIgnoresOverlayAndDecision(): void {
    $data = [
      'checkpoints' => [
        [
          'id' => 'cp1',
          'timestamp' => 10.0,
          'type' => 'overlay',
          'title' => 'Informacion adicional',
          'content' => ['text' => 'Dato interesante'],
        ],
        [
          'id' => 'cp2',
          'timestamp' => 20.0,
          'type' => 'decision',
          'title' => 'Elige tu camino',
          'content' => [
            'options' => [
              ['id' => 'path_a', 'text' => 'Camino A'],
              ['id' => 'path_b', 'text' => 'Camino B'],
            ],
          ],
        ],
        [
          'id' => 'cp3',
          'timestamp' => 30.0,
          'type' => 'quiz',
          'title' => 'Quiz real',
          'points' => 1,
          'content' => [
            'question' => 'Pregunta evaluable',
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
              ['id' => 'b', 'text' => 'Incorrecta', 'correct' => FALSE],
            ],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    // Solo se evalua el quiz (cp3), overlay y decision se ignoran.
    $responses = ['cp1' => 'anything', 'cp2' => 'path_a', 'cp3' => 'a'];

    $result = $this->plugin->calculateScore($data, $responses);

    // Solo el quiz (1 punto) se contabiliza.
    $this->assertSame(100.0, $result['score']);
    $this->assertSame(1, $result['raw_score']);
    $this->assertSame(1, $result['raw_max']);
    $this->assertTrue($result['passed']);

    // Solo el quiz aparece en details.
    $this->assertArrayHasKey('cp3', $result['details']);
    $this->assertArrayNotHasKey('cp1', $result['details']);
    $this->assertArrayNotHasKey('cp2', $result['details']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreNoQuizCheckpoints(): void {
    $data = [
      'checkpoints' => [
        [
          'id' => 'cp1',
          'timestamp' => 10.0,
          'type' => 'overlay',
          'content' => ['text' => 'Solo informacion'],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];

    $result = $this->plugin->calculateScore($data, []);

    // Sin quizzes: 0 puntos posibles, porcentaje 0.
    $this->assertSame(0.0, $result['score']);
    $this->assertSame(0, $result['raw_max']);
    $this->assertSame(0, $result['raw_score']);
    $this->assertEmpty($result['details']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePartialQuizResponses(): void {
    $data = [
      'checkpoints' => [
        [
          'id' => 'cp1',
          'timestamp' => 15.0,
          'type' => 'quiz',
          'points' => 1,
          'content' => [
            'question' => 'Pregunta 1',
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
              ['id' => 'b', 'text' => 'Incorrecta', 'correct' => FALSE],
            ],
          ],
        ],
        [
          'id' => 'cp2',
          'timestamp' => 45.0,
          'type' => 'quiz',
          'points' => 1,
          'content' => [
            'question' => 'Pregunta 2',
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
              ['id' => 'b', 'text' => 'Incorrecta', 'correct' => FALSE],
            ],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    // Solo responde correctamente la primera.
    $responses = ['cp1' => 'a', 'cp2' => 'b'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(50.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(1, $result['raw_score']);
    $this->assertSame(2, $result['raw_max']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreDetailsIncludeTimestamp(): void {
    $data = [
      'checkpoints' => [
        [
          'id' => 'cp1',
          'timestamp' => 42.5,
          'type' => 'quiz',
          'points' => 1,
          'content' => [
            'question' => 'Pregunta',
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
            ],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    $responses = ['cp1' => 'a'];

    $result = $this->plugin->calculateScore($data, $responses);

    $detail = $result['details']['cp1'];
    $this->assertArrayHasKey('timestamp', $detail);
    $this->assertSame(42.5, $detail['timestamp']);
    $this->assertTrue($detail['correct']);
    $this->assertSame('a', $detail['correct_answer']);
    $this->assertSame(1, $detail['points_earned']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreEmptyCheckpoints(): void {
    $data = [
      'checkpoints' => [],
      'settings' => ['passing_score' => 70],
    ];

    $result = $this->plugin->calculateScore($data, []);

    $this->assertSame(0.0, $result['score']);
    $this->assertSame(0, $result['raw_max']);
    $this->assertSame(0, $result['raw_score']);
  }

  // =========================================================================
  // XAPI VERBS TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetXapiVerbsReturnsExpected(): void {
    $verbs = $this->plugin->getXapiVerbs();

    $this->assertContains('attempted', $verbs);
    $this->assertContains('interacted', $verbs);
    $this->assertContains('completed', $verbs);
    $this->assertContains('passed', $verbs);
    $this->assertContains('failed', $verbs);
    $this->assertContains('progressed', $verbs);
    $this->assertCount(6, $verbs);
  }

  // =========================================================================
  // RENDER TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testRenderReturnsTheme(): void {
    $data = [
      'video_url' => 'https://example.com/video.mp4',
      'poster_url' => 'https://example.com/poster.jpg',
      'chapters' => [
        ['id' => 'ch1', 'title' => 'Capitulo 1', 'start_time' => 0],
      ],
      'checkpoints' => [
        ['id' => 'cp1', 'timestamp' => 30, 'type' => 'quiz'],
      ],
      'settings' => ['passing_score' => 70],
    ];

    $result = $this->plugin->render($data);

    $this->assertSame('interactive_video', $result['#theme']);
    $this->assertSame('https://example.com/video.mp4', $result['#video_url']);
    $this->assertSame('https://example.com/poster.jpg', $result['#poster_url']);
    $this->assertArrayHasKey('#chapters', $result);
    $this->assertArrayHasKey('#checkpoints', $result);
    $this->assertArrayHasKey('#settings', $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testRenderHandlesMissingOptionalFields(): void {
    $data = [
      'checkpoints' => [],
      'settings' => ['passing_score' => 70],
    ];

    $result = $this->plugin->render($data);

    $this->assertSame('interactive_video', $result['#theme']);
    // Campos opcionales usan valores por defecto.
    $this->assertSame('', $result['#video_url']);
    $this->assertSame('', $result['#poster_url']);
    $this->assertSame([], $result['#chapters']);
  }

}
