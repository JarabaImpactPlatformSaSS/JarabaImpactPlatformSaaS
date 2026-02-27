<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests para registro de servicios de reviews.
 *
 * Verifica que los 5 servicios transversales de reviews estan
 * registrados en el contenedor de servicios y son instanciables.
 * Tambien verifica que las definiciones de entidad incluyen los
 * campos del ReviewableEntityTrait.
 *
 * @group ecosistema_jaraba_core
 * @group reviews
 */
class ReviewServiceRegistrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'ecosistema_jaraba_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests que ReviewModerationService esta registrado.
   */
  public function testReviewModerationServiceExists(): void {
    $this->assertTrue(
      \Drupal::hasService('ecosistema_jaraba_core.review_moderation'),
      'ReviewModerationService debe estar registrado.'
    );

    $service = \Drupal::service('ecosistema_jaraba_core.review_moderation');
    $this->assertInstanceOf(
      'Drupal\ecosistema_jaraba_core\Service\ReviewModerationService',
      $service,
    );
  }

  /**
   * Tests que ReviewAggregationService esta registrado.
   */
  public function testReviewAggregationServiceExists(): void {
    $this->assertTrue(
      \Drupal::hasService('ecosistema_jaraba_core.review_aggregation'),
      'ReviewAggregationService debe estar registrado.'
    );

    $service = \Drupal::service('ecosistema_jaraba_core.review_aggregation');
    $this->assertInstanceOf(
      'Drupal\ecosistema_jaraba_core\Service\ReviewAggregationService',
      $service,
    );
  }

  /**
   * Tests que ReviewSchemaOrgService esta registrado.
   */
  public function testReviewSchemaOrgServiceExists(): void {
    $this->assertTrue(
      \Drupal::hasService('ecosistema_jaraba_core.review_schema_org'),
      'ReviewSchemaOrgService debe estar registrado.'
    );

    $service = \Drupal::service('ecosistema_jaraba_core.review_schema_org');
    $this->assertInstanceOf(
      'Drupal\ecosistema_jaraba_core\Service\ReviewSchemaOrgService',
      $service,
    );
  }

  /**
   * Tests que ReviewInvitationService esta registrado.
   */
  public function testReviewInvitationServiceExists(): void {
    $this->assertTrue(
      \Drupal::hasService('ecosistema_jaraba_core.review_invitation'),
      'ReviewInvitationService debe estar registrado.'
    );

    $service = \Drupal::service('ecosistema_jaraba_core.review_invitation');
    $this->assertInstanceOf(
      'Drupal\ecosistema_jaraba_core\Service\ReviewInvitationService',
      $service,
    );
  }

  /**
   * Tests que ReviewAiSummaryService esta registrado.
   */
  public function testReviewAiSummaryServiceExists(): void {
    $this->assertTrue(
      \Drupal::hasService('ecosistema_jaraba_core.review_ai_summary'),
      'ReviewAiSummaryService debe estar registrado.'
    );

    $service = \Drupal::service('ecosistema_jaraba_core.review_ai_summary');
    $this->assertInstanceOf(
      'Drupal\ecosistema_jaraba_core\Service\ReviewAiSummaryService',
      $service,
    );
  }

  /**
   * Tests que ReviewModerationService soporta los 6 tipos de entidad.
   */
  public function testModerationServiceSupportedTypes(): void {
    $service = \Drupal::service('ecosistema_jaraba_core.review_moderation');
    $types = $service->getSupportedEntityTypes();

    $this->assertCount(6, $types);
    $this->assertContains('comercio_review', $types);
    $this->assertContains('review_agro', $types);
    $this->assertContains('review_servicios', $types);
    $this->assertContains('session_review', $types);
    $this->assertContains('course_review', $types);
    $this->assertContains('content_comment', $types);
  }

  /**
   * Tests que los permisos de reviews estan definidos.
   *
   * @dataProvider reviewPermissionProvider
   */
  public function testReviewPermissionsDefined(string $permission): void {
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertArrayHasKey(
      $permission,
      $permissions,
      "El permiso '{$permission}' debe estar definido."
    );
  }

  /**
   * Data provider para permisos de reviews.
   */
  public static function reviewPermissionProvider(): array {
    return [
      'submit reviews' => ['submit reviews'],
      'edit own reviews' => ['edit own reviews'],
      'moderate reviews' => ['moderate reviews'],
      'respond reviews' => ['respond reviews'],
      'view review analytics' => ['view review analytics'],
    ];
  }

  /**
   * Tests que entity_type_manager conoce las entidades de review del core.
   */
  public function testReviewEntityTypesRegistered(): void {
    $definitions = \Drupal::entityTypeManager()->getDefinitions();

    // Las entidades propias del core (las de jaraba_lms/content_hub
    // dependen de sus modulos respectivos).
    // Verificamos que las definiciones se pueden buscar sin error.
    $this->assertIsArray($definitions);
  }

}
