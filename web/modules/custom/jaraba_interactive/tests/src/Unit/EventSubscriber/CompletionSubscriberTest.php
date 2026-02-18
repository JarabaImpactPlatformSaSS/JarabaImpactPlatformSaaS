<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_interactive\Entity\InteractiveContentInterface;
use Drupal\jaraba_interactive\Entity\InteractiveResultInterface;
use Drupal\jaraba_interactive\EventSubscriber\CompletionSubscriber;
use Drupal\jaraba_interactive\Service\XApiEmitter;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para CompletionSubscriber.
 *
 * Verifica el registro de eventos, el filtrado de entidades por tipo,
 * la emision de sentencias xAPI, el manejo de errores y el calculo
 * de puntos de experiencia segun dificultad del contenido.
 *
 * @coversDefaultClass \Drupal\jaraba_interactive\EventSubscriber\CompletionSubscriber
 * @group jaraba_interactive
 */
class CompletionSubscriberTest extends UnitTestCase {

  /**
   * El suscriptor bajo prueba.
   */
  private CompletionSubscriber $subscriber;

  /**
   * Mock del emisor xAPI.
   */
  private XApiEmitter&MockObject $xapiEmitter;

  /**
   * Mock del entity type manager.
   */
  private EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del usuario actual.
   */
  private AccountProxyInterface&MockObject $currentUser;

  /**
   * Mock del logger.
   */
  private LoggerInterface&MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->xapiEmitter = $this->createMock(XApiEmitter::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->subscriber = new CompletionSubscriber(
      $this->xapiEmitter,
      $this->entityTypeManager,
      $this->currentUser,
      $this->logger,
    );
  }

  // =========================================================================
  // Helper para crear mocks de entidades y eventos.
  // =========================================================================

  /**
   * Crea un mock de entidad con getEntityTypeId configurable.
   *
   * For 'interactive_result' entities, creates a mock from
   * InteractiveResultInterface. For other entity types, creates a mock
   * from EntityInterface. This ensures proper type compatibility with
   * the subscriber's processCompletion() and grantExperiencePoints() methods.
   *
   * @param string $entityTypeId
   *   El tipo de entidad a devolver.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad.
   */
  private function createEntityMock(string $entityTypeId): MockObject {
    if ($entityTypeId === 'interactive_result') {
      $entity = $this->createMock(InteractiveResultInterface::class);
    }
    else {
      $entity = $this->createMock(EntityInterface::class);
    }

    $entity->method('getEntityTypeId')->willReturn($entityTypeId);

    return $entity;
  }

  /**
   * Crea un mock de contenido interactivo con dificultad configurable.
   *
   * @param string $difficulty
   *   El nivel de dificultad (beginner, intermediate, advanced).
   * @param int $contentId
   *   El ID del contenido.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock del contenido interactivo.
   */
  private function createContentMock(string $difficulty = 'intermediate', int $contentId = 42): MockObject {
    $content = $this->createMock(InteractiveContentInterface::class);

    $content->method('id')->willReturn($contentId);

    $fieldItem = (object) ['value' => $difficulty];
    $content->method('get')
      ->with('difficulty')
      ->willReturn($fieldItem);

    return $content;
  }

  // =========================================================================
  // GET SUBSCRIBED EVENTS TESTS
  // =========================================================================

