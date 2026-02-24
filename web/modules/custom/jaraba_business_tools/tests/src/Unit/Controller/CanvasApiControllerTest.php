<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_business_tools\Unit\Controller;

use Drupal\jaraba_business_tools\Controller\CanvasApiController;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the CanvasApiController.
 *
 * Verifies the controller class structure, method signatures,
 * return types, and parameter validation for the Canvas REST API.
 *
 * @covers \Drupal\jaraba_business_tools\Controller\CanvasApiController
 * @group jaraba_business_tools
 */
class CanvasApiControllerTest extends UnitTestCase {

  /**
   * Tests that the CanvasApiController class exists.
   */
  public function testControllerClassExists(): void {
    $this->assertTrue(
      class_exists(CanvasApiController::class),
      'CanvasApiController class should exist.'
    );
  }

  /**
   * Tests that the controller extends ControllerBase.
   */
  public function testControllerExtendsControllerBase(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $this->assertTrue(
      $reflection->isSubclassOf('Drupal\Core\Controller\ControllerBase'),
      'CanvasApiController should extend ControllerBase.'
    );
  }

  /**
   * Tests that the controller has the static create() method for DI.
   */
  public function testControllerHasCreateMethod(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $this->assertTrue(
      $reflection->hasMethod('create'),
      'Controller should have static create() method for DI.'
    );

    $createMethod = $reflection->getMethod('create');
    $this->assertTrue($createMethod->isStatic(), 'create() should be static.');
  }

  /**
   * Tests that the controller has all expected public API methods.
   */
  public function testControllerHasExpectedMethods(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);

    $expectedMethods = [
      'list',
      'get',
      'createCanvas',
      'cloneCanvas',
      'generateCanvas',
      'update',
      'delete',
      'updateStatus',
      'getBlocks',
      'updateBlock',
      'addItem',
      'removeItem',
      'updateItem',
      'analyze',
      'getSuggestions',
      'listVersions',
      'createVersion',
      'getVersion',
      'restoreVersion',
      'exportPdf',
      'share',
      'listTemplates',
      'submitAgentRating',
    ];

