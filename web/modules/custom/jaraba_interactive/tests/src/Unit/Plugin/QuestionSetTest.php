<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\Plugin;

use Drupal\jaraba_interactive\Plugin\InteractiveType\QuestionSet;
use PHPUnit\Framework\TestCase;

// Stub de la funcion global t() para tests unitarios puros.
// La funcion real requiere el bootstrap de Drupal.
if (!function_exists('t')) {

  /**
   * Stub de traduccion para tests unitarios.
   *
   * @param string $string
   *   La cadena a traducir.
   * @param array $args
   *   Argumentos de reemplazo.
   * @param array $options
   *   Opciones adicionales.
   *
   * @return string
   *   La cadena sin traducir.
   */
  function t(string $string, array $args = [], array $options = []): string {
    return $string;
  }

}

/**
 * Tests para el plugin QuestionSet.
 *
 * Verifica getSchema, calculateScore, getXapiVerbs y render
 * para cuestionarios de evaluacion.
 *
 * @coversDefaultClass \Drupal\jaraba_interactive\Plugin\InteractiveType\QuestionSet
 * @group jaraba_interactive
 */
class QuestionSetTest extends TestCase {

  /**
   * El plugin bajo prueba.
   */
  private QuestionSet $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = $this->getMockBuilder(QuestionSet::class)
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
    $this->assertArrayHasKey('questions', $schema);
    $this->assertArrayHasKey('settings', $schema);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaQuestionsRequired(): void {
    $schema = $this->plugin->getSchema();

    $this->assertTrue($schema['questions']['required']);
    $this->assertSame('array', $schema['questions']['type']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaSettingsDefaults(): void {
    $schema = $this->plugin->getSchema();
    $settings = $schema['settings']['properties'];

    $this->assertSame(70, $settings['passing_score']['default']);
    $this->assertSame(3, $settings['max_attempts']['default']);
    $this->assertSame('immediate', $settings['show_feedback']['default']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaQuestionTypeEnumValues(): void {
    $schema = $this->plugin->getSchema();
    $typeEnum = $schema['questions']['items']['type']['enum'];

    $this->assertContains('multiple_choice', $typeEnum);
    $this->assertContains('true_false', $typeEnum);
    $this->assertContains('short_answer', $typeEnum);
    $this->assertContains('fill_blanks', $typeEnum);
    $this->assertCount(4, $typeEnum);
  }

  // =========================================================================
  // CALCULATE SCORE TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePerfect(): void {
    $data = [
      'questions' => [
        [
          'id' => 'q1',
          'type' => 'multiple_choice',
          'text' => 'Pregunta 1',
          'points' => 2,
          'options' => [
            ['id' => 'a', 'text' => 'Opcion A', 'correct' => TRUE],
            ['id' => 'b', 'text' => 'Opcion B', 'correct' => FALSE],
          ],
        ],
        [
          'id' => 'q2',
          'type' => 'true_false',
          'text' => 'Pregunta 2',
          'points' => 1,
          'correct_answer' => 'true',
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    $responses = ['q1' => 'a', 'q2' => 'true'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(100.0, $result['score']);
    $this->assertSame(100, $result['max_score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(3, $result['raw_score']);
    $this->assertSame(3, $result['raw_max']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePartial(): void {
    $data = [
      'questions' => [
        [
          'id' => 'q1',
          'type' => 'multiple_choice',
          'text' => 'Pregunta 1',
          'points' => 1,
          'options' => [
            ['id' => 'a', 'text' => 'Opcion A', 'correct' => TRUE],
            ['id' => 'b', 'text' => 'Opcion B', 'correct' => FALSE],
          ],
        ],
        [
          'id' => 'q2',
          'type' => 'multiple_choice',
          'text' => 'Pregunta 2',
          'points' => 1,
          'options' => [
            ['id' => 'a', 'text' => 'Opcion A', 'correct' => TRUE],
            ['id' => 'b', 'text' => 'Opcion B', 'correct' => FALSE],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    $responses = ['q1' => 'a', 'q2' => 'b'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(50.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(1, $result['raw_score']);
    $this->assertSame(2, $result['raw_max']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreZero(): void {
    $data = [
      'questions' => [
        [
          'id' => 'q1',
          'type' => 'multiple_choice',
          'text' => 'Pregunta 1',
          'points' => 1,
          'options' => [
            ['id' => 'a', 'text' => 'Opcion A', 'correct' => TRUE],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    $responses = ['q1' => 'b'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(0.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(0, $result['raw_score']);
    $this->assertSame(1, $result['raw_max']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreNoResponses(): void {
    $data = [
      'questions' => [
        [
          'id' => 'q1',
          'type' => 'multiple_choice',
          'text' => 'Pregunta 1',
          'points' => 1,
          'options' => [
            ['id' => 'a', 'text' => 'Opcion A', 'correct' => TRUE],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];

    $result = $this->plugin->calculateScore($data, []);

    $this->assertSame(0.0, $result['score']);
    $this->assertFalse($result['passed']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreEmptyQuestions(): void {
    $data = ['questions' => [], 'settings' => ['passing_score' => 70]];

    $result = $this->plugin->calculateScore($data, []);

    $this->assertSame(0.0, $result['score']);
    $this->assertSame(0, $result['raw_max']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreShortAnswerCaseInsensitive(): void {
    $data = [
      'questions' => [
        [
          'id' => 'q1',
          'type' => 'short_answer',
          'text' => 'Capital de Espana?',
          'points' => 1,
          'correct_answer' => 'Madrid',
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    // Respuesta en minusculas debe ser aceptada.
    $responses = ['q1' => 'madrid'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(100.0, $result['score']);
    $this->assertTrue($result['passed']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreDetailsStructure(): void {
    $data = [
      'questions' => [
        [
          'id' => 'q1',
          'type' => 'multiple_choice',
          'text' => 'Pregunta 1',
          'points' => 1,
          'options' => [
            ['id' => 'a', 'text' => 'Opcion A', 'correct' => TRUE],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    $responses = ['q1' => 'a'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertArrayHasKey('details', $result);
    $this->assertArrayHasKey('q1', $result['details']);

    $detail = $result['details']['q1'];
    $this->assertArrayHasKey('correct', $detail);
    $this->assertArrayHasKey('user_answer', $detail);
    $this->assertArrayHasKey('correct_answer', $detail);
    $this->assertArrayHasKey('points_earned', $detail);
    $this->assertArrayHasKey('feedback', $detail);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreTrueFalseIncorrect(): void {
    $data = [
      'questions' => [
        [
          'id' => 'q1',
          'type' => 'true_false',
          'text' => 'La tierra es plana',
          'points' => 1,
          'correct_answer' => 'false',
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    // Respuesta incorrecta.
    $responses = ['q1' => 'true'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(0.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertFalse($result['details']['q1']['correct']);
    $this->assertSame(0, $result['details']['q1']['points_earned']);
  }

  // =========================================================================
  // XAPI VERBS TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetXapiVerbsReturnsExpected(): void {
    $verbs = $this->plugin->getXapiVerbs();

    $this->assertContains('attempted', $verbs);
    $this->assertContains('answered', $verbs);
    $this->assertContains('completed', $verbs);
    $this->assertContains('passed', $verbs);
    $this->assertContains('failed', $verbs);
    $this->assertCount(5, $verbs);
  }

  // =========================================================================
  // RENDER TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testRenderReturnsTheme(): void {
    $data = [
      'questions' => [['id' => 'q1', 'text' => 'Pregunta de prueba']],
      'settings' => ['passing_score' => 70],
    ];

    $result = $this->plugin->render($data);

    $this->assertSame('interactive_question_set', $result['#theme']);
    $this->assertArrayHasKey('#questions', $result);
    $this->assertArrayHasKey('#settings', $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testRenderMergesSettings(): void {
    $data = [
      'questions' => [['id' => 'q1', 'text' => 'Pregunta']],
      'settings' => ['passing_score' => 80],
    ];
    $extraSettings = ['custom_option' => TRUE];

    $result = $this->plugin->render($data, $extraSettings);

    // Los settings del data sobreescriben los settings extra (array_merge).
    $this->assertSame(80, $result['#settings']['passing_score']);
    $this->assertTrue($result['#settings']['custom_option']);
  }

}
