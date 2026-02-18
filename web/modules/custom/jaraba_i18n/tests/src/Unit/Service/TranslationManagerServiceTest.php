<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_i18n\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\jaraba_i18n\Service\TranslationManagerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para TranslationManagerService.
 *
 * @group jaraba_i18n
 * @coversDefaultClass \Drupal\jaraba_i18n\Service\TranslationManagerService
 */
class TranslationManagerServiceTest extends TestCase {

  /**
   * Mock del entity type manager.
   */
  private EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del language manager.
   */
  private LanguageManagerInterface&MockObject $languageManager;

  /**
   * Mock de la config factory.
   */
  private ConfigFactoryInterface&MockObject $configFactory;

  /**
   * Mock del logger.
   */
  private LoggerInterface&MockObject $logger;

  /**
   * El servicio bajo test.
   */
  private TranslationManagerService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new TranslationManagerService(
      $this->entityTypeManager,
      $this->languageManager,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Crea un mock de LanguageInterface.
   *
   * @param string $langcode
   *   Codigo de idioma.
   * @param string $name
   *   Nombre del idioma.
   *
   * @return \Drupal\Core\Language\LanguageInterface&\PHPUnit\Framework\MockObject\MockObject
   *   Mock del idioma.
   */
  private function createLanguageMock(string $langcode, string $name): LanguageInterface&MockObject {
    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn($langcode);
    $language->method('getName')->willReturn($name);
    return $language;
  }

  /**
   * Configura los idiomas disponibles en el mock del language manager.
   *
   * @param array<string, string> $languages
   *   Array [langcode => name].
   */
  private function setUpLanguages(array $languages): void {
    $mocks = [];
    foreach ($languages as $langcode => $name) {
      $mocks[$langcode] = $this->createLanguageMock($langcode, $name);
    }
    $this->languageManager->method('getLanguages')->willReturn($mocks);
  }

  /**
   * Tests que getTranslationStatus retorna complete cuando hay traducciones para todos los idiomas.
   *
   * @covers ::getTranslationStatus
   */
  public function testGetTranslationStatusReturnsCompleteForAllLanguages(): void {
    $this->setUpLanguages([
      'es' => 'Spanish',
      'en' => 'English',
      'ca' => 'Catalan',
    ]);

    // Crear mock de entidad con interfaz EntityChangedInterface.
    $entity = $this->createMock(TranslatableChangedEntityStub::class);
    $entity->method('isTranslatable')->willReturn(TRUE);

    // Entidad sin traducir (original en 'es').
    $originalLanguage = $this->createLanguageMock('es', 'Spanish');
    $entity->method('getUntranslated')->willReturnSelf();
    $entity->method('language')->willReturn($originalLanguage);
    $entity->method('getChangedTime')->willReturn(1000);

    // Tiene traducciones para todos los idiomas.
    $entity->method('hasTranslation')->willReturn(TRUE);

    // Mock para getTranslation - retorna una entidad con timestamps.
    $translation = $this->createMock(TranslatableChangedEntityStub::class);
    $translation->method('getChangedTime')->willReturn(2000);
    $entity->method('getTranslation')->willReturn($translation);

    $status = $this->service->getTranslationStatus($entity);

    $this->assertCount(3, $status);
    foreach ($status as $langcode => $info) {
      $this->assertTrue($info['exists'], "El idioma {$langcode} debe tener traduccion.");
    }
  }

  /**
   * Tests que getTranslationStatus detecta traducciones faltantes.
   *
   * @covers ::getTranslationStatus
   */
  public function testGetTranslationStatusReturnsMissing(): void {
    $this->setUpLanguages([
      'es' => 'Spanish',
      'en' => 'English',
      'fr' => 'French',
    ]);

    $entity = $this->createMock(TranslatableChangedEntityStub::class);
    $entity->method('isTranslatable')->willReturn(TRUE);

    $originalLanguage = $this->createLanguageMock('es', 'Spanish');
    $entity->method('getUntranslated')->willReturnSelf();
    $entity->method('language')->willReturn($originalLanguage);
    $entity->method('getChangedTime')->willReturn(1000);

    // Solo tiene traduccion para 'es' y 'en', falta 'fr'.
    $entity->method('hasTranslation')
      ->willReturnCallback(fn(string $langcode) => in_array($langcode, ['es', 'en'], TRUE));

    $translation = $this->createMock(TranslatableChangedEntityStub::class);
    $translation->method('getChangedTime')->willReturn(2000);
    $entity->method('getTranslation')->willReturn($translation);

    $status = $this->service->getTranslationStatus($entity);

    $this->assertTrue($status['es']['exists']);
    $this->assertTrue($status['en']['exists']);
    $this->assertFalse($status['fr']['exists']);
  }

  /**
   * Tests que getTranslationStats retorna la estructura correcta.
   *
   * AUDIT-PERF-N09: Updated test to match the optimized query-based
   * implementation that uses count queries per language instead of
   * loading all entities with loadMultiple.
   *
   * @covers ::getTranslationStats
   */
  public function testGetTranslationStatsCountsCorrectly(): void {
    $this->setUpLanguages([
      'es' => 'Spanish',
      'en' => 'English',
    ]);

    // Mock the default language for getTranslationStats.
    $defaultLanguage = $this->createLanguageMock('es', 'Spanish');
    $this->languageManager->method('getDefaultLanguage')->willReturn($defaultLanguage);

    // Track query calls to return different counts per condition.
    $queryCallIndex = 0;

    // Create a query mock factory that returns the correct count for each call.
    // Call order: 1) total count, 2) 'es' langcode count, 3) 'en' langcode count.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturnCallback(
      function () use (&$queryCallIndex) {
        $queryCallIndex++;
        $currentCall = $queryCallIndex;
        $query = $this->createMock(QueryInterface::class);
        $query->method('accessCheck')->willReturnSelf();
        $query->method('condition')->willReturnSelf();
        $query->method('count')->willReturnSelf();
        $query->method('execute')->willReturnCallback(
          function () use ($currentCall) {
            // Call 1: total count = 2 entities.
            // Call 2: 'es' langcode count = 2 (both entities have 'es').
            // Call 3: 'en' langcode count = 1 (only one entity has 'en').
            return match ($currentCall) {
              1 => 2,
              2 => 2,
              3 => 1,
              default => 0,
            };
          }
        );
        return $query;
      }
    );

    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $stats = $this->service->getTranslationStats('page_content');

    $this->assertSame(2, $stats['total']);
    $this->assertArrayHasKey('translated', $stats);
    $this->assertArrayHasKey('missing', $stats);

    // Ambas entidades tienen 'es' (original), una tiene 'en'.
    $this->assertSame(2, $stats['translated']['es']);
    $this->assertSame(1, $stats['translated']['en']);
    // Solo una entidad le falta 'en'.
    $this->assertSame(1, $stats['missing']['en']);
    // 'es' is the default language, so missing is always 0.
    $this->assertSame(0, $stats['missing']['es']);
  }

