<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agents\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_agents\Service\AgentRouterService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for AgentRouterService.
 *
 * Covers intent classification via keyword analysis, routing of conversations
 * to agents, confidence scoring, fallback behaviour, and edge cases.
 *
 * @coversDefaultClass \Drupal\jaraba_agents\Service\AgentRouterService
 * @group jaraba_agents
 */
class AgentRouterServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected AgentRouterService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock tenant context.
   */
  protected object $tenantContext;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->tenantContext = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getCurrentTenantId'])
      ->getMock();
    $this->tenantContext->method('getCurrentTenantId')->willReturn(1);

    $this->service = new AgentRouterService(
      $this->entityTypeManager,
      $this->logger,
      $this->tenantContext,
    );
  }

  /**
   * Creates a mock agent entity.
   *
   * @param int $id
   *   Entity ID.
   * @param string $name
   *   Agent name.
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   Mock agent entity.
   */
  protected function createMockAgent(int $id, string $name): object {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'get', 'label'])
      ->getMock();

    $entity->method('id')->willReturn($id);

    $nameField = new \stdClass();
    $nameField->value = $name;

    $entity->method('get')->willReturnCallback(function (string $field) use ($nameField): object {
      if ($field === 'name') {
        return $nameField;
      }
      $f = new \stdClass();
      $f->value = NULL;
      return $f;
    });

    return $entity;
  }

  /**
   * Sets up entity storage with mock agents and query.
   *
   * @param array $agents
   *   Array of mock agent entities keyed by ID.
   */
  protected function setUpAgentStorage(array $agents): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn(array_keys($agents));

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn($agents);

    $this->entityTypeManager->method('getStorage')
      ->with('autonomous_agent')
      ->willReturn($storage);
  }

  // -----------------------------------------------------------------------
  // classify() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::classify
   */
  public function testClassifyDetectsEnrollmentIntent(): void {
    $result = $this->service->classify('Quiero matricularme en el curso de marketing');

    $this->assertEquals('enrollment', $result['intent']);
    $this->assertGreaterThan(0.0, $result['confidence']);
    $this->assertNotEmpty($result['entities']);
    $this->assertContains('matricular', $result['entities']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyDetectsPlanningIntent(): void {
    $result = $this->service->classify('Necesito ver mi horario de clases del semestre');

    $this->assertEquals('planning', $result['intent']);
    $this->assertGreaterThan(0.0, $result['confidence']);
    $this->assertNotEmpty($result['entities']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyDetectsSupportIntent(): void {
    $result = $this->service->classify('Tengo un problema con mi cuenta, necesito ayuda urgente');

    $this->assertEquals('support', $result['intent']);
    $this->assertGreaterThan(0.0, $result['confidence']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyDefaultsToSupportForUnknownMessage(): void {
    $result = $this->service->classify('Lorem ipsum dolor sit amet');

    $this->assertEquals('support', $result['intent']);
    $this->assertEquals(0.1, $result['confidence']);
    $this->assertEmpty($result['entities']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyHandlesEmptyMessage(): void {
    $result = $this->service->classify('');

    $this->assertEquals('support', $result['intent']);
    $this->assertEquals(0.1, $result['confidence']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyIsCaseInsensitive(): void {
    $result = $this->service->classify('QUIERO MATRICULARME EN EL CURSO');

    $this->assertEquals('enrollment', $result['intent']);
    $this->assertContains('matricular', $result['entities']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyPicksHighestScoringIntent(): void {
    // Message with more planning keywords than enrollment.
    $result = $this->service->classify('planificacion horario calendario agenda programacion clases');

    $this->assertEquals('planning', $result['intent']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyConfidenceIsCappedAtOne(): void {
    // A message with many keywords from one intent type.
    $result = $this->service->classify(
      'matricula inscripcion inscribir matricular registro registrar admision admisiones plaza plazas solicitud'
    );

    $this->assertEquals('enrollment', $result['intent']);
    $this->assertLessThanOrEqual(1.0, $result['confidence']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyTrimsWhitespace(): void {
    $result = $this->service->classify('   ayuda con un problema   ');

    $this->assertEquals('support', $result['intent']);
    $this->assertGreaterThan(0.1, $result['confidence']);
  }

  // -----------------------------------------------------------------------
  // route() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::route
   */
  public function testRouteMatchesAgentByName(): void {
    $agents = [
      1 => $this->createMockAgent(1, 'enrollment_agent'),
      2 => $this->createMockAgent(2, 'support_agent'),
      3 => $this->createMockAgent(3, 'planning_agent'),
    ];
    $this->setUpAgentStorage($agents);

    $result = $this->service->route(100, 'Quiero matricularme en el curso');

    $this->assertEquals(1, $result['agent_id']);
    $this->assertGreaterThan(0.0, $result['confidence']);
    $this->assertNotEmpty($result['reasoning']);
  }

  /**
   * @covers ::route
   */
  public function testRouteReturnsZeroWhenNoAgentsAvailable(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('autonomous_agent')
      ->willReturn($storage);

    $result = $this->service->route(100, 'Necesito ayuda');

    $this->assertEquals(0, $result['agent_id']);
    $this->assertEquals(0.0, $result['confidence']);
  }

  /**
   * @covers ::route
   */
  public function testRouteFallsBackToFirstAgentWhenNoNameMatch(): void {
    // Agents whose names don't match any intent keyword.
    $agents = [
      10 => $this->createMockAgent(10, 'generic_bot'),
      20 => $this->createMockAgent(20, 'another_bot'),
    ];
    $this->setUpAgentStorage($agents);

    $result = $this->service->route(100, 'Quiero matricularme');

    // Should fallback to first agent (ID 10).
    $this->assertEquals(10, $result['agent_id']);
    // Confidence should be reduced (fallback penalty).
    $this->assertLessThanOrEqual(0.5, $result['confidence']);
  }

  /**
   * @covers ::route
   */
  public function testRouteFallbackConfidenceIsAtLeast03(): void {
    $agents = [
      5 => $this->createMockAgent(5, 'generic_bot'),
    ];
    $this->setUpAgentStorage($agents);

    // Unknown message -> confidence 0.1, fallback: max(0.3, 0.1 * 0.5) = 0.3.
    $result = $this->service->route(100, 'random gibberish');

    $this->assertEquals(5, $result['agent_id']);
    $this->assertGreaterThanOrEqual(0.3, $result['confidence']);
  }

  /**
   * @covers ::route
   */
  public function testRouteHandlesExceptionGracefully(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \RuntimeException('DB connection lost'));

    $result = $this->service->route(100, 'ayuda');

    $this->assertEquals(0, $result['agent_id']);
    $this->assertEquals(0.0, $result['confidence']);
    $this->assertNotEmpty($result['reasoning']);
  }

  // -----------------------------------------------------------------------
  // getConfidence() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::getConfidence
   */
  public function testGetConfidenceReturnsZeroBeforeAnyClassification(): void {
    $this->assertEquals(0.0, $this->service->getConfidence());
  }

  /**
   * @covers ::getConfidence
   */
  public function testGetConfidenceReflectsLastClassification(): void {
    $this->service->classify('Necesito inscribirme en un curso');
    $confidence = $this->service->getConfidence();

    $this->assertGreaterThan(0.0, $confidence);
  }

  /**
   * @covers ::getConfidence
   */
  public function testGetConfidenceUpdatesAfterRoute(): void {
    $agents = [
      1 => $this->createMockAgent(1, 'enrollment_agent'),
    ];
    $this->setUpAgentStorage($agents);

    $this->service->route(1, 'Quiero matricularme');

    $this->assertGreaterThan(0.0, $this->service->getConfidence());
  }

}
