<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\Plugin;

use Drupal\jaraba_interactive\Plugin\InteractiveType\CoursePresentation;
use PHPUnit\Framework\TestCase;

/**
 * Tests para el plugin CoursePresentation.
 *
 * Verifica getSchema, calculateScore, getXapiVerbs y render
 * para presentaciones interactivas con slides y quizzes embebidos.
 *
 * @coversDefaultClass \Drupal\jaraba_interactive\Plugin\InteractiveType\CoursePresentation
 * @group jaraba_interactive
 */
class CoursePresentationTest extends TestCase {

  /**
   * El plugin bajo prueba.
   */
  private CoursePresentation $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = $this->getMockBuilder(CoursePresentation::class)
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
    $this->assertArrayHasKey('slides', $schema);
    $this->assertArrayHasKey('settings', $schema);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaSlidesRequired(): void {
    $schema = $this->plugin->getSchema();

    $this->assertTrue($schema['slides']['required']);
    $this->assertSame('array', $schema['slides']['type']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaSlideLayoutEnum(): void {
    $schema = $this->plugin->getSchema();
    $layoutEnum = $schema['slides']['items']['layout']['enum'];

    $this->assertContains('full', $layoutEnum);
    $this->assertContains('split', $layoutEnum);
    $this->assertContains('title_only', $layoutEnum);
    $this->assertContains('media_left', $layoutEnum);
    $this->assertContains('media_right', $layoutEnum);
    $this->assertContains('quiz', $layoutEnum);
    $this->assertCount(6, $layoutEnum);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSchemaSettingsDefaults(): void {
    $schema = $this->plugin->getSchema();
    $settings = $schema['settings']['properties'];

    $this->assertSame(70, $settings['passing_score']['default']);
    $this->assertSame('free', $settings['navigation']['default']);
    $this->assertTrue($settings['show_progress']['default']);
    $this->assertTrue($settings['show_slide_numbers']['default']);
    $this->assertTrue($settings['enable_keyboard']['default']);
    $this->assertFalse($settings['auto_advance']['default']);
    $this->assertSame(5, $settings['auto_advance_delay']['default']);
  }

  // =========================================================================
  // CALCULATE SCORE TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreNoQuizzesReturns100Passed(): void {
    $data = [
      'slides' => [
        [
          'id' => 's1',
          'title' => 'Introduccion',
          'layout' => 'full',
          'content' => ['text' => 'Bienvenidos al curso'],
        ],
        [
          'id' => 's2',
          'title' => 'Contenido',
          'layout' => 'split',
          'content' => ['text' => 'Informacion importante'],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];

    $result = $this->plugin->calculateScore($data, []);

    // Sin quizzes la presentacion se considera completada al 100%.
    $this->assertSame(100.0, $result['score']);
    $this->assertSame(100, $result['max_score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(0, $result['raw_score']);
    $this->assertSame(0, $result['raw_max']);
    $this->assertEmpty($result['details']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePerfectWithQuizzes(): void {
    $data = [
      'slides' => [
        [
          'id' => 's1',
          'title' => 'Slide con quiz',
          'layout' => 'quiz',
          'quiz' => [
            'question' => 'Cual es la respuesta?',
            'type' => 'multiple_choice',
            'points' => 2,
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
              ['id' => 'b', 'text' => 'Incorrecta', 'correct' => FALSE],
            ],
          ],
        ],
        [
          'id' => 's2',
          'title' => 'Otra slide con quiz',
          'layout' => 'quiz',
          'quiz' => [
            'question' => 'Segunda pregunta',
            'type' => 'multiple_choice',
            'points' => 1,
            'options' => [
              ['id' => 'x', 'text' => 'Correcta', 'correct' => TRUE],
              ['id' => 'y', 'text' => 'Incorrecta', 'correct' => FALSE],
            ],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    $responses = ['s1' => 'a', 's2' => 'x'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(100.0, $result['score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(3, $result['raw_score']);
    $this->assertSame(3, $result['raw_max']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePartialWithQuizzes(): void {
    $data = [
      'slides' => [
        [
          'id' => 's1',
          'title' => 'Slide informativa',
          'layout' => 'full',
          'content' => ['text' => 'Sin quiz aqui'],
        ],
        [
          'id' => 's2',
          'title' => 'Quiz 1',
          'layout' => 'quiz',
          'quiz' => [
            'question' => 'Pregunta 1',
            'type' => 'multiple_choice',
            'points' => 1,
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
              ['id' => 'b', 'text' => 'Incorrecta', 'correct' => FALSE],
            ],
          ],
        ],
        [
          'id' => 's3',
          'title' => 'Quiz 2',
          'layout' => 'quiz',
          'quiz' => [
            'question' => 'Pregunta 2',
            'type' => 'multiple_choice',
            'points' => 1,
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
              ['id' => 'b', 'text' => 'Incorrecta', 'correct' => FALSE],
            ],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    // Acierta solo el primer quiz.
    $responses = ['s2' => 'a', 's3' => 'b'];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(50.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(1, $result['raw_score']);
    $this->assertSame(2, $result['raw_max']);

    // Solo las slides con quiz aparecen en details.
    $this->assertArrayHasKey('s2', $result['details']);
    $this->assertArrayHasKey('s3', $result['details']);
    $this->assertArrayNotHasKey('s1', $result['details']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreOnlySlidesWithQuizCount(): void {
    $data = [
      'slides' => [
        [
          'id' => 's1',
          'title' => 'Informativa 1',
          'layout' => 'full',
          'content' => ['text' => 'Contenido'],
        ],
        [
          'id' => 's2',
          'title' => 'Informativa 2',
          'layout' => 'split',
          'content' => ['text' => 'Mas contenido'],
        ],
        [
          'id' => 's3',
          'title' => 'Unico quiz',
          'layout' => 'quiz',
          'quiz' => [
            'question' => 'Pregunta unica',
            'type' => 'multiple_choice',
            'points' => 1,
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
              ['id' => 'b', 'text' => 'Incorrecta', 'correct' => FALSE],
            ],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    $responses = ['s3' => 'a'];

    $result = $this->plugin->calculateScore($data, $responses);

    // Solo 1 quiz con 1 punto, respondido correctamente.
    $this->assertSame(100.0, $result['score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(1, $result['raw_score']);
    $this->assertSame(1, $result['raw_max']);
    $this->assertCount(1, $result['details']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreDetailsStructure(): void {
    $data = [
      'slides' => [
        [
          'id' => 's1',
          'title' => 'Quiz slide',
          'layout' => 'quiz',
          'quiz' => [
            'question' => 'Pregunta',
            'type' => 'multiple_choice',
            'points' => 1,
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
              ['id' => 'b', 'text' => 'Incorrecta', 'correct' => FALSE],
            ],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    $responses = ['s1' => 'a'];

    $result = $this->plugin->calculateScore($data, $responses);

    $detail = $result['details']['s1'];
    $this->assertArrayHasKey('correct', $detail);
    $this->assertArrayHasKey('user_answer', $detail);
    $this->assertArrayHasKey('correct_answer', $detail);
    $this->assertArrayHasKey('points_earned', $detail);
    $this->assertTrue($detail['correct']);
    $this->assertSame('a', $detail['user_answer']);
    $this->assertSame('a', $detail['correct_answer']);
    $this->assertSame(1, $detail['points_earned']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreNoResponseToQuiz(): void {
    $data = [
      'slides' => [
        [
          'id' => 's1',
          'title' => 'Quiz sin respuesta',
          'layout' => 'quiz',
          'quiz' => [
            'question' => 'Pregunta sin responder',
            'type' => 'multiple_choice',
            'points' => 1,
            'options' => [
              ['id' => 'a', 'text' => 'Correcta', 'correct' => TRUE],
            ],
          ],
        ],
      ],
      'settings' => ['passing_score' => 70],
    ];
    // El usuario no respondio la slide con quiz.
    $responses = [];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(0.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertSame(0, $result['raw_score']);
    $this->assertSame(1, $result['raw_max']);
    $this->assertFalse($result['details']['s1']['correct']);
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
    $this->assertContains('progressed', $verbs);
    $this->assertCount(6, $verbs);
  }

  // =========================================================================
  // RENDER TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testRenderReturnsTheme(): void {
    $data = [
      'slides' => [
        ['id' => 's1', 'title' => 'Slide de prueba', 'layout' => 'full'],
      ],
      'settings' => ['passing_score' => 70, 'navigation' => 'free'],
    ];

    $result = $this->plugin->render($data);

    $this->assertSame('interactive_course_presentation', $result['#theme']);
    $this->assertArrayHasKey('#slides', $result);
    $this->assertArrayHasKey('#settings', $result);
    $this->assertCount(1, $result['#slides']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testRenderMergesSettings(): void {
    $data = [
      'slides' => [
        ['id' => 's1', 'title' => 'Slide'],
      ],
      'settings' => ['passing_score' => 80, 'navigation' => 'sequential'],
    ];
    $extraSettings = ['custom_key' => 'valor_custom'];

    $result = $this->plugin->render($data, $extraSettings);

    // Los settings del data sobreescriben los settings extra (array_merge).
    $this->assertSame(80, $result['#settings']['passing_score']);
    $this->assertSame('sequential', $result['#settings']['navigation']);
    $this->assertSame('valor_custom', $result['#settings']['custom_key']);
  }

}
