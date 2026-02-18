<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal_intelligence\Entity\LegalResolution;
use Drupal\jaraba_legal_intelligence\Service\LegalCitationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for LegalCitationService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\Service\LegalCitationService
 * @group jaraba_legal_intelligence
 */
class LegalCitationServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_legal_intelligence\Service\LegalCitationService
   */
  protected LegalCitationService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tenantContext;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock feature gate service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $featureGate;

  /**
   * Mock resolution storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $resolutionStorage;

  /**
   * Mock bookmark storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $bookmarkStorage;

  /**
   * Mock citation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $citationStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->featureGate = $this->createMock(JarabaLexFeatureGateService::class);

    $this->resolutionStorage = $this->createMock(EntityStorageInterface::class);
    $this->bookmarkStorage = $this->createMock(EntityStorageInterface::class);
    $this->citationStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['legal_resolution', $this->resolutionStorage],
        ['legal_bookmark', $this->bookmarkStorage],
        ['legal_citation', $this->citationStorage],
      ]);

    $this->service = new LegalCitationService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
      $this->featureGate,
    );
  }

  /**
   * @covers ::generateCitation
   */
  public function testGenerateCitationInvalidFormatReturnsFalse(): void {
    $result = $this->service->generateCitation(1, 'invalid');

    $this->assertFalse($result['success']);
    $this->assertSame('', $result['citation']);
    $this->assertSame('invalid', $result['format']);
    $this->assertSame(1, $result['resolution_id']);
    $this->assertNotNull($result['error']);
    $this->assertStringContainsString('invalido', $result['error']);
  }

  /**
   * @covers ::generateCitation
   */
  public function testGenerateCitationValidFormats(): void {
    $validFormats = ['formal', 'resumida', 'bibliografica', 'nota_al_pie'];

    foreach ($validFormats as $format) {
      // Create a fresh mock entity for each format.
      $entity = $this->createMock(LegalResolution::class);
      $entity->method('formatCitation')
        ->with($format)
        ->willReturn('Citation text in ' . $format . ' format');

      $this->resolutionStorage->method('load')
        ->with(42)
        ->willReturn($entity);

      $result = $this->service->generateCitation(42, $format);

      $this->assertTrue($result['success'], "Format '{$format}' should succeed.");
      $this->assertSame('Citation text in ' . $format . ' format', $result['citation']);
      $this->assertSame($format, $result['format']);
      $this->assertSame(42, $result['resolution_id']);
      $this->assertNull($result['error']);
    }
  }

  /**
   * @covers ::generateCitation
   */
  public function testGenerateCitationResolutionNotFound(): void {
    $this->resolutionStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->generateCitation(999, 'formal');

    $this->assertFalse($result['success']);
    $this->assertSame('', $result['citation']);
    $this->assertNotNull($result['error']);
    $this->assertStringContainsString('999', $result['error']);
  }

  /**
   * @covers ::createBookmark
   */
  public function testCreateBookmarkNewBookmark(): void {
    // Mock entity query returning empty (no existing bookmark).
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $this->bookmarkStorage->method('getQuery')->willReturn($query);

    // Resolution exists.
    $resolution = $this->createMock(LegalResolution::class);
    $this->resolutionStorage->method('load')
      ->with(10)
      ->willReturn($resolution);

    // Mock bookmark creation.
    $bookmark = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $bookmark->method('id')->willReturn(100);
    $bookmark->method('save')->willReturn(1);
    $this->bookmarkStorage->method('create')->willReturn($bookmark);

    $result = $this->service->createBookmark(10, 5);

    $this->assertTrue($result['success']);
    $this->assertSame(100, $result['bookmark_id']);
    $this->assertTrue($result['created']);
    $this->assertNull($result['error']);
  }

  /**
   * @covers ::createBookmark
   */
  public function testCreateBookmarkDuplicate(): void {
    // Mock entity query returning an existing bookmark ID.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([77 => 77]);
    $this->bookmarkStorage->method('getQuery')->willReturn($query);

    $result = $this->service->createBookmark(10, 5);

    // Existing bookmark should be returned without creating a new one.
    $this->assertTrue($result['success']);
    $this->assertSame(77, $result['bookmark_id']);
    $this->assertFalse($result['created']);
    $this->assertNull($result['error']);
  }

  /**
   * @covers ::generateCitation
   */
  public function testValidFormatsConstant(): void {
    $reflection = new \ReflectionClass(LegalCitationService::class);
    $constant = $reflection->getReflectionConstant('VALID_FORMATS');

    $this->assertNotFalse($constant, 'VALID_FORMATS constant should exist.');

    $value = $constant->getValue();
    $this->assertIsArray($value);
    $this->assertSame(['formal', 'resumida', 'bibliografica', 'nota_al_pie'], $value);
  }

  /**
   * @covers ::attachToExpediente
   */
  public function testAttachToExpedienteInvalidFormat(): void {
    $result = $this->service->attachToExpediente(1, 2, 'invalid', 3);

    $this->assertFalse($result['success']);
    $this->assertSame(0, $result['citation_id']);
    $this->assertSame('', $result['citation_text']);
    $this->assertFalse($result['created']);
    $this->assertNotNull($result['error']);
    $this->assertStringContainsString('invalido', $result['error']);
  }

  /**
   * @covers ::detachFromExpediente
   */
  public function testDetachFromExpedienteNotFound(): void {
    // Citation not found returns success=true, deleted=false.
    $this->citationStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->detachFromExpediente(999, 1);

    $this->assertTrue($result['success']);
    $this->assertFalse($result['deleted']);
    $this->assertNull($result['error']);
  }

}
