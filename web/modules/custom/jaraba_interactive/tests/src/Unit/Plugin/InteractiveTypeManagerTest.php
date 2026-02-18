<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\Plugin;

use Drupal\jaraba_interactive\Plugin\InteractiveTypeManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests unitarios para InteractiveTypeManager.
 *
 * Verifica la obtencion de opciones de tipo, ordenacion por peso,
 * agrupacion por categoria y manejo de definiciones vacias.
 *
 * @coversDefaultClass \Drupal\jaraba_interactive\Plugin\InteractiveTypeManager
 * @group jaraba_interactive
 */
class InteractiveTypeManagerTest extends UnitTestCase {

  /**
   * El plugin manager bajo prueba (mock parcial).
   */
  private InteractiveTypeManager&MockObject $manager;

  /**
   * Definiciones de ejemplo para los plugins.
   *
   * @var array<string, array<string, mixed>>
   */
  private array $sampleDefinitions;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->manager = $this->getMockBuilder(InteractiveTypeManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getDefinitions'])
      ->getMock();

    // Definiciones de ejemplo simulando los 6 plugins reales del modulo.
    $this->sampleDefinitions = [
      'question_set' => [
        'id' => 'question_set',
        'label' => 'Conjunto de Preguntas',
        'category' => 'assessment',
        'weight' => 0,
      ],
      'interactive_video' => [
        'id' => 'interactive_video',
        'label' => 'Video Interactivo',
        'category' => 'media',
        'weight' => 10,
      ],
      'course_presentation' => [
        'id' => 'course_presentation',
        'label' => 'Presentacion de Curso',
        'category' => 'presentation',
        'weight' => 20,
      ],
      'branching_scenario' => [
        'id' => 'branching_scenario',
        'label' => 'Escenario Ramificado',
        'category' => 'scenario',
        'weight' => 30,
      ],
      'drag_and_drop' => [
        'id' => 'drag_and_drop',
        'label' => 'Arrastrar y Soltar',
        'category' => 'assessment',
        'weight' => 40,
      ],
      'essay' => [
        'id' => 'essay',
        'label' => 'Ensayo',
        'category' => 'assessment',
        'weight' => 50,
      ],
    ];
  }

  // =========================================================================
  // GET TYPE OPTIONS TESTS
  // =========================================================================

