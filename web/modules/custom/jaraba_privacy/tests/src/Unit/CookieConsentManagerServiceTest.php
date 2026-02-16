<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_privacy\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_privacy\Service\CookieConsentManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests para CookieConsentManagerService.
 *
 * Verifica el registro de consentimiento, revocación,
 * verificación por categoría y configuración del banner.
 *
 * @group jaraba_privacy
 * @coversDefaultClass \Drupal\jaraba_privacy\Service\CookieConsentManagerService
 */
class CookieConsentManagerServiceTest extends UnitTestCase {

  protected CookieConsentManagerService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected ConfigFactoryInterface $configFactory;
  protected RequestStack $requestStack;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new CookieConsentManagerService(
      $this->entityTypeManager,
      $this->configFactory,
      $this->requestStack,
      $this->logger,
    );
  }

  /**
   * Verifica que getBannerConfig devuelve la estructura correcta.
   *
   * @covers ::getBannerConfig
   */
  public function testGetBannerConfigReturnsCorrectStructure(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['enable_cookie_banner', TRUE],
      ['cookie_banner_position', 'bottom-bar'],
      ['cookie_expiry_days', 365],
    ]);
    $this->configFactory->method('get')
      ->with('jaraba_privacy.settings')
      ->willReturn($config);

    $result = $this->service->getBannerConfig(1);

    $this->assertArrayHasKey('enabled', $result);
    $this->assertArrayHasKey('position', $result);
    $this->assertArrayHasKey('expiry_days', $result);
    $this->assertArrayHasKey('categories', $result);
    $this->assertArrayHasKey('texts', $result);
  }

  /**
   * Verifica que las categorías del banner contienen las 5 obligatorias.
   *
   * @covers ::getBannerConfig
   */
  public function testBannerCategoriesContainAllRequired(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(NULL);
    $this->configFactory->method('get')->willReturn($config);

    $result = $this->service->getBannerConfig(1);
    $categories = $result['categories'];

    $this->assertArrayHasKey('necessary', $categories);
    $this->assertArrayHasKey('functional', $categories);
    $this->assertArrayHasKey('analytics', $categories);
    $this->assertArrayHasKey('marketing', $categories);
    $this->assertArrayHasKey('thirdparty', $categories);
  }

  /**
   * Verifica que la categoría 'necessary' es obligatoria.
   *
   * @covers ::getBannerConfig
   */
  public function testNecessaryCategoryIsRequired(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(NULL);
    $this->configFactory->method('get')->willReturn($config);

    $result = $this->service->getBannerConfig(1);
    $this->assertTrue($result['categories']['necessary']['required']);
  }

  /**
   * Verifica que getCurrentConsent devuelve NULL si no hay user_id ni session_id.
   *
   * @covers ::getCurrentConsent
   */
  public function testGetCurrentConsentReturnsNullWithoutIdentifiers(): void {
    $result = $this->service->getCurrentConsent(NULL, NULL);
    $this->assertNull($result);
  }

  /**
   * Verifica que getCurrentConsent devuelve NULL si no hay registros.
   *
   * @covers ::getCurrentConsent
   */
  public function testGetCurrentConsentReturnsNullWhenNoConsent(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('cookie_consent')
      ->willReturn($storage);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(365);
    $this->configFactory->method('get')->willReturn($config);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $result = $this->service->getCurrentConsent(1, NULL);
    $this->assertNull($result);
  }

  /**
   * Verifica que hasConsent devuelve FALSE sin consentimiento.
   *
   * @covers ::hasConsent
   */
  public function testHasConsentReturnsFalseWithoutConsent(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('cookie_consent')
      ->willReturn($storage);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(365);
    $this->configFactory->method('get')->willReturn($config);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->assertFalse($this->service->hasConsent('analytics', 1, NULL));
  }

  /**
   * Verifica que withdrawConsent lanza excepción con ID inválido.
   *
   * @covers ::withdrawConsent
   */
  public function testWithdrawConsentThrowsOnInvalidId(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('cookie_consent')
      ->willReturn($storage);

    $storage->method('load')->with(999)->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->service->withdrawConsent(999);
  }

}
