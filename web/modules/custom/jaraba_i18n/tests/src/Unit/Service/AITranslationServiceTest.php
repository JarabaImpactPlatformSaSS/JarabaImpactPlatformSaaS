<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_i18n\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_ai_agents\Service\AgentOrchestrator;
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

  /**
   * Mock del orquestador de agentes.
   */
  private AgentOrchestrator&MockObject $orchestrator;

  /**
   * Mock del router de modelos.
   */
  private ModelRouterService&MockObject $modelRouter;

  /**
   * Mock de la config factory.
   */
  private ConfigFactoryInterface&MockObject $configFactory;

  /**
   * Mock del logger.
   */
  private LoggerInterface&MockObject $logger;

  /**
   * Mock del tenant context.
   */
  private TenantContextService&MockObject $tenantContext;

  /**
   * El servicio bajo test.
   */
  private AITranslationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->orchestrator = $this->createMock(AgentOrchestrator::class);
    $this->modelRouter = $this->createMock(ModelRouterService::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);

    $this->service = new AITranslationService(
      $this->orchestrator,
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
    $this->assertSame('', $result);

    $result = $this->service->translate('   ', 'es', 'en');
    $this->assertSame('   ', $result);
  }

  /**
   * Tests que cuando source y target son iguales retorna el original.
   *
   * @covers ::translate
   */
  public function testTranslateReturnsSameWhenSameLanguage(): void {
    $text = 'Hola mundo';
    $result = $this->service->translate($text, 'es', 'es');
    $this->assertSame($text, $result);
  }

  /**
   * Tests que translate llama al orquestador y retorna la traduccion.
   *
   * @covers ::translate
   */
  public function testTranslateCallsOrchestrator(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $this->orchestrator->expects($this->once())
      ->method('execute')
      ->willReturn(['content' => 'Hello world']);

    $result = $this->service->translate('Hola mundo', 'es', 'en');

    $this->assertSame('Hello world', $result);
  }

  /**
   * Tests que se lanza RuntimeException cuando el orquestador falla.
   *
   * @covers ::translate
   */
  public function testTranslateThrowsOnOrchestratorError(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $this->orchestrator->method('execute')
      ->willThrowException(new \RuntimeException('API error'));

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/Error en traducción/');

    $this->service->translate('Hola mundo', 'es', 'en');
  }

  /**
   * Tests que buildPrompt contiene el contexto de marca.
   *
   * @covers ::buildPrompt
   */
  public function testBuildPromptContainsBrandContext(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $method = new \ReflectionMethod(AITranslationService::class, 'buildPrompt');
    $method->setAccessible(TRUE);

    $prompt = $method->invoke($this->service, 'Hola', 'es', 'en', []);

    // Debe contener el fallback de brand context.
    $this->assertStringContainsString('profesional pero accesible', $prompt);
    $this->assertStringContainsString('CONTEXTO DE MARCA', $prompt);
  }

  /**
   * Tests que buildPrompt contiene nombres de idiomas legibles.
   *
   * @covers ::buildPrompt
   */
  public function testBuildPromptContainsLanguageNames(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $method = new \ReflectionMethod(AITranslationService::class, 'buildPrompt');
    $method->setAccessible(TRUE);

    $prompt = $method->invoke($this->service, 'Hola', 'es', 'en', []);

    $this->assertStringContainsString('español', $prompt);
    $this->assertStringContainsString('inglés', $prompt);
  }

  /**
   * Tests que formatGlossary produce el formato correcto.
   *
   * @covers ::formatGlossary
   */
  public function testFormatGlossaryFormatsCorrectly(): void {
    $method = new \ReflectionMethod(AITranslationService::class, 'formatGlossary');
    $method->setAccessible(TRUE);

    $glossary = [
      'empleabilidad' => 'employability',
      'emprendimiento' => 'entrepreneurship',
    ];

    $result = $method->invoke($this->service, $glossary);

    $this->assertStringContainsString('- empleabilidad → employability', $result);
    $this->assertStringContainsString('- emprendimiento → entrepreneurship', $result);
  }

  /**
   * Tests que formatGlossary retorna cadena vacia para glosario vacio.
   *
   * @covers ::formatGlossary
   */
  public function testFormatGlossaryReturnsEmptyForEmptyInput(): void {
    $method = new \ReflectionMethod(AITranslationService::class, 'formatGlossary');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, []);

    $this->assertSame('', $result);
  }

  /**
   * Tests que postProcess elimina whitespace extra.
   *
   * @covers ::postProcess
   */
  public function testPostProcessTrimsWhitespace(): void {
    $method = new \ReflectionMethod(AITranslationService::class, 'postProcess');
    $method->setAccessible(TRUE);

    $result = $method->invoke(
      $this->service,
      "  Hello world  \n",
      'Hola mundo',
      []
    );

    $this->assertSame('Hello world', $result);
  }

  /**
   * Tests que getBrandContext contiene el nombre del tenant cuando existe.
   *
   * @covers ::getBrandContext
   */
  public function testGetBrandContextWithTenant(): void {
    // Crear mock de tenant con label.
    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('label')->willReturn('TestCo');

    // Mock field values para slogan y vertical.
    $sloganField = new \stdClass();
    $sloganField->value = 'Impulsando el futuro';
    $verticalField = new \stdClass();
    $verticalField->value = 'empleabilidad';

    $tenant->method('get')
      ->willReturnCallback(function (string $field) use ($sloganField, $verticalField) {
        return match ($field) {
          'slogan' => $sloganField,
          'vertical' => $verticalField,
          default => (object) ['value' => NULL],
        };
      });

    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);

    $method = new \ReflectionMethod(AITranslationService::class, 'getBrandContext');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 'tenant-1');

    $this->assertStringContainsString('TestCo', $result);
    $this->assertStringContainsString('Impulsando el futuro', $result);
    $this->assertStringContainsString('empleabilidad', $result);
  }

  /**
   * Tests que getBrandContext retorna fallback sin tenant.
   *
   * @covers ::getBrandContext
   */
  public function testGetBrandContextFallback(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $method = new \ReflectionMethod(AITranslationService::class, 'getBrandContext');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, NULL);

    $this->assertStringContainsString('profesional pero accesible', $result);
    $this->assertStringContainsString('impacto social', $result);
  }

}
