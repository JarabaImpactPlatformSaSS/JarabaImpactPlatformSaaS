<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Entity;

use Drupal\ecosistema_jaraba_core\Entity\ReviewableEntityTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests para ReviewableEntityTrait.
 *
 * Verifica constantes, fallback de status, display de estrellas,
 * parsing de fotos y metodos de estado.
 *
 * @covers \Drupal\ecosistema_jaraba_core\Entity\ReviewableEntityTrait
 * @group ecosistema_jaraba_core
 * @group reviews
 */
class ReviewableEntityTraitTest extends TestCase {

  /**
   * Verifica que las constantes de estado existen con valores correctos.
   */
  public function testStatusConstants(): void {
    // En PHP 8.4 no se pueden acceder constantes de trait directamente.
    // Se acceden via una clase que use el trait.
    $entity = $this->createTraitMock([]);
    $this->assertSame('pending', $entity::STATUS_PENDING);
    $this->assertSame('approved', $entity::STATUS_APPROVED);
    $this->assertSame('rejected', $entity::STATUS_REJECTED);
    $this->assertSame('flagged', $entity::STATUS_FLAGGED);
  }

  /**
   * Tests getReviewStatus() con campo review_status (nombre canonico).
   */
  public function testGetReviewStatusFromReviewStatusField(): void {
    $entity = $this->createTraitMock(['review_status' => 'approved']);
    $this->assertSame('approved', $entity->getReviewStatus());
  }

  /**
   * Tests getReviewStatus() fallback a campo 'status' (legacy).
   */
  public function testGetReviewStatusFallbackToStatusField(): void {
    $entity = $this->createTraitMock(['status' => 'rejected']);
    $this->assertSame('rejected', $entity->getReviewStatus());
  }

  /**
   * Tests getReviewStatus() fallback a campo 'state' (legacy agro).
   */
  public function testGetReviewStatusFallbackToStateField(): void {
    $entity = $this->createTraitMock(['state' => 'flagged']);
    $this->assertSame('flagged', $entity->getReviewStatus());
  }

  /**
   * Tests getReviewStatus() devuelve 'pending' si no hay campo.
   */
  public function testGetReviewStatusDefaultPending(): void {
    $entity = $this->createTraitMock([]);
    $this->assertSame('pending', $entity->getReviewStatus());
  }

  /**
   * Tests isApprovedReview() para entidad aprobada.
   */
  public function testIsApprovedReviewTrue(): void {
    $entity = $this->createTraitMock(['review_status' => 'approved']);
    $this->assertTrue($entity->isApprovedReview());
  }

  /**
   * Tests isApprovedReview() para entidad no aprobada.
   */
  public function testIsApprovedReviewFalse(): void {
    $entity = $this->createTraitMock(['review_status' => 'pending']);
    $this->assertFalse($entity->isApprovedReview());
  }

  /**
   * Tests getReviewRating() con campo 'rating'.
   *
   * @dataProvider ratingProvider
   */
  public function testGetReviewRating(array $fields, int $expected): void {
    $entity = $this->createTraitMock($fields);
    $this->assertSame($expected, $entity->getReviewRating());
  }

  /**
   * Data provider para ratings.
   */
  public static function ratingProvider(): array {
    return [
      'rating 5' => [['rating' => '5'], 5],
      'rating 1' => [['rating' => '1'], 1],
      'rating 0 clamp' => [['rating' => '0'], 0],
      'negative clamp to 0' => [['rating' => '-1'], 0],
      'over 5 clamp to 5' => [['rating' => '10'], 5],
      'overall_rating fallback' => [['overall_rating' => '4'], 4],
      'no field returns 0' => [[], 0],
    ];
  }

  /**
   * Tests getRatingStarsDisplay() genera estrellas Unicode correctas.
   *
   * @dataProvider starsDisplayProvider
   */
  public function testGetRatingStarsDisplay(int $rating, string $expected): void {
    $entity = $this->createTraitMock(['rating' => (string) $rating]);
    $this->assertSame($expected, $entity->getRatingStarsDisplay());
  }

  /**
   * Data provider para estrellas.
   */
  public static function starsDisplayProvider(): array {
    return [
      '5 stars' => [5, '★★★★★'],
      '3 stars' => [3, '★★★☆☆'],
      '0 stars' => [0, '☆☆☆☆☆'],
      '1 star' => [1, '★☆☆☆☆'],
    ];
  }

  /**
   * Tests getReviewPhotos() parsea JSON correctamente.
   */
  public function testGetReviewPhotosValidJson(): void {
    $entity = $this->createTraitMock(['photos' => '[1,2,3]']);
    $this->assertSame([1, 2, 3], $entity->getReviewPhotos());
  }

  /**
   * Tests getReviewPhotos() con JSON invalido.
   */
  public function testGetReviewPhotosInvalidJson(): void {
    $entity = $this->createTraitMock(['photos' => 'not-json']);
    $this->assertSame([], $entity->getReviewPhotos());
  }

  /**
   * Tests getReviewPhotos() sin campo fotos.
   */
  public function testGetReviewPhotosNoField(): void {
    $entity = $this->createTraitMock([]);
    $this->assertSame([], $entity->getReviewPhotos());
  }

  /**
   * Tests getReviewStatusLabel() devuelve etiquetas legibles.
   */
  public function testGetReviewStatusLabel(): void {
    $entity = $this->createTraitMock(['review_status' => 'approved']);
    $label = $entity->getReviewStatusLabel();
    // La etiqueta contiene "Aprobada" (TranslatableMarkup se convierte a string).
    $this->assertStringContainsString('probada', $label);
  }

  /**
   * Tests getReviewStatusLabel() con estado desconocido devuelve el valor raw.
   */
  public function testGetReviewStatusLabelUnknownStatus(): void {
    $entity = $this->createTraitMock(['review_status' => 'custom_state']);
    $this->assertSame('custom_state', $entity->getReviewStatusLabel());
  }

  /**
   * Crea un mock que usa el trait con campos simulados.
   */
  private function createTraitMock(array $fields): object {
    return new class($fields) {
      use ReviewableEntityTrait;

      private array $fieldValues;

      public function __construct(array $fields) {
        $this->fieldValues = $fields;
      }

      public function hasField(string $name): bool {
        return array_key_exists($name, $this->fieldValues);
      }

      public function get(string $name): object {
        $value = $this->fieldValues[$name] ?? NULL;
        return new class($value) {
          public ?string $value;

          public function __construct(?string $v) {
            $this->value = $v;
          }

          public function isEmpty(): bool {
            return $this->value === NULL || $this->value === '';
          }
        };
      }
    };
  }

}
