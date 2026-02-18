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
 *   id = "comercio_qr_code",
 *   label = @Translation("Codigo QR"),
 *   label_collection = @Translation("Codigos QR"),
 *   label_singular = @Translation("codigo QR"),
 *   label_plural = @Translation("codigos QR"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\QrCodeRetailListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\QrCodeRetailForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\QrCodeRetailForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\QrCodeRetailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\QrCodeRetailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_qr_code",
 *   admin_permission = "manage comercio qr codes",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-qr-code/{comercio_qr_code}",
 *     "add-form" = "/admin/content/comercio-qr-code/add",
 *     "edit-form" = "/admin/content/comercio-qr-code/{comercio_qr_code}/edit",
 *     "delete-form" = "/admin/content/comercio-qr-code/{comercio_qr_code}/delete",
 *     "collection" = "/admin/content/comercio-qr-codes",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.qr_code.settings",
 * )
 */
class QrCodeRetail extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'merchant_profile')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['qr_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de QR'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'product' => t('Producto'),
        'merchant' => t('Comercio'),
        'offer' => t('Oferta'),
        'landing' => t('Landing'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['target_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL destino'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['target_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de entidad destino'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['target_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID de entidad destino'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    $fields['short_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Codigo corto'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 16)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['scan_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Escaneos'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ab_variant'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Variante A/B'))
      ->setSetting('max_length', 16)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE);

    $fields['ab_target_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL variante B'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['design_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuracion de diseno'))
      ->setDescription(t('JSON: colores, logo, estilo'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
