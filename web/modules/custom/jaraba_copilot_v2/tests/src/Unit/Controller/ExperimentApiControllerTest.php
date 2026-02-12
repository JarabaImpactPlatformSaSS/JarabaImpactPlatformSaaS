<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_copilot_v2\Unit\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Controller\ExperimentApiController;
use Drupal\jaraba_copilot_v2\Service\ExperimentLibraryService;
use Drupal\jaraba_copilot_v2\Service\FeatureUnlockService;
use Drupal\jaraba_copilot_v2\Service\LearningCardService;
use Drupal\jaraba_copilot_v2\Service\TestCardGeneratorService;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the ExperimentApiController.
 *
 * @covers \Drupal\jaraba_copilot_v2\Controller\ExperimentApiController
 * @group jaraba_copilot_v2
 */
class ExperimentApiControllerTest extends UnitTestCase {

  /**
   * The mocked experiment library service.
   *
   * @var \Drupal\jaraba_copilot_v2\Service\ExperimentLibraryService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ExperimentLibraryService $experimentLibrary;

  /**
   * The mocked feature unlock service.
   *
   * @var \Drupal\jaraba_copilot_v2\Service\FeatureUnlockService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected FeatureUnlockService $featureUnlock;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked learning card service.
   *
   * @var \Drupal\jaraba_copilot_v2\Service\LearningCardService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LearningCardService $learningCard;

  /**
   * The mocked test card generator service.
   *
   * @var \Drupal\jaraba_copilot_v2\Service\TestCardGeneratorService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected TestCardGeneratorService $testCardGenerator;

  /**
   * The controller under test.
   *
   * @var \Drupal\jaraba_copilot_v2\Controller\ExperimentApiController
   */
  protected ExperimentApiController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->experimentLibrary = $this->createMock(ExperimentLibraryService::class);
    $this->featureUnlock = $this->createMock(FeatureUnlockService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->learningCard = $this->createMock(LearningCardService::class);
    $this->testCardGenerator = $this->createMock(TestCardGeneratorService::class);

    $this->controller = new ExperimentApiController(
      $this->experimentLibrary,
      $this->featureUnlock,
      $this->entityTypeManager,
      $this->learningCard,
      $this->testCardGenerator,
    );
  }

  /**
   * Tests that the controller can be instantiated.
   */
  public function testControllerExists(): void {
    $this->assertInstanceOf(ExperimentApiController::class, $this->controller);
  }

  /**
   * Tests that the controller has all expected API methods.
   */
  public function testControllerHasExpectedMethods(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);

    $expectedMethods = [
      'listUserExperiments',
      'store',
      'get',
      'start',
      'recordResult',
    ];

    foreach ($expectedMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "Controller should have method: {$method}"
      );
    }
  }

  /**
   * Tests that all API methods declare JsonResponse as their return type.
   */
  public function testMethodReturnTypes(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);

    $apiMethods = [
      'listUserExperiments',
      'store',
      'get',
      'start',
      'recordResult',
    ];

    foreach ($apiMethods as $methodName) {
      $method = $reflection->getMethod($methodName);
      $returnType = $method->getReturnType();
      $this->assertNotNull($returnType, "Method {$methodName} should have a return type");
      $this->assertEquals(
        'Symfony\Component\HttpFoundation\JsonResponse',
        $returnType->getName(),
        "Method {$methodName} should return JsonResponse"
      );
    }
  }

  /**
   * Tests that IMPACT_POINTS constant is properly defined.
   */
  public function testImpactPointsConstant(): void {
    $points = ExperimentApiController::IMPACT_POINTS;

    $this->assertIsArray($points);
    $this->assertArrayHasKey('PERSEVERE', $points);
    $this->assertArrayHasKey('PIVOT', $points);
    $this->assertArrayHasKey('ZOOM_IN', $points);
    $this->assertArrayHasKey('ZOOM_OUT', $points);
    $this->assertArrayHasKey('KILL', $points);

    $this->assertSame(100, $points['PERSEVERE']);
    $this->assertSame(75, $points['PIVOT']);
    $this->assertSame(75, $points['ZOOM_IN']);
    $this->assertSame(75, $points['ZOOM_OUT']);
    $this->assertSame(50, $points['KILL']);
  }

  /**
   * Tests that the controller also has the library catalog methods.
   */
  public function testControllerHasLibraryCatalogMethods(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);

    $this->assertTrue($reflection->hasMethod('list'), 'Controller should have list method');
    $this->assertTrue($reflection->hasMethod('suggest'), 'Controller should have suggest method');
  }

  /**
   * Tests that serializeExperiment is a protected method.
   */
  public function testSerializeExperimentIsProtected(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);
    $method = $reflection->getMethod('serializeExperiment');
    $this->assertTrue($method->isProtected(), 'serializeExperiment method should be protected');
  }

}
