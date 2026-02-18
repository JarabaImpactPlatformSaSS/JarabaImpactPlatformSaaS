<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
   * @param string $entityTypeId
   *   El tipo de entidad a devolver.
   * @param array $methods
   *   Metodos adicionales para el mock.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad.
   */
  private function createEntityMock(string $entityTypeId, array $methods = []): MockObject {
    $defaultMethods = ['getEntityTypeId', 'getInteractiveContent', 'getOwnerId', 'getScore', 'hasPassed', 'id'];
    $allMethods = array_unique(array_merge($defaultMethods, $methods));

    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods($allMethods)
      ->getMock();

    $entity->method('getEntityTypeId')->willReturn($entityTypeId);

    return $entity;
  }

  /**
   * Crea un mock de evento de insercion con la entidad dada.
   *
   * @param \PHPUnit\Framework\MockObject\MockObject $entity
   *   La entidad que devuelve getEntity().
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock del evento.
   */
  private function createInsertEventMock(MockObject $entity): MockObject {
    $event = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getEntity'])
      ->getMock();

    $event->method('getEntity')->willReturn($entity);

    return $event;
  }

  /**
   * Crea un mock de evento de actualizacion con la entidad dada.
   *
   * @param \PHPUnit\Framework\MockObject\MockObject $entity
   *   La entidad que devuelve getEntity().
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock del evento.
   */
  private function createUpdateEventMock(MockObject $entity): MockObject {
    $event = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getEntity'])
      ->getMock();

    $event->method('getEntity')->willReturn($entity);

    return $event;
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
    $content = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'get'])
      ->getMock();

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
   * Verifica que getSubscribedEvents devuelve las claves de eventos esperadas.
   *
   * @covers ::getSubscribedEvents
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSubscribedEventsReturnsExpectedKeys(): void {
    $events = CompletionSubscriber::getSubscribedEvents();

    $this->assertIsArray($events);
    // Verificar que las constantes de EntityHookEvents estan registradas.
    $eventKeys = array_keys($events);
    $this->assertCount(2, $eventKeys);

    // Verificar los metodos callback.
    $insertConfig = array_values($events)[0];
    $updateConfig = array_values($events)[1];
    $this->assertSame('onEntityInsert', $insertConfig[0]);
    $this->assertSame('onEntityUpdate', $updateConfig[0]);
  }

  /**
   * Verifica que los eventos registrados tienen prioridad 100.
   *
   * @covers ::getSubscribedEvents
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSubscribedEventsHasPriority100(): void {
    $events = CompletionSubscriber::getSubscribedEvents();

    foreach ($events as $eventConfig) {
      $this->assertSame(100, $eventConfig[1], 'Todos los eventos deben tener prioridad 100');
    }
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
    $event = $this->createInsertEventMock($entity);

    // El xapiEmitter NO debe ser invocado.
    $this->xapiEmitter->expects($this->never())
      ->method('emitCompleted');

    $this->subscriber->onEntityInsert($event);
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

    $event = $this->createInsertEventMock($entity);

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

    $this->subscriber->onEntityInsert($event);
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
    $event = $this->createUpdateEventMock($entity);

    $this->xapiEmitter->expects($this->never())
      ->method('emitCompleted');

    $this->subscriber->onEntityUpdate($event);
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

    $event = $this->createUpdateEventMock($entity);

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

    $this->subscriber->onEntityUpdate($event);
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

    $event = $this->createInsertEventMock($entity);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        'CompletionSubscriber: Resultado @id sin contenido asociado.',
        ['@id' => 999],
      );

    // El xapiEmitter NO debe ser invocado si no hay contenido.
    $this->xapiEmitter->expects($this->never())
      ->method('emitCompleted');

    $this->subscriber->onEntityInsert($event);
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

    $event = $this->createInsertEventMock($entity);

    $this->xapiEmitter->method('emitCompleted')
      ->willThrowException(new \RuntimeException('Servicio xAPI no disponible'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Error al procesar completitud: @message',
        ['@message' => 'Servicio xAPI no disponible'],
      );

    $this->subscriber->onEntityInsert($event);
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
