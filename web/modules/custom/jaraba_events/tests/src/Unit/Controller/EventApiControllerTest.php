<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_events\Unit\Controller;

use Drupal\jaraba_events\Controller\EventApiController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests unitarios para EventApiController.
 *
 * Verifica la existencia de la clase, sus métodos públicos
 * y los tipos de retorno de los endpoints del API REST.
 *
 * @covers \Drupal\jaraba_events\Controller\EventApiController
 * @group jaraba_events
 */
class EventApiControllerTest extends UnitTestCase {

  /**
   * Tests que la clase EventApiController existe y es instanciable.
   */
  public function testControllerClassExists(): void {
    $this->assertTrue(
      class_exists(EventApiController::class),
      'EventApiController class should exist.'
    );
  }

  /**
   * Tests que el controlador extiende ControllerBase.
   */
  public function testControllerExtendsControllerBase(): void {
    $reflection = new \ReflectionClass(EventApiController::class);
    $this->assertTrue(
      $reflection->isSubclassOf('Drupal\Core\Controller\ControllerBase'),
      'EventApiController should extend ControllerBase.'
    );
  }

  /**
   * Tests que el controlador tiene todos los métodos esperados.
   */
  public function testControllerHasExpectedMethods(): void {
    $reflection = new \ReflectionClass(EventApiController::class);

    $expectedMethods = [
      'listEvents',
      'getEvent',
      'createEvent',
      'updateEvent',
      'deleteEvent',
      'listRegistrations',
      'registerForEvent',
      'checkIn',
      'getEventStats',
      'generateCertificate',
    ];

    foreach ($expectedMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "Controller should have method: {$method}"
      );
    }
  }

  /**
   * Tests que todos los métodos API devuelven JsonResponse.
   */
  public function testMethodReturnTypes(): void {
    $reflection = new \ReflectionClass(EventApiController::class);

    $apiMethods = [
      'listEvents',
      'getEvent',
      'createEvent',
      'updateEvent',
      'deleteEvent',
      'listRegistrations',
      'registerForEvent',
      'checkIn',
      'getEventStats',
      'generateCertificate',
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
   * Tests que el controlador tiene el método create() para inyección de dependencias.
   */
  public function testControllerHasCreateMethod(): void {
    $reflection = new \ReflectionClass(EventApiController::class);
    $this->assertTrue(
      $reflection->hasMethod('create'),
      'Controller should have static create() method for DI.'
    );

    $createMethod = $reflection->getMethod('create');
    $this->assertTrue($createMethod->isStatic(), 'create() should be static.');
  }

  /**
   * Tests que el controlador tiene el método getCurrentTenantId().
   */
  public function testControllerHasGetCurrentTenantId(): void {
    $reflection = new \ReflectionClass(EventApiController::class);
    $this->assertTrue(
      $reflection->hasMethod('getCurrentTenantId'),
      'Controller should have getCurrentTenantId() method.'
    );
  }

}
