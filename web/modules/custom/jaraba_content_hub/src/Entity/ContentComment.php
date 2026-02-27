<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\ecosistema_jaraba_core\Entity\ReviewableEntityTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad ContentComment.
 *
 * Sistema de comentarios con threading para articulos del Content Hub.
 * Implementa ReviewableEntityTrait para moderacion y social proof.
 * NO tiene rating ni photos — es un sistema de comentarios, no de resenas.
 *
 * @ContentEntityType(
 *   id = "content_comment",
 *   label = @Translation("Comentario"),
 *   label_collection = @Translation("Comentarios"),
 *   label_singular = @Translation("comentario"),
 *   label_plural = @Translation("comentarios"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_content_hub\Form\ContentCommentForm",
 *       "add" = "Drupal\jaraba_content_hub\Form\ContentCommentForm",
 *       "edit" = "Drupal\jaraba_content_hub\Form\ContentCommentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_content_hub\Access\ContentCommentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "content_comment",
 *   admin_permission = "administer content hub",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "body",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/content-comment/{content_comment}",
 *     "add-form" = "/admin/content/content-comment/add",
 *     "edit-form" = "/admin/content/content-comment/{content_comment}/edit",
 *     "delete-form" = "/admin/content/content-comment/{content_comment}/delete",
 *     "collection" = "/admin/content/content-comments",
 *   },
 *   field_ui_base_route = "entity.content_comment.settings",
 * )
 */
class ContentComment extends ContentEntityBase implements ContentCommentInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use ReviewableEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function isApproved(): bool {
    return $this->getReviewStatus() === self::STATUS_APPROVED;
  }

  /**
   * {@inheritdoc}
   */
  public function getArticleId(): ?int {
    if ($this->hasField('article_id') && !$this->get('article_id')->isEmpty()) {
      return (int) $this->get('article_id')->target_id;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId(): ?int {
    if ($this->hasField('parent_id') && !$this->get('parent_id')->isEmpty()) {
      return (int) $this->get('parent_id')->target_id;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // TENANT-BRIDGE-001.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE);

    $fields['article_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Articulo'))
      ->setDescription(t('Articulo comentado.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'content_article')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['parent_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comentario padre'))
      ->setDescription(t('Para threading: referencia al comentario padre.'))
      ->setSetting('target_type', 'content_comment')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Comentario'))
      ->setDescription(t('Texto del comentario.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['author_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del autor'))
      ->setDescription(t('Nombre mostrado para usuarios anonimos.'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    $fields['author_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email del autor'))
      ->setDescription(t('Email para usuarios anonimos.'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE);

    // Campos del trait: review_status, helpful_count, ai_summary, ai_summary_generated_at.
    // Excluimos photos: ContentComment no tiene fotos.
    $traitFields = static::reviewableBaseFieldDefinitions();
    $fields['review_status'] = $traitFields['review_status'];
    $fields['helpful_count'] = $traitFields['helpful_count'];
    $fields['ai_summary'] = $traitFields['ai_summary'];
    $fields['ai_summary_generated_at'] = $traitFields['ai_summary_generated_at'];

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
