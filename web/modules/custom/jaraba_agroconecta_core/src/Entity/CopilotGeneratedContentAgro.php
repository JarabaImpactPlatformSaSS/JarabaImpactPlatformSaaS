<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad CopilotGeneratedContentAgro.
 *
 * Almacena contenido generado por la IA del copiloto agrícola,
 * como descripciones, títulos, meta-descriptions, respuestas a reseñas,
 * sugerencias de precio, keywords SEO y posts para redes sociales.
 *
 * @ContentEntityType(
 *   id = "copilot_generated_content_agro",
 *   label = @Translation("Contenido Generado IA"),
 *   label_collection = @Translation("Contenido Generado IA"),
 *   label_singular = @Translation("contenido generado IA"),
 *   label_plural = @Translation("contenidos generados IA"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_agroconecta_core\Access\CopilotGeneratedContentAgroAccessControlHandler",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\ListBuilder\CopilotGeneratedContentAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "copilot_generated_content_agro",
 *   admin_permission = "manage agro copilot",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agro-generated-content",
 *     "canonical" = "/admin/content/agro-generated-content/{copilot_generated_content_agro}",
 *   },
 * )
 */
class CopilotGeneratedContentAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este contenido'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE);

    $fields['message_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Mensaje origen'))
      ->setDescription(t('Mensaje del copiloto que generó este contenido'))
      ->setSetting('target_type', 'copilot_message_agro');

    $fields['content_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de contenido'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'description' => t('Descripción'),
        'title' => t('Título'),
        'meta_description' => t('Meta descripción'),
        'review_response' => t('Respuesta a reseña'),
        'price_suggestion' => t('Sugerencia de precio'),
        'seo_keywords' => t('Keywords SEO'),
        'social_post' => t('Post social'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['target_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo entidad destino'))
      ->setDescription(t('Tipo de entidad sobre la que se generó el contenido (product_agro, review_agro...)'))
      ->setSetting('max_length', 64);

    $fields['target_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID entidad destino'))
      ->setSetting('unsigned', TRUE);

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Contenido generado'))
      ->setDescription(t('Texto generado por la IA'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'draft' => t('Borrador'),
        'published' => t('Publicado'),
        'rejected' => t('Rechazado'),
      ])
      ->setDefaultValue('draft')
      ->setDisplayConfigurable('view', TRUE);

    $fields['model_used'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Modelo IA'))
      ->setDescription(t('Modelo utilizado para generar'))
      ->setSetting('max_length', 64);

    $fields['tokens_used'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tokens utilizados'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0);

    $fields['quality_score'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Puntuación calidad'))
      ->setDescription(t('Score 0-100 evaluado por QualityEvaluator'));

    $fields['rejection_reason'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Motivo rechazo'))
      ->setDescription(t('Razón del rechazo si status=rejected'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene el tipo de contenido generado.
   */
  public function getContentType(): string {
    return $this->get('content_type')->value ?? '';
  }

  /**
   * Obtiene el tipo de entidad destino.
   */
  public function getTargetEntityType(): ?string {
    return $this->get('target_entity_type')->value;
  }

  /**
   * Obtiene el ID de la entidad destino.
   */
  public function getTargetEntityId(): ?int {
    $value = $this->get('target_entity_id')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * Obtiene el contenido generado.
   */
  public function getContent(): string {
    return $this->get('content')->value ?? '';
  }

  /**
   * Obtiene el estado del contenido.
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? 'draft';
  }

  /**
   * Obtiene el modelo IA utilizado.
   */
  public function getModelUsed(): string {
    return $this->get('model_used')->value ?? '';
  }

  /**
   * Obtiene la cantidad de tokens utilizados.
   */
  public function getTokensUsed(): int {
    return (int) ($this->get('tokens_used')->value ?? 0);
  }

  /**
   * Obtiene la puntuación de calidad.
   */
  public function getQualityScore(): ?float {
    $value = $this->get('quality_score')->value;
    return $value !== NULL ? (float) $value : NULL;
  }

  /**
   * Comprueba si el contenido está en borrador.
   */
  public function isDraft(): bool {
    return $this->getStatus() === 'draft';
  }

  /**
   * Comprueba si el contenido está publicado.
   */
  public function isPublished(): bool {
    return $this->getStatus() === 'published';
  }

  /**
   * Comprueba si el contenido fue rechazado.
   */
  public function isRejected(): bool {
    return $this->getStatus() === 'rejected';
  }

  /**
   * Publica el contenido generado.
   */
  public function publish(): self {
    $this->set('status', 'published');
    return $this;
  }

  /**
   * Rechaza el contenido generado con un motivo.
   */
  public function reject(string $reason): self {
    $this->set('status', 'rejected');
    $this->set('rejection_reason', $reason);
    return $this;
  }

}
