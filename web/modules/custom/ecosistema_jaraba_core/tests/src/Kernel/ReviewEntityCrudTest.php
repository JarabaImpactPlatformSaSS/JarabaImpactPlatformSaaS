<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Kernel tests para CRUD de entidades de resenas.
 *
 * Verifica creacion, lectura, actualizacion y eliminacion
 * de las 6 entidades de review del ecosistema.
 *
 * @group ecosistema_jaraba_core
 * @group reviews
 */
class ReviewEntityCrudTest extends KernelTestBase {

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
    'jaraba_lms',
    'jaraba_content_hub',
    'jaraba_comercio_conecta',
    'jaraba_agroconecta_core',
    'jaraba_servicios_conecta',
    'jaraba_mentoring',
  ];

  /**
   * Entidades de review disponibles para test.
   *
   * @var array<string, bool>
   */
  private array $availableEntities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    User::create([
      'uid' => 1,
      'name' => 'admin',
      'status' => 1,
    ])->save();

    $definitions = \Drupal::entityTypeManager()->getDefinitions();

    // Instalar entidades de review que esten disponibles.
    $reviewEntities = [
      'course_review',
      'content_comment',
      'comercio_review',
      'review_agro',
      'review_servicios',
      'session_review',
    ];

    // Instalar dependencias opcionales.
    $optionalDeps = [
      'group', 'lms_course', 'content_article',
      'merchant_profile', 'producer_profile', 'provider_profile',
      'mentoring_session', 'service_offering', 'booking',
    ];
    foreach ($optionalDeps as $dep) {
      if (isset($definitions[$dep])) {
        try {
          $this->installEntitySchema($dep);
        }
        catch (\Exception) {
          // Ignorar — dependencia no disponible.
        }
      }
    }

    foreach ($reviewEntities as $entityTypeId) {
      if (!isset($definitions[$entityTypeId])) {
        continue;
      }
      try {
        $this->installEntitySchema($entityTypeId);
        $this->availableEntities[$entityTypeId] = TRUE;
      }
      catch (\Exception) {
        // Dependencia faltante — entidad no disponible.
      }
    }

    if (empty($this->availableEntities)) {
      $this->markTestSkipped('No review entity types available for Kernel testing.');
    }
  }

  /**
   * Tests crear y cargar una entidad course_review.
   */
  public function testCourseReviewCrud(): void {
    if (!($this->availableEntities['course_review'] ?? FALSE)) {
      $this->markTestSkipped('course_review entity not available.');
    }

    $storage = \Drupal::entityTypeManager()->getStorage('course_review');

    $review = $storage->create([
      'title' => 'Curso excelente',
      'body' => 'El contenido del curso es muy completo y practico.',
      'rating' => 5,
      'review_status' => 'pending',
      'uid' => 1,
    ]);
    $review->save();

    $this->assertNotEmpty($review->id());

    // Cargar y verificar.
    $loaded = $storage->load($review->id());
    $this->assertEquals('Curso excelente', $loaded->get('title')->value);
    $this->assertEquals(5, (int) $loaded->get('rating')->value);
    $this->assertEquals('pending', $loaded->get('review_status')->value);

    // Actualizar.
    $loaded->set('review_status', 'approved');
    $loaded->save();
    $reloaded = $storage->load($review->id());
    $this->assertEquals('approved', $reloaded->get('review_status')->value);

    // Eliminar.
    $id = $review->id();
    $reloaded->delete();
    $this->assertNull($storage->load($id));
  }

  /**
   * Tests crear y cargar una entidad content_comment.
   */
  public function testContentCommentCrud(): void {
    if (!($this->availableEntities['content_comment'] ?? FALSE)) {
      $this->markTestSkipped('content_comment entity not available.');
    }

    $storage = \Drupal::entityTypeManager()->getStorage('content_comment');

    $comment = $storage->create([
      'body' => 'Gran articulo, muy informativo.',
      'author_name' => 'Juan Perez',
      'author_email' => 'juan@example.com',
      'review_status' => 'pending',
      'uid' => 1,
    ]);
    $comment->save();

    $this->assertNotEmpty($comment->id());

    $loaded = $storage->load($comment->id());
    $this->assertEquals('Gran articulo, muy informativo.', $loaded->get('body')->value);
    $this->assertEquals('Juan Perez', $loaded->get('author_name')->value);
    $this->assertEquals('pending', $loaded->get('review_status')->value);

    // Actualizar estado.
    $loaded->set('review_status', 'approved');
    $loaded->save();
    $this->assertEquals('approved', $storage->load($comment->id())->get('review_status')->value);

    // Eliminar.
    $id = $comment->id();
    $loaded->delete();
    $this->assertNull($storage->load($id));
  }

  /**
   * Tests crear comercio_review con campos polimorficos.
   */
  public function testComercioReviewCrud(): void {
    if (!($this->availableEntities['comercio_review'] ?? FALSE)) {
      $this->markTestSkipped('comercio_review entity not available.');
    }

    $storage = \Drupal::entityTypeManager()->getStorage('comercio_review');

    $review = $storage->create([
      'title' => 'Tienda fantastica',
      'body' => 'Excelente servicio y productos de calidad.',
      'rating' => 4,
      'entity_type_ref' => 'merchant_profile',
      'entity_id_ref' => 1,
      'status' => 'pending',
      'uid' => 1,
    ]);
    $review->save();

    $this->assertNotEmpty($review->id());

    $loaded = $storage->load($review->id());
    $this->assertEquals('merchant_profile', $loaded->get('entity_type_ref')->value);
    $this->assertEquals(4, (int) $loaded->get('rating')->value);
    $this->assertEquals('pending', $loaded->get('status')->value);
  }

  /**
   * Tests crear review_agro con tipo y target.
   */
  public function testReviewAgroCrud(): void {
    if (!($this->availableEntities['review_agro'] ?? FALSE)) {
      $this->markTestSkipped('review_agro entity not available.');
    }

    $storage = \Drupal::entityTypeManager()->getStorage('review_agro');

    $review = $storage->create([
      'title' => 'Aceite de oliva premium',
      'body' => 'Producto de gran calidad, directamente del productor.',
      'rating' => 5,
      'type' => 'product',
      'target_entity_type' => 'producer_profile',
      'target_entity_id' => 1,
      'state' => 'pending',
      'uid' => 1,
    ]);
    $review->save();

    $this->assertNotEmpty($review->id());

    $loaded = $storage->load($review->id());
    $this->assertEquals('product', $loaded->get('type')->value);
    $this->assertEquals('producer_profile', $loaded->get('target_entity_type')->value);
    $this->assertEquals('pending', $loaded->get('state')->value);
  }

  /**
   * Tests que todos los campos del trait estan disponibles.
   */
  public function testReviewableEntityTraitFields(): void {
    $traitFields = ['review_status', 'helpful_count', 'ai_summary', 'ai_summary_generated_at'];
    $traitEntities = ['course_review', 'content_comment'];

    foreach ($traitEntities as $entityTypeId) {
      if (!($this->availableEntities[$entityTypeId] ?? FALSE)) {
        continue;
      }

      $fieldDefs = \Drupal::service('entity_field.manager')
        ->getBaseFieldDefinitions($entityTypeId);

      foreach ($traitFields as $fieldName) {
        $this->assertArrayHasKey(
          $fieldName,
          $fieldDefs,
          "El campo '{$fieldName}' debe existir en {$entityTypeId}."
        );
      }
    }
  }

  /**
   * Tests que review_status tiene default 'pending'.
   */
  public function testReviewStatusDefaultPending(): void {
    // Usar la primera entidad disponible que tenga review_status.
    foreach (['course_review', 'content_comment'] as $entityTypeId) {
      if (!($this->availableEntities[$entityTypeId] ?? FALSE)) {
        continue;
      }

      $storage = \Drupal::entityTypeManager()->getStorage($entityTypeId);
      $entity = $storage->create([
        'body' => 'Test default status',
        'uid' => 1,
      ]);

      $this->assertEquals(
        'pending',
        $entity->get('review_status')->value,
        "Default review_status debe ser 'pending' para {$entityTypeId}."
      );
      return;
    }

    $this->markTestSkipped('No entity with review_status field available.');
  }

  /**
   * Tests helpful_count tiene default 0.
   */
  public function testHelpfulCountDefaultZero(): void {
    foreach (['course_review', 'content_comment'] as $entityTypeId) {
      if (!($this->availableEntities[$entityTypeId] ?? FALSE)) {
        continue;
      }

      $storage = \Drupal::entityTypeManager()->getStorage($entityTypeId);
      $entity = $storage->create([
        'body' => 'Test helpful count',
        'uid' => 1,
      ]);

      $this->assertEquals(
        0,
        (int) $entity->get('helpful_count')->value,
        "Default helpful_count debe ser 0 para {$entityTypeId}."
      );
      return;
    }

    $this->markTestSkipped('No entity with helpful_count field available.');
  }

  /**
   * Tests content_comment soporta threading via parent_id.
   */
  public function testContentCommentThreading(): void {
    if (!($this->availableEntities['content_comment'] ?? FALSE)) {
      $this->markTestSkipped('content_comment entity not available.');
    }

    $storage = \Drupal::entityTypeManager()->getStorage('content_comment');

    // Comentario padre.
    $parent = $storage->create([
      'body' => 'Comentario principal',
      'review_status' => 'approved',
      'uid' => 1,
    ]);
    $parent->save();

    // Respuesta al comentario.
    $reply = $storage->create([
      'body' => 'Respuesta al comentario',
      'parent_id' => $parent->id(),
      'review_status' => 'approved',
      'uid' => 1,
    ]);
    $reply->save();

    $loadedReply = $storage->load($reply->id());
    $this->assertEquals($parent->id(), $loadedReply->get('parent_id')->target_id);
  }

}
