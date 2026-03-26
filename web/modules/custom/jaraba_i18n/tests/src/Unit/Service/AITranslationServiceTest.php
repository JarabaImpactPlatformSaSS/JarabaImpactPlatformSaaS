<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_i18n\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_i18n\Service\AITranslationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AITranslationService.
 *
 * @group jaraba_i18n
 * @coversDefaultClass \Drupal\jaraba_i18n\Service\AITranslationService
 */
class AITranslationServiceTest extends TestCase {

  private ModelRouterService&MockObject $modelRouter;
  private ConfigFactoryInterface&MockObject $configFactory;
  private LoggerInterface&MockObject $logger;
  private TenantContextService&MockObject $tenantContext;
  private AITranslationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->modelRouter = $this->createMock(ModelRouterService::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);

    $this->service = new AITranslationService(
      NULL,
      $this->modelRouter,
      $this->configFactory,
      $this->logger,
      $this->tenantContext,
    );
  }

  /**
   * Tests que texto vacio retorna el mismo texto.
   *
   * @covers ::translate
   */
  public function testTranslateReturnsOriginalWhenEmpty(): void {
    $result = $this->service->translate('', 'es', 'en');
    static::assertSame('', $result);

    $result = $this->service->translate('   ', 'es', 'en');
    static::assertSame('   ', $result);
  }

  /**
   * Tests que cuando source y target son iguales retorna el original.
   *
   * @covers ::translate
   */
  public function testTranslateReturnsSameWhenSameLanguage(): void {
    $text = 'Hola mundo';
    $result = $this->service->translate($text, 'es', 'es');
    static::assertSame($text, $result);
  }

  /**
   * Tests que translate lanza excepcion sin AI Provider.
   *
   * El AI Provider framework no esta disponible en unit tests.
   *
   * @covers ::translate
   */
  public function testTranslateThrowsWithoutProvider(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/traduccion/i');

    $this->service->translate('Hola mundo', 'es', 'en');
  }

  /**
   * Tests que buildSystemPrompt contiene nombres de idiomas legibles.
   *
   * @covers ::buildSystemPrompt
   */
  public function testBuildSystemPromptContainsLanguageNames(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $method = new \ReflectionMethod(AITranslationService::class, 'buildSystemPrompt');

    $prompt = $method->invoke($this->service, 'es', 'en');

    static::assertStringContainsString('español', $prompt);
    static::assertStringContainsString('inglés', $prompt);
    static::assertStringContainsString('profesional pero accesible', $prompt);
  }

  /**
   * Tests que cleanResult elimina artefactos comunes.
   *
   * @covers ::cleanResult
   */
  public function testCleanResultRemovesCodeFences(): void {
    $method = new \ReflectionMethod(AITranslationService::class, 'cleanResult');

    $result = $method->invoke($this->service, "```html\n<p>Hello</p>\n```", '<p>Hola</p>');
    static::assertSame('<p>Hello</p>', $result);
  }

  /**
   * Tests que cleanResult detecta alucinaciones.
   *
   * @covers ::cleanResult
   */
  public function testCleanResultDetectsHallucination(): void {
    $method = new \ReflectionMethod(AITranslationService::class, 'cleanResult');

    $result = $method->invoke(
      $this->service,
      "I'm ready to translate this for you. Here is the translation: Hello",
      'Hola mundo',
    );

    // Debe devolver el texto original al detectar alucinacion.
    static::assertSame('Hola mundo', $result);
  }

  /**
   * Tests que cleanResult preserva texto limpio.
   *
   * @covers ::cleanResult
   */
  public function testCleanResultPreservesCleanText(): void {
    $method = new \ReflectionMethod(AITranslationService::class, 'cleanResult');

    $result = $method->invoke($this->service, '  Hello world  ', 'Hola mundo');
    static::assertSame('Hello world', $result);
  }

  /**
   * Tests que getBrandContext retorna fallback sin tenant.
   *
   * @covers ::getBrandContext
   */
  public function testGetBrandContextFallback(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $method = new \ReflectionMethod(AITranslationService::class, 'getBrandContext');

    $result = $method->invoke($this->service);

    static::assertStringContainsString('profesional pero accesible', $result);
    static::assertStringContainsString('impacto social', $result);
  }

  /**
   * Tests que getBrandContext incluye datos del tenant.
   *
   * @covers ::getBrandContext
   */
  public function testGetBrandContextWithTenant(): void {
    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('label')->willReturn('TestCo');

    $sloganField = new class {

      public string $value = 'Impulsando el futuro';

    };
    $verticalField = new class {

      public string $value = 'empleabilidad';

    };

    $tenant->method('get')
      ->willReturnCallback(function (string $field) use ($sloganField, $verticalField) {
        return match ($field) {
          'slogan' => $sloganField,
          'vertical' => $verticalField,
          default => new class {

            public ?string $value = NULL;

          },
        };
      });

    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);

    $method = new \ReflectionMethod(AITranslationService::class, 'getBrandContext');

    $result = $method->invoke($this->service);

    static::assertStringContainsString('TestCo', $result);
    static::assertStringContainsString('Impulsando el futuro', $result);
    static::assertStringContainsString('empleabilidad', $result);
  }

  /**
   * Tests que translateBatch retorna vacio para input vacio.
   *
   * @covers ::translateBatch
   */
  public function testTranslateBatchReturnsEmptyForEmptyInput(): void {
    $result = $this->service->translateBatch([], 'es', 'en');
    static::assertSame([], $result);
  }

}
