<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
    $context = $this->service->getRelevantContext(1);

    $this->assertIsArray($context);
    $this->assertArrayHasKey('vertical', $context);
    $this->assertArrayHasKey('active_requests', $context);
    $this->assertArrayHasKey('pending_documents', $context);
    $this->assertArrayHasKey('approved_requests', $context);
    $this->assertArrayHasKey('active_programs', $context);
    $this->assertSame('andalucia_ei', $context['vertical']);
  }

  /**
   * @covers ::getRelevantContext
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRelevantContextSinEntidadesDevuelveCeros(): void {
    // hasDefinition devuelve FALSE, asi que no se ejecutan queries.
    $context = $this->service->getRelevantContext(42);

    $this->assertSame(0, $context['active_requests']);
    $this->assertSame(0, $context['pending_documents']);
    $this->assertSame(0, $context['approved_requests']);
    $this->assertSame(0, $context['active_programs']);
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