  /**
   * Verifica que getTypeOptions devuelve las claves esperadas.
   *
   * @covers ::getTypeOptions
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetTypeOptionsReturnsExpectedKeys(): void {
    $this->manager->method('getDefinitions')
      ->willReturn($this->sampleDefinitions);

    $options = $this->manager->getTypeOptions();

    $this->assertArrayHasKey('question_set', $options);
    $this->assertArrayHasKey('interactive_video', $options);
    $this->assertArrayHasKey('branching_scenario', $options);
    $this->assertSame('Conjunto de Preguntas', $options['question_set']);
    $this->assertSame('Video Interactivo', $options['interactive_video']);
  }

  /**
   * Verifica que getTypeOptions ordena los resultados por peso ascendente.
   *
   * @covers ::getTypeOptions
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetTypeOptionsOrderedByWeight(): void {
    // Definiciones en orden inverso de peso para comprobar el sorting.
    $unordered = [
      'essay' => [
        'id' => 'essay',
        'label' => 'Ensayo',
        'weight' => 50,
      ],
      'question_set' => [
        'id' => 'question_set',
        'label' => 'Conjunto de Preguntas',
        'weight' => 0,
      ],
      'branching_scenario' => [
        'id' => 'branching_scenario',
        'label' => 'Escenario Ramificado',
        'weight' => 30,
      ],
    ];

    $this->manager->method('getDefinitions')
      ->willReturn($unordered);

    $options = $this->manager->getTypeOptions();
    $keys = array_keys($options);

    $this->assertSame('question_set', $keys[0], 'Peso 0 debe ser primero');
    $this->assertSame('branching_scenario', $keys[1], 'Peso 30 debe ser segundo');
    $this->assertSame('essay', $keys[2], 'Peso 50 debe ser tercero');
  }

  /**
   * Verifica que getTypeOptions devuelve array vacio sin definiciones.
   *
   * @covers ::getTypeOptions
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetTypeOptionsEmptyDefinitions(): void {
    $this->manager->method('getDefinitions')
      ->willReturn([]);

    $options = $this->manager->getTypeOptions();

    $this->assertIsArray($options);
    $this->assertEmpty($options);
  }

  // =========================================================================
  // GET GROUPED OPTIONS TESTS
  // =========================================================================

  /**
   * Verifica que getGroupedOptions agrupa correctamente por categoria.
   *
   * @covers ::getGroupedOptions
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetGroupedOptionsGroupsByCategory(): void {
    $this->manager->method('getDefinitions')
      ->willReturn($this->sampleDefinitions);

    $grouped = $this->manager->getGroupedOptions();

    $this->assertArrayHasKey('assessment', $grouped);
    $this->assertArrayHasKey('media', $grouped);
    $this->assertArrayHasKey('presentation', $grouped);
    $this->assertArrayHasKey('scenario', $grouped);

    // La categoria assessment debe tener 3 plugins.
    $this->assertCount(3, $grouped['assessment']);
    $this->assertArrayHasKey('question_set', $grouped['assessment']);
    $this->assertArrayHasKey('drag_and_drop', $grouped['assessment']);
    $this->assertArrayHasKey('essay', $grouped['assessment']);
  }

  /**
   * Verifica que plugins sin categoria van a 'other'.
   *
   * @covers ::getGroupedOptions
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetGroupedOptionsUncategorizedGoesToOther(): void {
    $definitions = [
      'custom_plugin' => [
        'id' => 'custom_plugin',
        'label' => 'Plugin Personalizado',
        // Sin campo 'category' -> debe ir a 'other'.
      ],
      'question_set' => [
        'id' => 'question_set',
        'label' => 'Conjunto de Preguntas',
        'category' => 'assessment',
      ],
    ];

    $this->manager->method('getDefinitions')
      ->willReturn($definitions);

    $grouped = $this->manager->getGroupedOptions();

    $this->assertArrayHasKey('other', $grouped);
    $this->assertArrayHasKey('custom_plugin', $grouped['other']);
    $this->assertSame('Plugin Personalizado', $grouped['other']['custom_plugin']);
  }

  /**
   * Verifica que multiples categorias se agrupan correctamente.
   *
   * @covers ::getGroupedOptions
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetGroupedOptionsMultipleCategories(): void {
    $this->manager->method('getDefinitions')
      ->willReturn($this->sampleDefinitions);

    $grouped = $this->manager->getGroupedOptions();

    // Deben existir exactamente 4 categorias: assessment, media, presentation, scenario.
    $this->assertCount(4, $grouped);

    // Cada categoria tiene la cantidad correcta de plugins.
    $this->assertCount(3, $grouped['assessment']);
    $this->assertCount(1, $grouped['media']);
    $this->assertCount(1, $grouped['presentation']);
    $this->assertCount(1, $grouped['scenario']);
  }

  // =========================================================================
  // FULL PLUGIN SET TESTS
  // =========================================================================

  /**
   * Verifica que getTypeOptions contiene los 6 plugins del modulo.
   *
   * @covers ::getTypeOptions
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetTypeOptionsContainsAllSixPlugins(): void {
    $this->manager->method('getDefinitions')
      ->willReturn($this->sampleDefinitions);

    $options = $this->manager->getTypeOptions();

    $this->assertCount(6, $options);
    $this->assertArrayHasKey('question_set', $options);
    $this->assertArrayHasKey('interactive_video', $options);
    $this->assertArrayHasKey('course_presentation', $options);
    $this->assertArrayHasKey('branching_scenario', $options);
    $this->assertArrayHasKey('drag_and_drop', $options);
    $this->assertArrayHasKey('essay', $options);
  }

}
