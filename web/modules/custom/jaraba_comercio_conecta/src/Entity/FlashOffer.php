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
 *   id = "comercio_flash_offer",
 *   label = @Translation("Oferta Flash"),
 *   label_collection = @Translation("Ofertas Flash"),
 *   label_singular = @Translation("oferta flash"),
 *   label_plural = @Translation("ofertas flash"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\FlashOfferListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\FlashOfferForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\FlashOfferForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\FlashOfferForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\FlashOfferAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_flash_offer",
 *   admin_permission = "manage comercio flash offers",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-flash-offer/{comercio_flash_offer}",
 *     "add-form" = "/admin/content/comercio-flash-offer/add",
 *     "edit-form" = "/admin/content/comercio-flash-offer/{comercio_flash_offer}/edit",
 *     "delete-form" = "/admin/content/comercio-flash-offer/{comercio_flash_offer}/delete",
 *     "collection" = "/admin/content/comercio-flash-offers",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.flash_offer.settings",
 * )
 */
class FlashOffer extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

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

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descripcion'))
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'merchant_profile')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Producto'))
      ->setSetting('target_type', 'product_retail')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de descuento'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'percentage' => t('Porcentaje'),
        'fixed' => t('Fijo'),
        'bogo' => t('Compra uno lleva otro'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor del descuento'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['original_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio original'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['offer_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio de oferta'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Hora de inicio'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Hora de fin'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_claims'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Maximo de canjes'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['current_claims'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Canjes actuales'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_lat'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Latitud'))
      ->setDisplayOptions('form', ['weight' => 14])
      ->setDisplayConfigurable('form', TRUE);

    $fields['location_lng'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Longitud'))
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE);

    $fields['radius_km'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Radio en km'))
      ->setDefaultValue(5.0)
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'draft' => t('Borrador'),
        'scheduled' => t('Programada'),
        'active' => t('Activa'),
        'expired' => t('Expirada'),
        'cancelled' => t('Cancelada'),
      ])
      ->setDefaultValue('draft')
      ->setDisplayOptions('form', ['weight' => 17])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['image_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL de imagen'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 18])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
