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
 * Define la entidad Lista de Deseos.
 *
 * Estructura: Cada usuario puede tener multiples listas de deseos, una de
 *   ellas marcada como predeterminada (is_default). La visibilidad controla
 *   si la lista es publica o privada. Los items se gestionan mediante la
 *   entidad hija WishlistItem (comercio_wishlist_item).
 *
 * Logica: Al crear el perfil de cliente se genera automaticamente una lista
 *   predeterminada. Las listas publicas pueden compartirse via URL.
 *   La gestion se realiza integramente desde el portal del cliente.
 *
 * @ContentEntityType(
 *   id = "comercio_wishlist",
 *   label = @Translation("Lista de Deseos"),
 *   label_collection = @Translation("Listas de Deseos"),
 *   label_singular = @Translation("lista de deseos"),
 *   label_plural = @Translation("listas de deseos"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\WishlistForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\WishlistForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\WishlistForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\WishlistAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_wishlist",
 *   admin_permission = "manage comercio wishlists",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-wishlist/{comercio_wishlist}",
 *     "add-form" = "/admin/content/comercio-wishlist/add",
 *     "edit-form" = "/admin/content/comercio-wishlist/{comercio_wishlist}/edit",
 *     "delete-form" = "/admin/content/comercio-wishlist/{comercio_wishlist}/delete",
 *     "collection" = "/admin/content/comercio-wishlists",
 *   },
 * )
 */
class Wishlist extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

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
      ->setLabel(t('Nombre de la lista'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Lista predeterminada'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['visibility'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Visibilidad'))
      ->setRequired(TRUE)
      ->setDefaultValue('private')
      ->setSetting('allowed_values', [
        'private' => t('Privada'),
        'public' => t('Publica'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
