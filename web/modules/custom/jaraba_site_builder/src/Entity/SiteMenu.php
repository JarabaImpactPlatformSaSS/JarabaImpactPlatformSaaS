<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SiteMenu para menús personalizados por tenant.
 *
 * @ContentEntityType(
 *   id = "site_menu",
 *   label = @Translation("Menú del Sitio"),
 *   label_collection = @Translation("Menús del Sitio"),
 *   label_singular = @Translation("menú del sitio"),
 *   label_plural = @Translation("menús del sitio"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_site_builder\SiteMenuListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_site_builder\Form\SiteMenuForm",
 *       "add" = "Drupal\jaraba_site_builder\Form\SiteMenuForm",
 *       "edit" = "Drupal\jaraba_site_builder\Form\SiteMenuForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_site_builder\SiteMenuAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "site_menu",
 *   fieldable = TRUE,
 *   admin_permission = "administer site structure",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/site-menus",
 *     "add-form" = "/admin/structure/site-menus/add",
 *     "canonical" = "/admin/structure/site-menus/{site_menu}",
 *     "edit-form" = "/admin/structure/site-menus/{site_menu}/edit",
 *     "delete-form" = "/admin/structure/site-menus/{site_menu}/delete",
 *   },
 *   field_ui_base_route = "entity.site_menu.collection",
 * )
 */
class SiteMenu extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant (organización) al que pertenece este menú.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -100,
      ]);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre máquina'))
      ->setDescription(t('Identificador interno del menú (ej: main, footer, sidebar).'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 100,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre visible del menú.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción del propósito de este menú.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 2,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creación del menú.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'))
      ->setDescription(t('Fecha de última modificación.'));

    return $fields;
  }

  /**
   * Obtiene el nombre máquina.
   */
  public function getMachineName(): string {
    return $this->get('machine_name')->value ?? '';
  }

  /**
   * Obtiene el tenant ID.
   */
  public function getTenantId(): ?int {
    return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
  }

  /**
   * Obtiene la descripción.
   */
  public function getDescription(): ?string {
    return $this->get('description')->value;
  }

}
