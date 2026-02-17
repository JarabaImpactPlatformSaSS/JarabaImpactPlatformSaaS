<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Perfil de Cliente.
 *
 * Estructura: Perfil extendido del consumidor final dentro de ComercioConecta.
 *   Almacena direcciones de envio/facturacion como JSON, preferencias de
 *   notificacion, comercios favoritos y puntos de fidelidad. Cada perfil
 *   pertenece a un usuario Drupal (uid) y a un tenant (tenant_id).
 *
 * Logica: El perfil se crea automaticamente al primer pedido o registro
 *   en el portal de cliente. Los campos JSON (shipping_address,
 *   billing_address, preferences, favorite_merchants) se gestionan
 *   programaticamente desde el portal frontend.
 *
 * @ContentEntityType(
 *   id = "customer_profile_retail",
 *   label = @Translation("Perfil de Cliente"),
 *   label_collection = @Translation("Perfiles de Cliente"),
 *   label_singular = @Translation("perfil de cliente"),
 *   label_plural = @Translation("perfiles de cliente"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\CustomerProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\CustomerProfileForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\CustomerProfileForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\CustomerProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\CustomerProfileAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "customer_profile_retail",
 *   admin_permission = "manage comercio customers",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "display_name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-customer/{customer_profile_retail}",
 *     "add-form" = "/admin/content/comercio-customer/add",
 *     "edit-form" = "/admin/content/comercio-customer/{customer_profile_retail}/edit",
 *     "delete-form" = "/admin/content/comercio-customer/{customer_profile_retail}/delete",
 *     "collection" = "/admin/content/comercio-customers",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.customer_profile_retail.settings",
 * )
 */
class CustomerProfile extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

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

    $fields['display_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre visible'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Telefono'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['shipping_address'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Direccion de envio'))
      ->setDescription(t('JSON: street, city, postal_code, province.'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_address'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Direccion de facturacion'))
      ->setDescription(t('JSON: street, city, postal_code, province.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['preferences'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Preferencias'))
      ->setDescription(t('JSON: preferencias de notificacion, idioma, etc.'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['favorite_merchants'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Comercios favoritos'))
      ->setDescription(t('JSON: array de IDs de comercios.'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    $fields['avatar_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL del avatar'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['loyalty_points'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntos de fidelidad'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