  /**
   * Tests que una entidad no-translatable retorna array vacio.
   *
   * @covers ::getTranslationStatus
   */
  public function testNonTranslatableEntityReturnsEmpty(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('isTranslatable')->willReturn(FALSE);

    $status = $this->service->getTranslationStatus($entity);

    $this->assertSame([], $status);
  }

  /**
   * Tests getBatchTranslationStatus batch-loads entities and returns status.
   *
   * AUDIT-PERF-N09: Verifies that the new batch method loads all entities
   * in a single loadMultiple call and returns correct per-entity status.
   *
   * @covers ::getBatchTranslationStatus
   */
  public function testGetBatchTranslationStatusLoadsInBatch(): void {
    $this->setUpLanguages([
      'es' => 'Spanish',
      'en' => 'English',
    ]);

    // Entity 1: original in 'es', has 'en' translation (up to date).
    $entity1 = $this->createMock(TranslatableChangedEntityStub::class);
    $entity1->method('isTranslatable')->willReturn(TRUE);
    $originalLang1 = $this->createLanguageMock('es', 'Spanish');
    $entity1->method('getUntranslated')->willReturnSelf();
    $entity1->method('language')->willReturn($originalLang1);
    $entity1->method('getChangedTime')->willReturn(1000);
    $entity1->method('hasTranslation')
      ->willReturnCallback(fn(string $langcode) => in_array($langcode, ['es', 'en'], TRUE));

    $translation1 = $this->createMock(TranslatableChangedEntityStub::class);
    $translation1->method('getChangedTime')->willReturn(2000);
    $entity1->method('getTranslation')->willReturn($translation1);

    // Entity 2: original in 'es', no 'en' translation.
    $entity2 = $this->createMock(TranslatableChangedEntityStub::class);
    $entity2->method('isTranslatable')->willReturn(TRUE);
    $originalLang2 = $this->createLanguageMock('es', 'Spanish');
    $entity2->method('getUntranslated')->willReturnSelf();
    $entity2->method('language')->willReturn($originalLang2);
    $entity2->method('getChangedTime')->willReturn(1500);
    $entity2->method('hasTranslation')
      ->willReturnCallback(fn(string $langcode) => $langcode === 'es');

    // Mock storage — loadMultiple should be called exactly once with both IDs.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([1 => $entity1, 2 => $entity2]);

    $this->entityTypeManager->method('getStorage')
      ->with('page_content')
      ->willReturn($storage);

    $results = $this->service->getBatchTranslationStatus('page_content', [1, 2]);

    // Verify entity 1 status.
    $this->assertArrayHasKey(1, $results);
    $this->assertTrue($results[1]['es']['exists']);
    $this->assertTrue($results[1]['es']['is_original']);
    $this->assertTrue($results[1]['en']['exists']);
    $this->assertFalse($results[1]['en']['outdated']);

    // Verify entity 2 status.
    $this->assertArrayHasKey(2, $results);
    $this->assertTrue($results[2]['es']['exists']);
    $this->assertTrue($results[2]['es']['is_original']);
    $this->assertFalse($results[2]['en']['exists']);
  }

