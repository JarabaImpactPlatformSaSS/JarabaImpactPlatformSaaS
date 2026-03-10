<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\EiAlumniBridgeService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para EiAlumniBridgeService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\EiAlumniBridgeService
 * @group jaraba_andalucia_ei
 */
class EiAlumniBridgeServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $storage;
  protected QueryInterface $query;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->query = $this->createMock(QueryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->storage);

    // Standard entity query chain mocking.
    $this->query->method('accessCheck')->willReturnSelf();
    $this->query->method('condition')->willReturnSelf();
    $this->query->method('sort')->willReturnSelf();
    $this->query->method('range')->willReturnSelf();

    $this->storage->method('getQuery')
      ->willReturn($this->query);
  }

  /**
   * Creates a fresh service instance.
   */
  protected function createService(
    ?object $mentoringService = NULL,
    ?object $tenantContext = NULL,
  ): EiAlumniBridgeService {
    return new EiAlumniBridgeService(
      $this->entityTypeManager,
      $this->logger,
      $mentoringService,
      $tenantContext,
    );
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionSucceeds(): void {
    $service = $this->createService();
    $this->assertInstanceOf(EiAlumniBridgeService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithMentoringService(): void {
    $mentoring = new class {
      public function getMentor(): ?array {
        return NULL;
      }
    };
    $service = $this->createService(mentoringService: $mentoring);
    $this->assertInstanceOf(EiAlumniBridgeService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithAllOptionalServices(): void {
    $mentoring = new class {};
    $tenant = new class {};

    $service = $this->createService($mentoring, $tenant);
    $this->assertInstanceOf(EiAlumniBridgeService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlumniDirectoryReturnsEmptyArrayWhenNoResults(): void {
    $this->query->method('execute')->willReturn([]);

    $service = $this->createService();
    $result = $service->getAlumniDirectory(1);

    $this->assertIsArray($result);
    $this->assertSame([], $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlumniDirectoryReturnsEmptyArrayWithFilters(): void {
    $this->query->method('execute')->willReturn([]);

    $service = $this->createService();
    $result = $service->getAlumniDirectory(1, [
      'sector' => 'tecnologia',
      'tipo_insercion' => 'cuenta_ajena',
      'year' => '2025',
      'municipio' => 'Malaga',
    ]);

    $this->assertIsArray($result);
    $this->assertSame([], $result);
  }

}