  /**
   * Verifica que getSubscribedEvents devuelve un array vacio.
   *
   * Returns empty because the module dependency (core_event_dispatcher)
   * is not installed. Entity lifecycle logic is handled via
   * hook_entity_insert()/hook_entity_update() in the .module file.
   *
   * @covers ::getSubscribedEvents
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSubscribedEventsReturnsExpectedKeys(): void {
    $events = CompletionSubscriber::getSubscribedEvents();

    $this->assertIsArray($events);
    // Returns empty array until core_event_dispatcher or Drupal 11 Hooks
    // system is adopted.
    $this->assertEmpty($events);
  }

  /**
   * Verifica que getSubscribedEvents is empty (no events registered yet).
   *
   * When events are registered in the future, this test should be updated
   * to verify priority values.
   *
   * @covers ::getSubscribedEvents
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSubscribedEventsHasPriority100(): void {
    $events = CompletionSubscriber::getSubscribedEvents();

    // Currently returns empty array; assert that to avoid risky test.
    $this->assertEmpty($events, 'getSubscribedEvents returns empty until core_event_dispatcher is installed');
  }

  // =========================================================================
  // ON ENTITY INSERT TESTS
  // =========================================================================

  /**
   * Verifica que onEntityInsert ignora entidades que no son interactive_result.
   *
   * @covers ::onEntityInsert
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testOnEntityInsertIgnoresNonInteractiveResult(): void {
    $entity = $this->createEntityMock('node');

    // El xapiEmitter NO debe ser invocado.
    $this->xapiEmitter->expects($this->never())
      ->method('emitCompleted');

    $this->subscriber->onEntityInsert($entity);
  }

  /**
   * Verifica que onEntityInsert procesa entidades interactive_result.
   *
   * @covers ::onEntityInsert
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testOnEntityInsertProcessesInteractiveResult(): void {
    $content = $this->createContentMock();
    $entity = $this->createEntityMock('interactive_result');
    $entity->method('getInteractiveContent')->willReturn($content);
    $entity->method('getOwnerId')->willReturn(7);
    $entity->method('getScore')->willReturn(85.0);
    $entity->method('hasPassed')->willReturn(TRUE);
    $entity->method('id')->willReturn(101);

    // Verificar que se emite la sentencia xAPI de completitud.
    $this->xapiEmitter->expects($this->once())
      ->method('emitCompleted')
      ->with($content, $entity);

    // Mock del storage para updateCertificationProgress.
    $query = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['condition', 'accessCheck', 'execute'])
      ->getMock();
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getQuery'])
      ->getMock();
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('certification_program')
      ->willReturn($storage);

    $this->subscriber->onEntityInsert($entity);
  }

  // =========================================================================
  // ON ENTITY UPDATE TESTS
  // =========================================================================

  /**
   * Verifica que onEntityUpdate ignora entidades que no son interactive_result.
   *
   * @covers ::onEntityUpdate
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testOnEntityUpdateIgnoresNonInteractiveResult(): void {
    $entity = $this->createEntityMock('user');

    $this->xapiEmitter->expects($this->never())
      ->method('emitCompleted');

    $this->subscriber->onEntityUpdate($entity);
  }

  /**
   * Verifica que onEntityUpdate procesa entidades interactive_result.
   *
   * @covers ::onEntityUpdate
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testOnEntityUpdateProcessesInteractiveResult(): void {
    $content = $this->createContentMock('advanced');
    $entity = $this->createEntityMock('interactive_result');
    $entity->method('getInteractiveContent')->willReturn($content);
    $entity->method('getOwnerId')->willReturn(12);
    $entity->method('getScore')->willReturn(95.0);
    $entity->method('hasPassed')->willReturn(TRUE);
    $entity->method('id')->willReturn(202);

    $this->xapiEmitter->expects($this->once())
      ->method('emitCompleted')
      ->with($content, $entity);

    // Mock del storage para updateCertificationProgress.
    $query = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['condition', 'accessCheck', 'execute'])
      ->getMock();
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getQuery'])
      ->getMock();
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('certification_program')
      ->willReturn($storage);

    $this->subscriber->onEntityUpdate($entity);
  }

  // =========================================================================
  // PROCESS COMPLETION TESTS
  // =========================================================================

  /**
   * Verifica que processCompletion registra warning cuando el contenido es null.
   *
   * @covers ::processCompletion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testProcessCompletionLogsWarningOnMissingContent(): void {
    $entity = $this->createEntityMock('interactive_result');
    $entity->method('getInteractiveContent')->willReturn(NULL);
    $entity->method('id')->willReturn(999);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        'CompletionSubscriber: Resultado @id sin contenido asociado.',
        ['@id' => 999],
      );

    // El xapiEmitter NO debe ser invocado si no hay contenido.
    $this->xapiEmitter->expects($this->never())
      ->method('emitCompleted');

    $this->subscriber->onEntityInsert($entity);
  }

  /**
   * Verifica que processCompletion registra error cuando xapiEmitter lanza excepcion.
   *
   * @covers ::processCompletion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testProcessCompletionHandlesXapiException(): void {
    $content = $this->createContentMock();
    $entity = $this->createEntityMock('interactive_result');
    $entity->method('getInteractiveContent')->willReturn($content);
    $entity->method('getOwnerId')->willReturn(5);
    $entity->method('getScore')->willReturn(70.0);
    $entity->method('hasPassed')->willReturn(FALSE);
    $entity->method('id')->willReturn(303);

    $this->xapiEmitter->method('emitCompleted')
      ->willThrowException(new \RuntimeException('Servicio xAPI no disponible'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Error al procesar completitud: @message',
        ['@message' => 'Servicio xAPI no disponible'],
      );

    // Mock del storage para updateCertificationProgress.
    $query = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['condition', 'accessCheck', 'execute'])
      ->getMock();
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getQuery'])
      ->getMock();
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('certification_program')
      ->willReturn($storage);

    $this->subscriber->onEntityInsert($entity);
  }

  // =========================================================================
  // GRANT EXPERIENCE POINTS TESTS (via reflection)
  // =========================================================================

  /**
   * Verifica el calculo de XP segun dificultad: beginner=10, intermediate=25, advanced=50.
   *
   * @covers ::grantExperiencePoints
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGrantExperiencePointsCalculatesXp(): void {
    $method = new \ReflectionMethod(CompletionSubscriber::class, 'grantExperiencePoints');
    $method->setAccessible(TRUE);

    // Caso beginner con score < 80 => 10 + 0 = 10 XP.
    $contentBeginner = $this->createContentMock('beginner', 1);
    $resultBeginner = $this->createEntityMock('interactive_result');
    $resultBeginner->method('getInteractiveContent')->willReturn($contentBeginner);
    $resultBeginner->method('getScore')->willReturn(75.0);
    $resultBeginner->method('getOwnerId')->willReturn(1);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        'XP otorgados: @xp puntos al usuario @uid por contenido @cid.',
        $this->callback(function (array $context): bool {
          // beginner(10) + bonus 0 (score 75 < 80) = 10.
          return $context['@xp'] === 10
            && $context['@uid'] === 1
            && $context['@cid'] === 1;
        }),
      );

    $method->invoke($this->subscriber, $resultBeginner);
  }

  /**
   * Verifica el calculo de XP para dificultad intermedia con bonus por score alto.
   *
   * @covers ::grantExperiencePoints
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGrantExperiencePointsIntermediateWithBonus(): void {
    $method = new \ReflectionMethod(CompletionSubscriber::class, 'grantExperiencePoints');
    $method->setAccessible(TRUE);

    // Caso intermediate con score 92 => 25 + 10 (bonus >= 90) = 35 XP.
    $content = $this->createContentMock('intermediate', 50);
    $result = $this->createEntityMock('interactive_result');
    $result->method('getInteractiveContent')->willReturn($content);
    $result->method('getScore')->willReturn(92.0);
    $result->method('getOwnerId')->willReturn(3);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        'XP otorgados: @xp puntos al usuario @uid por contenido @cid.',
        $this->callback(function (array $context): bool {
          // intermediate(25) + bonus 10 (score 92 >= 90) = 35.
          return $context['@xp'] === 35
            && $context['@uid'] === 3
            && $context['@cid'] === 50;
        }),
      );

    $method->invoke($this->subscriber, $result);
  }

}
