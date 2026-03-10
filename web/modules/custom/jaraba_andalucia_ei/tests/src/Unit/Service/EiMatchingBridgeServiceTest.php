<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\EiMatchingBridgeService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para EiMatchingBridgeService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\EiMatchingBridgeService
 * @group jaraba_andalucia_ei
 */
class EiMatchingBridgeServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $storage;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->storage);
  }

  /**
   * Creates a fresh service instance.
   */
  protected function createService(
    ?object $matchingService = NULL,
    ?object $profileService = NULL,
    ?object $skillsService = NULL,
    ?object $tenantContext = NULL,
  ): EiMatchingBridgeService {
    return new EiMatchingBridgeService(
      $this->entityTypeManager,
      $this->logger,
      $matchingService,
      $profileService,
      $skillsService,
      $tenantContext,
    );
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithAllNulls(): void {
    $service = $this->createService();
    $this->assertInstanceOf(EiMatchingBridgeService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithSomeServices(): void {
    $matching = new class {
      public function match(): array {
        return [];
      }
    };
    $profile = new class {
      public function getProfile(): ?array {
        return NULL;
      }
    };

    $service = $this->createService(
      matchingService: $matching,
      profileService: $profile,
    );
    $this->assertInstanceOf(EiMatchingBridgeService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function sincronizarPerfilCandidatoReturnsFalseWhenParticipanteNotFound(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $service = $this->createService();
    $result = $service->sincronizarPerfilCandidato(999);

    $this->assertFalse($result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function sincronizarPerfilCandidatoReturnsFalseWhenNoProfileService(): void {
    // Create a minimal participante mock that passes the load check.
    $participante = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $this->storage->method('load')->with(1)->willReturn($participante);

    // No profileService injected — should return FALSE.
    $service = $this->createService();
    $result = $service->sincronizarPerfilCandidato(1);

    $this->assertFalse($result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function sincronizarPerfilCandidatoLogsWhenNoProfileService(): void {
    $participante = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('no disponible'),
        $this->arrayHasKey('@id'),
      );

    $service = $this->createService();
    $service->sincronizarPerfilCandidato(1);
  }

}
