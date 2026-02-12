<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_customer_success\Unit\Controller;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_customer_success\Controller\NpsApiController;
use Drupal\jaraba_customer_success\Service\NpsSurveyService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests para NpsApiController.
 *
 * @covers \Drupal\jaraba_customer_success\Controller\NpsApiController
 * @group jaraba_customer_success
 */
class NpsApiControllerTest extends UnitTestCase {

  protected NpsSurveyService $npsSurveyService;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;
  protected NpsApiController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->npsSurveyService = $this->createMock(NpsSurveyService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->controller = new NpsApiController(
      $this->entityTypeManager,
      $this->npsSurveyService,
      $this->logger,
    );
  }

  /**
   * Tests getActiveSurvey returns active survey data.
   */
  public function testGetActiveSurveyReturnsData(): void {
    $request = Request::create('/api/v1/cs/nps/survey', 'GET');

    $response = $this->controller->getActiveSurvey($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertTrue($body['data']['active']);
    $this->assertTrue($body['data']['can_send']);
    $this->assertArrayHasKey('question', $body['data']);
    $this->assertArrayHasKey('scale', $body['data']);
    $this->assertEquals(0, $body['data']['scale']['min']);
    $this->assertEquals(10, $body['data']['scale']['max']);
    $this->assertArrayHasKey('ranges', $body['data']);
  }

  /**
   * Tests getActiveSurvey checks cooldown for specific tenant.
   */
  public function testGetActiveSurveyChecksCooldown(): void {
    $this->npsSurveyService->expects($this->once())
      ->method('canSendSurvey')
      ->with('tenant_123')
      ->willReturn(FALSE);

    $request = Request::create('/api/v1/cs/nps/survey?tenant_id=tenant_123', 'GET');

    $response = $this->controller->getActiveSurvey($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertFalse($body['data']['can_send']);
  }

  /**
   * Tests submitResponse with valid data returns 201.
   */
  public function testSubmitResponseValid(): void {
    $this->npsSurveyService->expects($this->once())
      ->method('canSendSurvey')
      ->with('tenant_42')
      ->willReturn(TRUE);

    $this->npsSurveyService->expects($this->once())
      ->method('collectResponse')
      ->with('tenant_42', 9, 'Great platform!');

    $this->npsSurveyService->expects($this->once())
      ->method('markSurveySent')
      ->with('tenant_42');

    $request = Request::create('/api/v1/cs/nps/submit', 'POST', [], [], [], [], json_encode([
      'tenant_id' => 'tenant_42',
      'score' => 9,
      'comment' => 'Great platform!',
    ]));

    $response = $this->controller->submitResponse($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(201, $response->getStatusCode());
    $this->assertTrue($body['data']['success']);
    $this->assertEquals('promoter', $body['data']['category']);
    $this->assertEquals(9, $body['data']['score']);
  }

  /**
   * Tests submitResponse with score 7 returns passive category.
   */
  public function testSubmitResponsePassiveCategory(): void {
    $this->npsSurveyService->method('canSendSurvey')->willReturn(TRUE);
    $this->npsSurveyService->expects($this->once())
      ->method('collectResponse')
      ->with('tenant_42', 7, '');

    $request = Request::create('/api/v1/cs/nps/submit', 'POST', [], [], [], [], json_encode([
      'tenant_id' => 'tenant_42',
      'score' => 7,
    ]));

    $response = $this->controller->submitResponse($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(201, $response->getStatusCode());
    $this->assertEquals('passive', $body['data']['category']);
  }

  /**
   * Tests submitResponse with score 4 returns detractor category.
   */
  public function testSubmitResponseDetractorCategory(): void {
    $this->npsSurveyService->method('canSendSurvey')->willReturn(TRUE);
    $this->npsSurveyService->expects($this->once())
      ->method('collectResponse')
      ->with('tenant_42', 4, '');

    $request = Request::create('/api/v1/cs/nps/submit', 'POST', [], [], [], [], json_encode([
      'tenant_id' => 'tenant_42',
      'score' => 4,
    ]));

    $response = $this->controller->submitResponse($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(201, $response->getStatusCode());
    $this->assertEquals('detractor', $body['data']['category']);
  }

  /**
   * Tests submitResponse rejects empty JSON body.
   */
  public function testSubmitResponseInvalidJson(): void {
    $request = Request::create('/api/v1/cs/nps/submit', 'POST', [], [], [], [], '');

    $response = $this->controller->submitResponse($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(400, $response->getStatusCode());
    $this->assertArrayHasKey('error', $body);
  }

  /**
   * Tests submitResponse rejects missing tenant_id.
   */
  public function testSubmitResponseMissingTenantId(): void {
    $request = Request::create('/api/v1/cs/nps/submit', 'POST', [], [], [], [], json_encode([
      'score' => 8,
    ]));

    $response = $this->controller->submitResponse($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(400, $response->getStatusCode());
    $this->assertStringContainsString('tenant_id', $body['error']);
  }

  /**
   * Tests submitResponse rejects missing score.
   */
  public function testSubmitResponseMissingScore(): void {
    $request = Request::create('/api/v1/cs/nps/submit', 'POST', [], [], [], [], json_encode([
      'tenant_id' => 'tenant_1',
    ]));

    $response = $this->controller->submitResponse($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(400, $response->getStatusCode());
    $this->assertStringContainsString('score', $body['error']);
  }

  /**
   * Tests submitResponse rejects score below 0.
   */
  public function testSubmitResponseScoreTooLow(): void {
    $request = Request::create('/api/v1/cs/nps/submit', 'POST', [], [], [], [], json_encode([
      'tenant_id' => 'tenant_1',
      'score' => -1,
    ]));

    $response = $this->controller->submitResponse($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(400, $response->getStatusCode());
    $this->assertStringContainsString('between 0 and 10', $body['error']);
  }

  /**
   * Tests submitResponse rejects score above 10.
   */
  public function testSubmitResponseScoreTooHigh(): void {
    $request = Request::create('/api/v1/cs/nps/submit', 'POST', [], [], [], [], json_encode([
      'tenant_id' => 'tenant_1',
      'score' => 11,
    ]));

    $response = $this->controller->submitResponse($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(400, $response->getStatusCode());
    $this->assertStringContainsString('between 0 and 10', $body['error']);
  }

  /**
   * Tests submitResponse rejects when cooldown not elapsed.
   */
  public function testSubmitResponseCooldownNotElapsed(): void {
    $this->npsSurveyService->expects($this->once())
      ->method('canSendSurvey')
      ->with('tenant_1')
      ->willReturn(FALSE);

    $request = Request::create('/api/v1/cs/nps/submit', 'POST', [], [], [], [], json_encode([
      'tenant_id' => 'tenant_1',
      'score' => 8,
    ]));

    $response = $this->controller->submitResponse($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(429, $response->getStatusCode());
    $this->assertStringContainsString('cooldown', $body['error']);
  }

  /**
   * Tests getResults for a single tenant.
   */
  public function testGetResultsSingleTenant(): void {
    $this->npsSurveyService->expects($this->once())
      ->method('getScore')
      ->with('tenant_99')
      ->willReturn(45);

    $this->npsSurveyService->expects($this->once())
      ->method('getTrend')
      ->with('tenant_99', 12)
      ->willReturn([
        ['month' => '2026-01', 'score' => 40, 'responses' => 5],
        ['month' => '2026-02', 'score' => 45, 'responses' => 3],
      ]);

    $this->npsSurveyService->expects($this->once())
      ->method('getSatisfactionScore')
      ->with('tenant_99')
      ->willReturn(73);

    $request = Request::create('/api/v1/cs/nps/results?tenant_id=tenant_99', 'GET');

    $response = $this->controller->getResults($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('tenant_99', $body['data']['tenant_id']);
    $this->assertEquals(45, $body['data']['nps_score']);
    $this->assertEquals(73, $body['data']['satisfaction_score']);
    $this->assertCount(2, $body['data']['trend']);
  }

  /**
   * Tests getResults aggregated across all tenants.
   */
  public function testGetResultsAggregated(): void {
    // Mock entity type manager to return health entities.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('customer_health')
      ->willReturn($storage);

    $request = Request::create('/api/v1/cs/nps/results', 'GET');

    $response = $this->controller->getResults($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertArrayHasKey('data', $body);
    $this->assertNull($body['data']['nps_score']);
    $this->assertEquals(0, $body['data']['tenants_surveyed']);
  }

}
