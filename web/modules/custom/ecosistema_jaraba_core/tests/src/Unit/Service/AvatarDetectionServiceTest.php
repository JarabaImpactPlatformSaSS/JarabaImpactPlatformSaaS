<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService;
use Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests del servicio AvatarDetectionService.
 *
 * Verifica los 4 niveles de cascada de deteccion de avatar:
 * Domain > Path/UTM > Group > Rol > Default.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService
 * @group ecosistema_jaraba_core
 */
class AvatarDetectionServiceTest extends UnitTestCase {

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock del usuario actual.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Mock de la ruta actual.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Mock de la pila de peticiones.
   */
  protected RequestStack $requestStack;

  /**
   * Mock del logger.
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
  }

  /**
   * Crea el servicio con mocks.
   */
  protected function createService(): AvatarDetectionService {
    return new AvatarDetectionService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->routeMatch,
      $this->requestStack,
      $this->logger,
    );
  }

  /**
   * Configura un Request mock con host, path y UTM.
   */
  protected function mockRequest(string $host = 'jaraba-saas.lndo.site', string $path = '/', string $utmCampaign = ''): void {
    $request = $this->createMock(Request::class);
    $request->method('getHost')->willReturn($host);
    $request->method('getPathInfo')->willReturn($path);
    $request->query = new InputBag(['utm_campaign' => $utmCampaign]);
    $this->requestStack->method('getCurrentRequest')->willReturn($request);
  }

  /**
   * Test 1: Deteccion por subdominio 'empleo'.
   *
   * @covers ::detect
   * @covers ::detectByDomain
   */
  public function testDetectByDomainEmpleo(): void {
    $this->mockRequest('empleo.jaraba.es');
    $this->currentUser->method('isAuthenticated')->willReturn(FALSE);

    $service = $this->createService();
    $result = $service->detect();

    $this->assertInstanceOf(AvatarDetectionResult::class, $result);
    $this->assertEquals('jobseeker', $result->avatarType);
    $this->assertEquals('empleabilidad', $result->vertical);
    $this->assertEquals('domain', $result->detectionSource);
    $this->assertEquals(1.0, $result->confidence);
    $this->assertTrue($result->isDetected());
  }

  /**
   * Test 2: Deteccion por subdominio 'emprender'.
   *
   * @covers ::detect
   * @covers ::detectByDomain
   */
  public function testDetectByDomainEmprender(): void {
    $this->mockRequest('emprender.jaraba.es');
    $this->currentUser->method('isAuthenticated')->willReturn(FALSE);

    $service = $this->createService();
    $result = $service->detect();

    $this->assertEquals('entrepreneur', $result->avatarType);
    $this->assertEquals('emprendimiento', $result->vertical);
    $this->assertEquals('domain', $result->detectionSource);
    $this->assertEquals(1.0, $result->confidence);
  }

  /**
   * Test 3: Deteccion por UTM campaign.
   *
   * @covers ::detect
   * @covers ::detectByPathOrUtm
   */
  public function testDetectByUtmCampaign(): void {
    $this->mockRequest('jaraba-saas.lndo.site', '/', 'empleabilidad_2026');
    $this->currentUser->method('isAuthenticated')->willReturn(FALSE);

    $service = $this->createService();
    $result = $service->detect();

    $this->assertEquals('jobseeker', $result->avatarType);
    $this->assertEquals('empleabilidad', $result->vertical);
    $this->assertEquals('utm', $result->detectionSource);
    $this->assertEquals('Programa Empleabilidad 2026', $result->programaOrigen);
    $this->assertEquals(0.9, $result->confidence);
  }

  /**
   * Test 4: Deteccion por path /empleabilidad.
   *
   * @covers ::detect
   * @covers ::detectByPathOrUtm
   */
  public function testDetectByPathEmpleabilidad(): void {
    $this->mockRequest('jaraba-saas.lndo.site', '/empleabilidad/diagnostico');
    $this->currentUser->method('isAuthenticated')->willReturn(FALSE);

    $service = $this->createService();
    $result = $service->detect();

    $this->assertEquals('jobseeker', $result->avatarType);
    $this->assertEquals('empleabilidad', $result->vertical);
    $this->assertEquals('path', $result->detectionSource);
    $this->assertEquals(0.8, $result->confidence);
  }

  /**
   * Test 5: Deteccion por rol 'candidate'.
   *
   * @covers ::detect
   * @covers ::detectByRole
   */
  public function testDetectByRoleCandidate(): void {
    $this->mockRequest('jaraba-saas.lndo.site', '/dashboard');
    $this->currentUser->method('isAuthenticated')->willReturn(TRUE);
    $this->currentUser->method('id')->willReturn(42);

    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated', 'candidate']);
    $this->currentUser->method('getAccount')->willReturn($account);

    // No tenants encontrados para este usuario.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('tenant')
      ->willReturn($storage);

    $service = $this->createService();
    $result = $service->detect();

    $this->assertEquals('jobseeker', $result->avatarType);
    $this->assertEquals('empleabilidad', $result->vertical);
    $this->assertEquals('role', $result->detectionSource);
    $this->assertEquals(0.6, $result->confidence);
  }

  /**
   * Test 6: Default cuando no se detecta nada.
   *
   * @covers ::detect
   */
  public function testDetectDefaultWhenNoMatch(): void {
    $this->mockRequest('unknown.example.com', '/some-random-page');
    $this->currentUser->method('isAuthenticated')->willReturn(FALSE);

    $service = $this->createService();
    $result = $service->detect();

    $this->assertEquals('general', $result->avatarType);
    $this->assertNull($result->vertical);
    $this->assertEquals('default', $result->detectionSource);
    $this->assertEquals(0.1, $result->confidence);
    $this->assertFalse($result->isDetected());
  }

  /**
   * Test 7: Domain tiene prioridad sobre path y UTM.
   *
   * @covers ::detect
   */
  public function testDomainTakesPriorityOverPathAndUtm(): void {
    // Domain = empleo, Path = /emprendimiento, UTM = emprende_andalucia.
    $this->mockRequest('empleo.jaraba.es', '/emprendimiento', 'emprende_andalucia');
    $this->currentUser->method('isAuthenticated')->willReturn(FALSE);

    $service = $this->createService();
    $result = $service->detect();

    // Domain gana sobre path y UTM.
    $this->assertEquals('jobseeker', $result->avatarType);
    $this->assertEquals('domain', $result->detectionSource);
    $this->assertEquals(1.0, $result->confidence);
  }

}
