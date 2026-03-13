<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_andalucia_ei\Service\AndaluciaEiCopilotBridgeService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AndaluciaEiCopilotBridgeService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\AndaluciaEiCopilotBridgeService
 * @group jaraba_andalucia_ei
 */
class AndaluciaEiCopilotBridgeServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected AndaluciaEiCopilotBridgeService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // hasDefinition devuelve FALSE por defecto para evitar queries reales.
    $this->entityTypeManager->method('hasDefinition')
      ->willReturn(FALSE);

    $this->service = new AndaluciaEiCopilotBridgeService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::getVerticalKey
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getVerticalKeyDevuelveAndaluciaEi(): void {
    $this->assertSame('andalucia_ei', $this->service->getVerticalKey());
  }

  /**
   * @covers ::getRelevantContext
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRelevantContextDevuelveArrayConClavesEsperadas(): void {
    // Sin hasDefinition, no se detecta coordinador ni participante.
    // Cae al fallback genérico.
    $context = $this->service->getRelevantContext(1);

    $this->assertIsArray($context);
    $this->assertArrayHasKey('vertical', $context);
    $this->assertArrayHasKey('solicitudes_pendientes', $context);
    $this->assertArrayHasKey('solicitudes_admitidas', $context);
    $this->assertArrayHasKey('participaciones_activas', $context);
    $this->assertSame('andalucia_ei', $context['vertical']);
  }

  /**
   * @covers ::getRelevantContext
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRelevantContextSinEntidadesDevuelveCeros(): void {
    // hasDefinition devuelve FALSE, asi que no se ejecutan queries.
    $context = $this->service->getRelevantContext(42);

    $this->assertSame(0, $context['solicitudes_pendientes']);
    $this->assertSame(0, $context['solicitudes_admitidas']);
    $this->assertSame(0, $context['participaciones_activas']);
  }

  /**
   * @covers ::getRelevantContext
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRelevantContextCoordinadorDevuelveContextoOperativo(): void {
    // Configurar user storage para detectar coordinador.
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->with('administer andalucia ei')
      ->willReturn(TRUE);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->with(99)
      ->willReturn($account);

    // hasDefinition: TRUE para user, FALSE para entities de datos.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('hasDefinition')
      ->willReturnCallback(fn(string $type): bool => $type === 'user');
    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(fn(string $type) => match ($type) {
        'user' => $userStorage,
        default => $this->createMock(EntityStorageInterface::class),
      });

    $service = new AndaluciaEiCopilotBridgeService(
      $this->entityTypeManager,
      $this->logger,
    );

    $context = $service->getRelevantContext(99);

    // Debe devolver contexto coordinador con _system_prompt_addition.
    $this->assertSame('coordinador', $context['rol_usuario']);
    $this->assertSame('andalucia_ei', $context['vertical']);
    $this->assertArrayHasKey('_system_prompt_addition', $context);
    $this->assertArrayHasKey('_modos_permitidos', $context);
    $this->assertArrayHasKey('_instrucciones_fase', $context);
    $this->assertNotEmpty($context['_system_prompt_addition']);
    $this->assertStringContainsString('COORDINACIÓN', $context['_system_prompt_addition']);
    $this->assertStringContainsString('Hub de Coordinación', $context['_system_prompt_addition']);
    $this->assertStringContainsString('ORDEN DE CONFIGURACIÓN', $context['_system_prompt_addition']);
    // Guardrails: must ground responses in the SaaS platform, not external sources.
    $this->assertStringContainsString('plataforma SaaS', $context['_system_prompt_addition']);
    $this->assertStringContainsString('PROHIBIDO', $context['_system_prompt_addition']);
    $this->assertStringContainsString('NO uses conocimiento externo', $context['_system_prompt_addition']);
  }

  /**
   * @covers ::getRelevantContext
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRelevantContextNoCoordinadorCaeAFallback(): void {
    // Usuario sin permiso de coordinador.
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->with('administer andalucia ei')
      ->willReturn(FALSE);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->with(50)
      ->willReturn($account);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('hasDefinition')
      ->willReturnCallback(fn(string $type): bool => $type === 'user');
    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(fn(string $type) => match ($type) {
        'user' => $userStorage,
        default => $this->createMock(EntityStorageInterface::class),
      });

    $service = new AndaluciaEiCopilotBridgeService(
      $this->entityTypeManager,
      $this->logger,
    );

    $context = $service->getRelevantContext(50);

    // Sin coordinador ni PIIL → cae a fallback genérico.
    $this->assertArrayNotHasKey('rol_usuario', $context);
    $this->assertArrayNotHasKey('_system_prompt_addition', $context);
    $this->assertSame('andalucia_ei', $context['vertical']);
  }

  /**
   * @covers ::getSoftSuggestion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getSoftSuggestionDevuelveNullSinContexto(): void {
    // Sin entidades definidas, no hay datos para sugerencia.
    $suggestion = $this->service->getSoftSuggestion(1);
    $this->assertNull($suggestion);
  }

  /**
   * @covers ::getMarketInsights
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getMarketInsightsDevuelveArrayConClavesEsperadas(): void {
    $insights = $this->service->getMarketInsights(1);

    $this->assertIsArray($insights);
    $this->assertArrayHasKey('total_programs', $insights);
    $this->assertArrayHasKey('active_programs', $insights);
    $this->assertArrayHasKey('total_participants', $insights);
    $this->assertSame(0, $insights['total_programs']);
  }

}
