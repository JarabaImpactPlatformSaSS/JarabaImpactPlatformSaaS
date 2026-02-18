<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Paquete de Servicios.
 *
 * Estructura: Representa un paquete de sesiones (bono) que un
 *   profesional ofrece con descuento (ej: 5 sesiones por 200€).
 *
 * Lógica: Un ServicePackage agrupa N sesiones de un servicio
 *   a precio reducido. Al comprar un paquete, se crea un
 *   ClientPackage que lleva la cuenta de sesiones consumidas.
 *
 * @ContentEntityType(
 *   id = "service_package",
 *   label = @Translation("Paquete de Servicios"),
 *   label_collection = @Translation("Paquetes de Servicios"),
 *   label_singular = @Translation("paquete de servicios"),
 *   label_plural = @Translation("paquetes de servicios"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_servicios_conecta\ListBuilder\ServicePackageListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_servicios_conecta\Form\ServicePackageForm",
 *       "add" = "Drupal\jaraba_servicios_conecta\Form\ServicePackageForm",
 *       "edit" = "Drupal\jaraba_servicios_conecta\Form\ServicePackageForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_servicios_conecta\Access\ServicePackageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "service_package",
 *   admin_permission = "manage servicios offerings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/servicios-package/{service_package}",
 *     "add-form" = "/admin/content/servicios-package/add",
 *     "edit-form" = "/admin/content/servicios-package/{service_package}/edit",
 *     "delete-form" = "/admin/content/servicios-package/{service_package}/delete",
 *     "collection" = "/admin/content/servicios-packages",
 *   },
 *   field_ui_base_route = "jaraba_servicios_conecta.service_package.settings",
 * )
 */
class ServicePackage extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Paquete'))
      ->setDescription(t('Ej: Bono 5 Sesiones Fisioterapia'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Profesional'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'provider_profile')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['offering_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Servicio Incluido'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'service_offering')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_sessions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total de Sesiones'))
      ->setRequired(TRUE)
      ->setDefaultValue(5)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio del Paquete (€)'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_percent'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Descuento (%)'))
      ->setDescription(t('Descuento respecto al precio individual.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['validity_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Validez (días)'))
      ->setDescription(t('Días de validez del paquete desde la compra.'))
      ->setDefaultValue(90)
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE);

    $fields['is_published'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publicado'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
