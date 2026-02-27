<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewTranslationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ReviewTranslationService (B-12).
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ReviewTranslationService
 */
class ReviewTranslationServiceTest extends UnitTestCase {

  protected ReviewTranslationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new ReviewTranslationService($entityTypeManager, $logger);
  }

  /**
   * @covers ::detectLanguage
   */
  public function testDetectSpanish(): void {
    $result = $this->service->detectLanguage('El servicio fue muy bueno y la atención es excelente para los clientes');
    $this->assertEquals('es', $result);
  }

  /**
   * @covers ::detectLanguage
   */
  public function testDetectEnglish(): void {
    $result = $this->service->detectLanguage('The service was great and you can have all the things you need');
    $this->assertEquals('en', $result);
  }

  /**
   * @covers ::detectLanguage
   */
  public function testDetectPortuguese(): void {
    $result = $this->service->detectLanguage('O servico foi muito bom e para os clientes é uma ótima opcao');
    $this->assertEquals('pt-br', $result);
  }

  /**
   * @covers ::detectLanguage
   */
  public function testDetectEmptyTextDefaultsSpanish(): void {
    $result = $this->service->detectLanguage('');
    $this->assertEquals('es', $result);
  }

  /**
   * @covers ::translate
   */
  public function testTranslateEmptyBody(): void {
    $entity = $this->createReviewEntity('');
    $result = $this->service->translate($entity, 'en');

    $this->assertEquals('', $result['translated_text']);
    $this->assertEquals('empty', $result['method']);
  }

  /**
   * @covers ::translate
   */
  public function testTranslateSameLanguage(): void {
    $entity = $this->createReviewEntity('El servicio fue muy bueno en la tienda de los productos');
    $result = $this->service->translate($entity, 'es');

    $this->assertEquals('same_language', $result['method']);
    $this->assertEquals('es', $result['source_language']);
    $this->assertEquals('es', $result['target_language']);
  }

  /**
   * @covers ::translate
   */
  public function testTranslateFallbackWhenNoAi(): void {
    $entity = $this->createReviewEntity('El servicio fue muy bueno en la tienda');
    $result = $this->service->translate($entity, 'en');

    $this->assertEquals('fallback', $result['method']);
    $this->assertEquals('es', $result['source_language']);
    $this->assertEquals('en', $result['target_language']);
  }

  /**
   * Helper: create mock review entity with body.
   *
   * MOCK-DYNPROP-001: Uses anonymous class for ->value access.
   */
  protected function createReviewEntity(string $body): ContentEntityInterface {
    $entity = $this->createMock(ContentEntityInterface::class);

    $entity->method('hasField')->willReturnCallback(function ($field) {
      return $field === 'body';
    });

    $bodyField = new class ($body) {
      public string $value;
      private bool $empty;

      public function __construct(string $v) {
        $this->value = $v;
        $this->empty = ($v === '');
      }

      public function isEmpty(): bool {
        return $this->empty;
      }
    };

    $entity->method('get')->willReturn($bodyField);

    return $entity;
  }

}
