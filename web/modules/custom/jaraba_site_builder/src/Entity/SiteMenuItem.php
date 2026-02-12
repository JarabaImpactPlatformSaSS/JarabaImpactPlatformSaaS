<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SiteMenuItem para items jerárquicos dentro de un menú.
 *
 * @ContentEntityType(
 *   id = "site_menu_item",
 *   label = @Translation("Item de Menú"),
 *   label_collection = @Translation("Items de Menú"),
 *   label_singular = @Translation("item de menú"),
 *   label_plural = @Translation("items de menú"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_site_builder\SiteMenuItemListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_site_builder\Form\SiteMenuItemForm",
 *       "add" = "Drupal\jaraba_site_builder\Form\SiteMenuItemForm",
 *       "edit" = "Drupal\jaraba_site_builder\Form\SiteMenuItemForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_site_builder\SiteMenuItemAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "site_menu_item",
 *   fieldable = TRUE,
 *   admin_permission = "administer site structure",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/site-menu-items",
 *     "add-form" = "/admin/structure/site-menu-items/add",
 *     "canonical" = "/admin/structure/site-menu-items/{site_menu_item}",
 *     "edit-form" = "/admin/structure/site-menu-items/{site_menu_item}/edit",
 *     "delete-form" = "/admin/structure/site-menu-items/{site_menu_item}/delete",
 *   },
 *   field_ui_base_route = "entity.site_menu_item.collection",
 * )
 */
class SiteMenuItem extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['menu_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Menú'))
      ->setDescription(t('El menú al que pertenece este item.'))
      ->setSetting('target_type', 'site_menu')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Item padre'))
      ->setDescription(t('Item padre para crear submenús (NULL para nivel raíz).'))
      ->setSetting('target_type', 'site_menu_item')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título'))
      ->setDescription(t('Texto visible del enlace del menú.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL'))
      ->setDescription(t('URL manual del enlace (alternativa a seleccionar página).'))
      ->setSettings([
        'max_length' => 500,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['page_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Página'))
      ->setDescription(t('Página del Page Builder enlazada (alternativa a URL manual).'))
      ->setSetting('target_type', 'page_content')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['item_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de item'))
      ->setDescription(t('Tipo de elemento del menú.'))
      ->setSettings([
        'allowed_values' => [
          'link' => 'Enlace',
          'page' => 'Página',
          'dropdown' => 'Desplegable',
          'mega_column' => 'Columna Mega Menú',
          'divider' => 'Separador',
          'heading' => 'Encabezado',
        ],
      ])
      ->setDefaultValue('link')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['icon'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Icono'))
      ->setDescription(t('Nombre del icono jaraba_icon (ej: home, blog, settings).'))
      ->setSettings([
        'max_length' => 50,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['badge_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Texto del badge'))
      ->setDescription(t('Texto del badge junto al enlace (ej: Nuevo, Beta).'))
      ->setSettings([
        'max_length' => 50,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['badge_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color del badge'))
      ->setDescription(t('Color hexadecimal del badge (ej: #FF8C42).'))
      ->setSettings([
        'max_length' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['highlight'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Destacar'))
      ->setDescription(t('Resaltar visualmente este item en la navegación.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mega_content'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Contenido Mega Menú'))
      ->setDescription(t('JSON con contenido de columnas para mega menú.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 8,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['open_in_new_tab'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Abrir en nueva pestaña'))
      ->setDescription(t('Abrir el enlace en una nueva pestaña del navegador.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Habilitado'))
      ->setDescription(t('Mostrar este item en el menú.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Peso'))
      ->setDescription(t('Orden entre items hermanos (menor = primero).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['depth'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Profundidad'))
      ->setDescription(t('Nivel en el árbol del menú (0 = raíz).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creación del item.'));

    return $fields;
  }

  /**
   * Obtiene el menú al que pertenece.
   */
  public function getMenu(): ?SiteMenu {
    return $this->get('menu_id')->entity;
  }

  /**
   * Obtiene el item padre.
   */
  public function getParent(): ?SiteMenuItem {
    return $this->get('parent_id')->entity;
  }

  /**
   * Obtiene el tipo de item.
   */
  public function getItemType(): string {
    return $this->get('item_type')->value ?? 'link';
  }

  /**
   * Verifica si el item está habilitado.
   */
  public function isEnabled(): bool {
    return (bool) $this->get('is_enabled')->value;
  }

  /**
   * Obtiene la URL resuelta (manual o de la página enlazada).
   */
  public function getResolvedUrl(): ?string {
    $url = $this->get('url')->value;
    if (!empty($url)) {
      return $url;
    }
    $page = $this->get('page_id')->entity;
    if ($page && $page->hasField('path_alias')) {
      return $page->get('path_alias')->value;
    }
    return NULL;
  }

  /**
   * Obtiene el contenido del mega menú como array.
   */
  public function getMegaContent(): array {
    $json = $this->get('mega_content')->value ?? '[]';
    return json_decode($json, TRUE) ?: [];
  }

  /**
   * Verifica si tiene badge.
   */
  public function hasBadge(): bool {
    return !empty($this->get('badge_text')->value);
  }

}
