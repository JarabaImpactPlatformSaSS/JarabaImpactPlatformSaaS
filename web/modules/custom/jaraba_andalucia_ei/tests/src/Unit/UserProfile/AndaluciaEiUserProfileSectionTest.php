<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\UserProfile;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\jaraba_andalucia_ei\UserProfile\AndaluciaEiUserProfileSection;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AndaluciaEiUserProfileSection.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\UserProfile\AndaluciaEiUserProfileSection
 * @group jaraba_andalucia_ei
 */
class AndaluciaEiUserProfileSectionTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected AndaluciaEiUserProfileSection $section;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Mock database connection.
   */
  protected Connection $database;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * Mock query.
   */
  protected QueryInterface $query;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->query = $this->createMock(QueryInterface::class);

    $this->query->method('accessCheck')->willReturnSelf();
    $this->query->method('condition')->willReturnSelf();
    $this->query->method('sort')->willReturnSelf();
    $this->query->method('range')->willReturnSelf();
    $this->query->method('count')->willReturnSelf();
    $this->query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($this->query);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->storage);

    // hasDefinition devuelve FALSE por defecto para mentor_profile.
    $this->entityTypeManager->method('hasDefinition')
      ->willReturn(FALSE);

    // currentUser sin permiso de coordinador.
    $this->currentUser->method('hasPermission')
      ->willReturn(FALSE);

    $this->section = new AndaluciaEiUserProfileSection(
      $this->entityTypeManager,
      $this->currentUser,
      NULL,
      $this->database,
      $this->logger,
    );

    // Inyectar string translation para evitar ContainerNotInitializedException.
    $stringTranslation = $this->createMock(TranslationInterface::class);
    $stringTranslation->method('translateString')
      ->willReturnCallback(fn ($input) => (string) $input->getUntranslatedString());
    $this->section->setStringTranslation($stringTranslation);
  }

  /**
   * @covers ::getId
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getIdDevuelveAndaluciaEiPrograma(): void {
    $this->assertEquals('andalucia_ei_programa', $this->section->getId());
  }

  /**
   * @covers ::getColor
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getColorDevuelveInnovation(): void {
    $this->assertEquals('innovation', $this->section->getColor());
  }

  /**
   * @covers ::getWeight
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getWeightDevuelve15(): void {
    $this->assertEquals(15, $this->section->getWeight());
  }

  /**
   * @covers ::getIcon
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getIconDevuelveArrayEsperado(): void {
    $icon = $this->section->getIcon();

    $this->assertIsArray($icon);
    $this->assertArrayHasKey('category', $icon);
    $this->assertArrayHasKey('name', $icon);
    $this->assertEquals('verticals', $icon['category']);
    $this->assertEquals('andalucia-ei', $icon['name']);
  }

  /**
   * @covers ::isApplicable
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function isApplicableSinParticipanteNiOrientadorNiCoordinador(): void {
    // Query devuelve vacio (sin participante).
    // hasDefinition FALSE (sin mentor_profile).
    // hasPermission FALSE (sin coordinador).
    $result = $this->section->isApplicable(42);

    $this->assertFalse($result);
  }

  /**
   * @covers ::isApplicable
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function isApplicableConPermisoCoordinador(): void {
    // Recrear con permiso de coordinador.
    $currentUser = $this->createMock(AccountProxyInterface::class);
    $currentUser->method('hasPermission')
      ->willReturn(TRUE);

    $section = new AndaluciaEiUserProfileSection(
      $this->entityTypeManager,
      $currentUser,
      NULL,
      $this->database,
      $this->logger,
    );

    $stringTranslation = $this->createMock(TranslationInterface::class);
    $stringTranslation->method('translateString')
      ->willReturnCallback(fn ($input) => $input);
    $section->setStringTranslation($stringTranslation);

    $result = $section->isApplicable(42);

    $this->assertTrue($result);
  }

  /**
   * @covers ::getTitle
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getTitleSinRolesDevuelveTituloBase(): void {
    $title = $this->section->getTitle(42);

    $this->assertIsString($title);
    $this->assertStringContainsString('Andalucia +ei', $title);
  }

  /**
   * @covers ::getSubtitle
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getSubtitleSinRolesDevuelveTextoGeneral(): void {
    $subtitle = $this->section->getSubtitle(42);

    $this->assertIsString($subtitle);
    $this->assertStringContainsString('Programa Andalucia +ei', $subtitle);
  }

}
