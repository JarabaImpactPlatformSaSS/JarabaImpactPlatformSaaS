<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Controller;

use Drupal\jaraba_analytics\Controller\ConsentController;
use Drupal\jaraba_analytics\Service\ConsentService;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests para ConsentController.
 *
 * @covers \Drupal\jaraba_analytics\Controller\ConsentController
 * @group jaraba_billing
 */
class ConsentControllerTest extends UnitTestCase {

  protected $consentService;
  protected ConsentController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->consentService = $this->createMock(ConsentService::class);

    // Use reflection to instantiate without container.
    $this->controller = new ConsentController($this->consentService);
  }

  /**
   * Tests status endpoint returns banner_required when no visitor_id.
   */
  public function testStatusWithNoVisitorId(): void {
    $request = Request::create('/api/v1/consent/status', 'GET');

    $response = $this->controller->status($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertFalse($body['has_consent']);
    $this->assertTrue($body['banner_required']);
    $this->assertTrue($body['categories']['necessary']);
    $this->assertFalse($body['categories']['analytics']);
  }

  /**
   * Tests status endpoint returns banner_required when no consent record.
   */
  public function testStatusWithVisitorIdButNoRecord(): void {
    $this->consentService->expects($this->once())
      ->method('getConsent')
      ->with('visitor_abc')
      ->willReturn(NULL);

    $request = Request::create('/api/v1/consent/status', 'GET');
    $request->cookies->set('jaraba_visitor_id', 'visitor_abc');

    $response = $this->controller->status($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertFalse($body['has_consent']);
    $this->assertTrue($body['banner_required']);
  }

  /**
   * Tests grant endpoint returns error on invalid JSON.
   */
  public function testGrantWithInvalidJson(): void {
    $request = Request::create('/api/v1/consent/grant', 'POST', [], [], [], [], '');

    $response = $this->controller->grant($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertFalse($body['success']);
    $this->assertEquals(400, $response->getStatusCode());
  }

  /**
   * Tests revoke endpoint returns error when no visitor_id.
   */
  public function testRevokeWithNoVisitorId(): void {
    $request = Request::create('/api/v1/consent/revoke', 'POST');

    $response = $this->controller->revoke($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertFalse($body['success']);
    $this->assertEquals(400, $response->getStatusCode());
  }

  /**
   * Tests revoke endpoint succeeds with visitor_id.
   */
  public function testRevokeSucceeds(): void {
    $this->consentService->expects($this->once())
      ->method('revokeConsent')
      ->with('visitor_xyz');

    $request = Request::create('/api/v1/consent/revoke', 'POST');
    $request->cookies->set('jaraba_visitor_id', 'visitor_xyz');

    $response = $this->controller->revoke($request);
    $body = json_decode($response->getContent(), TRUE);

    $this->assertTrue($body['success']);
    $this->assertEquals(200, $response->getStatusCode());
  }

}
