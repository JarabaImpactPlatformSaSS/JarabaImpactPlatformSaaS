<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_i18n\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @covers ::getTranslationStats
   */
  public function testGetTranslationStatsCountsCorrectly(): void {
    $this->setUpLanguages([
      'es' => 'Spanish',
      'en' => 'English',
    ]);

    // Crear dos entidades: una con traduccion a 'en', otra sin.
    $entity1 = $this->createMock(TranslatableChangedEntityStub::class);
    $entity1->method('isTranslatable')->willReturn(TRUE);
    $originalLang1 = $this->createLanguageMock('es', 'Spanish');
    $entity1->method('getUntranslated')->willReturnSelf();
    $entity1->method('language')->willReturn($originalLang1);
    $entity1->method('hasTranslation')
      ->willReturnCallback(fn(string $langcode) => in_array($langcode, ['es', 'en'], TRUE));

    $entity2 = $this->createMock(TranslatableChangedEntityStub::class);
    $entity2->method('isTranslatable')->willReturn(TRUE);
    $originalLang2 = $this->createLanguageMock('es', 'Spanish');
    $entity2->method('getUntranslated')->willReturnSelf();
    $entity2->method('language')->willReturn($originalLang2);
    $entity2->method('hasTranslation')
      ->willReturnCallback(fn(string $langcode) => $langcode === 'es');

    // Mock storage.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([$entity1, $entity2]);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $stats = $this->service->getTranslationStats('page_content');

    $this->assertSame(2, $stats['total']);
    $this->assertArrayHasKey('translated', $stats);
    $this->assertArrayHasKey('missing', $stats);

    // Ambas entidades tienen 'es' (original), una tiene 'en'.
    $this->assertSame(2, $stats['translated']['es']);
    $this->assertSame(1, $stats['translated']['en']);
    // Solo entity2 le falta 'en'.
    $this->assertSame(1, $stats['missing']['en']);
    // Nadie le falta 'es' porque es el original de ambas.
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

}

/**
 * Stub interface que combina ContentEntityInterface y EntityChangedInterface.
 *
 * PHPUnit necesita una interfaz concreta para mockear multiples interfaces.
 */
interface TranslatableChangedEntityStub extends ContentEntityInterface, EntityChangedInterface {}
