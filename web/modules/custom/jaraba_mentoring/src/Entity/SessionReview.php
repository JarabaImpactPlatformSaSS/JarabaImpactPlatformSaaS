<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\ecosistema_jaraba_core\Entity\ReviewableEntityTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Session Review.
 *
 * Evaluacion bidireccional mentor-emprendedor post-sesion.
 * Elevada a estandar clase mundial con tenant isolation, moderacion,
 * EntityOwnerTrait, EntityChangedTrait y ReviewableEntityTrait.
 *
 * @ContentEntityType(
 *   id = "session_review",
 *   label = @Translation("Review de Sesion"),
 *   label_collection = @Translation("Reviews de Sesiones"),
 *   label_singular = @Translation("review de sesion"),
 *   label_plural = @Translation("reviews de sesiones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_mentoring\Form\SessionReviewForm",
 *       "add" = "Drupal\jaraba_mentoring\Form\SessionReviewForm",
 *       "edit" = "Drupal\jaraba_mentoring\Form\SessionReviewForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_mentoring\Access\SessionReviewAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "session_review",
 *   admin_permission = "manage sessions",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/session-review/{session_review}",
 *     "add-form" = "/admin/content/session-review/add",
 *     "edit-form" = "/admin/content/session-review/{session_review}/edit",
 *     "delete-form" = "/admin/content/session-review/{session_review}/delete",
 *     "collection" = "/admin/content/session-reviews",
 *   },
 *   field_ui_base_route = "entity.session_review.settings",
 * )
 */
class SessionReview extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use ReviewableEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Titulo computado para label key.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Titulo descriptivo de la review.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // TENANT-BRIDGE-001: tenant_id como entity_reference a group (REV-S2 fix).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Grupo/tenant al que pertenece esta review.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE);

    $fields['session_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sesion'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'mentoring_session')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['reviewer_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revisor'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['reviewee_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Evaluado'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['review_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Review'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'mentor_to_mentee' => t('Mentor evalua a Emprendedor'),
        'mentee_to_mentor' => t('Emprendedor evalua a Mentor'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['overall_rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Valoracion General'))
      ->setRequired(TRUE)
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['punctuality_rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntualidad'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    $fields['preparation_rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Preparacion'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE);

    $fields['communication_rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Comunicacion'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE);

    $fields['comment'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Comentario Publico'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['private_feedback'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Feedback Privado'))
      ->setDescription(t('Solo visible para el administrador.'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['would_recommend'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Recomendaria'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    // Todos los campos del trait.
    $fields += static::reviewableBaseFieldDefinitions();

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
