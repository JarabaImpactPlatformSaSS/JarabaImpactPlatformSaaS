<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * @ContentEntityType(
 *   id = "comercio_review",
 *   label = @Translation("Resena"),
 *   label_collection = @Translation("Resenas"),
 *   label_singular = @Translation("resena"),
 *   label_plural = @Translation("resenas"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\ReviewRetailListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\ReviewRetailForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\ReviewRetailForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\ReviewRetailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\ReviewRetailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_review",
 *   admin_permission = "manage comercio reviews",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-review/{comercio_review}",
 *     "add-form" = "/admin/content/comercio-review/add",
 *     "edit-form" = "/admin/content/comercio-review/{comercio_review}/edit",
 *     "delete-form" = "/admin/content/comercio-review/{comercio_review}/delete",
 *     "collection" = "/admin/content/comercio-reviews",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.review.settings",
 * )
 */
class ReviewRetail extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

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
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Contenido'))
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Valoracion'))
      ->setRequired(TRUE)
      ->setDescription(t('Valoracion de 1 a 5 estrellas'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de entidad referenciada'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_id_ref'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID de entidad referenciada'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['photos'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Fotos'))
      ->setDescription(t('JSON: array de URLs de imagenes'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'approved' => t('Aprobada'),
        'rejected' => t('Rechazada'),
        'flagged' => t('Marcada'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['merchant_response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Respuesta del comercio'))
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['merchant_response_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de respuesta del comercio'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['helpful_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Votos de utilidad'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['verified_purchase'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Compra verificada'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