  /**
   * Tests getBatchTranslationStatus returns empty for empty input.
   *
   * @covers ::getBatchTranslationStatus
   */
  public function testGetBatchTranslationStatusEmptyInput(): void {
    $results = $this->service->getBatchTranslationStatus('page_content', []);
    $this->assertSame([], $results);
  }

  /**
   * Tests getBatchTranslationStatus detects outdated translations.
   *
   * @covers ::getBatchTranslationStatus
   */
  public function testGetBatchTranslationStatusDetectsOutdated(): void {
    $this->setUpLanguages([
      'es' => 'Spanish',
      'en' => 'English',
    ]);

    // Entity with outdated 'en' translation (original changed after translation).
    $entity = $this->createMock(TranslatableChangedEntityStub::class);
    $entity->method('isTranslatable')->willReturn(TRUE);
    $originalLang = $this->createLanguageMock('es', 'Spanish');
    $entity->method('getUntranslated')->willReturnSelf();
    $entity->method('language')->willReturn($originalLang);
    $entity->method('getChangedTime')->willReturn(3000);
    $entity->method('hasTranslation')
      ->willReturnCallback(fn(string $langcode) => in_array($langcode, ['es', 'en'], TRUE));

    $outdatedTranslation = $this->createMock(TranslatableChangedEntityStub::class);
    $outdatedTranslation->method('getChangedTime')->willReturn(1000);
    $entity->method('getTranslation')->willReturn($outdatedTranslation);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([5 => $entity]);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $results = $this->service->getBatchTranslationStatus('page_content', [5]);

    $this->assertArrayHasKey(5, $results);
    $this->assertTrue($results[5]['en']['exists']);
    $this->assertTrue($results[5]['en']['outdated'], 'Translation should be detected as outdated.');
  }

}

/**
 * Stub interface que combina ContentEntityInterface y EntityChangedInterface.
 *
 * PHPUnit necesita una interfaz concreta para mockear multiples interfaces.
 */
interface TranslatableChangedEntityStub extends ContentEntityInterface, EntityChangedInterface {}