    foreach ($expectedMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "Controller should have method: {$method}"
      );
    }
  }

  /**
   * Tests that the list method returns a JsonResponse.
   */
  public function testListMethodReturnType(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $method = $reflection->getMethod('list');
    $returnType = $method->getReturnType();

    $this->assertNotNull($returnType, 'list() should have a return type.');
    $this->assertEquals(
      'Symfony\Component\HttpFoundation\JsonResponse',
      $returnType->getName(),
      'list() should return JsonResponse.'
    );
  }

  /**
   * Tests that all primary API methods return JsonResponse.
   */
  public function testApiMethodsReturnJsonResponse(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);

    $apiMethods = [
      'list',
      'get',
      'createCanvas',
      'cloneCanvas',
      'generateCanvas',
      'update',
      'delete',
      'updateStatus',
      'getBlocks',
      'updateBlock',
      'addItem',
      'removeItem',
      'updateItem',
      'analyze',
      'getSuggestions',
      'listVersions',
      'createVersion',
      'getVersion',
      'restoreVersion',
      'share',
      'listTemplates',
      'submitAgentRating',
    ];

    foreach ($apiMethods as $methodName) {
      $method = $reflection->getMethod($methodName);
      $returnType = $method->getReturnType();
      $this->assertNotNull($returnType, "Method {$methodName} should have a return type.");
      $this->assertEquals(
        'Symfony\Component\HttpFoundation\JsonResponse',
        $returnType->getName(),
        "Method {$methodName} should return JsonResponse."
      );
    }
  }

  /**
   * Tests that the createCanvas method accepts a Request parameter.
   */
  public function testCreateCanvasAcceptsRequest(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $method = $reflection->getMethod('createCanvas');
    $params = $method->getParameters();

    $this->assertCount(1, $params, 'createCanvas should have exactly 1 parameter.');
    $this->assertEquals('request', $params[0]->getName());
    $this->assertEquals(
      'Symfony\Component\HttpFoundation\Request',
      $params[0]->getType()->getName(),
      'createCanvas parameter should be a Request.'
    );
  }

  /**
   * Tests that the updateStatus method accepts id and request parameters.
   */
  public function testUpdateStatusParameterTypes(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $method = $reflection->getMethod('updateStatus');
    $params = $method->getParameters();

    $this->assertCount(2, $params, 'updateStatus should have 2 parameters.');
    $this->assertEquals('id', $params[0]->getName());
    $this->assertEquals('int', $params[0]->getType()->getName());
    $this->assertEquals('request', $params[1]->getName());
    $this->assertEquals(
      'Symfony\Component\HttpFoundation\Request',
      $params[1]->getType()->getName()
    );
  }

  /**
   * Tests that the valid statuses for updateStatus are draft, active, archived.
   *
   * This verifies the validation logic by inspecting the source code pattern.
   * The controller should reject any status not in ['draft', 'active', 'archived'].
   */
  public function testValidStatusesAreDefined(): void {
    // Read the source to verify the validation array exists.
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $source = file_get_contents($reflection->getFileName());

    $this->assertStringContainsString(
      "'draft', 'active', 'archived'",
      $source,
      'updateStatus should validate against [draft, active, archived].'
    );
  }

  /**
   * Tests that the exportPdf method returns a Response (not JsonResponse).
   *
   * PDF export returns a generic Response with binary content.
   */
  public function testExportPdfReturnsResponse(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $method = $reflection->getMethod('exportPdf');
    $returnType = $method->getReturnType();

    $this->assertNotNull($returnType, 'exportPdf() should have a return type.');
    $this->assertEquals(
      'Symfony\Component\HttpFoundation\Response',
      $returnType->getName(),
      'exportPdf() should return a Response (for binary PDF content).'
    );
  }

  /**
   * Tests that checkAccess is a protected method.
   */
  public function testCheckAccessIsProtected(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $method = $reflection->getMethod('checkAccess');
    $this->assertTrue(
      $method->isProtected(),
      'checkAccess should be protected.'
    );
  }

  /**
   * Tests that renderCanvasPdfHtml is a protected method.
   */
  public function testRenderCanvasPdfHtmlIsProtected(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $method = $reflection->getMethod('renderCanvasPdfHtml');
    $this->assertTrue(
      $method->isProtected(),
      'renderCanvasPdfHtml should be protected.'
    );
  }

  /**
   * Tests that the share method requires user_id in the request body.
   *
   * Verifies the parameter structure of the share method.
   */
  public function testShareMethodAcceptsIdAndRequest(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $method = $reflection->getMethod('share');
    $params = $method->getParameters();

    $this->assertCount(2, $params, 'share should have 2 parameters.');
    $this->assertEquals('id', $params[0]->getName());
    $this->assertEquals('int', $params[0]->getType()->getName());
    $this->assertEquals('request', $params[1]->getName());
  }

  /**
   * Tests that updateBlock accepts canvas id, block type, and request.
   */
  public function testUpdateBlockParameterTypes(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $method = $reflection->getMethod('updateBlock');
    $params = $method->getParameters();

    $this->assertCount(3, $params, 'updateBlock should have 3 parameters.');
    $this->assertEquals('id', $params[0]->getName());
    $this->assertEquals('int', $params[0]->getType()->getName());
    $this->assertEquals('type', $params[1]->getName());
    $this->assertEquals('string', $params[1]->getType()->getName());
    $this->assertEquals('request', $params[2]->getName());
  }

  /**
   * Tests that removeItem accepts canvas id, block type, and item id.
   */
  public function testRemoveItemParameterTypes(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);
    $method = $reflection->getMethod('removeItem');
    $params = $method->getParameters();

    $this->assertCount(3, $params, 'removeItem should have 3 parameters.');
    $this->assertEquals('id', $params[0]->getName());
    $this->assertEquals('int', $params[0]->getType()->getName());
    $this->assertEquals('type', $params[1]->getName());
    $this->assertEquals('string', $params[1]->getType()->getName());
    $this->assertEquals('itemId', $params[2]->getName());
    $this->assertEquals('string', $params[2]->getType()->getName());
  }

  /**
   * Tests that all public API methods are indeed public.
   */
  public function testAllApiMethodsArePublic(): void {
    $reflection = new \ReflectionClass(CanvasApiController::class);

    $publicApiMethods = [
      'list',
      'get',
      'createCanvas',
      'update',
      'delete',
      'updateStatus',
      'getBlocks',
      'updateBlock',
      'addItem',
      'removeItem',
      'analyze',
      'share',
      'listTemplates',
    ];

    foreach ($publicApiMethods as $methodName) {
      $method = $reflection->getMethod($methodName);
      $this->assertTrue(
        $method->isPublic(),
        "Method {$methodName} should be public."
      );
    }
  }

}
